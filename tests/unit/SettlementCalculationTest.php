<?php

use App\Models\SettlementModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the commission and net calculation of the owner settlements (HU-19 business rules), including the
 * limit values of the percentage and the rounding to the cent.
 *
 * @internal
 */
final class SettlementCalculationTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function calculationProvider(): array
    {
        return [
            'whole percentage'                  => ['500000.00', '8.00', '40000.00', '460000.00'],
            'percentage with decimals'          => ['780000.00', '8.50', '66300.00', '713700.00'],
            '0 % keeps the whole gross'         => ['420000.00', '0.00', '0.00', '420000.00'],
            '100 % keeps nothing for the owner' => ['420000.00', '100.00', '420000.00', '0.00'],
            'rounds down below half a cent'     => ['333.33', '8.50', '28.33', '305.00'],
            'rounds half a cent up'             => ['10.10', '5.00', '0.51', '9.59'],
            'one cent at 50 %'                  => ['0.01', '50.00', '0.01', '0.00'],
            'one cent at 49.99 %'               => ['0.01', '49.99', '0.00', '0.01'],
            'amount that is not the rent'       => ['512345.67', '8.50', '43549.38', '468796.29'],
            'largest amount of the column'      => ['9999999999.99', '12.34', '1234000000.00', '8765999999.99'],
            'amounts without decimals'          => ['1000', '10', '100.00', '900.00'],
        ];
    }

    #[DataProvider('calculationProvider')]
    public function testCommissionAndNetAddUpToTheGross(string $gross, string $percentage, string $commission, string $net): void
    {
        $result = SettlementModel::calculate($gross, $percentage);

        $this->assertSame($commission, $result['commission']);
        $this->assertSame($net, $result['net']);
        $this->assertSame(
            SettlementModel::toCents($result['gross']),
            SettlementModel::toCents($result['commission']) + SettlementModel::toCents($result['net'])
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'percentage over 100' => ['1000.00', '100.01'],
            'negative percentage' => ['1000.00', '-1.00'],
            'negative gross'      => ['-1000.00', '8.00'],
            'three decimals'      => ['1000.001', '8.00'],
            'not a number'        => ['mil', '8.00'],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function testRejectsAmountsAndPercentagesOutOfRange(string $gross, string $percentage): void
    {
        $this->expectException(RuntimeException::class);

        SettlementModel::calculate($gross, $percentage);
    }

    public function testCentsConversionIsExact(): void
    {
        $this->assertSame(50, SettlementModel::toCents('0.5'));
        $this->assertSame(1999, SettlementModel::toCents('19.99'));
        $this->assertSame(-1999, SettlementModel::toCents('-19.99'));
        $this->assertSame('500000.50', SettlementModel::fromCents(50000050));
        $this->assertSame('-0.05', SettlementModel::fromCents(-5));
        $this->assertSame('0.00', SettlementModel::fromCents(0));
    }
}
