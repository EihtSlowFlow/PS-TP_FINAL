<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Settlements paid to the owners (HU-19): what the agency collected for a contract and period, minus the commission
 * agreed in the contract.
 *
 * There is one settlement per contract and period (analysis, section 9.4); the installments it covers are recorded in
 * `settlement_installments`, whose UNIQUE key on the installment guarantees each one is settled only once. Since a
 * contract has at most one installment per period, in practice each settlement covers exactly one installment.
 *
 * Amounts are handled in whole cents, never in floating point:
 * - gross      = sum of the approved payments of the installment (what was actually collected);
 * - commission = gross × percentage ÷ 100, rounded half up to the cent;
 * - net        = gross − commission, so the three always add up.
 *
 * The percentage is the one in the contract when the settlement is generated, and it is stored on the settlement, so
 * changing the contract later does not alter past settlements.
 */
class SettlementModel extends Model
{
    public const STATUS_PENDING_TRANSFER = 'pending_transfer';
    public const STATUS_TRANSFERRED      = 'transferred';

    /**
     * Spanish labels for each settlement status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING_TRANSFER => 'Pendiente de transferencia',
        self::STATUS_TRANSFERRED      => 'Transferida',
    ];

    /** No installment was chosen. */
    public const ERROR_NOTHING_SELECTED = 'nothing_selected';

    /** One of the chosen installments does not exist. */
    public const ERROR_NOT_FOUND = 'not_found';

    /** One of the chosen installments is not paid or has no approved payment (HU-19 Scenario 2). */
    public const ERROR_NOT_COLLECTED = 'not_collected';

    /** One of the chosen installments was already settled (HU-19 Scenario 3). */
    public const ERROR_ALREADY_SETTLED = 'already_settled';

    /** The settlements could not be stored; nothing was persisted. */
    public const ERROR_PERSISTENCE = 'persistence';

    /** MySQL error number of a duplicate key: another operator settled the same installment at the same time. */
    private const MYSQL_DUPLICATE_KEY = 1062;

    protected $table            = 'settlements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'owner_id',
        'contract_id',
        'period',
        'gross_amount',
        'commission_percentage',
        'commission_amount',
        'net_amount',
        'status',
        'transferred_at',
        'operation_number',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label of a settlement status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Calculates the commission and the net amount of a gross amount (HU-19 business rules).
     *
     * The commission is gross × percentage ÷ 100 rounded half up to the cent, and the net is gross − commission, so
     * the net plus the commission is always exactly the gross. All the arithmetic is done in integers.
     *
     * @param int|string $gross      Collected amount, e.g. "500000.00"
     * @param int|string $percentage Commission percentage between 0 and 100 with up to two decimals, e.g. "8.50"
     *
     * @return array{gross: string, commission: string, net: string}
     */
    public static function calculate(int|string $gross, int|string $percentage): array
    {
        $grossCents = self::toCents($gross);
        // The percentage, like the amounts, has two decimals: 8.5 % is 850 hundredths of a percent.
        $basisPoints = self::toCents($percentage);

        if ($grossCents < 0 || $basisPoints < 0 || $basisPoints > 10000) {
            throw new RuntimeException('The gross amount must not be negative and the percentage must be between 0 and 100.');
        }

        // gross (cents) × percentage (hundredths) ÷ 10 000 = commission in cents; adding half the divisor rounds half up.
        $commissionCents = intdiv($grossCents * $basisPoints + 5000, 10000);

        return [
            'gross'      => self::fromCents($grossCents),
            'commission' => self::fromCents($commissionCents),
            'net'        => self::fromCents($grossCents - $commissionCents),
        ];
    }

    /**
     * Lists the installments ready to be settled: "paid", with at least one approved payment and not included in any
     * settlement yet (HU-19 Scenario 1). Each row carries the collected amount, the contract's current commission
     * percentage and the resulting commission and net, plus the owner, tenant and property data the screen shows.
     *
     * Deactivated owners and tenants are included on purpose: what was collected for them is still owed.
     *
     * @return list<array<string, mixed>>
     */
    public function findSettleableInstallments(): array
    {
        $db = $this->db;

        $rows = $db->query(
            'SELECT i.id AS installment_id, i.number, i.period, i.due_date, i.base_amount, i.late_fee, '
            . 'c.id AS contract_id, c.commission_percentage, c.owner_id, '
            . 'owner.first_name AS owner_first_name, owner.last_name AS owner_last_name, '
            . 'owner.document_number AS owner_document_number, owner.deleted_at AS owner_deleted_at, '
            . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
            . 'p.address AS property_address, p.city AS property_city, p.type AS property_type, p.rooms AS property_rooms, '
            . 'ap.collected_amount, ap.last_paid_on '
            . 'FROM ' . $db->prefixTable('installments') . ' i '
            . 'INNER JOIN ' . $db->prefixTable('contracts') . ' c ON c.id = i.contract_id '
            . 'INNER JOIN ' . $db->prefixTable('properties') . ' p ON p.id = c.property_id '
            . 'INNER JOIN ' . $db->prefixTable('users') . ' owner ON owner.id = c.owner_id '
            . 'INNER JOIN ' . $db->prefixTable('users') . ' tenant ON tenant.id = c.tenant_id '
            // What was collected for each installment: the sum of its approved payments.
            . 'INNER JOIN (SELECT installment_id, SUM(amount) AS collected_amount, MAX(paid_on) AS last_paid_on '
            . '    FROM ' . $db->prefixTable('payments') . ' WHERE status = ? GROUP BY installment_id) ap '
            . '    ON ap.installment_id = i.id '
            . 'LEFT JOIN ' . $db->prefixTable('settlement_installments') . ' si ON si.installment_id = i.id '
            . 'WHERE i.status = ? AND si.id IS NULL '
            . 'ORDER BY owner.last_name, owner.first_name, c.owner_id, i.period, c.id',
            [PaymentModel::STATUS_APPROVED, InstallmentModel::STATUS_PAID]
        )->getResultArray();

        foreach ($rows as &$row) {
            $amounts                  = self::calculate((string) $row['collected_amount'], (string) $row['commission_percentage']);
            $row['gross_amount']      = $amounts['gross'];
            $row['commission_amount'] = $amounts['commission'];
            $row['net_amount']        = $amounts['net'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Generates the settlements of the chosen installments in ONE transaction (HU-19 Scenarios 1 to 3): either all of
     * them are created or none is.
     *
     * The installments and their contracts are locked first (in id order), and every check is made on the locked rows:
     * each installment must exist, be "paid", have an approved payment and not be settled yet. Then one settlement per
     * contract and period is created as "pending transfer", with the contract's current commission percentage, and its
     * installments are linked to it. If a simultaneous operation settled one of them first, the UNIQUE keys reject the
     * duplicate and nothing is stored.
     *
     * @param list<int> $installmentIds
     *
     * @return array{
     *     error: string|null,
     *     installment: array<string, mixed>|null,
     *     settlement_ids: list<int>,
     *     count: int,
     *     gross_total: string,
     *     commission_total: string,
     *     net_total: string
     * }
     */
    public function generate(array $installmentIds): array
    {
        $installmentIds = array_values(array_unique(array_map('intval', $installmentIds)));
        sort($installmentIds);

        if ($installmentIds === []) {
            return $this->generateResult(self::ERROR_NOTHING_SELECTED);
        }

        $db           = $this->db;
        $now          = date('Y-m-d H:i:s');
        $placeholders = implode(', ', array_fill(0, count($installmentIds), '?'));

        $db->transBegin();

        try {
            $installments = $db->query(
                'SELECT i.id, i.number, i.period, i.status, i.contract_id, c.owner_id, c.commission_percentage '
                . 'FROM ' . $db->prefixTable('installments') . ' i '
                . 'INNER JOIN ' . $db->prefixTable('contracts') . ' c ON c.id = i.contract_id '
                . 'WHERE i.id IN (' . $placeholders . ') '
                . 'ORDER BY i.id '
                . 'FOR UPDATE',
                $installmentIds
            )->getResultArray();

            if (count($installments) !== count($installmentIds)) {
                return $this->rollBackWith(self::ERROR_NOT_FOUND);
            }

            $collected = [];

            foreach ($db->query(
                'SELECT installment_id, SUM(amount) AS collected_amount '
                . 'FROM ' . $db->prefixTable('payments') . ' '
                . 'WHERE status = ? AND installment_id IN (' . $placeholders . ') '
                . 'GROUP BY installment_id',
                [PaymentModel::STATUS_APPROVED, ...$installmentIds]
            )->getResultArray() as $row) {
                $collected[(int) $row['installment_id']] = (string) $row['collected_amount'];
            }

            $settled = [];

            foreach ($db->query(
                'SELECT installment_id FROM ' . $db->prefixTable('settlement_installments') . ' '
                . 'WHERE installment_id IN (' . $placeholders . ')',
                $installmentIds
            )->getResultArray() as $row) {
                $settled[(int) $row['installment_id']] = true;
            }

            // Scenario 3 is checked before Scenario 2 so a settled installment is reported as such, whatever its state.
            foreach ($installments as $installment) {
                if (isset($settled[(int) $installment['id']])) {
                    return $this->rollBackWith(self::ERROR_ALREADY_SETTLED, $installment);
                }
            }

            foreach ($installments as $installment) {
                if ((string) $installment['status'] !== InstallmentModel::STATUS_PAID || ! isset($collected[(int) $installment['id']])) {
                    return $this->rollBackWith(self::ERROR_NOT_COLLECTED, $installment);
                }
            }

            // One settlement per contract and period (a contract has one installment per period, but the grouping
            // keeps the rule explicit).
            $groups = [];

            foreach ($installments as $installment) {
                $key = $installment['contract_id'] . '|' . $installment['period'];

                $groups[$key] ??= [
                    'owner_id'              => (int) $installment['owner_id'],
                    'contract_id'           => (int) $installment['contract_id'],
                    'period'                => (string) $installment['period'],
                    'commission_percentage' => (string) $installment['commission_percentage'],
                    'installment_ids'       => [],
                    'collected'             => [],
                ];
                $groups[$key]['installment_ids'][] = (int) $installment['id'];
                $groups[$key]['collected'][]       = $collected[(int) $installment['id']];
            }

            $settlementIds = [];
            $totals        = ['gross' => 0, 'commission' => 0, 'net' => 0];

            foreach ($groups as $group) {
                $grossCents = 0;

                foreach ($group['collected'] as $amount) {
                    $grossCents += self::toCents($amount);
                }

                $amounts = self::calculate(self::fromCents($grossCents), $group['commission_percentage']);

                $inserted = $db->table('settlements')->insert([
                    'owner_id'              => $group['owner_id'],
                    'contract_id'           => $group['contract_id'],
                    'period'                => $group['period'],
                    'gross_amount'          => $amounts['gross'],
                    'commission_percentage' => $group['commission_percentage'],
                    'commission_amount'     => $amounts['commission'],
                    'net_amount'            => $amounts['net'],
                    'status'                => self::STATUS_PENDING_TRANSFER,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);

                if ($inserted === false) {
                    throw new RuntimeException('Could not store the settlement.');
                }

                $settlementId = (int) $db->insertID();

                foreach ($group['installment_ids'] as $installmentId) {
                    if ($db->table('settlement_installments')->insert([
                        'settlement_id'  => $settlementId,
                        'installment_id' => $installmentId,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]) === false) {
                        throw new RuntimeException('Could not link the installment to its settlement.');
                    }
                }

                $settlementIds[]       = $settlementId;
                $totals['gross']      += self::toCents($amounts['gross']);
                $totals['commission'] += self::toCents($amounts['commission']);
                $totals['net']        += self::toCents($amounts['net']);
            }

            $db->transCommit();

            return [
                'error'            => null,
                'installment'      => null,
                'settlement_ids'   => $settlementIds,
                'count'            => count($settlementIds),
                'gross_total'      => self::fromCents($totals['gross']),
                'commission_total' => self::fromCents($totals['commission']),
                'net_total'        => self::fromCents($totals['net']),
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            $db->resetTransStatus();

            if ($this->isDuplicateKey($e)) {
                return $this->generateResult(self::ERROR_ALREADY_SETTLED);
            }

            log_message('error', 'Failed to generate settlements: {message}', ['message' => $e->getMessage()]);

            return $this->generateResult(self::ERROR_PERSISTENCE);
        }
    }

    /**
     * Finds settlements by id with the owner, contract and property data the screen shows.
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
                'settlements.*, '
                . 'owner.first_name AS owner_first_name, owner.last_name AS owner_last_name, '
                . 'owner.document_number AS owner_document_number, '
                . 'properties.address AS property_address, properties.city AS property_city'
            )
            ->join('users AS owner', 'owner.id = settlements.owner_id')
            ->join('contracts', 'contracts.id = settlements.contract_id')
            ->join('properties', 'properties.id = contracts.property_id')
            ->whereIn('settlements.id', array_map('intval', $ids))
            ->orderBy('owner.last_name', 'ASC')
            ->orderBy('owner.first_name', 'ASC')
            ->orderBy('settlements.period', 'ASC')
            ->orderBy('settlements.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Converts a decimal amount with up to two decimals ("500000", "8.5", "12.34") to whole cents without going
     * through floating point.
     */
    public static function toCents(int|string $amount): int
    {
        $amount = trim((string) $amount);

        if (preg_match('/^(-?)([0-9]+)(?:\.([0-9]{1,2}))?$/', $amount, $parts) !== 1) {
            throw new RuntimeException('Invalid amount: ' . $amount);
        }

        $cents = (int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0');

        return $parts[1] === '-' ? -$cents : $cents;
    }

    /**
     * Formats whole cents as a decimal amount with two decimals, e.g. 50000050 → "500000.50".
     */
    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs  = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }

    /**
     * Rolls the open transaction back and reports the error, with the installment that caused it.
     *
     * @param array<string, mixed>|null $installment
     *
     * @return array{error: string, installment: array<string, mixed>|null, settlement_ids: list<int>, count: int, gross_total: string, commission_total: string, net_total: string}
     */
    private function rollBackWith(string $error, ?array $installment = null): array
    {
        $this->db->transRollback();
        $this->db->resetTransStatus();

        return $this->generateResult($error, $installment);
    }

    /**
     * @param array<string, mixed>|null $installment
     *
     * @return array{error: string, installment: array<string, mixed>|null, settlement_ids: list<int>, count: int, gross_total: string, commission_total: string, net_total: string}
     */
    private function generateResult(string $error, ?array $installment = null): array
    {
        return [
            'error'            => $error,
            'installment'      => $installment,
            'settlement_ids'   => [],
            'count'            => 0,
            'gross_total'      => '0.00',
            'commission_total' => '0.00',
            'net_total'        => '0.00',
        ];
    }

    private function isDuplicateKey(Throwable $e): bool
    {
        return (int) $e->getCode() === self::MYSQL_DUPLICATE_KEY
            || str_contains($e->getMessage(), 'Duplicate entry');
    }
}
