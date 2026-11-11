<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Persistence for the monthly rent installments and their issuing (HU-13).
 *
 * An installment belongs to a contract and to a "period", which is always the first day of a month. The database
 * allows one installment per contract and period and one number per contract; this model also makes sure the numbers
 * are sequential for each contract.
 */
class InstallmentModel extends Model
{
    public const STATUS_PENDING      = 'pending';
    public const STATUS_OVERDUE      = 'overdue';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PAID         = 'paid';

    /**
     * Spanish labels for each installment status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING      => 'Pendiente',
        self::STATUS_OVERDUE      => 'Vencida',
        self::STATUS_UNDER_REVIEW => 'En revisión',
        self::STATUS_PAID         => 'Pagada',
    ];

    /**
     * Spanish month names, indexed by month number.
     *
     * @var array<int, string>
     */
    public const MONTH_NAMES = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /** Earliest and latest year accepted for a period; a sanity limit, the contracts' terms narrow it much further. */
    public const MIN_YEAR = 2000;
    public const MAX_YEAR = 2100;

    /** No contract in force covers the period (or, for an individual issuing, the chosen contract does not). */
    public const ERROR_NO_ELIGIBLE_CONTRACTS = 'no_eligible_contracts';

    /** The installments could not be stored; nothing was persisted. */
    public const ERROR_PERSISTENCE = 'persistence';

    protected $table            = 'installments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'contract_id',
        'number',
        'period',
        'due_date',
        'base_amount',
        'late_fee',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label of an installment status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Builds the period ("YYYY-MM-01") from a year and a month typed by the user, or returns null when either is not
     * a whole number, the month is not between 1 and 12 or the year is outside the accepted range (HU-13 Scenario 4).
     */
    public static function buildPeriod(mixed $year, mixed $month): ?string
    {
        if (! is_scalar($year) || ! is_scalar($month)) {
            return null;
        }

        $year  = trim((string) $year);
        $month = trim((string) $month);

        if (preg_match('/^[0-9]{4}$/', $year) !== 1 || preg_match('/^(0?[1-9]|1[0-2])$/', $month) !== 1) {
            return null;
        }

        if ((int) $year < self::MIN_YEAR || (int) $year > self::MAX_YEAR) {
            return null;
        }

        return sprintf('%04d-%02d-01', (int) $year, (int) $month);
    }

    /**
     * Describes a period for people, e.g. "Diciembre 2026" for 2026-12-01.
     */
    public static function labelForPeriod(string $period): string
    {
        $month = (int) substr($period, 5, 2);

        return (self::MONTH_NAMES[$month] ?? $period) . ' ' . substr($period, 0, 4);
    }

    /**
     * The due date of the installment of a period: the contract's due day (1 to 28, so it exists in every month)
     * inside the month of the period.
     */
    public static function dueDateFor(string $period, int $dueDay): string
    {
        return sprintf('%s-%02d', substr($period, 0, 7), $dueDay);
    }

    /**
     * Adds up decimal amounts exactly (in cents) and returns the total with two decimals, e.g. "2240000.00".
     *
     * @param iterable<int|float|string> $amounts
     */
    public static function sumAmounts(iterable $amounts): string
    {
        $cents = 0;

        foreach ($amounts as $amount) {
            $cents += (int) round(((float) $amount) * 100);
        }

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Issues the installment of a period for every contract in force that covers it, or only for one contract, in ONE
     * transaction (HU-13 Scenarios 1, 2, 3 and 5).
     *
     * The contract rows are locked first (always in id order), so two simultaneous issuings of the same contract
     * cannot both create an installment or take the same number: the second one waits and then finds the first one's
     * installment. A contract that already has an installment for the period is skipped, untouched. Every new
     * installment is "pending", has no late fee, takes the contract's monthly rent as its amount, is due on the
     * contract's due day and gets the next number of its contract. If anything fails nothing is stored.
     *
     * @param string   $period     First day of the month, "YYYY-MM-01" (see {@see buildPeriod()})
     * @param int|null $contractId Limits the issuing to this contract; null issues for all the contracts that cover it
     *
     * @return array{error: string|null, issued_ids: list<int>, issued_count: int, issued_total: string, skipped_count: int}
     */
    public function issueForPeriod(string $period, ?int $contractId = null): array
    {
        $this->db->transBegin();

        try {
            $contracts = model(ContractModel::class)->lockInForceCoveringPeriod($period, $contractId);

            if ($contracts === []) {
                $this->db->transRollback();

                return $this->issueResult(self::ERROR_NO_ELIGIBLE_CONTRACTS);
            }

            $contractIds   = array_map(static fn (array $contract): int => (int) $contract['id'], $contracts);
            $alreadyIssued = $this->contractIdsWithInstallmentIn($period, $contractIds);
            $lastNumbers   = $this->lastNumberByContract($contractIds);

            $issuedIds = [];
            $amounts   = [];
            $skipped   = 0;

            foreach ($contracts as $contract) {
                $id = (int) $contract['id'];

                if (isset($alreadyIssued[$id])) {
                    $skipped++;

                    continue;
                }

                $installmentId = $this->insert([
                    'contract_id' => $id,
                    'number'      => ($lastNumbers[$id] ?? 0) + 1,
                    'period'      => $period,
                    'due_date'    => self::dueDateFor($period, (int) $contract['due_day']),
                    'base_amount' => $contract['monthly_rent'],
                    'late_fee'    => '0.00',
                    'status'      => self::STATUS_PENDING,
                ]);

                if ($installmentId === false || (int) $installmentId <= 0) {
                    throw new RuntimeException("The installment of contract {$id} could not be inserted.");
                }

                $issuedIds[] = (int) $installmentId;
                $amounts[]   = $contract['monthly_rent'];
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Installment issuing failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return $this->issueResult(self::ERROR_PERSISTENCE);
        }

        return [
            'error'         => null,
            'issued_ids'    => $issuedIds,
            'issued_count'  => count($issuedIds),
            'issued_total'  => self::sumAmounts($amounts),
            'skipped_count' => $skipped,
        ];
    }

    /**
     * Finds installments by id with the contract, property and tenant data the issuing report shows. Deactivated
     * tenants are included on purpose: the history must stay consultable.
     *
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>>
     */
    public function findWithDetails(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->builder()
            ->select(
                'installments.*, '
                . 'properties.address AS property_address, properties.city AS property_city, '
                . 'properties.type AS property_type, properties.rooms AS property_rooms, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.document_number AS tenant_document_number'
            )
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('properties', 'properties.id = contracts.property_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->whereIn('installments.id', $ids)
            ->orderBy('installments.contract_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * The ids of the given contracts that already have an installment for the period, as a set (id => true).
     *
     * @param list<int> $contractIds
     *
     * @return array<int, true>
     */
    private function contractIdsWithInstallmentIn(string $period, array $contractIds): array
    {
        $rows = $this->builder()
            ->select('contract_id')
            ->where('period', $period)
            ->whereIn('contract_id', $contractIds)
            ->get()
            ->getResultArray();

        $set = [];

        foreach ($rows as $row) {
            $set[(int) $row['contract_id']] = true;
        }

        return $set;
    }

    /**
     * The highest installment number already used by each of the given contracts (contracts without installments
     * are left out).
     *
     * @param list<int> $contractIds
     *
     * @return array<int, int>
     */
    private function lastNumberByContract(array $contractIds): array
    {
        $rows = $this->builder()
            ->select('contract_id, MAX(number) AS last_number')
            ->whereIn('contract_id', $contractIds)
            ->groupBy('contract_id')
            ->get()
            ->getResultArray();

        $numbers = [];

        foreach ($rows as $row) {
            $numbers[(int) $row['contract_id']] = (int) $row['last_number'];
        }

        return $numbers;
    }

    /**
     * An issuing result that created nothing.
     *
     * @return array{error: string|null, issued_ids: list<int>, issued_count: int, issued_total: string, skipped_count: int}
     */
    private function issueResult(?string $error): array
    {
        return [
            'error'         => $error,
            'issued_ids'    => [],
            'issued_count'  => 0,
            'issued_total'  => '0.00',
            'skipped_count' => 0,
        ];
    }
}
