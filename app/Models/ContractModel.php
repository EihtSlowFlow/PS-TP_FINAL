<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Persistence and lookup queries for the lease contracts (HU-10).
 *
 * A contract keeps its property, tenant and owner even after any of them is deactivated (HU-04, HU-05, HU-09):
 * every lookup here joins the users and the property without filtering by `deleted_at`, so the history stays
 * consultable.
 */
class ContractModel extends Model
{
    public const STATUS_IN_FORCE  = 'in_force';
    public const STATUS_FINISHED  = 'finished';
    public const STATUS_RESCINDED = 'rescinded';

    /**
     * Spanish labels for each contract status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_IN_FORCE  => 'Vigente',
        self::STATUS_FINISHED  => 'Finalizado',
        self::STATUS_RESCINDED => 'Rescindido',
    ];

    /** The property cannot be linked to a new contract (rented, in maintenance, deactivated or missing). */
    public const ERROR_PROPERTY_UNAVAILABLE = 'property_unavailable';

    /** The contract or the property update could not be stored; nothing was persisted. */
    public const ERROR_PERSISTENCE = 'persistence';

    protected $table            = 'contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'property_id',
        'tenant_id',
        'owner_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'update_percentage',
        'due_day',
        'commission_percentage',
        'late_fee_daily_percentage',
        'status',
        'closed_at',
        'closing_reason',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label of a contract status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Describes the length of a contract in whole months plus the remaining days, counting the end date as part of
     * the term (a contract from 2026-12-01 to 2028-11-30 lasts "24 meses").
     */
    public static function describeDuration(string $startDate, string $endDate): string
    {
        try {
            $start = new DateTimeImmutable($startDate);
            $end   = (new DateTimeImmutable($endDate))->modify('+1 day');
        } catch (Throwable) {
            return '—';
        }

        if ($end <= $start) {
            return '—';
        }

        $interval = $start->diff($end);
        $months   = $interval->y * 12 + $interval->m;
        $days     = $interval->d;

        $monthsText = $months === 1 ? '1 mes' : $months . ' meses';
        $daysText   = $days === 1 ? '1 día' : $days . ' días';

        if ($months === 0) {
            return $daysText;
        }

        return $days === 0 ? $monthsText : "{$monthsText} y {$daysText}";
    }

    /**
     * Registers a contract in force and sets its property to "rented" in ONE transaction (HU-10 Scenarios 1, 2, 4).
     *
     * The property row is locked first, so two simultaneous requests cannot link the same property twice: the second
     * one waits, then finds it already rented. The contract's owner is taken from the locked property row, never from
     * the caller. If anything fails, neither the contract nor the property change is stored.
     *
     * @param array<string, mixed> $data Contract columns: property_id, tenant_id, start_date, end_date, monthly_rent,
     *                                   update_percentage, due_day, commission_percentage, late_fee_daily_percentage
     *
     * @return array{id: int|null, error: string|null}
     */
    public function createAndRentProperty(array $data): array
    {
        $propertyId = (int) ($data['property_id'] ?? 0);

        $this->db->transBegin();

        try {
            $property = $this->lockProperty($propertyId);

            if ($property === null
                || (string) $property['status'] !== PropertyModel::STATUS_AVAILABLE
                || $this->hasContractInForce($propertyId)
            ) {
                $this->db->transRollback();

                return ['id' => null, 'error' => self::ERROR_PROPERTY_UNAVAILABLE];
            }

            $data['owner_id'] = (int) $property['owner_id'];
            $data['status']   = self::STATUS_IN_FORCE;

            $contractId = $this->insert($data);

            if ($contractId === false || (int) $contractId <= 0) {
                throw new RuntimeException('The contract could not be inserted.');
            }

            if (model(PropertyModel::class)->update($propertyId, ['status' => PropertyModel::STATUS_RENTED]) === false) {
                throw new RuntimeException('The property status could not be updated.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            // A failed query leaves the connection's transaction status "failed" (strict mode), which would make the
            // next transaction on this connection roll back too; the rollback above already discarded everything.
            $this->db->resetTransStatus();
            log_message('error', 'Contract creation failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['id' => null, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['id' => (int) $contractId, 'error' => null];
    }

    /**
     * Finds a contract by id with its property, tenant and owner data (HU-10 file screen). Deactivated users and
     * properties are included on purpose: the history of a contract must stay consultable.
     *
     * @return array<string, mixed>|null
     */
    public function findWithDetails(int $id): ?array
    {
        $row = $this->builder()
            ->select(
                'contracts.*, '
                . 'properties.address AS property_address, properties.city AS property_city, '
                . 'properties.type AS property_type, properties.rooms AS property_rooms, '
                . 'properties.status AS property_status, properties.deleted_at AS property_deleted_at, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.document_number AS tenant_document_number, tenant.email AS tenant_email, '
                . 'tenant.phone AS tenant_phone, tenant.deleted_at AS tenant_deleted_at, '
                . 'owner.first_name AS owner_first_name, owner.last_name AS owner_last_name, '
                . 'owner.document_number AS owner_document_number, owner.email AS owner_email, '
                . 'owner.phone AS owner_phone, owner.deleted_at AS owner_deleted_at'
            )
            ->join('properties', 'properties.id = contracts.property_id')
            ->join('users AS tenant', 'tenant.id = contracts.tenant_id')
            ->join('users AS owner', 'owner.id = contracts.owner_id')
            ->where('contracts.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Lists the contracts in force that cover a period, with their property and tenant data and the id of the
     * installment already issued for that period, if any (HU-13 preview). Deactivated tenants and properties are
     * included on purpose: a contract in force keeps being billed whatever happened to its parties.
     *
     * A contract covers a period when the due date of that month falls inside its term (see
     * {@see coverageCondition()}).
     *
     * @param string $period First day of the month, "YYYY-MM-01"
     *
     * @return list<array<string, mixed>>
     */
    public function findInForceCoveringPeriod(string $period): array
    {
        $contracts    = $this->db->protectIdentifiers('contracts', true);
        $properties   = $this->db->protectIdentifiers('properties', true);
        $users        = $this->db->protectIdentifiers('users', true);
        $installments = $this->db->protectIdentifiers('installments', true);

        $sql = 'SELECT c.id, c.start_date, c.end_date, c.monthly_rent, c.due_day, '
            . 'p.address AS property_address, p.city AS property_city, p.type AS property_type, '
            . 'p.rooms AS property_rooms, '
            . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
            . 'tenant.document_number AS tenant_document_number, tenant.deleted_at AS tenant_deleted_at, '
            . 'i.id AS installment_id '
            . "FROM {$contracts} AS c "
            . "JOIN {$properties} AS p ON p.id = c.property_id "
            . "JOIN {$users} AS tenant ON tenant.id = c.tenant_id "
            . "LEFT JOIN {$installments} AS i ON i.contract_id = c.id AND i.period = ? "
            . 'WHERE ' . $this->coverageCondition('c') . ' '
            . 'ORDER BY c.id ASC';

        return $this->db->query($sql, [$period, $period, $period])->getResultArray();
    }

    /**
     * Locks, until the end of the current transaction, the contracts in force that cover a period (or just one of
     * them) and returns their id, monthly rent and due day, in id order. It must run inside a transaction: the
     * installment issuing (HU-13) uses it to keep two simultaneous issuings from creating the same installment or
     * taking the same number.
     *
     * @param string   $period     First day of the month, "YYYY-MM-01"
     * @param int|null $contractId Limits the result to this contract (null for all)
     *
     * @return list<array<string, mixed>>
     */
    public function lockInForceCoveringPeriod(string $period, ?int $contractId = null): array
    {
        $contracts = $this->db->protectIdentifiers('contracts', true);
        $binds     = [$period, $period];

        $sql = "SELECT c.id, c.monthly_rent, c.due_day FROM {$contracts} AS c WHERE " . $this->coverageCondition('c');

        if ($contractId !== null) {
            $sql    .= ' AND c.id = ?';
            $binds[] = $contractId;
        }

        return $this->db->query($sql . ' ORDER BY c.id ASC FOR UPDATE', $binds)->getResultArray();
    }

    /**
     * SQL condition (two `?` placeholders, both for the period) that selects the contracts that cover a period: they
     * are in force and the due date of that month falls inside their term. Each contract's due day is 1 to 28, so the
     * due date always exists, no installment is due before the contract starts or after it ends, and a contract of N
     * whole months (from a day to the day before the same day N months later) is covered by exactly N periods.
     *
     * @param string $alias Alias of the contracts table in the query
     */
    private function coverageCondition(string $alias): string
    {
        $dueDate = "DATE_ADD(CAST(? AS DATE), INTERVAL ({$alias}.due_day - 1) DAY)";

        return "{$alias}.status = '" . self::STATUS_IN_FORCE . "' "
            . "AND {$alias}.start_date <= {$dueDate} "
            . "AND {$alias}.end_date >= {$dueDate}";
    }

    /**
     * Locks the (not deactivated) property row until the end of the transaction and returns it.
     *
     * @return array<string, mixed>|null
     */
    private function lockProperty(int $propertyId): ?array
    {
        $table = $this->db->protectIdentifiers('properties', true);

        $row = $this->db
            ->query("SELECT id, owner_id, status FROM {$table} WHERE id = ? AND deleted_at IS NULL FOR UPDATE", [$propertyId])
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Tells whether the property already has a contract in force (only one is allowed at a time).
     */
    private function hasContractInForce(int $propertyId): bool
    {
        return $this->builder()
            ->where('property_id', $propertyId)
            ->where('status', self::STATUS_IN_FORCE)
            ->countAllResults() > 0;
    }
}
