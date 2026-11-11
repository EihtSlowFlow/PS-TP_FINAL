<?php

use App\Models\ContractModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests of the contract duration text shown in the contract file (HU-10).
 *
 * @internal
 */
final class ContractDurationTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function durationProvider(): array
    {
        return [
            'two full years'                => ['2026-12-01', '2028-11-30', '24 meses'],
            'one month'                     => ['2026-01-01', '2026-01-31', '1 mes'],
            'months and days'               => ['2026-01-01', '2026-02-14', '1 mes y 14 días'],
            'less than a month'             => ['2026-01-01', '2026-01-10', '10 días'],
            'a single day'                  => ['2026-01-01', '2026-01-01', '1 día'],
            'one month and one day'         => ['2026-01-01', '2026-02-01', '1 mes y 1 día'],
            'a leap year'                   => ['2028-01-01', '2028-12-31', '12 meses'],
            'end before start'              => ['2026-12-01', '2026-11-30', '—'],
            'not a date'                    => ['mañana', '2026-11-30', '—'],
        ];
    }

    #[DataProvider('durationProvider')]
    public function testDescribeDuration(string $start, string $end, string $expected): void
    {
        $this->assertSame($expected, ContractModel::describeDuration($start, $end));
    }

    public function testStatusLabelsAreInSpanish(): void
    {
        $this->assertSame('Vigente', ContractModel::labelForStatus(ContractModel::STATUS_IN_FORCE));
        $this->assertSame('Finalizado', ContractModel::labelForStatus(ContractModel::STATUS_FINISHED));
        $this->assertSame('Rescindido', ContractModel::labelForStatus(ContractModel::STATUS_RESCINDED));
        $this->assertSame('otro', ContractModel::labelForStatus('otro'));
    }
}
