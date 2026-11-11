<?php

use App\Controllers\InstallmentController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\ContractModel;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\DOMParser;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Automated acceptance, atomicity and security tests for HU-13: monthly installment issuing.
 *
 * @internal
 */
final class InstallmentIssuingTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const BLOCKING_TRIGGER = 'trg_hu13_block_installment_insert';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TRIGGER IF EXISTS ' . self::BLOCKING_TRIGGER);
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: issuing the installments of a period
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
    public function testScenario1OperatorIssuesOneInstallmentPerContractInForceThatCoversThePeriod(string $roleName): void
    {
        $session = $this->sessionFor($roleName);
        $first   = $this->createContract(['monthly_rent' => '420000.00', 'due_day' => 10]);
        $second  = $this->createContract(['monthly_rent' => '390000.50', 'due_day' => 5]);

        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $rows = $this->installmentsByContract();
        $this->assertCount(2, $rows);

        $this->assertSame($first, (int) $rows[0]->contract_id);
        $this->assertSame('2026-12-01', $rows[0]->period);
        $this->assertSame('2026-12-10', $rows[0]->due_date);
        $this->assertSame('420000.00', $rows[0]->base_amount);

        $this->assertSame($second, (int) $rows[1]->contract_id);
        $this->assertSame('2026-12-01', $rows[1]->period);
        $this->assertSame('2026-12-05', $rows[1]->due_date);
        $this->assertSame('390000.50', $rows[1]->base_amount);

        foreach ($rows as $row) {
            $this->assertSame('pending', $row->status);
            $this->assertSame('0.00', $row->late_fee);
            $this->assertSame('1', (string) $row->number);
            $this->assertNotNull($row->created_at);
        }
    }

    public function testScenario1TheReportShowsTheCountAndTheTotalToCollect(): void
    {
        $admin = $this->sessionFor('admin');
        $this->createContract(['monthly_rent' => '420000.00', 'due_day' => 10], ['tenant_last_name' => 'Rojas', 'address' => 'Belgrano 1250']);
        $this->createContract(['monthly_rent' => '390000.50', 'due_day' => 5], ['tenant_last_name' => 'Aguirre', 'address' => 'Moreno 560']);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
        $report = $this->withSession()->get('admin/cuotas/emitir/resultado');

        $report->assertStatus(200);
        $report->assertSee('Resultado de la emisión');
        $report->assertSee('Diciembre 2026');
        $report->assertSee('2', '#report-issued-count');
        $report->assertSee('0', '#report-skipped-count');
        $report->assertSee('$ 810.000,50', '#report-total');
        $report->assertSee('Rojas, Inquilino');
        $report->assertSee('Belgrano 1250');
        $report->assertSee('10/12/2026');
        $report->assertSee('05/12/2026');
        $report->assertSee('Pendiente');
        $report->assertSeeElement('#btn-emit-another');
    }

    public function testTheReportPageWithoutAnIssuingGoesBackToTheForm(): void
    {
        $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir/resultado')
            ->assertRedirectTo('/admin/cuotas/emitir');
    }

    public function testTheMonthAcceptsALeadingZeroAndTheAmountOfTheContractIsCopiedExactly(): void
    {
        $admin    = $this->sessionFor('admin');
        $contract = $this->createContract(['monthly_rent' => '0.01', 'due_day' => 28]);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '03', 'year' => '2026']);

        $row = $this->installmentsByContract()[0] ?? null;
        $this->assertNotNull($row);
        $this->assertSame($contract, (int) $row->contract_id);
        $this->assertSame('2026-03-01', $row->period);
        $this->assertSame('2026-03-28', $row->due_date);
        $this->assertSame('0.01', $row->base_amount);
    }

    // ---------------------------------------------------------------------------------------------
    // Preview
    // ---------------------------------------------------------------------------------------------

    public function testTheFormWithoutAPeriodShowsTheSelectorsAndNoPreview(): void
    {
        $this->createContract();

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir');

        $page->assertStatus(200);
        $page->assertSee('Emitir cuotas');
        $page->assertSeeElement('#month');
        $page->assertSeeElement('#year');
        $page->assertSeeElement('#btn-preview');
        $page->assertDontSeeElement('#preview-table');
        $page->assertDontSeeElement('#emit-form');
    }

    public function testThePreviewListsTheContractsThatWillBeIssuedWithTheirDataAndTheTotal(): void
    {
        $first  = $this->createContract(['monthly_rent' => '420000.00', 'due_day' => 10], ['tenant_last_name' => 'Rojas', 'address' => 'Belgrano 1250']);
        $second = $this->createContract(['monthly_rent' => '390000.00', 'due_day' => 5], ['tenant_last_name' => 'Aguirre', 'address' => 'Moreno 560']);

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir?month=12&year=2026');

        $page->assertStatus(200);
        $page->assertSee('Vista previa — Diciembre 2026');
        $page->assertSee('Rojas, Inquilino');
        $page->assertSee('Belgrano 1250');
        $page->assertSee('Aguirre, Inquilino');
        $page->assertSee('Moreno 560');
        $page->assertSee('10/12/2026');
        $page->assertSee('05/12/2026');
        $page->assertSee('$ 420.000');
        $page->assertSee('$ 810.000', '#preview-total');
        $page->assertSee('Se emitirá');
        $page->assertSee('Emitir 2 cuotas');
        $this->assertTrue($this->seeXPath($page, '//form[@id="emit-form"]//input[@name="month"][@value="12"]'));
        $this->assertTrue($this->seeXPath($page, '//form[@id="emit-form"]//input[@name="year"][@value="2026"]'));
        // Scenario 5: every row offers to issue only its own contract.
        $this->assertTrue($this->seeXPath($page, '//form[@id="emit-form"]//button[@name="contract_id"][@value="' . $first . '"]'));
        $this->assertTrue($this->seeXPath($page, '//form[@id="emit-form"]//button[@name="contract_id"][@value="' . $second . '"]'));
        $this->assertTrue($this->seeXPath($page, '//button[@id="btn-emit-all"][not(@disabled)]'));

        $this->assertSame(0, $this->db->table('installments')->countAllResults(), 'Previewing must not store anything.');
    }

    public function testThePreviewMarksTheContractsThatAlreadyHaveTheInstallmentAndLeavesThemOutOfTheTotal(): void
    {
        $issued = $this->createContract(['monthly_rent' => '100000.00']);
        $this->createContract(['monthly_rent' => '250000.00']);
        $this->insertInstallment($issued, '2026-12-01');

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir?month=12&year=2026');

        $page->assertStatus(200);
        $page->assertSee('Ya emitida');
        $page->assertSee('1 / 2');
        $page->assertSee('$ 250.000', '#preview-total');
        $page->assertSee('Emitir 1 cuota');
        $this->assertFalse($this->seeXPath($page, '//form[@id="emit-form"]//button[@name="contract_id"][@value="' . $issued . '"]'));
    }

    public function testThePreviewDisablesTheConfirmationWhenEverythingWasAlreadyIssued(): void
    {
        $this->insertInstallment($this->createContract(), '2026-12-01');

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir?month=12&year=2026');

        $page->assertStatus(200);
        $page->assertSee('ya fueron emitidas');
        $this->assertTrue($this->seeXPath($page, '//button[@id="btn-emit-all"][@disabled]'));
    }

    public function testThePreviewOfAPeriodWithoutContractsSaysSo(): void
    {
        $this->createContract(['start_date' => '2026-01-01', 'end_date' => '2026-06-30']);

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir?month=12&year=2026');

        $page->assertStatus(200);
        $page->assertSee('No hay contratos vigentes que cubran Diciembre 2026.');
        $this->assertTrue($this->seeXPath($page, '//button[@id="btn-emit-all"][@disabled]'));
    }

    public function testThePreviewStillListsAContractWhoseTenantWasDeactivated(): void
    {
        $this->createContract([], ['tenant_last_name' => 'Duarte', 'tenant_deleted_at' => '2026-10-01 10:00:00']);

        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir?month=12&year=2026');

        $page->assertSee('Duarte, Inquilino');
        $page->assertSee('dado de baja');
        $page->assertSee('Se emitirá');
    }

    public function testTheDataShownByThePreviewAndTheReportIsEscaped(): void
    {
        $admin = $this->sessionFor('admin');
        $this->createContract([], ['address' => '<script>alert(1)</script>']);

        $preview = (string) $this->withSession($admin)->get('admin/cuotas/emitir?month=12&year=2026')->getBody();
        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
        $report = (string) $this->withSession()->get('admin/cuotas/emitir/resultado')->getBody();

        foreach ([$preview, $report] as $body) {
            $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
            $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
        }
    }

    public function testTheSidebarOffersTheIssuingToOperators(): void
    {
        $page = $this->withSession($this->sessionFor('admin'))->get('admin/cuotas/emitir');

        $this->assertTrue($this->seeXPath($page, '//aside//a[contains(@class,"is-active")][contains(@href,"admin/cuotas/emitir")]'));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: installment already issued
    // ---------------------------------------------------------------------------------------------

    public function testScenario2AnExistingInstallmentIsNotDuplicatedNorModified(): void
    {
        $admin    = $this->sessionFor('admin');
        $existing = $this->createContract(['monthly_rent' => '500000.00', 'due_day' => 10]);
        $other    = $this->createContract(['monthly_rent' => '300000.00', 'due_day' => 10]);

        $this->insertInstallment($existing, '2026-12-01', [
            'number'      => 7,
            'due_date'    => '2026-12-20',
            'base_amount' => 111111.11,
            'late_fee'    => 2500.00,
            'status'      => 'paid',
            'updated_at'  => '2026-11-15 09:00:00',
        ]);
        $before = $this->db->table('installments')->where('contract_id', $existing)->get()->getRowArray();

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame(2, $this->db->table('installments')->countAllResults());
        $this->assertSame(
            $before,
            $this->db->table('installments')->where('contract_id', $existing)->get()->getRowArray(),
            'The installment that already existed must stay exactly as it was.'
        );
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $other)->countAllResults());

        $report = $this->withSession()->get('admin/cuotas/emitir/resultado');
        $report->assertSee('1', '#report-issued-count');
        $report->assertSee('1', '#report-skipped-count');
        $report->assertSee('$ 300.000', '#report-total');
    }

    public function testScenario2IssuingTheSamePeriodAgainCreatesNothing(): void
    {
        $admin = $this->sessionFor('admin');
        $this->createContract();
        $this->createContract();

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
        $this->assertSame(2, $this->db->table('installments')->countAllResults());

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');
        $report = $this->withSession()->get('admin/cuotas/emitir/resultado');

        $this->assertSame(2, $this->db->table('installments')->countAllResults(), 'No duplicates may be created.');
        $report->assertSee('0', '#report-issued-count');
        $report->assertSee('2', '#report-skipped-count');
        $report->assertSee('No se generó ninguna cuota nueva');
        $report->assertSee('Sin cuotas nuevas');
    }

    public function testAnInstallmentOfAnotherPeriodDoesNotBlockTheIssuing(): void
    {
        $contract = $this->createContract();
        $this->insertInstallment($contract, '2026-11-01', ['number' => 1]);

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);

        $this->assertSame(2, $this->db->table('installments')->where('contract_id', $contract)->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Sequential numbering per contract
    // ---------------------------------------------------------------------------------------------

    public function testNumbersAreSequentialPerContractAcrossPeriods(): void
    {
        $admin    = $this->sessionFor('admin');
        $contract = $this->createContract();

        foreach ([['12', '2026'], ['1', '2027'], ['2', '2027']] as [$month, $year]) {
            $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => $month, 'year' => $year]);
        }

        $numbers = array_map(
            static fn (object $row): int => (int) $row->number,
            $this->db->table('installments')->where('contract_id', $contract)->orderBy('period', 'ASC')->get()->getResult()
        );

        $this->assertSame([1, 2, 3], $numbers);
    }

    public function testEveryContractHasItsOwnNumbering(): void
    {
        $admin = $this->sessionFor('admin');
        $old   = $this->createContract();

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
        $new = $this->createContract();
        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '1', 'year' => '2027']);

        $numberOf = fn (int $contractId, string $period): int => (int) $this->db->table('installments')
            ->where('contract_id', $contractId)->where('period', $period)->get()->getRow()->number;

        $this->assertSame(1, $numberOf($old, '2026-12-01'));
        $this->assertSame(2, $numberOf($old, '2027-01-01'));
        $this->assertSame(1, $numberOf($new, '2027-01-01'), 'A contract that starts being billed later starts at number 1.');
    }

    public function testTheNextNumberContinuesFromTheHighestOneAlreadyUsedByTheContract(): void
    {
        $contract = $this->createContract();
        $this->insertInstallment($contract, '2026-09-01', ['number' => 3]);
        $this->insertInstallment($contract, '2026-10-01', ['number' => 4]);

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);

        $row = $this->db->table('installments')->where('contract_id', $contract)->where('period', '2026-12-01')->get()->getRow();
        $this->assertNotNull($row);
        $this->assertSame(5, (int) $row->number);
    }

    public function testNumbersFollowTheOrderInWhichThePeriodsAreIssued(): void
    {
        $admin    = $this->sessionFor('admin');
        $contract = $this->createContract();

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '2', 'year' => '2027']);
        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '1', 'year' => '2027']);

        $numberOf = fn (string $period): int => (int) $this->db->table('installments')
            ->where('contract_id', $contract)->where('period', $period)->get()->getRow()->number;

        $this->assertSame(1, $numberOf('2027-02-01'));
        $this->assertSame(2, $numberOf('2027-01-01'));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: contracts that are not included
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function contractsOutsideThePeriodProvider(): array
    {
        return [
            'finished contract'                                  => [['status' => 'finished', 'closed_at' => '2026-11-30', 'closing_reason' => 'Fin del plazo']],
            'rescinded contract'                                 => [['status' => 'rescinded', 'closed_at' => '2026-11-15', 'closing_reason' => 'Mudanza']],
            'contract that ended before the period'              => [['start_date' => '2025-01-01', 'end_date' => '2026-11-30']],
            'contract that starts after the period'              => [['start_date' => '2027-01-01', 'end_date' => '2028-12-31']],
            'contract that starts after the due date of the month' => [['start_date' => '2026-12-15', 'end_date' => '2028-12-14', 'due_day' => 10]],
            'contract that ends before the due date of the month' => [['start_date' => '2025-12-01', 'end_date' => '2026-12-09', 'due_day' => 10]],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('contractsOutsideThePeriodProvider')]
    public function testScenario3AContractThatIsNotInForceOrDoesNotCoverThePeriodGetsNoInstallment(array $overrides): void
    {
        $admin = $this->sessionFor('admin');
        $this->createContract($overrides);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir?month=12&year=2026');

        $this->assertSame(sprintf(InstallmentController::NO_CONTRACTS_MESSAGE, 'Diciembre 2026'), session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults());
        $this->assertNull(session()->getFlashdata(InstallmentController::REPORT_FLASH_KEY));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('contractsOutsideThePeriodProvider')]
    public function testScenario3TheExcludedContractsAreAbsentFromThePreviewAndTheyDoNotStopTheOthers(array $overrides): void
    {
        $admin    = $this->sessionFor('admin');
        $excluded = $this->createContract($overrides);
        $included = $this->createContract();

        $page = $this->withSession($admin)->get('admin/cuotas/emitir?month=12&year=2026');
        $this->assertTrue($this->seeXPath($page, '//table[@id="preview-table"]//tr[@data-contract-id="' . $included . '"]'));
        $this->assertFalse($this->seeXPath($page, '//table[@id="preview-table"]//tr[@data-contract-id="' . $excluded . '"]'));

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame(1, $this->db->table('installments')->countAllResults());
        $this->assertSame(0, $this->db->table('installments')->where('contract_id', $excluded)->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $included)->countAllResults());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function contractsOnTheEdgeOfThePeriodProvider(): array
    {
        return [
            'due date is the first day of the term' => [['start_date' => '2026-12-10', 'end_date' => '2028-12-09', 'due_day' => 10]],
            'due date is the last day of the term'  => [['start_date' => '2025-12-11', 'end_date' => '2026-12-10', 'due_day' => 10]],
            'term is exactly the month of the due date' => [['start_date' => '2026-12-01', 'end_date' => '2026-12-31', 'due_day' => 28]],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('contractsOnTheEdgeOfThePeriodProvider')]
    public function testAContractWhoseDueDateIsOnTheEdgeOfItsTermIsCovered(array $overrides): void
    {
        $contract = $this->createContract($overrides);

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $contract)->countAllResults());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function wholeMonthContractsProvider(): array
    {
        return [
            'starts on the 1st, due day 10'    => ['2026-12-01', '2028-11-30', 10],
            'starts on the 15th, due day 10'   => ['2026-12-15', '2028-12-14', 10],
            'starts on the 15th, due day 20'   => ['2026-12-15', '2028-12-14', 20],
            'starts on the 28th, due day 1'    => ['2027-01-28', '2029-01-27', 1],
            'starts on the 5th, due day 28'    => ['2027-03-05', '2029-03-04', 28],
        ];
    }

    #[DataProvider('wholeMonthContractsProvider')]
    public function testAContractOfTwentyFourMonthsIsCoveredByExactlyTwentyFourPeriodsAndEachDueDateIsInsideItsTerm(
        string $start,
        string $end,
        int $dueDay
    ): void {
        $contract = $this->createContract(['start_date' => $start, 'end_date' => $end, 'due_day' => $dueDay]);
        $model    = new ContractModel();

        $covered = [];

        for ($year = 2026; $year <= 2029; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $period = sprintf('%04d-%02d-01', $year, $month);
                $ids    = array_map(static fn (array $row): int => (int) $row['id'], $model->findInForceCoveringPeriod($period));

                if (in_array($contract, $ids, true)) {
                    $covered[] = $period;
                }
            }
        }

        $this->assertCount(24, $covered);

        foreach ($covered as $period) {
            $due = sprintf('%s-%02d', substr($period, 0, 7), $dueDay);
            $this->assertGreaterThanOrEqual($start, $due);
            $this->assertLessThanOrEqual($end, $due);
        }
    }

    public function testPastAndFuturePeriodsCanBeIssuedWhenTheContractCoversThem(): void
    {
        $admin    = $this->sessionFor('admin');
        $contract = $this->createContract(['start_date' => '2024-01-01', 'end_date' => '2036-12-31']);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '3', 'year' => '2024']);
        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '7', 'year' => '2035']);

        $periods = array_map(
            static fn (object $row): string => $row->period,
            $this->db->table('installments')->where('contract_id', $contract)->orderBy('period', 'ASC')->get()->getResult()
        );

        $this->assertSame(['2024-03-01', '2035-07-01'], $periods);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: invalid period
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPeriodProvider(): array
    {
        return [
            'nothing selected'        => [[]],
            'empty month and year'    => [['month' => '', 'year' => '']],
            'only the month'          => [['month' => '12']],
            'only the year'           => [['year' => '2026']],
            'month zero'              => [['month' => '0', 'year' => '2026']],
            'month thirteen'          => [['month' => '13', 'year' => '2026']],
            'month with letters'      => [['month' => 'diciembre', 'year' => '2026']],
            'negative month'          => [['month' => '-1', 'year' => '2026']],
            'decimal month'           => [['month' => '1.5', 'year' => '2026']],
            'year with letters'       => [['month' => '12', 'year' => 'abcd']],
            'two digit year'          => [['month' => '12', 'year' => '26']],
            'year before the limit'   => [['month' => '12', 'year' => '1999']],
            'year after the limit'    => [['month' => '12', 'year' => '2101']],
            'month sent as a list'    => [['month' => ['12'], 'year' => '2026']],
            'year sent as a list'     => [['month' => '12', 'year' => ['2026']]],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPeriodProvider')]
    public function testScenario4AnInvalidPeriodIsRejectedAndNothingIsIssued(array $payload): void
    {
        $this->createContract();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', $payload)
            ->assertRedirectTo('/admin/cuotas/emitir');

        $this->assertSame(InstallmentController::INVALID_PERIOD_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults());
    }

    public function testScenario4TheFormShowsTheErrorWhenThePeriodIsInvalid(): void
    {
        $this->createContract();
        $admin = $this->sessionFor('admin');

        foreach (['admin/cuotas/emitir?month=13&year=2026', 'admin/cuotas/emitir?month=12', 'admin/cuotas/emitir?month=&year='] as $uri) {
            $page = $this->withSession($admin)->get($uri);

            $page->assertStatus(200);
            $page->assertSee(InstallmentController::INVALID_PERIOD_MESSAGE, '#period-error');
            $page->assertDontSeeElement('#preview-table');
            $page->assertDontSeeElement('#emit-form');
        }
    }

    public function testTheFormStillRendersAfterARejectedSubmissionWithValuesSentAsLists(): void
    {
        $admin = $this->sessionFor('admin');

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => ['2026']])
            ->assertRedirectTo('/admin/cuotas/emitir');

        $page = $this->withSession()->get('admin/cuotas/emitir');

        $page->assertStatus(200);
        $page->assertSeeElement('#period-form');
    }

    public function testTheFormRemembersTheMonthAndYearOfARejectedSubmission(): void
    {
        $admin = $this->sessionFor('admin');

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '13', 'year' => '2027'])
            ->assertRedirectTo('/admin/cuotas/emitir');

        $page = (string) $this->withSession()->get('admin/cuotas/emitir')->getBody();

        $this->assertStringContainsString('<option value="2027" selected>2027</option>', $page);
        $this->assertStringNotContainsString('<option value="13" selected>', $page);
    }

    public function testTheLimitsOfTheAcceptedYearsAreValidPeriods(): void
    {
        $admin = $this->sessionFor('admin');
        $this->createContract();

        foreach ([['1', '2000'], ['12', '2100']] as [$month, $year]) {
            $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => $month, 'year' => $year])
                ->assertRedirectTo("/admin/cuotas/emitir?month={$month}&year={$year}");

            $this->assertSame(
                sprintf(InstallmentController::NO_CONTRACTS_MESSAGE, ($month === '1' ? 'Enero' : 'Diciembre') . ' ' . $year),
                session()->getFlashdata('error'),
                'The period is valid; there is simply no contract covering it.'
            );
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: individual issuing
    // ---------------------------------------------------------------------------------------------

    public function testScenario5TheIssuingCanBeLimitedToOneContract(): void
    {
        $admin  = $this->sessionFor('admin');
        $first  = $this->createContract(['monthly_rent' => '100000.00']);
        $second = $this->createContract(['monthly_rent' => '200000.00', 'due_day' => 15]);
        $third  = $this->createContract(['monthly_rent' => '300000.00']);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => (string) $second])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $rows = $this->installmentsByContract();
        $this->assertCount(1, $rows);
        $this->assertSame($second, (int) $rows[0]->contract_id);
        $this->assertSame('2026-12-15', $rows[0]->due_date);
        $this->assertSame('200000.00', $rows[0]->base_amount);
        $this->assertSame('pending', $rows[0]->status);
        $this->assertSame('0.00', $rows[0]->late_fee);
        $this->assertSame('1', (string) $rows[0]->number);

        $report = $this->withSession()->get('admin/cuotas/emitir/resultado');
        $report->assertSee('1', '#report-issued-count');
        $report->assertSee('$ 200.000', '#report-total');
        $report->assertSee('Emisión de un solo contrato');

        // The rest of the period can still be issued afterwards, and the contract already issued is skipped.
        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);

        $this->assertSame(3, $this->db->table('installments')->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $first)->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $second)->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $third)->countAllResults());
    }

    public function testScenario5AnIndividualIssuingOfAContractThatAlreadyHasTheInstallmentChangesNothing(): void
    {
        $contract = $this->createContract();
        $this->insertInstallment($contract, '2026-12-01', ['status' => 'paid', 'base_amount' => 99999.99]);
        $before = $this->db->table('installments')->get()->getResultArray();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => (string) $contract])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame($before, $this->db->table('installments')->get()->getResultArray());

        $report = $this->withSession()->get('admin/cuotas/emitir/resultado');
        $report->assertSee('0', '#report-issued-count');
        $report->assertSee('1', '#report-skipped-count');
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function contractsThatCannotBeIssuedIndividuallyProvider(): array
    {
        return [
            'finished contract'                     => [['status' => 'finished', 'closed_at' => '2026-11-30', 'closing_reason' => 'Fin del plazo']],
            'rescinded contract'                    => [['status' => 'rescinded', 'closed_at' => '2026-11-15', 'closing_reason' => 'Mudanza']],
            'contract that does not cover the period' => [['start_date' => '2025-01-01', 'end_date' => '2026-06-30']],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('contractsThatCannotBeIssuedIndividuallyProvider')]
    public function testScenario5AnIndividualIssuingOfAContractThatIsNotEligibleIsRejected(array $overrides): void
    {
        $contract = $this->createContract($overrides);
        $this->createContract();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => (string) $contract])
            ->assertRedirectTo('/admin/cuotas/emitir?month=12&year=2026');

        $this->assertSame(InstallmentController::INVALID_CONTRACT_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults(), 'Not even the other contract may be issued by an individual request.');
    }

    public function testScenario5AnIndividualIssuingOfAMissingContractIsRejected(): void
    {
        $this->createContract();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => '999999'])
            ->assertRedirectTo('/admin/cuotas/emitir?month=12&year=2026');

        $this->assertSame(InstallmentController::INVALID_CONTRACT_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function malformedContractIdProvider(): array
    {
        return [
            'letters'          => ['abc'],
            'zero'             => ['0'],
            'negative'         => ['-1'],
            'decimal'          => ['1.5'],
            'with spaces'      => [' 1'],
            'sql fragment'     => ['1 OR 1=1'],
            'too long'         => ['12345678901'],
            'sent as a list'   => [['1']],
        ];
    }

    #[DataProvider('malformedContractIdProvider')]
    public function testAMalformedContractIdIsRejectedInsteadOfIssuingEverything(mixed $contractId): void
    {
        $this->createContract();
        $this->createContract();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => $contractId])
            ->assertRedirectTo('/admin/cuotas/emitir?month=12&year=2026');

        $this->assertSame(InstallmentController::INVALID_CONTRACT_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults());
    }

    public function testAnEmptyContractIdMeansTheMassIssuing(): void
    {
        $this->createContract();
        $this->createContract();

        $this->postWithSession($this->sessionFor('admin'), 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => ''])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame(2, $this->db->table('installments')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Atomicity
    // ---------------------------------------------------------------------------------------------

    public function testAFailureWhileStoringOneInstallmentDiscardsTheWholeIssuing(): void
    {
        $admin = $this->sessionFor('admin');
        $first = $this->createContract();
        $last  = $this->createContract();

        // The insert of the last contract's installment fails after the first contract's one was already stored.
        $this->db->query(
            'CREATE TRIGGER ' . self::BLOCKING_TRIGGER . ' BEFORE INSERT ON installments FOR EACH ROW '
            . "IF NEW.contract_id = {$last} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'blocked for the atomicity test'; END IF"
        );

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir?month=12&year=2026');

        $this->assertSame(InstallmentController::SAVE_ERROR_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('installments')->countAllResults(), 'No installment may survive a failed issuing.');

        // Once the fault is gone the same request works: the failed attempt left nothing behind.
        $this->db->query('DROP TRIGGER IF EXISTS ' . self::BLOCKING_TRIGGER);

        $this->postWithSession($admin, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');

        $this->assertSame(2, $this->db->table('installments')->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $first)->countAllResults());
        $this->assertSame(1, $this->db->table('installments')->where('contract_id', $last)->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------------------------------------

    #[DataProvider('operatorRoleProvider')]
    public function testAdministratorsAndEmployeesCanUseTheIssuingScreens(string $roleName): void
    {
        $session = $this->sessionFor($roleName);
        $this->createContract();

        foreach (['admin/cuotas/emitir', 'admin/cuotas/emitir?month=12&year=2026'] as $uri) {
            $this->withSession($session)->get($uri)->assertStatus(200);
        }

        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])
            ->assertRedirectTo('/admin/cuotas/emitir/resultado');
        $this->assertSame(1, $this->db->table('installments')->countAllResults());
    }

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
    public function testOwnersAndTenantsCannotReachAnyIssuingRoute(string $roleName, string $ownPanel): void
    {
        $session = $this->sessionFor($roleName);
        $this->createContract();

        foreach (['admin/cuotas/emitir', 'admin/cuotas/emitir?month=12&year=2026', 'admin/cuotas/emitir/resultado'] as $uri) {
            $this->withSession($session)->get($uri)->assertRedirectTo($ownPanel);
            $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])->assertRedirectTo($ownPanel);
        $this->postWithSession($session, 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026', 'contract_id' => '1'])->assertRedirectTo($ownPanel);

        $this->assertSame(0, $this->db->table('installments')->countAllResults(), 'No installment may be issued by an external role.');
    }

    public function testVisitorsAreRedirectedToTheLogin(): void
    {
        $this->createContract();

        foreach (['admin/cuotas/emitir', 'admin/cuotas/emitir?month=12&year=2026', 'admin/cuotas/emitir/resultado'] as $uri) {
            $this->get($uri)->assertRedirectTo('/auth/login');
            $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));
        }

        $this->postWithSession([], 'admin/cuotas/emitir', ['month' => '12', 'year' => '2026'])->assertRedirectTo('/auth/login');
        $this->assertSame(0, $this->db->table('installments')->countAllResults());
    }

    public function testThePostWithoutACsrfTokenIsRejected(): void
    {
        $session = $this->sessionFor('admin');
        $this->createContract();

        $this->expectException(SecurityException::class);

        $this->withSession($session)->post('admin/cuotas/emitir', ['month' => '12', 'year' => '2026']);
    }

    // ---------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------

    /**
     * Creates a user with the given role and returns the session data its login would store.
     *
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionFor(string $roleName): array
    {
        $email  = "{$roleName}.session.hu13@example.com";
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
     * application). By default it is in force from 2026-01-01 to 2027-12-31 and falls due on the 10th.
     *
     * @param array<string, mixed>  $overrides Columns of the contract
     * @param array<string, string> $options   `tenant_last_name`, `tenant_deleted_at` and `address`
     */
    private function createContract(array $overrides = [], array $options = []): int
    {
        $n = ++$this->sequence;

        $owner  = $this->createUser('owner', 'Propietario', "Dueño{$n}", (string) (20000000 + $n), "owner{$n}.hu13@example.com");
        $tenant = $this->createUser(
            'tenant',
            'Inquilino',
            $options['tenant_last_name'] ?? "Número{$n}",
            (string) (30000000 + $n),
            "tenant{$n}.hu13@example.com",
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
     * Inserts an installment straight into the table (bypassing the application).
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
     * Tells whether an XPath expression matches something in the page. The framework's CSS selector support is too
     * limited for descendant selectors and bare attributes such as `disabled`.
     */
    private function seeXPath(TestResponse $response, string $xpath): bool
    {
        return (new DOMParser())->withString((string) $response->getBody())->seeXPath($xpath);
    }

    /**
     * @return list<object>
     */
    private function installmentsByContract(): array
    {
        return $this->db->table('installments')->orderBy('contract_id', 'ASC')->get()->getResult();
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
