<?php

use App\Libraries\RentUpdateCalculator;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the yearly update of the rent in the installments (HU-13): when it applies, rounding, limits and
 * invalid input.
 *
 * @internal
 */
final class RentUpdateCalculatorTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: int}> start date, period, updates applied
     */
    public static function updatesProvider(): array
    {
        return [
            'the first month'                        => ['2026-01-01', '2026-01-01', 0],
            'the eleventh month after the start'     => ['2026-01-01', '2026-12-01', 0],
            'twelve months after the start'          => ['2026-01-01', '2027-01-01', 1],
            'twenty three months after the start'    => ['2026-01-01', '2027-12-01', 1],
            'twenty four months after the start'     => ['2026-01-01', '2028-01-01', 2],
            'three years after the start'            => ['2026-01-01', '2029-01-01', 3],
            'the day of the month of the start does not matter' => ['2026-03-15', '2027-03-01', 1],
            'one month short of the anniversary'     => ['2026-03-15', '2027-02-01', 0],
            'a period before the start'              => ['2026-06-01', '2026-03-01', 0],
        ];
    }

    #[DataProvider('updatesProvider')]
    public function testTheUpdatesAreCountedInWholeYearsFromTheMonthTheContractStarts(string $start, string $period, int $expected): void
    {
        $this->assertSame($expected, RentUpdateCalculator::updatesApplied($start, $period));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}> rent, percentage, start, period, amount
     */
    public static function amountProvider(): array
    {
        return [
            'no update yet'                        => ['250000.00', '10.00', '2026-01-01', '2026-12-01', '250000.00'],
            'the first update'                     => ['250000.00', '10.00', '2026-01-01', '2027-01-01', '275000.00'],
            'it stays the same all the year'       => ['250000.00', '10.00', '2026-01-01', '2027-12-01', '275000.00'],
            'the second update is over the first'  => ['250000.00', '10.00', '2026-01-01', '2028-01-01', '302500.00'],
            'a percentage of zero changes nothing' => ['250000.00', '0.00', '2026-01-01', '2030-01-01', '250000.00'],
            'a percentage with decimals'           => ['100000.00', '8.25', '2026-01-01', '2027-01-01', '108250.00'],
            'the rent keeps its cents'             => ['390000.50', '10.00', '2026-01-01', '2027-01-01', '429000.55'],
            'half a cent rounds up'                => ['0.05', '10.00', '2026-01-01', '2027-01-01', '0.06'],
            'less than half a cent rounds down'    => ['0.04', '10.00', '2026-01-01', '2027-01-01', '0.04'],
            'a rent with one decimal'              => ['1000.5', '10', '2026-01-01', '2027-01-01', '1100.55'],
            'an update of 100% doubles the rent'   => ['1000.00', '100.00', '2026-01-01', '2028-01-01', '4000.00'],
        ];
    }

    #[DataProvider('amountProvider')]
    public function testTheAmountOfThePeriodHasTheUpdatesAppliedYearAfterYear(
        string $rent,
        string $percentage,
        string $start,
        string $period,
        string $expected
    ): void {
        $this->assertSame($expected, RentUpdateCalculator::amountForPeriod($rent, $percentage, $start, $period));
    }

    public function testTheAmountNeverOverflowsTheColumn(): void
    {
        $this->assertSame(
            RentUpdateCalculator::MAX_AMOUNT,
            RentUpdateCalculator::amountForPeriod('9999999999.99', '100.00', '2026-01-01', '2027-01-01')
        );
        $this->assertSame(
            RentUpdateCalculator::MAX_AMOUNT,
            RentUpdateCalculator::amountForPeriod('5000000000.00', '100.00', '2000-01-01', '2100-12-01')
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}> rent, percentage, start, period
     */
    public static function invalidInputProvider(): array
    {
        return [
            'a percentage over 100'      => ['1000.00', '100.01', '2026-01-01', '2027-01-01'],
            'a negative percentage'      => ['1000.00', '-5', '2026-01-01', '2027-01-01'],
            'a percentage with letters'  => ['1000.00', 'diez', '2026-01-01', '2027-01-01'],
            'a negative rent'            => ['-1000.00', '10', '2026-01-01', '2027-01-01'],
            'a rent with three decimals' => ['1000.123', '10', '2026-01-01', '2027-01-01'],
            'an empty rent'              => ['', '10', '2026-01-01', '2027-01-01'],
            'a start date that is text'  => ['1000.00', '10', 'ayer', '2027-01-01'],
            'a period that is text'      => ['1000.00', '10', '2026-01-01', 'enero'],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testInvalidInputIsRejected(string $rent, string $percentage, string $start, string $period): void
    {
        $this->expectException(InvalidArgumentException::class);

        RentUpdateCalculator::amountForPeriod($rent, $percentage, $start, $period);
    }
}
