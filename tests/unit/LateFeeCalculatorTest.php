<?php

use App\Libraries\LateFeeCalculator;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the late fee calculation of HU-14: days late, rounding, limits and invalid input.
 *
 * @internal
 */
final class LateFeeCalculatorTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------------------------------------
    // Days late: counted from the day after the due date
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function daysLateProvider(): array
    {
        return [
            'long before the due date'                 => ['2026-11-10', '2026-10-01', 0],
            'the day before the due date'              => ['2026-11-10', '2026-11-09', 0],
            'on the due date it is still on time'      => ['2026-11-10', '2026-11-10', 0],
            'the day after the due date is day one'    => ['2026-11-10', '2026-11-11', 1],
            'two days after'                           => ['2026-11-10', '2026-11-12', 2],
            'a whole month after'                      => ['2026-10-10', '2026-11-10', 31],
            'across the end of a month'                => ['2026-10-28', '2026-11-02', 5],
            'across the end of a year'                 => ['2026-12-28', '2027-01-02', 5],
            'February of a common year'                => ['2026-02-28', '2026-03-01', 1],
            'February of a leap year, 28th to March 1' => ['2028-02-28', '2028-03-01', 2],
            'February of a leap year, 29th to March 1' => ['2028-02-29', '2028-03-01', 1],
            'almost a year late'                       => ['2026-01-10', '2026-12-31', 355],
            'a year and a day late'                    => ['2025-11-10', '2026-11-11', 366],
        ];
    }

    #[DataProvider('daysLateProvider')]
    public function testDaysLateAreCountedFromTheDayAfterTheDueDate(string $dueDate, string $today, int $expected): void
    {
        $this->assertSame($expected, LateFeeCalculator::daysLate($dueDate, $today));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function daysUntilDueProvider(): array
    {
        return [
            'due today'                => ['2026-11-10', '2026-11-10', 0],
            'due tomorrow'             => ['2026-11-10', '2026-11-09', 1],
            'due in four days'         => ['2026-11-10', '2026-11-06', 4],
            'already one day past due' => ['2026-11-10', '2026-11-11', 0],
        ];
    }

    #[DataProvider('daysUntilDueProvider')]
    public function testDaysUntilDueStopAtZeroOnceTheDateArrives(string $dueDate, string $today, int $expected): void
    {
        $this->assertSame($expected, LateFeeCalculator::daysUntilDue($dueDate, $today));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidDateProvider(): array
    {
        return [
            'empty'                  => [''],
            'text'                   => ['mañana'],
            'day first'              => ['10-11-2026'],
            'slashes'                => ['2026/11/10'],
            'two digit year'         => ['26-11-10'],
            'month thirteen'         => ['2026-13-01'],
            'February 30th'          => ['2026-02-30'],
            'February 29th, common'  => ['2026-02-29'],
            'day zero'               => ['2026-11-00'],
            'with a time'            => ['2026-11-10 00:00:00'],
            'with a trailing space'  => ['2026-11-10 '],
            'single digit month'     => ['2026-1-10'],
        ];
    }

    #[DataProvider('invalidDateProvider')]
    public function testAnInvalidDueDateIsRejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        LateFeeCalculator::daysLate($value, '2026-11-10');
    }

    #[DataProvider('invalidDateProvider')]
    public function testAnInvalidCalculationDayIsRejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        LateFeeCalculator::daysLate('2026-11-10', $value);
    }

    // ---------------------------------------------------------------------------------------------
    // The formula: base × (daily % ÷ 100) × days
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: int, 3: string}>
     */
    public static function lateFeeProvider(): array
    {
        return [
            'one day'                                  => ['500000.00', '0.50', 1, '2500.00'],
            'three days'                               => ['100000.00', '0.50', 3, '1500.00'],
            'twenty-seven days'                        => ['390000.00', '0.50', 27, '52650.00'],
            'a different percentage'                   => ['380000.00', '0.40', 5, '7600.00'],
            'amount without decimals'                  => ['420000', '0.5', 2, '4200.00'],
            'amount with a single decimal'             => ['1000.5', '1', 1, '10.01'],
            'one hundred percent a day'                => ['250000.00', '100.00', 3, '750000.00'],
            'the smallest percentage'                  => ['100000.00', '0.01', 1, '10.00'],
            'the smallest amount'                      => ['0.01', '100.00', 1, '0.01'],
            'a year of delay'                          => ['500000.00', '0.50', 365, '912500.00'],
            'amount with thousands'                    => ['1234567.89', '0.25', 10, '30864.20'],
            'zero percent never charges'               => ['500000.00', '0.00', 400, '0.00'],
            'zero days never charge'                   => ['500000.00', '0.50', 0, '0.00'],
            'zero amount never charges'                => ['0.00', '0.50', 10, '0.00'],
        ];
    }

    #[DataProvider('lateFeeProvider')]
    public function testLateFeeIsTheBaseTimesTheDailyPercentageTimesTheDays(string $base, string $percentage, int $days, string $expected): void
    {
        $this->assertSame($expected, LateFeeCalculator::lateFee($base, $percentage, $days));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int, 3: string}>
     */
    public static function roundingProvider(): array
    {
        // 1.00 × 0.50 % = 0.005 per day: every odd day ends exactly on half a cent.
        return [
            'half a cent rounds up'              => ['1.00', '0.50', 1, '0.01'],
            'a whole cent is exact'              => ['1.00', '0.50', 2, '0.01'],
            'one and a half cents rounds up'     => ['1.00', '0.50', 3, '0.02'],
            'two and a half cents rounds up'     => ['1.00', '0.50', 5, '0.03'],
            'just under half a cent rounds down' => ['1.00', '0.49', 1, '0.00'],
            'a third of a cent rounds down'      => ['1.00', '0.30', 1, '0.00'],
            'six tenths of a cent rounds up'     => ['1.00', '0.30', 2, '0.01'],
            'a real amount rounds up'            => ['333.33', '0.50', 1, '1.67'],
            'a real amount rounds down'          => ['332.60', '0.50', 1, '1.66'],
            'exactly half on a real amount'      => ['333.00', '0.50', 1, '1.67'],
            'five hundredths of a cent over'     => ['100.10', '0.50', 1, '0.50'],
            'three quarters of a cent rounds up'   => ['1.15', '1.00', 5, '0.06'],
        ];
    }

    #[DataProvider('roundingProvider')]
    public function testLateFeeIsRoundedHalfUpToTheCent(string $base, string $percentage, int $days, string $expected): void
    {
        $this->assertSame($expected, LateFeeCalculator::lateFee($base, $percentage, $days));
    }

    public function testTheSameInputsAlwaysGiveTheSameResult(): void
    {
        $first = LateFeeCalculator::forInstallment('1234567.89', '0.35', '2026-08-10', '2026-11-13');

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, LateFeeCalculator::forInstallment('1234567.89', '0.35', '2026-08-10', '2026-11-13'));
        }

        // 95 days: 1234567.89 × 0.35 % × 95 = 410,493.823425
        $this->assertSame('410493.82', $first);
    }

    public function testTheFeeGrowsByTheSameStepEveryDayUntilItIsRounded(): void
    {
        $previous = '0.00';

        for ($day = 1; $day <= 60; $day++) {
            $fee = LateFeeCalculator::lateFee('500000.00', '0.50', $day);

            $this->assertSame(sprintf('%d.00', 2500 * $day), $fee);
            $this->assertGreaterThan((float) $previous, (float) $fee);
            $previous = $fee;
        }
    }

    // ---------------------------------------------------------------------------------------------
    // The technical limit of the column
    // ---------------------------------------------------------------------------------------------

    public function testTheLargestFeeThatFitsTheColumnIsReturnedAsIs(): void
    {
        $this->assertSame('9999999999.99', LateFeeCalculator::lateFee('9999999999.99', '100.00', 1));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function overflowProvider(): array
    {
        return [
            'one day more than the column holds' => ['9999999999.99', '100.00', 2],
            'a long delay'                       => ['9999999999.99', '100.00', 40000],
            'more days than the integer holds'   => ['9999999999.99', '100.00', PHP_INT_MAX],
            'a small amount and a huge delay'    => ['0.01', '100.00', PHP_INT_MAX],
            'a high percentage over many years'  => ['5000000000.00', '50.00', 100000],
        ];
    }

    #[DataProvider('overflowProvider')]
    public function testAFeeThatDoesNotFitTheColumnIsLimitedToItsLargestValue(string $base, string $percentage, int $days): void
    {
        $this->assertSame(LateFeeCalculator::MAX_LATE_FEE, LateFeeCalculator::lateFee($base, $percentage, $days));
    }

    public function testTheSmallestPercentageOnTheLargestAmountDoesNotOverflow(): void
    {
        $this->assertSame('1000000.00', LateFeeCalculator::lateFee('9999999999.99', '0.01', 1));
    }

    // ---------------------------------------------------------------------------------------------
    // From the installment's dates
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function installmentProvider(): array
    {
        return [
            'before the due date'    => ['500000.00', '0.50', '2026-11-10', '2026-11-05', '0.00'],
            'on the due date'        => ['500000.00', '0.50', '2026-11-10', '2026-11-10', '0.00'],
            'the day after'          => ['500000.00', '0.50', '2026-11-10', '2026-11-11', '2500.00'],
            'three days after'       => ['500000.00', '0.50', '2026-11-10', '2026-11-13', '7500.00'],
            'across a month'         => ['500000.00', '0.50', '2026-10-28', '2026-11-02', '12500.00'],
        ];
    }

    #[DataProvider('installmentProvider')]
    public function testTheFeeOfAnInstallmentUsesTheDaysBetweenItsDueDateAndTheDay(
        string $base,
        string $percentage,
        string $dueDate,
        string $today,
        string $expected
    ): void {
        $this->assertSame($expected, LateFeeCalculator::forInstallment($base, $percentage, $dueDate, $today));
    }

    // ---------------------------------------------------------------------------------------------
    // Invalid input
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'negative amount'              => ['-1.00', '0.50', 1],
            'empty amount'                 => ['', '0.50', 1],
            'text amount'                  => ['mucho', '0.50', 1],
            'amount with a comma'          => ['1000,50', '0.50', 1],
            'amount with three decimals'   => ['1000.505', '0.50', 1],
            'amount with a thousands dot'  => ['1.000.000', '0.50', 1],
            'amount with a sign'           => ['+100.00', '0.50', 1],
            'amount with spaces'           => [' 100.00', '0.50', 1],
            'amount with a trailing newline' => ["100.00\n", '0.50', 1],
            'percentage with a trailing newline' => ['100.00', "0.50\n", 1],
            'amount with eleven digits'    => ['10000000000.00', '0.50', 1],
            'amount in exponent notation'  => ['1e3', '0.50', 1],
            'negative percentage'          => ['100.00', '-0.50', 1],
            'empty percentage'             => ['100.00', '', 1],
            'text percentage'              => ['100.00', 'medio', 1],
            'percentage with a comma'      => ['100.00', '0,5', 1],
            'percentage over one hundred'  => ['100.00', '100.01', 1],
            'percentage far over'          => ['100.00', '1000', 1],
            'percentage with three decimals' => ['100.00', '0.505', 1],
            'negative days'                => ['100.00', '0.50', -1],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testInvalidAmountsPercentagesAndDaysAreRejected(string $base, string $percentage, int $days): void
    {
        $this->expectException(InvalidArgumentException::class);

        LateFeeCalculator::lateFee($base, $percentage, $days);
    }
}
