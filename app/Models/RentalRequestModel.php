<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Persistence and lookup queries for tenant rental requests (HU-30, HU-31).
 *
 * A tenant can submit a request for an available and approved property, track all their requests in "Mis solicitudes"
 * and cancel a pending one. Submitting a request never reserves the property or changes its commercial status.
 *
 * At most one request may remain "pending" for the same tenant and property at a time; because MySQL does not support
 * partial unique indexes, {@see createRequest()} checks that rule under a row lock inside a transaction.
 */
class RentalRequestModel extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const DESIRED_MONTHS_MAX          = 255;
    public const CONTACT_PHONE_MAX_LENGTH    = 30;
    public const MESSAGE_MAX_LENGTH          = 500;
    public const REJECTION_REASON_MAX_LENGTH = 255;

    /** Automatic rejection reason set on competing pending requests when a contract is created (HU-31 Scenario 4). */
    public const AUTO_REJECTION_REASON_PROPERTY_RENTED = 'El inmueble fue alquilado a otro interesado';

    public const ERROR_NOT_FOUND            = 'not_found';
    public const ERROR_PROPERTY_UNAVAILABLE = 'property_unavailable';
    public const ERROR_DUPLICATE_PENDING    = 'duplicate_pending';
    public const ERROR_NOT_PENDING          = 'not_pending';
    public const ERROR_ALREADY_RESOLVED     = 'already_resolved';
    public const ERROR_MISMATCH             = 'mismatch';
    public const ERROR_INACTIVE_TENANT      = 'inactive_tenant';
    public const ERROR_PERSISTENCE          = 'persistence';

    /**
     * Spanish labels for each rental request status (HU-30 Scenario 5, Analysis section 9.3).
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Pendiente',
        self::STATUS_APPROVED  => 'Aprobada',
        self::STATUS_REJECTED  => 'Rechazada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected $table            = 'rental_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'property_id',
        'tenant_id',
        'desired_start_date',
        'desired_months',
        'contact_phone',
        'message',
        'status',
        'rejection_reason',
        'resolved_by',
        'resolved_at',
        'contract_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the Spanish label of a rental request status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Current calendar date ("YYYY-MM-DD"), honoring `Time::setTestNow()` in tests.
     */
    public static function today(): string
    {
        return Time::now()->toDateString();
    }

    /**
     * Finds a property that can currently receive a rental request (HU-30 Scenarios 1 and 4):
     * active (`deleted_at IS NULL`), approved by the administration (`approval_status = 'approved'`),
     * commercially available (`status = 'available'`), and without a contract in force.
     *
     * Returns only the public property fields needed for the tenant form (never `owner_id` or audit columns).
     *
     * @return array<string, mixed>|null
     */
    public function findRequestableProperty(int $propertyId): ?array
    {
        if ($propertyId <= 0) {
            return null;
        }

        $row = $this->db->table('properties')
            ->select(
                'properties.id, properties.address, properties.city, properties.type, properties.rooms, '
                . 'properties.surface, properties.included_services, properties.base_rent, properties.description, properties.status'
            )
            ->join('users AS owner', 'owner.id = properties.owner_id')
            ->where('properties.id', $propertyId)
            ->where('properties.deleted_at', null)
            ->where('properties.status', PropertyModel::STATUS_AVAILABLE)
            ->where('properties.approval_status', PropertyModel::APPROVAL_APPROVED)
            ->where('owner.deleted_at', null)
            ->get()
            ->getRowArray();

        if ($row === null || $this->hasContractInForce($propertyId)) {
            return null;
        }

        return $row;
    }

    /**
     * Tells whether the tenant already has a pending request on the given property (HU-30 Scenario 3).
     */
    public function hasPendingForTenantAndProperty(int $tenantId, int $propertyId): bool
    {
        return $this->builder()
            ->where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('status', self::STATUS_PENDING)
            ->countAllResults() > 0;
    }

    /**
     * Atomically registers a new rental request in status "pending" (HU-30 Scenarios 1, 3 and 4).
     *
     * Locks the property row and checks availability, approval, active tenant and duplicate pending requests inside
     * the transaction so concurrent submissions cannot create two pending requests for the same tenant and property
     * or target a property that just ceased to be available.
     *
     * Submitting a request does NOT modify `properties.status`.
     *
     * @param array{
     *     desired_start_date: string,
     *     desired_months: int|string,
     *     contact_phone: string,
     *     message?: string|null
     * } $data
     *
     * @return array{ok: bool, id: int|null, error: string|null}
     */
    public function createRequest(int $tenantId, int $propertyId, array $data): array
    {
        if ($tenantId <= 0 || $propertyId <= 0) {
            return ['ok' => false, 'id' => null, 'error' => self::ERROR_PERSISTENCE];
        }

        $this->db->transBegin();

        try {
            $propertiesTable = $this->db->protectIdentifiers('properties', true);
            $usersTable      = $this->db->protectIdentifiers('users', true);
            $property        = $this->db
                ->query(
                    "SELECT p.id, p.status, p.approval_status, p.deleted_at, o.deleted_at AS owner_deleted_at
                     FROM {$propertiesTable} p
                     JOIN {$usersTable} o ON o.id = p.owner_id
                     WHERE p.id = ? FOR UPDATE",
                    [$propertyId]
                )
                ->getRowArray();

            if (
                $property === null
                || $property['deleted_at'] !== null
                || $property['owner_deleted_at'] !== null
                || (string) $property['status'] !== PropertyModel::STATUS_AVAILABLE
                || (string) $property['approval_status'] !== PropertyModel::APPROVAL_APPROVED
                || $this->hasContractInForce($propertyId)
            ) {
                $this->db->transRollback();

                return ['ok' => false, 'id' => null, 'error' => self::ERROR_PROPERTY_UNAVAILABLE];
            }

            $rolesTable = $this->db->protectIdentifiers('roles', true);
            $tenant     = $this->db
                ->query(
                    "SELECT u.id, u.deleted_at, r.name AS role_name
                     FROM {$usersTable} u
                     JOIN {$rolesTable} r ON r.id = u.role_id
                     WHERE u.id = ? FOR UPDATE",
                    [$tenantId]
                )
                ->getRowArray();

            if (
                $tenant === null
                || $tenant['deleted_at'] !== null
                || (string) $tenant['role_name'] !== UserModel::ROLE_TENANT
            ) {
                $this->db->transRollback();

                return ['ok' => false, 'id' => null, 'error' => self::ERROR_INACTIVE_TENANT];
            }

            $requestsTable   = $this->db->protectIdentifiers('rental_requests', true);
            $existingPending = $this->db
                ->query(
                    "SELECT id FROM {$requestsTable} WHERE property_id = ? AND tenant_id = ? AND status = ? FOR UPDATE",
                    [$propertyId, $tenantId, self::STATUS_PENDING]
                )
                ->getRowArray();

            if ($existingPending !== null) {
                $this->db->transRollback();

                return ['ok' => false, 'id' => null, 'error' => self::ERROR_DUPLICATE_PENDING];
            }

            $message = trim((string) ($data['message'] ?? ''));

            $requestId = $this->insert([
                'property_id'        => $propertyId,
                'tenant_id'          => $tenantId,
                'desired_start_date' => (string) $data['desired_start_date'],
                'desired_months'     => (int) $data['desired_months'],
                'contact_phone'      => trim((string) $data['contact_phone']),
                'message'            => $message !== '' ? $message : null,
                'status'             => self::STATUS_PENDING,
                'rejection_reason'   => null,
                'resolved_by'        => null,
                'resolved_at'        => null,
                'contract_id'        => null,
            ]);

            if ($requestId === false || (int) $requestId <= 0) {
                throw new RuntimeException('The rental request could not be inserted.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The rental request transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            log_message('error', 'Rental request creation failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'id' => null, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'id' => (int) $requestId, 'error' => null];
    }

    /**
     * Lists all rental requests of a tenant, newest first, with property summary and linked contract status (HU-30 Scenario 5).
     * Deactivated properties are still joined so the tenant's historical requests remain visible.
     *
     * @return list<array<string, mixed>>
     */
    public function findForTenant(int $tenantId): array
    {
        return $this->builder()
            ->select($this->tenantSelect())
            ->join('properties', 'properties.id = rental_requests.property_id')
            ->join('contracts', 'contracts.id = rental_requests.contract_id', 'left')
            ->where('rental_requests.tenant_id', $tenantId)
            ->orderBy('rental_requests.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Finds one rental request belonging to the given tenant, or returns null when it does not exist or belongs
     * to another tenant (so the controller answers 404 without leaking its existence).
     *
     * @return array<string, mixed>|null
     */
    public function findOneForTenant(int $requestId, int $tenantId): ?array
    {
        if ($requestId <= 0 || $tenantId <= 0) {
            return null;
        }

        $row = $this->builder()
            ->select($this->tenantSelect())
            ->join('properties', 'properties.id = rental_requests.property_id')
            ->join('contracts', 'contracts.id = rental_requests.contract_id', 'left')
            ->where('rental_requests.id', $requestId)
            ->where('rental_requests.tenant_id', $tenantId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Cancels a pending rental request belonging to the tenant (HU-30 Scenario 6).
     *
     * Locks the row first so concurrent resolutions cannot collide. A request that belongs to someone else is reported
     * as not found; one that is no longer pending is rejected without modifying it.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function cancelByTenant(int $requestId, int $tenantId): array
    {
        if ($requestId <= 0 || $tenantId <= 0) {
            return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
        }

        $this->db->transBegin();

        try {
            $table = $this->db->protectIdentifiers('rental_requests', true);
            $row   = $this->db
                ->query("SELECT id, tenant_id, status FROM {$table} WHERE id = ? FOR UPDATE", [$requestId])
                ->getRowArray();

            if ($row === null || (int) $row['tenant_id'] !== $tenantId) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
            }

            if ((string) $row['status'] !== self::STATUS_PENDING) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_PENDING];
            }

            if ($this->update($requestId, ['status' => self::STATUS_CANCELLED]) === false) {
                throw new RuntimeException('The rental request status could not be updated to cancelled.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The rental request cancellation transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            log_message('error', 'Rental request cancellation failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Counts the rental requests currently in status "pending" for the administration tray (HU-30 Scenario 6, HU-31).
     */
    public function countPending(): int
    {
        return (int) $this->builder()
            ->where('status', self::STATUS_PENDING)
            ->countAllResults();
    }

    /**
     * Lists the pending rental requests visible in the administration tray (HU-30 Scenario 6, HU-31).
     * Cancelled, approved and rejected requests are excluded from the pending tray.
     *
     * @return list<array<string, mixed>>
     */
    public function findPendingQueueForAdmin(): array
    {
        return $this->builder()
            ->select(
                'rental_requests.*, '
                . 'properties.address AS property_address, properties.city AS property_city, '
                . 'properties.type AS property_type, properties.base_rent AS property_base_rent, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.email AS tenant_email, tenant.document_number AS tenant_document_number'
            )
            ->join('properties', 'properties.id = rental_requests.property_id')
            ->join('users AS tenant', 'tenant.id = rental_requests.tenant_id')
            ->where('rental_requests.status', self::STATUS_PENDING)
            ->orderBy('rental_requests.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Searches and paginates rental requests for the administration tray (HU-31 Scenario 1), ordering pending requests
     * first and then newest first.
     *
     * @param array{status?: string, q?: string} $filters
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
    public function paginateForAdmin(int $perPage = 10, int $page = 1, array $filters = []): array
    {
        $perPage     = max(1, min(100, $perPage));
        $currentPage = max(1, $page);

        $builder = $this->builder()
            ->select($this->adminSelect())
            ->join('properties', 'properties.id = rental_requests.property_id')
            ->join('users AS tenant', 'tenant.id = rental_requests.tenant_id')
            ->join('users AS owner', 'owner.id = properties.owner_id')
            ->join('users AS resolver', 'resolver.id = rental_requests.resolved_by', 'left');

        if (isset($filters['status']) && array_key_exists($filters['status'], self::STATUS_LABELS)) {
            $builder->where('rental_requests.status', $filters['status']);
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $builder->groupStart()
                ->like('tenant.first_name', $query)
                ->orLike('tenant.last_name', $query)
                ->orLike('tenant.document_number', $query)
                ->orLike('tenant.email', $query)
                ->orWhere("CONCAT(tenant.first_name, ' ', tenant.last_name) LIKE", '%' . $this->db->escapeLikeString($query) . '%')
                ->orLike('properties.address', $query)
                ->orLike('properties.city', $query)
                ->groupEnd();
        }

        $total      = (int) $builder->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $items  = $builder
            ->orderBy("CASE WHEN rental_requests.status = '" . self::STATUS_PENDING . "' THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('rental_requests.id', 'DESC')
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
     * Counts rental requests grouped by status for the administrative tray summary (HU-31 Scenario 1).
     *
     * @return array{pending: int, approved: int, rejected: int, cancelled: int, total: int}
     */
    public function getStatusCounts(): array
    {
        $counts = [
            self::STATUS_PENDING   => 0,
            self::STATUS_APPROVED  => 0,
            self::STATUS_REJECTED  => 0,
            self::STATUS_CANCELLED => 0,
            'total'                => 0,
        ];

        $rows = $this->builder()
            ->select('status, COUNT(id) AS total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total  = (int) ($row['total'] ?? 0);

            if (array_key_exists($status, self::STATUS_LABELS)) {
                $counts[$status]  = $total;
                $counts['total'] += $total;
            }
        }

        return $counts;
    }

    /**
     * Finds a rental request with full tenant, property, owner and resolver details for the administrative file
     * screen (HU-31 Scenario 2).
     *
     * @return array<string, mixed>|null
     */
    public function findForAdmin(int $requestId): ?array
    {
        if ($requestId <= 0) {
            return null;
        }

        $row = $this->builder()
            ->select($this->adminSelect())
            ->join('properties', 'properties.id = rental_requests.property_id')
            ->join('users AS tenant', 'tenant.id = rental_requests.tenant_id')
            ->join('users AS owner', 'owner.id = properties.owner_id')
            ->join('users AS resolver', 'resolver.id = rental_requests.resolved_by', 'left')
            ->where('rental_requests.id', $requestId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Lists the other pending requests on the same property so the reviewer sees competing applicants on the file
     * screen (HU-31 Scenario 2).
     *
     * @return list<array<string, mixed>>
     */
    public function findOtherPendingForProperty(int $propertyId, int $excludeRequestId): array
    {
        return $this->builder()
            ->select(
                'rental_requests.id, rental_requests.desired_start_date, rental_requests.desired_months, '
                . 'rental_requests.contact_phone, rental_requests.message, rental_requests.created_at, '
                . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
                . 'tenant.document_number AS tenant_document_number, tenant.email AS tenant_email'
            )
            ->join('users AS tenant', 'tenant.id = rental_requests.tenant_id')
            ->where('rental_requests.property_id', $propertyId)
            ->where('rental_requests.status', self::STATUS_PENDING)
            ->where('rental_requests.id !=', $excludeRequestId)
            ->orderBy('rental_requests.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Rejects a pending rental request with a mandatory reason of up to 255 characters, recording who resolved it and
     * when (HU-31 Scenario 5).
     *
     * @return array{ok: bool, error: string|null}
     */
    public function rejectByAdmin(int $requestId, int $reviewerId, string $reason): array
    {
        $trimmedReason = trim($reason);

        if ($requestId <= 0 || $reviewerId <= 0 || $trimmedReason === '' || mb_strlen($trimmedReason) > self::REJECTION_REASON_MAX_LENGTH) {
            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        $this->db->transBegin();

        try {
            $table = $this->db->protectIdentifiers('rental_requests', true);
            $row   = $this->db
                ->query("SELECT id, status FROM {$table} WHERE id = ? FOR UPDATE", [$requestId])
                ->getRowArray();

            if ($row === null) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
            }

            if ((string) $row['status'] !== self::STATUS_PENDING) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_ALREADY_RESOLVED];
            }

            $updated = $this->update($requestId, [
                'status'           => self::STATUS_REJECTED,
                'rejection_reason' => $trimmedReason,
                'resolved_by'      => $reviewerId,
                'resolved_at'      => Time::now()->toDateTimeString(),
            ]);

            if ($updated === false) {
                throw new RuntimeException('The rental request rejection could not be stored.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The rental request rejection transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            log_message('error', 'Rental request rejection failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Tells whether the property already has a lease contract in force.
     */
    private function hasContractInForce(int $propertyId): bool
    {
        return $this->db->table('contracts')
            ->where('property_id', $propertyId)
            ->where('status', ContractModel::STATUS_IN_FORCE)
            ->countAllResults() > 0;
    }

    /**
     * Columns exposed to the owning tenant in "Mis solicitudes" (excludes internal owner and reviewer identities).
     */
    private function tenantSelect(): string
    {
        return 'rental_requests.id, rental_requests.property_id, rental_requests.tenant_id, '
            . 'rental_requests.desired_start_date, rental_requests.desired_months, rental_requests.contact_phone, '
            . 'rental_requests.message, rental_requests.status, rental_requests.rejection_reason, '
            . 'rental_requests.resolved_at, rental_requests.contract_id, '
            . 'rental_requests.created_at, rental_requests.updated_at, '
            . 'properties.address AS property_address, properties.city AS property_city, '
            . 'properties.type AS property_type, properties.rooms AS property_rooms, '
            . 'properties.surface AS property_surface, properties.base_rent AS property_base_rent, '
            . 'properties.status AS property_status, properties.approval_status AS property_approval_status, '
            . 'properties.deleted_at AS property_deleted_at, '
            . 'contracts.status AS contract_status';
    }

    /**
     * Columns selected for the administration tray and file views (HU-31 Scenarios 1 and 2).
     */
    private function adminSelect(): string
    {
        return 'rental_requests.*, '
            . 'properties.address AS property_address, properties.city AS property_city, '
            . 'properties.type AS property_type, properties.rooms AS property_rooms, '
            . 'properties.surface AS property_surface, properties.included_services AS property_included_services, '
            . 'properties.base_rent AS property_base_rent, properties.status AS property_status, '
            . 'properties.approval_status AS property_approval_status, properties.deleted_at AS property_deleted_at, '
            . 'properties.owner_id AS property_owner_id, '
            . 'tenant.first_name AS tenant_first_name, tenant.last_name AS tenant_last_name, '
            . 'tenant.document_number AS tenant_document_number, tenant.email AS tenant_email, '
            . 'tenant.phone AS tenant_profile_phone, tenant.deleted_at AS tenant_deleted_at, '
            . 'owner.first_name AS owner_first_name, owner.last_name AS owner_last_name, '
            . 'owner.document_number AS owner_document_number, owner.email AS owner_email, '
            . 'owner.deleted_at AS owner_deleted_at, '
            . 'resolver.first_name AS resolved_by_first_name, resolver.last_name AS resolved_by_last_name';
    }
}

