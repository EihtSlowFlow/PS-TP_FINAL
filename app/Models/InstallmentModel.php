<?php

namespace App\Models;

use App\Libraries\LateFeeCalculator;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Persistence for the monthly rent installments, their issuing (HU-13) and their late fees (HU-14).
 *
 * An installment belongs to a contract and to a "period", which is always the first day of a month. The database
 * allows one installment per contract and period and one number per contract; this model also makes sure the numbers
 * are sequential for each contract.
 *
 * The late fee stored in `installments.late_fee` is a pure function of the base amount, the contract's daily
 * percentage, the due date and the day it is calculated for ({@see LateFeeCalculator}); {@see refreshLateFees()} keeps
 * the stored status and fee in line with a given day and is called whenever installments are consulted.
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
     * Today's date ("YYYY-MM-DD") in the application's time zone: the day late fees are calculated for. A single
     * place, so tests can freeze it with `Time::setTestNow()`.
     */
    public static function today(): string
    {
        return Time::now()->format('Y-m-d');
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
            // A failed query leaves the connection's transaction status "failed" (strict mode), which would make the
            // next transaction on this connection roll back too; the rollback above already discarded everything.
            $this->db->resetTransStatus();
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
     * Brings the status and the late fee of the unpaid installments up to date for a day (HU-14 Scenarios 1 and 3).
     *
     * It only looks at installments that are "pending" or "overdue" and whose due date is BEFORE `$today`:
     * - a "pending" one becomes "overdue" (Scenario 1);
     * - every one of them gets `late_fee = base × daily % ÷ 100 × days late` ({@see LateFeeCalculator}), rounded to
     *   cents, with the percentage of its contract, so the fee grows one day at a time and is the same whenever and
     *   as many times as it is calculated for the same day (idempotent and reproducible).
     * Installments "under review" and "paid" are never touched (Scenarios 2 and 3: the fee stays as it was while a
     * payment is under review, and a paid installment keeps its fee), and neither are those not yet past due.
     *
     * Every row is updated with its own statement that repeats the status condition, so a payment that moves an
     * installment to "under review" or "paid" at the same moment is not overwritten with an old status. Rows whose
     * status and fee are already right are not written, so `updated_at` only moves when something changed.
     *
     * @param string|null $today         Day to calculate for, "YYYY-MM-DD" (null: today, see {@see today()})
     * @param int|null    $installmentId Limits the refresh to this installment (null: all of them)
     *
     * @return array{checked: int, marked_overdue: int, updated: int, failed: int} Installments examined, turned
     *                                                                              overdue, written (including
     *                                                                              those turned overdue) and not
     *                                                                              written because the update failed
     *
     * @throws \InvalidArgumentException when `$today` is not a valid "YYYY-MM-DD" date
     */
    public function refreshLateFees(?string $today = null, ?int $installmentId = null): array
    {
        $today ??= self::today();
        LateFeeCalculator::assertValidDate($today);

        $now = date('Y-m-d H:i:s');

        $builder = $this->builder()
            ->select(
                'installments.id, installments.status, installments.due_date, installments.base_amount, '
                . 'installments.late_fee, contracts.late_fee_daily_percentage'
            )
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->whereIn('installments.status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
            ->where('installments.due_date <', $today);

        if ($installmentId !== null) {
            $builder->where('installments.id', $installmentId);
        }

        $rows   = $builder->orderBy('installments.id', 'ASC')->get()->getResultArray();
        $result = ['checked' => count($rows), 'marked_overdue' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($rows as $row) {
            $lateFee = LateFeeCalculator::forInstallment(
                (string) $row['base_amount'],
                (string) $row['late_fee_daily_percentage'],
                (string) $row['due_date'],
                $today
            );
            $wasPending = (string) $row['status'] === self::STATUS_PENDING;

            if (! $wasPending && self::sumAmounts([$row['late_fee']]) === self::sumAmounts([$lateFee])) {
                continue;
            }

            $updated = $this->builder()
                ->where('id', (int) $row['id'])
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE])
                ->update([
                    'status'     => self::STATUS_OVERDUE,
                    'late_fee'   => $lateFee,
                    'updated_at' => $now,
                ]);

            if ($updated === false) {
                $result['failed']++;
                log_message('error', 'The late fee of installment {id} could not be updated.', ['id' => $row['id']]);

                continue;
            }

            if ($this->db->affectedRows() > 0) {
                $result['updated']++;
                $result['marked_overdue'] += $wasPending ? 1 : 0;
            }
        }

        return $result;
    }

    /**
     * Pages the installments for the collections screen (ADM-10), most recent period first, with the tenant and
     * property data. Deactivated tenants and properties are included on purpose: the debt stays consultable.
     *
     * @param array{q?: string, status?: string, period?: string} $filters `q` searches tenant name or document and
     *                                                                     property address or city; `period` is the
     *                                                                     first day of a month ("YYYY-MM-01")
     *
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     perPage: int,
     *     currentPage: int,
     *     totalPages: int,
     *     from: int,
     *     to: int
     * }
     */
    public function paginateCollections(int $perPage = 10, int $page = 1, array $filters = []): array
    {
        $perPage     = max(1, min(100, $perPage));
        $currentPage = max(1, $page);

        $total       = (int) $this->collectionsBuilder($filters)->countAllResults();
        $totalPages  = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset      = ($currentPage - 1) * $perPage;

        $items = $this->collectionsBuilder($filters)
            ->select(
                'installments.*, contracts.late_fee_daily_percentage, '
                . 'properties.address AS property_address, properties.city AS property_city, '
                . 'properties.type AS property_type, properties.rooms AS property_rooms, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.document_number AS tenant_document_number, tenant.deleted_at AS tenant_deleted_at'
            )
            ->orderBy('installments.period', 'DESC')
            ->orderBy('installments.contract_id', 'ASC')
            ->orderBy('installments.id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'items'       => $items,
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages,
            'from'        => $total > 0 ? $offset + 1 : 0,
            'to'          => $total > 0 ? min($total, $offset + count($items)) : 0,
        ];
    }

    /**
     * Adds up the installments that match the same filters as {@see paginateCollections()} (all pages), by status:
     * how many there are and the sum of their base amounts and of their late fees, as strings with two decimals.
     *
     * @param array{q?: string, status?: string, period?: string} $filters
     *
     * @return array<string, array{count: int, base: string, late_fee: string}> Every status is present
     */
    public function summarizeCollections(array $filters = []): array
    {
        $summary = [];

        foreach (array_keys(self::STATUS_LABELS) as $status) {
            $summary[$status] = ['count' => 0, 'base' => '0.00', 'late_fee' => '0.00'];
        }

        $rows = $this->collectionsBuilder($filters)
            ->select(
                'installments.status, COUNT(*) AS installments_count, '
                . 'SUM(installments.base_amount) AS base_total, SUM(installments.late_fee) AS late_fee_total',
                false
            )
            ->groupBy('installments.status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $summary[(string) $row['status']] = [
                'count'    => (int) $row['installments_count'],
                'base'     => self::sumAmounts([$row['base_total']]),
                'late_fee' => self::sumAmounts([$row['late_fee_total']]),
            ];
        }

        return $summary;
    }

    /**
     * The periods ("YYYY-MM-01") that have installments, most recent first, for the period filter.
     *
     * @return list<string>
     */
    public function periodsWithInstallments(): array
    {
        $rows = $this->builder()
            ->select('period')
            ->distinct()
            ->orderBy('period', 'DESC')
            ->limit(120)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): string => (string) $row['period'], $rows);
    }

    /**
     * One installment with its contract, property and tenant data for the detail screen (deactivated users and
     * properties included on purpose), or null when it does not exist.
     *
     * @return array<string, mixed>|null
     */
    public function findCollectionDetail(int $id): ?array
    {
        $row = $this->builder()
            ->select(
                'installments.*, contracts.late_fee_daily_percentage, contracts.monthly_rent AS contract_monthly_rent, '
                . 'contracts.due_day AS contract_due_day, contracts.status AS contract_status, '
                . 'contracts.start_date AS contract_start_date, contracts.end_date AS contract_end_date, '
                . 'properties.address AS property_address, properties.city AS property_city, '
                . 'properties.type AS property_type, properties.rooms AS property_rooms, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.document_number AS tenant_document_number, tenant.email AS tenant_email, '
                . 'tenant.phone AS tenant_phone, tenant.deleted_at AS tenant_deleted_at'
            )
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('properties', 'properties.id = contracts.property_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->where('installments.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
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
     * The query shared by the collections listing, its count and its totals: installments joined with their
     * contract, property and tenant, filtered by status, period and a free-text search.
     *
     * @param array{q?: string, status?: string, period?: string} $filters
     */
    private function collectionsBuilder(array $filters): BaseBuilder
    {
        $builder = $this->builder()
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->join('properties', 'properties.id = contracts.property_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id');

        if (isset($filters['status']) && array_key_exists($filters['status'], self::STATUS_LABELS)) {
            $builder->where('installments.status', $filters['status']);
        }

        if (isset($filters['period']) && $filters['period'] !== '') {
            $builder->where('installments.period', $filters['period']);
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            // `%` and `_` typed by the user are searched literally, not as LIKE wildcards (the query builder appends
            // the matching ESCAPE clause).
            $escape = $this->db->likeEscapeChar;
            $term   = str_replace([$escape, '%', '_'], [$escape . $escape, $escape . '%', $escape . '_'], $filters['q']);

            $builder->groupStart()
                ->like('tenant.first_name', $term, 'both', true)
                ->orLike('tenant.last_name', $term, 'both', true)
                ->orLike('tenant.document_number', $term, 'both', true)
                ->orLike('properties.address', $term, 'both', true)
                ->orLike('properties.city', $term, 'both', true)
                ->groupEnd();
        }

        return $builder;
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
