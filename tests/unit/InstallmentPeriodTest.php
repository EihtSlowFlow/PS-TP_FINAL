<?php

use App\Models\InstallmentModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the period, due date and amount helpers of the installment issuing (HU-13).
 *
 * @internal
 */
final class InstallmentPeriodTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: mixed, 1: mixed, 2: string}>
     */
    public static function validPeriodProvider(): array
    {
        return [
            'plain strings'            => ['2026', '12', '2026-12-01'],
            'month with a leading zero' => ['2026', '03', '2026-03-01'],
            'month without padding'    => ['2026', '3', '2026-03-01'],
            'integers'                 => [2027, 1, '2027-01-01'],
            'spaces around the values' => [' 2026 ', ' 7 ', '2026-07-01'],
            'lowest year'              => ['2000', '1', '2000-01-01'],
            'highest year'             => ['2100', '12', '2100-12-01'],
        ];
    }

    #[DataProvider('validPeriodProvider')]
    public function testBuildPeriodReturnsTheFirstDayOfTheMonth(mixed $year, mixed $month, string $expected): void
    {
        $this->assertSame($expected, InstallmentModel::buildPeriod($year, $month));
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function invalidPeriodProvider(): array
    {
        return [
            'nothing'            => [null, null],
            'empty strings'      => ['', ''],
            'month zero'         => ['2026', '0'],
            'month thirteen'     => ['2026', '13'],
            'three digit month'  => ['2026', '012'],
            'negative month'     => ['2026', '-1'],
            'decimal month'      => ['2026', '1.5'],
            'letters in month'   => ['2026', 'dic'],
            'letters in year'    => ['20x6', '12'],
            'two digit year'     => ['26', '12'],
            'five digit year'    => ['20261', '12'],
            'year below limit'   => ['1999', '12'],
            'year above limit'   => ['2101', '12'],
            'array month'        => ['2026', ['12']],
            'array year'         => [['2026'], '12'],
            'float month'        => ['2026', 1.5],
            'boolean'            => [true, true],
        ];
    }

    #[DataProvider('invalidPeriodProvider')]
    public function testBuildPeriodRejectsAnythingThatIsNotAWholeMonthOfAnAcceptedYear(mixed $year, mixed $month): void
    {
        $this->assertNull(InstallmentModel::buildPeriod($year, $month));
    }

    public function testLabelForPeriodUsesTheSpanishMonthName(): void
    {
        $this->assertSame('Diciembre 2026', InstallmentModel::labelForPeriod('2026-12-01'));
        $this->assertSame('Enero 2027', InstallmentModel::labelForPeriod('2027-01-01'));
        $this->assertSame('Septiembre 2030', InstallmentModel::labelForPeriod('2030-09-01'));
    }

    public function testDueDateFallsOnTheDueDayInsideTheMonthOfThePeriod(): void
    {
        $this->assertSame('2026-12-10', InstallmentModel::dueDateFor('2026-12-01', 10));
        $this->assertSame('2026-12-01', InstallmentModel::dueDateFor('2026-12-01', 1));
        $this->assertSame('2027-02-28', InstallmentModel::dueDateFor('2027-02-01', 28));
        $this->assertSame('2028-02-05', InstallmentModel::dueDateFor('2028-02-01', 5));
    }

    public function testSumAmountsAddsDecimalsExactly(): void
    {
        $this->assertSame('0.00', InstallmentModel::sumAmounts([]));
        $this->assertSame('2240000.00', InstallmentModel::sumAmounts(['420000.00', '650000.00', '780000.00', '390000.00']));
        $this->assertSame('0.30', InstallmentModel::sumAmounts(['0.10', '0.20']), 'Floating point drift must not show up.');
        $this->assertSame('1000000.01', InstallmentModel::sumAmounts(['999999.99', '0.02']));
        $this->assertSame('12345678.90', InstallmentModel::sumAmounts([12345678.9]));
    }

    public function testLabelForStatusReturnsTheSpanishLabel(): void
    {
        $this->assertSame('Pendiente', InstallmentModel::labelForStatus('pending'));
        $this->assertSame('Vencida', InstallmentModel::labelForStatus('overdue'));
        $this->assertSame('En revisión', InstallmentModel::labelForStatus('under_review'));
        $this->assertSame('Pagada', InstallmentModel::labelForStatus('paid'));
        $this->assertSame('otro', InstallmentModel::labelForStatus('otro'));
    }
}
