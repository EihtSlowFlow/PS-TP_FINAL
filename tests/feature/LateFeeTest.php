<?php

use App\Controllers\InstallmentController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\InstallmentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\BadRequestException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\DOMParser;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance, boundary and security tests for HU-14: automatic late fee ("recargo por mora").
 *
 * The clock is frozen on 2026-11-13 (a Friday) with `Time::setTestNow()`, so "today" never depends on the day the
 * tests run. Unless a test says otherwise, a contract is in force, falls due on the 10th and charges 0.5 % a day on a
 * monthly rent of 500,000 (the November installment is therefore 3 days late: 3 × 2,500 = 7,500).
 *
 * @internal
 */
final class LateFeeTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-13';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: unpaid installment past its due date
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function operatorRoleProvider(): array
    {
        return [
            'admin'    => ['admin'],
            'employee' => ['employee'],
        ];
    }

    #[DataProvider('operatorRoleProvider')]
    public function testScenario1ConsultingTheCollectionsMarksThePastDueInstallmentOverdueAndCalculatesItsFee(string $roleName): void
    {
        $session  = $this->sessionFor($roleName);
        $contract = $this->createContract();
        $id       = $this->insertInstallment($contract, '2026-11-01');

        $page = $this->withSession($session)->get('admin/cuotas');
        $page->assertStatus(200);

        $row = $this->fetchInstallment($id);
        $this->assertSame('overdue', $row['status']);
        $this->assertSame('7500.00', $row['late_fee']);
        $this->assertSame('500000.00', $row['base_amount'], 'The base amount is never modified.');

        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '[@data-status="overdue"]'));
        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '//span[contains(@class,"badge-installment-overdue")][contains(.,"Vencida")]'));
        $this->assertStringContainsString('pasó 1 cuota a «Vencida»', $this->text($page));
    }

    public function testScenario1TheListShowsTheBreakdownOfBaseLateFeeAndTotal(): void
    {
        $contract = $this->createContract();
        $id       = $this->insertInstallment($contract, '2026-11-01');

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas');

        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '/td[@data-cell="base"][contains(.,"$ 500.000")]'));
        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '/td[@data-cell="late-fee"][contains(.,"$ 7.500")]'));
        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '/td[@data-cell="late-fee"][contains(.,"3 días")][contains(.,"0,5 % diario")]'));
        $this->assertTrue($this->seeXPath($page, $this->rowXPath($id) . '/td[@data-cell="total"][contains(.,"$ 507.500")]'));
        $this->assertStringContainsString('Venció hace 3 días', $this->text($page));
    }

    public function testScenario1TheDetailShowsTheBreakdownAndTheFormula(): void
    {
        $contract = $this->createContract();
        $id       = $this->insertInstallment($contract, '2026-11-01');

        $page = $this->withSession($this->sessionFor('admin'))->get("admin/cuotas/{$id}");
        $page->assertStatus(200);

        $this->assertSame('overdue', $this->fetchInstallment($id)['status'], 'Consulting the detail also brings the installment up to date.');
        $this->assertTrue($this->seeXPath($page, '//*[@id="installment-status"][contains(.,"Vencida")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-base"][contains(.,"$ 500.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-late-fee"][contains(.,"$ 7.500")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-total"][contains(.,"$ 507.500")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-formula"][contains(.,"$ 500.000 × 0,5 % diario × 3 días de atraso")]'));
    }

    public function testTheLateFeeUsesTheDailyPercentageOfEachContract(): void
    {
        $first  = $this->insertInstallment($this->createContract(['late_fee_daily_percentage' => '0.50']), '2026-11-01', ['due_date' => '2026-11-08']);
        $second = $this->insertInstallment(
            $this->createContract(['monthly_rent' => '380000.00', 'late_fee_daily_percentage' => '0.40']),
            '2026-11-01',
            ['due_date' => '2026-11-08', 'base_amount' => '380000.00']
        );
        $third = $this->insertInstallment($this->createContract(['late_fee_daily_percentage' => '2.25']), '2026-11-01', ['due_date' => '2026-11-12']);

        $this->refresh();

        $this->assertSame('12500.00', $this->fetchInstallment($first)['late_fee'], '500000 × 0.50 % × 5 days');
        $this->assertSame('7600.00', $this->fetchInstallment($second)['late_fee'], '380000 × 0.40 % × 5 days');
        $this->assertSame('11250.00', $this->fetchInstallment($third)['late_fee'], '500000 × 2.25 % × 1 day');
    }

    public function testTheLateFeeGrowsOneDayAtATimeAndKeepsTheInstallmentOverdue(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01');

        foreach (['2026-11-11' => '2500.00', '2026-11-12' => '5000.00', '2026-11-13' => '7500.00', '2026-11-20' => '25000.00', '2026-12-10' => '75000.00'] as $day => $fee) {
            $this->refresh($day);

            $row = $this->fetchInstallment($id);
            $this->assertSame('overdue', $row['status'], "Status on {$day}");
            $this->assertSame($fee, $row['late_fee'], "Fee on {$day}");
        }
    }

    public function testCalculatingTwiceForTheSameDayChangesNothing(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01');

        $first = $this->refresh();
        $after = $this->fetchInstallment($id);
        $again = $this->refresh();

        $this->assertSame(['checked' => 1, 'marked_overdue' => 1, 'updated' => 1, 'failed' => 0], $first);
        $this->assertSame(['checked' => 1, 'marked_overdue' => 0, 'updated' => 0, 'failed' => 0], $again, 'Nothing is written when the fee is already right.');
        $this->assertSame($after, $this->fetchInstallment($id), 'The row, including updated_at, is exactly as the first calculation left it.');
    }

    public function testTheResultIsTheSameWhateverTheOrderOrTheNumberOfConsultations(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01');

        $this->refresh('2026-12-01');
        $this->refresh('2026-11-12');
        $this->refresh('2026-11-13');
        $this->refresh('2026-11-13');

        $row = $this->fetchInstallment($id);
        $this->assertSame('7500.00', $row['late_fee'], 'The fee only depends on the day it is calculated for, not on the earlier calculations.');
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function dueDateBoundaryProvider(): array
    {
        // The installment falls due on 2026-11-10.
        return [
            'two days before the due date'     => ['2026-11-08', 'pending', '0.00'],
            'the day before the due date'      => ['2026-11-09', 'pending', '0.00'],
            'on the due date it is still on time' => ['2026-11-10', 'pending', '0.00'],
            'the day after is the first day late' => ['2026-11-11', 'overdue', '2500.00'],
            'the second day late'              => ['2026-11-12', 'overdue', '5000.00'],
        ];
    }

    #[DataProvider('dueDateBoundaryProvider')]
    public function testTheDelayIsCountedFromTheDayAfterTheDueDate(string $today, string $expectedStatus, string $expectedFee): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01');

        $this->refresh($today);

        $row = $this->fetchInstallment($id);
        $this->assertSame($expectedStatus, $row['status']);
        $this->assertSame($expectedFee, $row['late_fee']);
    }

    public function testTheDelayIsCountedAcrossTheEndOfFebruaryInCommonAndLeapYears(): void
    {
        $common = $this->insertInstallment($this->createContract(), '2026-02-01', ['due_date' => '2026-02-28']);
        $leap   = $this->insertInstallment($this->createContract(), '2028-02-01', ['due_date' => '2028-02-28']);

        $this->refresh('2026-03-01');
        $this->assertSame('2500.00', $this->fetchInstallment($common)['late_fee'], '28 February to 1 March of a common year is one day.');

        $this->refresh('2028-03-01');
        $this->assertSame('5000.00', $this->fetchInstallment($leap)['late_fee'], '28 February to 1 March of a leap year is two days.');
    }

    public function testTheDelayIsCountedAcrossTheEndOfTheYear(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-12-01', ['due_date' => '2026-12-28']);

        $this->refresh('2027-01-02');

        $this->assertSame('12500.00', $this->fetchInstallment($id)['late_fee'], '5 days from 28 December to 2 January');
    }

    public function testAnOldDebtAccumulatesEveryDayOfDelay(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-08-01', ['due_date' => '2026-08-10']);

        $this->refresh();

        $this->assertSame('237500.00', $this->fetchInstallment($id)['late_fee'], '95 days × 2,500');
    }

    public function testTheFeeIsRoundedHalfUpToTheCentWhenItIsStored(): void
    {
        $up   = $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => '2026-11-12', 'base_amount' => '333.33']);
        $down = $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => '2026-11-12', 'base_amount' => '332.60']);
        $half = $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => '2026-11-12', 'base_amount' => '333.00']);

        $this->refresh();

        $this->assertSame('1.67', $this->fetchInstallment($up)['late_fee'], '333.33 × 0.5 % = 1.66665');
        $this->assertSame('1.66', $this->fetchInstallment($down)['late_fee'], '332.60 × 0.5 % = 1.663');
        $this->assertSame('1.67', $this->fetchInstallment($half)['late_fee'], '333.00 × 0.5 % = 1.665 rounds up');
    }

    public function testAContractWithoutLateFeePercentageStillMakesTheInstallmentOverdue(): void
    {
        $id = $this->insertInstallment($this->createContract(['late_fee_daily_percentage' => '0.00']), '2026-11-01');

        $this->refresh();

        $row = $this->fetchInstallment($id);
        $this->assertSame('overdue', $row['status']);
        $this->assertSame('0.00', $row['late_fee']);
    }

    public function testAnHundredPercentDailyFeeIsCalculatedExactly(): void
    {
        $id = $this->insertInstallment($this->createContract(['late_fee_daily_percentage' => '100.00']), '2026-11-01', ['due_date' => '2026-11-11']);

        $this->refresh();

        $this->assertSame('1000000.00', $this->fetchInstallment($id)['late_fee'], '500000 × 100 % × 2 days');
    }

    public function testAFeeThatDoesNotFitTheColumnIsStoredAtItsLargestValueInsteadOfFailing(): void
    {
        $id = $this->insertInstallment(
            $this->createContract(['monthly_rent' => '9999999999.99', 'late_fee_daily_percentage' => '100.00']),
            '2026-11-01',
            ['base_amount' => '9999999999.99']
        );

        $result = $this->refresh();

        $this->assertSame(0, $result['failed']);
        $this->assertSame('9999999999.99', $this->fetchInstallment($id)['late_fee']);
    }

    public function testAnInstallmentThatIsNotPastDueIsNotTouched(): void
    {
        $contract = $this->createContract();
        $today    = $this->insertInstallment($contract, '2026-11-01', ['due_date' => '2026-11-13']);
        $tomorrow = $this->insertInstallment($contract, '2026-12-01', ['number' => 2, 'due_date' => '2026-11-14']);
        $future   = $this->insertInstallment($contract, '2027-01-01', ['number' => 3]);
        $before   = [$this->fetchInstallment($today), $this->fetchInstallment($tomorrow), $this->fetchInstallment($future)];

        $result = $this->refresh();

        $this->assertSame(['checked' => 0, 'marked_overdue' => 0, 'updated' => 0, 'failed' => 0], $result);
        $this->assertSame($before, [$this->fetchInstallment($today), $this->fetchInstallment($tomorrow), $this->fetchInstallment($future)]);
    }

    public function testAnOverdueInstallmentWithAStaleFeeIsCorrectedAndOneWithTheRightFeeIsLeftAlone(): void
    {
        $contract = $this->createContract();
        $stale    = $this->insertInstallment($contract, '2026-10-01', ['number' => 1, 'due_date' => '2026-11-12', 'status' => 'overdue', 'late_fee' => '100.00']);
        $current  = $this->insertInstallment($contract, '2026-11-01', ['number' => 2, 'status' => 'overdue', 'late_fee' => '7500.00']);
        $untouched = $this->fetchInstallment($current);

        $result = $this->refresh();

        $this->assertSame('2500.00', $this->fetchInstallment($stale)['late_fee']);
        $this->assertSame($untouched, $this->fetchInstallment($current), 'A row that is already right is not written again.');
        $this->assertSame(['checked' => 2, 'marked_overdue' => 0, 'updated' => 1, 'failed' => 0], $result);
    }

    public function testSeveralInstallmentsAreBroughtUpToDateInTheSameConsultation(): void
    {
        $contract = $this->createContract();
        $ids      = [
            $this->insertInstallment($contract, '2026-09-01', ['number' => 1, 'due_date' => '2026-09-10']),
            $this->insertInstallment($contract, '2026-10-01', ['number' => 2, 'due_date' => '2026-10-10']),
            $this->insertInstallment($contract, '2026-11-01', ['number' => 3, 'due_date' => '2026-11-10']),
            $this->insertInstallment($contract, '2026-12-01', ['number' => 4, 'due_date' => '2026-12-10']),
        ];

        $result = $this->refresh();

        $this->assertSame(['checked' => 3, 'marked_overdue' => 3, 'updated' => 3, 'failed' => 0], $result);
        $this->assertSame(['overdue', 'overdue', 'overdue', 'pending'], array_map(fn (int $id): string => $this->fetchInstallment($id)['status'], $ids));
        // 64, 34 and 3 days late: 10 September, 10 October and 10 November up to 13 November.
        $this->assertSame(['160000.00', '85000.00', '7500.00', '0.00'], array_map(fn (int $id): string => $this->fetchInstallment($id)['late_fee'], $ids));
    }

    public function testTheUnpaidInstallmentsOfAClosedContractAreAlsoUpdated(): void
    {
        $finished = $this->insertInstallment(
            $this->createContract(['status' => 'finished', 'closed_at' => '2026-11-05', 'closing_reason' => 'Finalizado']),
            '2026-11-01'
        );
        $rescinded = $this->insertInstallment(
            $this->createContract(['status' => 'rescinded', 'closed_at' => '2026-11-05', 'closing_reason' => 'Rescindido']),
            '2026-11-01'
        );

        $this->refresh();

        $this->assertSame(['overdue', '7500.00'], [$this->fetchInstallment($finished)['status'], $this->fetchInstallment($finished)['late_fee']]);
        $this->assertSame(['overdue', '7500.00'], [$this->fetchInstallment($rescinded)['status'], $this->fetchInstallment($rescinded)['late_fee']]);
    }

    public function testRefreshingOneInstallmentLeavesTheOthersAsTheyWere(): void
    {
        $contract = $this->createContract();
        $first    = $this->insertInstallment($contract, '2026-10-01', ['number' => 1, 'due_date' => '2026-10-10']);
        $second   = $this->insertInstallment($contract, '2026-11-01', ['number' => 2]);
        $before   = $this->fetchInstallment($first);

        $result = $this->refresh(self::TODAY, $second);

        $this->assertSame(1, $result['checked']);
        $this->assertSame($before, $this->fetchInstallment($first));
        $this->assertSame('overdue', $this->fetchInstallment($second)['status']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidDayProvider(): array
    {
        return [
            'text'          => ['hoy'],
            'day first'     => ['13/11/2026'],
            'impossible'    => ['2026-02-30'],
            'with a time'   => ['2026-11-13 10:00:00'],
        ];
    }

    #[DataProvider('invalidDayProvider')]
    public function testAnInvalidDayIsRejectedBeforeAnythingIsCalculated(string $day): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01');

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->refresh($day);
        } finally {
            $this->assertSame('pending', $this->fetchInstallment($id)['status']);
        }
    }

    public function testAnInstallmentIssuedForAPastPeriodIsOverdueTheFirstTimeItIsConsulted(): void
    {
        $session = $this->sessionFor('admin');
        $this->createContract();

        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '11', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $issued = $this->db->table('installments')->get()->getRowArray();
        $this->assertSame('pending', $issued['status'], 'Issuing never marks an installment overdue by itself.');
        $this->assertSame('0.00', $issued['late_fee']);

        $this->withSession($session)->get('admin/cuotas')->assertStatus(200);

        $row = $this->fetchInstallment((int) $issued['id']);
        $this->assertSame('overdue', $row['status']);
        $this->assertSame('7500.00', $row['late_fee']);
    }

    public function testAnInstallmentIssuedForAFuturePeriodStaysPendingWithoutFee(): void
    {
        $session = $this->sessionFor('admin');
        $this->createContract();

        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
        $page = $this->withSession($session)->get('admin/cuotas');

        $row = $this->db->table('installments')->get()->getRowArray();
        $this->assertSame('pending', $row['status']);
        $this->assertSame('0.00', $row['late_fee']);
        $this->assertStringContainsString('Vence en 27 días', $this->text($page));
        $this->assertStringNotContainsString('pasó 1 cuota', $this->text($page));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: installment paid on time
    // ---------------------------------------------------------------------------------------------

    public function testScenario2AnInstallmentPaidOnTimeKeepsAZeroLateFee(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-09-01', ['due_date' => '2026-09-10', 'status' => 'paid']);

        $before = $this->fetchInstallment($id);
        $result = $this->refresh();

        $this->assertSame(['checked' => 0, 'marked_overdue' => 0, 'updated' => 0, 'failed' => 0], $result);
        $this->assertSame($before, $this->fetchInstallment($id));
        $this->assertSame('0.00', $before['late_fee']);
    }

    public function testScenario2TheDetailOfAnInstallmentPaidOnTimeShowsAZeroLateFee(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-09-01', ['due_date' => '2026-09-10', 'status' => 'paid']);

        $page = $this->withSession($this->sessionFor('admin'))->get("admin/cuotas/{$id}");

        $page->assertStatus(200);
        $this->assertTrue($this->seeXPath($page, '//*[@id="installment-status"][contains(.,"Pagada")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-late-fee"][contains(.,"$ 0")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-total"][contains(.,"$ 500.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-formula"][contains(.,"ya no se modifica")]'));
        $this->assertSame('0.00', $this->fetchInstallment($id)['late_fee']);
    }

    public function testAPaidInstallmentNeverChangesNotEvenWhenItHasAFeeAlready(): void
    {
        $id = $this->insertInstallment(
            $this->createContract(),
            '2026-09-01',
            ['due_date' => '2026-09-10', 'status' => 'paid', 'late_fee' => '1234.56']
        );
        $before = $this->fetchInstallment($id);

        $this->refresh();
        $this->refresh('2027-06-01');
        $this->withSession($this->sessionFor('admin'))->get('admin/cuotas')->assertStatus(200);

        $this->assertSame($before, $this->fetchInstallment($id));
        $this->assertSame('1234.56', $before['late_fee']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: installment under review
    // ---------------------------------------------------------------------------------------------

    public function testScenario3TheLateFeeOfAnInstallmentUnderReviewDoesNotChangeWhileTheReviewLasts(): void
    {
        $id = $this->insertInstallment(
            $this->createContract(),
            '2026-11-01',
            ['status' => 'under_review', 'late_fee' => '3000.00']
        );
        $before = $this->fetchInstallment($id);

        foreach (['2026-11-13', '2026-11-20', '2026-12-31', '2027-11-13'] as $day) {
            $result = $this->refresh($day);

            $this->assertSame(0, $result['checked'], "Day {$day}: an installment under review is not even examined.");
            $this->assertSame($before, $this->fetchInstallment($id), "Day {$day}");
        }
    }

    public function testScenario3AnInstallmentUnderReviewWithoutFeeDoesNotStartAccruingOne(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-10-01', ['due_date' => '2026-10-10', 'status' => 'under_review']);

        $this->refresh();

        $row = $this->fetchInstallment($id);
        $this->assertSame('under_review', $row['status']);
        $this->assertSame('0.00', $row['late_fee']);
    }

    public function testScenario3TheScreensDoNotModifyAnInstallmentUnderReviewAndShowItsFee(): void
    {
        $id = $this->insertInstallment(
            $this->createContract(),
            '2026-11-01',
            ['status' => 'under_review', 'late_fee' => '3000.00']
        );
        $before  = $this->fetchInstallment($id);
        $session = $this->sessionFor('admin');

        $list   = $this->withSession($session)->get('admin/cuotas');
        $detail = $this->withSession($session)->get("admin/cuotas/{$id}");

        $this->assertSame($before, $this->fetchInstallment($id));
        $this->assertTrue($this->seeXPath($list, $this->rowXPath($id) . '[@data-status="under_review"]'));
        $this->assertTrue($this->seeXPath($list, $this->rowXPath($id) . '/td[@data-cell="late-fee"][contains(.,"$ 3.000")][contains(.,"Fijo durante la revisión")]'));
        $this->assertTrue($this->seeXPath($list, $this->rowXPath($id) . '/td[@data-cell="total"][contains(.,"$ 503.000")]'));
        $this->assertTrue($this->seeXPath($detail, '//*[@id="breakdown-late-fee"][contains(.,"$ 3.000")]'));
        $this->assertTrue($this->seeXPath($detail, '//*[@id="breakdown-formula"][contains(.,"en revisión")]'));
    }

    public function testWhenTheReviewEndsUnpaidTheFeeIsCalculatedAgainFromTheDueDate(): void
    {
        $id = $this->insertInstallment(
            $this->createContract(),
            '2026-11-01',
            ['status' => 'under_review', 'late_fee' => '2500.00']
        );

        $this->refresh();
        $this->assertSame('2500.00', $this->fetchInstallment($id)['late_fee']);

        // Another story rejects the payment and the installment goes back to being overdue.
        $this->db->table('installments')->where('id', $id)->update(['status' => 'overdue']);
        $this->refresh();

        $row = $this->fetchInstallment($id);
        $this->assertSame('overdue', $row['status']);
        $this->assertSame('7500.00', $row['late_fee'], 'The days late are always counted from the due date up to the day of the calculation.');
    }

    // ---------------------------------------------------------------------------------------------
    // Collections screen: totals, filters, pagination and presentation
    // ---------------------------------------------------------------------------------------------

    public function testTheTotalsAddUpEveryStatusAndTheBreakdownIsConsistent(): void
    {
        $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => '2026-11-20', 'base_amount' => '100000.00']);
        $this->insertInstallment($this->createContract(), '2026-11-01', ['status' => 'under_review', 'base_amount' => '200000.00']);
        $this->insertInstallment($this->createContract(), '2026-11-01', ['status' => 'paid', 'base_amount' => '350000.00']);
        $this->insertInstallment($this->createContract(), '2026-11-01', ['base_amount' => '400000.00']);

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas');

        $this->assertTrue($this->seeXPath($page, '//*[@id="kpi-to-collect"][contains(.,"$ 300.000")]'), 'Pending plus under review: 100,000 + 200,000.');
        $this->assertTrue($this->seeXPath($page, '//*[@id="kpi-collected"][contains(.,"$ 350.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="kpi-overdue"][contains(.,"$ 406.000")]'), '400,000 + 3 days × 2,000.');
        $this->assertTrue($this->seeXPath($page, '//*[@id="total-base"][contains(.,"$ 1.050.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="total-late-fee"][contains(.,"$ 6.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="total-amount"][contains(.,"$ 1.056.000")]'));
        $this->assertStringContainsString('1 cuota vencida', (string) $page->getBody());
        $this->assertStringContainsString('$ 6.000 de recargos', (string) $page->getBody());
    }

    public function testThePageWithoutInstallmentsSaysSoAndOffersTheIssuing(): void
    {
        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas');

        $page->assertStatus(200);
        $page->assertSee('Todavía no hay cuotas emitidas.');
        $this->assertTrue($this->seeXPath($page, '//a[@id="btn-emit-installments"][contains(@href,"admin/cuotas/emitir")]'));
        $this->assertFalse($this->seeXPath($page, '//tfoot'));
    }

    public function testTheListFiltersByStatusPeriodAndSearchText(): void
    {
        $alvarez = $this->insertInstallment(
            $this->createContract([], ['tenant_last_name' => 'Alvarez', 'address' => 'Calle Alfa 1']),
            '2026-11-01'
        );
        $benitez = $this->insertInstallment(
            $this->createContract([], ['tenant_last_name' => 'Benitez', 'address' => 'Calle Beta 2']),
            '2026-11-01',
            ['due_date' => '2026-11-20']
        );
        $cabrera = $this->insertInstallment(
            $this->createContract([], ['tenant_last_name' => 'Cabrera', 'address' => 'Calle Gamma 3']),
            '2026-10-01',
            ['due_date' => '2026-10-10', 'status' => 'paid']
        );
        $session = $this->sessionFor('admin');

        $this->assertSame([$alvarez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?status=overdue')));
        $this->assertSame([$benitez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?status=pending')));
        $this->assertSame([$cabrera], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?status=paid')));
        $this->assertSame([], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?status=under_review')));

        $this->assertSame([$alvarez, $benitez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?period=2026-11')));
        $this->assertSame([$cabrera], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?period=2026-10')));

        $this->assertSame([$benitez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=Benitez')));
        $this->assertSame([$cabrera], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=Gamma')));
        $this->assertSame([$alvarez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=30000001')));
        $this->assertSame([$alvarez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=Cipolletti&status=overdue')));
        $this->assertSame([$alvarez], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=Alfa&period=2026-11&status=overdue')));
        $this->assertSame([], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=Alfa&status=paid')));

        $none = $this->withSession($session)->get('admin/cuotas?q=NadieSeLlamaAsi');
        $none->assertSee('No se encontraron cuotas con los filtros seleccionados.');
    }

    public function testTheFiltersChangeTheTotalsToo(): void
    {
        $this->insertInstallment($this->createContract(), '2026-11-01', ['status' => 'paid', 'base_amount' => '350000.00']);
        $this->insertInstallment($this->createContract(), '2026-11-01', ['base_amount' => '400000.00']);

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas?status=overdue');

        $this->assertTrue($this->seeXPath($page, '//*[@id="total-base"][contains(.,"$ 400.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="total-amount"][contains(.,"$ 406.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="kpi-collected"][contains(.,"$ 0")]'));
    }

    public function testTheFiltersKeepTheirValuesInTheForm(): void
    {
        $this->insertInstallment($this->createContract(), '2026-11-01');

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas?q=Cipolletti&status=overdue&period=2026-11');

        $this->assertTrue($this->seeXPath($page, '//input[@id="filter-query"][@value="Cipolletti"]'));
        $this->assertTrue($this->seeXPath($page, '//select[@id="filter-status"]/option[@value="overdue"][@selected]'));
        $this->assertTrue($this->seeXPath($page, '//select[@id="filter-period"]/option[@value="2026-11"][@selected]'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidFilterProvider(): array
    {
        return [
            'unknown status'          => ['status=inexistente', InstallmentController::INVALID_STATUS_FILTER_MESSAGE],
            'status as a list'        => ['status[]=overdue', InstallmentController::INVALID_STATUS_FILTER_MESSAGE],
            'period with month 13'    => ['period=2026-13', InstallmentController::INVALID_PERIOD_FILTER_MESSAGE],
            'period with text'        => ['period=noviembre', InstallmentController::INVALID_PERIOD_FILTER_MESSAGE],
            'period with a full date' => ['period=2026-11-01', InstallmentController::INVALID_PERIOD_FILTER_MESSAGE],
            'period out of the range' => ['period=1999-12', InstallmentController::INVALID_PERIOD_FILTER_MESSAGE],
            'period as a list'        => ['period[]=2026-11', InstallmentController::INVALID_PERIOD_FILTER_MESSAGE],
            'search as a list'        => ['q[]=Alvarez', InstallmentController::INVALID_QUERY_MESSAGE],
            'search too long'         => ['q=' . 'a' . '%s', InstallmentController::INVALID_QUERY_MESSAGE],
        ];
    }

    #[DataProvider('invalidFilterProvider')]
    public function testAnInvalidFilterIsReportedAndIgnoredSoTheRestStillShows(string $query, string $message): void
    {
        $id = $this->insertInstallment($this->createContract([], ['tenant_last_name' => 'Alvarez']), '2026-11-01');

        $query = sprintf($query, str_repeat('a', 100));
        $page  = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas?' . $query);

        $page->assertStatus(200);
        $page->assertSee($message);
        $this->assertSame([$id], $this->visibleInstallmentIds($page), 'An invalid filter is ignored: the listing is not empty and nothing breaks.');
    }

    public function testASearchOfExactlyOneHundredCharactersIsAccepted(): void
    {
        $this->insertInstallment($this->createContract(), '2026-11-01');

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas?q=' . str_repeat('a', 100));

        $page->assertStatus(200);
        $page->assertDontSee(InstallmentController::INVALID_QUERY_MESSAGE);
        $page->assertSee('No se encontraron cuotas con los filtros seleccionados.');
    }

    public function testASearchWithWildcardCharactersIsTakenLiterally(): void
    {
        $this->insertInstallment($this->createContract([], ['tenant_last_name' => 'Alvarez']), '2026-11-01');
        $session = $this->sessionFor('admin');

        $this->assertSame([], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=%25')));
        $this->assertSame([], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=A_varez')));
        $this->assertSame([], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?q=' . urlencode("' OR 1=1 --"))));
    }

    public function testTheListIsPagedAndShowsTheMostRecentPeriodsFirst(): void
    {
        $contract = $this->createContract();
        $ids      = [];

        for ($month = 1; $month <= 12; $month++) {
            $period = sprintf('2026-%02d-01', $month);
            $ids[$month] = $this->insertInstallment($contract, $period, ['number' => $month, 'status' => 'paid']);
        }

        $session = $this->sessionFor('admin');

        $first = $this->withSession($session)->get('admin/cuotas');
        $this->assertSame([$ids[12], $ids[11], $ids[10], $ids[9], $ids[8], $ids[7], $ids[6], $ids[5], $ids[4], $ids[3]], $this->visibleInstallmentIds($first));
        $this->assertStringContainsString('Mostrando <strong>1–10</strong>', $this->text($first));
        $this->assertStringContainsString('de <strong>12</strong> cuotas', (string) $first->getBody());

        $second = $this->withSession($session)->get('admin/cuotas?page=2');
        $this->assertSame([$ids[2], $ids[1]], $this->visibleInstallmentIds($second));
        $this->assertStringContainsString('Mostrando <strong>11–12</strong>', $this->text($second));

        $this->assertSame([$ids[2], $ids[1]], $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?page=99')), 'A page past the end shows the last one.');
        $this->assertSame(array_values(array_slice(array_reverse($ids), 0, 10)), $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?page=abc')));
        $this->assertSame(array_values(array_slice(array_reverse($ids), 0, 10)), $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?page=0')));
        $this->assertSame(array_values(array_slice(array_reverse($ids), 0, 10)), $this->visibleInstallmentIds($this->withSession($session)->get('admin/cuotas?page[]=2')));
    }

    public function testThePagesKeepTheFiltersInTheirLinks(): void
    {
        $contract = $this->createContract();

        for ($month = 1; $month <= 11; $month++) {
            $this->insertInstallment($contract, sprintf('2026-%02d-01', $month), ['number' => $month, 'status' => 'paid']);
        }

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas?status=paid');

        $this->assertTrue($this->seeXPath($page, '//nav[contains(@class,"pagination-controls")]//a[contains(@href,"status=paid")][contains(@href,"page=2")]'));
    }

    public function testTheTotalsCoverEveryPageNotOnlyTheVisibleOne(): void
    {
        $contract = $this->createContract();

        for ($month = 1; $month <= 12; $month++) {
            $this->insertInstallment($contract, sprintf('2026-%02d-01', $month), ['number' => $month, 'status' => 'paid', 'base_amount' => '1000.00']);
        }

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas');

        $this->assertTrue($this->seeXPath($page, '//*[@id="total-base"][contains(.,"$ 12.000")]'));
        $this->assertStringContainsString('Total de lo filtrado (12 cuotas)', (string) $page->getBody());
    }

    public function testTheListStillShowsAnInstallmentWhoseTenantWasDeactivated(): void
    {
        $id = $this->insertInstallment(
            $this->createContract([], ['tenant_last_name' => 'Baja', 'tenant_deleted_at' => '2026-11-02 10:00:00']),
            '2026-11-01'
        );

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas');

        $this->assertSame([$id], $this->visibleInstallmentIds($page));
        $this->assertStringContainsString('dado de baja', (string) $page->getBody());
        $this->assertSame('overdue', $this->fetchInstallment($id)['status']);
    }

    public function testTheDataShownByTheListAndTheDetailIsEscaped(): void
    {
        $id = $this->insertInstallment($this->createContract([], ['address' => '<script>alert(1)</script>']), '2026-11-01');
        $session = $this->sessionFor('admin');

        foreach (['admin/cuotas', "admin/cuotas/{$id}"] as $uri) {
            $body = (string) $this->withSession($session)->get($uri)->getBody();

            $this->assertStringNotContainsString('<script>alert(1)</script>', $body, $uri);
            $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body, $uri);
        }
    }

    public function testTheSearchTextIsEscapedWhenItIsShownAgain(): void
    {
        $this->insertInstallment($this->createContract(), '2026-11-01');

        $body = (string) $this->withSession($this->sessionFor('admin'))
            ->get('admin/cuotas?q=' . urlencode('"><script>alert(1)</script>'))
            ->getBody();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
    }

    // ---------------------------------------------------------------------------------------------
    // Detail screen
    // ---------------------------------------------------------------------------------------------

    public function testTheDetailOfAPendingInstallmentShowsNoDelay(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => '2026-11-20']);

        $page = $this->withSession($this->sessionFor('admin'))->get("admin/cuotas/{$id}");

        $page->assertStatus(200);
        $this->assertSame('pending', $this->fetchInstallment($id)['status']);
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-late-fee"][contains(.,"$ 0")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-total"][contains(.,"$ 500.000")]'));
        $this->assertTrue($this->seeXPath($page, '//*[@id="breakdown-formula"][contains(.,"Sin atraso")]'));
        $this->assertStringContainsString('vence en 7 días', $this->text($page));
    }

    public function testTheDetailOfTheLastDayInTermSaysItFallsDueToday(): void
    {
        $id = $this->insertInstallment($this->createContract(), '2026-11-01', ['due_date' => self::TODAY]);

        $page = $this->withSession($this->sessionFor('admin'))->get("admin/cuotas/{$id}");

        $this->assertStringContainsString('vence hoy', (string) $page->getBody());
        $this->assertSame('pending', $this->fetchInstallment($id)['status']);
    }

    public function testTheDetailOfAMissingInstallmentGoesBackToTheListWithAnError(): void
    {
        $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/99999')->assertRedirectTo('/admin/cuotas');

        $this->assertSame(InstallmentController::INSTALLMENT_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    public function testTheDetailOfAnIdTooBigForAnIntegerIsNotFoundEither(): void
    {
        $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/99999999999999999999999')->assertRedirectTo('/admin/cuotas');
    }

    public function testAnIdThatIsNotANumberDoesNotMatchAnyRoute(): void
    {
        $session = $this->sessionFor('admin');

        foreach (['admin/cuotas/abc', 'admin/cuotas/1.5', 'admin/cuotas/-1', 'admin/cuotas/1;DROP'] as $uri) {
            try {
                $result = $this->withSession($session)->get($uri);
                $this->assertSame(404, $result->response()->getStatusCode(), $uri);
            } catch (PageNotFoundException | BadRequestException) {
                // Not found, or the router refuses the characters before it even looks for a route.
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testTheDetailOnlyRefreshesTheInstallmentItShows(): void
    {
        $contract = $this->createContract();
        $other    = $this->insertInstallment($contract, '2026-10-01', ['number' => 1, 'due_date' => '2026-10-10']);
        $shown    = $this->insertInstallment($contract, '2026-11-01', ['number' => 2]);
        $before   = $this->fetchInstallment($other);

        $this->withSession($this->sessionFor('admin'))->get("admin/cuotas/{$shown}")->assertStatus(200);

        $this->assertSame($before, $this->fetchInstallment($other), 'Another installment stays as it was until it is consulted.');
        $this->assertSame('overdue', $this->fetchInstallment($shown)['status']);
    }

    // ---------------------------------------------------------------------------------------------
    // Access control and navigation
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function externalRoleProvider(): array
    {
        return [
            'owner'  => ['owner', '/propietario/dashboard'],
            'tenant' => ['tenant', '/inquilino/estado-cuenta'],
        ];
    }

    #[DataProvider('externalRoleProvider')]
    public function testOwnersAndTenantsCannotReachTheCollectionsNorChangeAnyFee(string $roleName, string $ownPanel): void
    {
        $session = $this->sessionFor($roleName);
        $id      = $this->insertInstallment($this->createContract(), '2026-11-01');
        $before  = $this->fetchInstallment($id);

        foreach (['admin/cuotas', 'admin/cuotas?status=overdue', "admin/cuotas/{$id}"] as $uri) {
            $this->withSession($session)->get($uri)->assertRedirectTo($ownPanel);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'), $uri);
        }

        $this->assertSame($before, $this->fetchInstallment($id), 'A forbidden request must not even bring the installments up to date.');
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $id     = $this->insertInstallment($this->createContract(), '2026-11-01');
        $before = $this->fetchInstallment($id);

        foreach (['admin/cuotas', "admin/cuotas/{$id}"] as $uri) {
            $this->get($uri)->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'), $uri);
        }

        $this->assertSame($before, $this->fetchInstallment($id));
    }

    #[DataProvider('operatorRoleProvider')]
    public function testTheSidebarOffersTheCollectionsToOperatorsAndMarksTheActiveSection(string $roleName): void
    {
        $session = $this->sessionFor($roleName);

        $collections = $this->withSession($session)->get('admin/cuotas');
        $this->assertTrue($this->seeXPath($collections, '//aside//a[contains(@class,"is-active")][normalize-space(.)="Cobranzas"][contains(@href,"admin/cuotas")]'));
        $this->assertFalse($this->seeXPath($collections, '//aside//a[contains(@class,"is-active")][normalize-space(.)="Cuotas"]'));

        $issuing = $this->withSession($session)->get('admin/cuotas/emitir');
        $this->assertTrue($this->seeXPath($issuing, '//aside//a[contains(@class,"is-active")][normalize-space(.)="Cuotas"]'));
        $this->assertTrue($this->seeXPath($issuing, '//aside//a[normalize-space(.)="Cobranzas"]'));
    }

    #[DataProvider('externalRoleProvider')]
    public function testOwnersAndTenantsDoNotSeeTheCollectionsInTheirMenu(string $roleName, string $ownPanel): void
    {
        $page = $this->withSession($this->sessionFor($roleName))->get(ltrim($ownPanel, '/'));

        $page->assertStatus(200);
        $this->assertFalse($this->seeXPath($page, '//aside//a[normalize-space(.)="Cobranzas"]'));
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * Brings the installments up to date for a day (by default the frozen "today").
     *
     * @return array{checked: int, marked_overdue: int, updated: int, failed: int}
     */
    private function refresh(string $day = self::TODAY, ?int $installmentId = null): array
    {
        return model(InstallmentModel::class)->refreshLateFees($day, $installmentId);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchInstallment(int $id): array
    {
        $row = $this->db->table('installments')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row, "Installment {$id} must exist");

        return $row;
    }

    /**
     * XPath of the table row of an installment in the collections screen.
     */
    private function rowXPath(int $id): string
    {
        return sprintf('//tr[@data-installment-id="%d"]', $id);
    }

    /**
     * The ids of the installments listed on the page, in the order shown.
     *
     * @return list<int>
     */
    private function visibleInstallmentIds(TestResponse $response): array
    {
        preg_match_all('/<tr data-installment-id="(\d+)"/', (string) $response->getBody(), $matches);

        return array_map('intval', $matches[1]);
    }

    /**
     * The body of a response with its HTML entities decoded (the test environment encodes accented letters and
     * dashes as entities), to look for visible text.
     */
    private function text(TestResponse $response): string
    {
        return html_entity_decode((string) $response->getBody(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Tells whether an XPath expression matches something in the page. The framework's CSS selector support is too
     * limited for descendant selectors and bare attributes such as `disabled`.
     */
    private function seeXPath(TestResponse $response, string $xpath): bool
    {
        return (new DOMParser())->withString((string) $response->getBody())->seeXPath($xpath);
    }

    private function sessionFor(string $roleName): array
    {
        $email  = "{$roleName}.session.hu14@example.com";
        $userId = $this->createUser($roleName, 'Sesión', ucfirst($roleName), (string) (40000000 + crc32($roleName) % 99999), $email);

        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => $email,
            'user_name'    => 'Sesión ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ];
    }

    private function createUser(
        string $roleName,
        string $firstName,
        string $lastName,
        string $documentNumber,
        string $email,
        ?string $deletedAt = null
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => $documentNumber,
            'email'           => $email,
            'phone'           => '2994000000',
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Inserts a contract with its own owner, tenant and property straight into the tables (bypassing the
     * application). By default it is in force from 2026-01-01 to 2027-12-31, falls due on the 10th, rents for 500,000
     * and charges 0.5 % a day.
     *
     * @param array<string, mixed>  $overrides Columns of the contract
     * @param array<string, string> $options   `tenant_last_name`, `tenant_deleted_at` and `address`
     */
    private function createContract(array $overrides = [], array $options = []): int
    {
        $n = ++$this->sequence;

        $owner  = $this->createUser('owner', 'Propietario', "Dueño{$n}", (string) (20000000 + $n), "owner{$n}.hu14@example.com");
        $tenant = $this->createUser(
            'tenant',
            'Inquilino',
            $options['tenant_last_name'] ?? "Número{$n}",
            (string) (30000000 + $n),
            "tenant{$n}.hu14@example.com",
            $options['tenant_deleted_at'] ?? null
        );

        $this->db->table('properties')->insert([
            'owner_id'          => $owner,
            'address'           => $options['address'] ?? "Calle {$n}",
            'city'              => 'Cipolletti',
            'type'              => 'apartment',
            'rooms'             => 3,
            'surface'           => 80.00,
            'included_services' => 'Agua',
            'base_rent'         => 500000.00,
            'description'       => 'Departamento de prueba',
            'status'            => 'rented',
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ]);
        $property = (int) $this->db->insertID();

        $this->db->table('contracts')->insert(array_merge([
            'property_id'               => $property,
            'tenant_id'                 => $tenant,
            'owner_id'                  => $owner,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => 500000.00,
            'update_percentage'         => 10,
            'due_day'                   => 10,
            'commission_percentage'     => 8,
            'late_fee_daily_percentage' => 0.5,
            'status'                    => 'in_force',
            'created_at'                => '2026-01-01 10:00:00',
            'updated_at'                => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * Inserts an installment straight into the table (bypassing the application). By default it is the first one of
     * its contract, falls due on the 10th of its month, is for 500,000 and is "pending" with no late fee.
     *
     * @param array<string, mixed> $overrides
     */
    private function insertInstallment(int $contractId, string $period, array $overrides = []): int
    {
        $this->db->table('installments')->insert(array_merge([
            'contract_id' => $contractId,
            'number'      => 1,
            'period'      => $period,
            'due_date'    => substr($period, 0, 7) . '-10',
            'base_amount' => 500000.00,
            'late_fee'    => 0,
            'status'      => 'pending',
            'created_at'  => '2026-11-01 10:00:00',
            'updated_at'  => '2026-11-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * Sends a POST with the given session data plus a valid session CSRF token.
     *
     * @param array<string, mixed> $sessionData
     * @param array<string, mixed> $postData
     */
    private function postWithSession(array $sessionData, string $uri, array $postData)
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, array_merge($postData, [$tokenName => $tokenHash]));
    }
}
