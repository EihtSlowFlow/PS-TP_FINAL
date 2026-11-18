<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Persistence and lookup queries for the properties managed by the agency (HU-07, HU-08, HU-09).
 *
 * Deactivation (HU-09) is a soft delete (`deleted_at` + `deleted_by`), so every active query leaves out deactivated
 * properties unless the administrator explicitly filters by inactive status. Owners are always joined without
 * filtering by `users.deleted_at`: a property keeps its owner even if the owner was deactivated afterwards (HU-05).
 */
class PropertyModel extends Model
{
    public const TYPE_APARTMENT = 'apartment';
    public const TYPE_HOUSE     = 'house';
    public const TYPE_COMMERCIAL = 'commercial';
    public const TYPE_LAND      = 'land';
    public const TYPE_OFFICE    = 'office';

    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_RENTED      = 'rented';
    public const STATUS_MAINTENANCE = 'maintenance';

    /** Review of the properties published by owners (HU-32, HU-33); independent from the commercial status. */
    public const APPROVAL_PENDING  = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const FILTER_STATUS_ACTIVE   = 'active';
    public const FILTER_STATUS_INACTIVE = 'inactive';

    public const REJECTION_REASON_MAX_LENGTH = 255;

    public const ERROR_NOT_FOUND         = 'not_found';
    public const ERROR_ALREADY_DELETED   = 'already_deleted';
    public const ERROR_CONTRACT_IN_FORCE = 'contract_in_force';
    public const ERROR_PERSISTENCE       = 'persistence';
    public const ERROR_ALREADY_RESOLVED  = 'already_resolved';
    public const ERROR_ALREADY_APPROVED  = 'already_approved';

    /**
     * The services an owner can mark as included. They are stored as text, separated by commas, in
     * `properties.included_services`, exactly as they are shown to the public.
     *
     * @var list<string>
     */
    public const SERVICE_OPTIONS = ['Gas natural', 'Agua', 'Luz', 'Internet', 'Cochera', 'Apto mascotas'];

    /**
     * Spanish labels for each property type.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        self::TYPE_APARTMENT  => 'Departamento',
        self::TYPE_HOUSE      => 'Casa',
        self::TYPE_COMMERCIAL => 'Local comercial',
        self::TYPE_LAND       => 'Terreno',
        self::TYPE_OFFICE     => 'Oficina',
    ];

    /**
     * Spanish labels for each operational property status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_AVAILABLE   => 'Disponible',
        self::STATUS_RENTED      => 'Alquilado',
        self::STATUS_MAINTENANCE => 'En mantenimiento',
    ];

    /**
     * Status filter options available in the administrative listing (HU-08, HU-09 Scenario 5).
     *
     * @var array<string, string>
     */
    public const ADMIN_FILTER_STATUS_LABELS = [
        self::FILTER_STATUS_ACTIVE   => 'Activo',
        self::STATUS_AVAILABLE       => 'Disponible',
        self::STATUS_RENTED          => 'Alquilado',
        self::STATUS_MAINTENANCE     => 'En mantenimiento',
        self::FILTER_STATUS_INACTIVE => 'Inactivo',
    ];

    /**
     * Statuses an administrator can set by hand. "Rented" is assigned by the lease contract (HU-10), never manually.
     *
     * @var list<string>
     */
    public const MANUAL_STATUSES = [self::STATUS_AVAILABLE, self::STATUS_MAINTENANCE];

    /**
     * Spanish labels of the review state, as the administration sees it (HU-33).
     *
     * @var array<string, string>
     */
    public const APPROVAL_LABELS = [
        self::APPROVAL_PENDING  => 'Pendiente de aprobación',
        self::APPROVAL_APPROVED => 'Aprobado',
        self::APPROVAL_REJECTED => 'Rechazado',
    ];

    /**
     * Options of the review filter of the administrative listing (HU-33 Scenario 1).
     *
     * @var array<string, string>
     */
    public const APPROVAL_FILTER_LABELS = [
        self::APPROVAL_PENDING  => 'Pendientes de aprobación',
        self::APPROVAL_APPROVED => 'Aprobados',
        self::APPROVAL_REJECTED => 'Rechazados',
    ];

    /**
     * Shorter labels of the review state, as the owner sees it in "Mis inmuebles" (HU-32 Scenario 3).
     *
     * @var array<string, string>
     */
    public const OWNER_APPROVAL_LABELS = [
        self::APPROVAL_PENDING  => 'En revisión',
        self::APPROVAL_APPROVED => 'Aprobado',
        self::APPROVAL_REJECTED => 'Rechazado',
    ];

    protected $table            = 'properties';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'owner_id',
        'address',
        'city',
        'type',
        'rooms',
        'surface',
        'included_services',
        'base_rent',
        'description',
        'status',
        'approval_status',
        'submitted_by',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Splits a stored or submitted list of services ("Agua, Luz") into its items. Items that match an option up to case
     * are written as the option; any other text is kept as it comes so the validation can reject it.
     *
     * @param array<int|string, mixed>|string|null $services
     *
     * @return list<string>
     */
    public static function splitServices(array|string|null $services): array
    {
        $items = is_array($services) ? $services : explode(',', (string) $services);
        $known = array_combine(array_map('mb_strtolower', self::SERVICE_OPTIONS), self::SERVICE_OPTIONS);
        $out   = [];

        foreach ($items as $item) {
            $item = trim((string) $item);

            if ($item === '') {
                continue;
            }

            $item = $known[mb_strtolower($item)] ?? $item;

            if (! in_array($item, $out, true)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * The items of a list of services that are not one of the options.
     *
     * @param array<int|string, mixed>|string|null $services
     *
     * @return list<string>
     */
    public static function unknownServices(array|string|null $services): array
    {
        return array_values(array_diff(self::splitServices($services), self::SERVICE_OPTIONS));
    }

    /**
     * Returns the Spanish label of a property type.
     */
    public static function labelForType(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    /**
     * Returns the Spanish label of a property status.
     */
    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Returns the Spanish label of a review state; `$forOwner` uses the owner's wording («En revisión»).
     */
    public static function labelForApproval(string $approvalStatus, bool $forOwner = false): string
    {
        $labels = $forOwner ? self::OWNER_APPROVAL_LABELS : self::APPROVAL_LABELS;

        return $labels[$approvalStatus] ?? $approvalStatus;
    }

    /**
     * Finds a property by id together with its owner's data (including deactivated owners).
     * By default it leaves out deactivated properties unless `$withDeleted` is set to true.
     *
     * @return array<string, mixed>|null
     */
    public function findWithOwner(int $id, bool $withDeleted = false): ?array
    {
        $builder = $this->builder()
            ->select($this->ownerSelect() . ', reviewer.first_name AS reviewed_by_first_name, reviewer.last_name AS reviewed_by_last_name')
            ->join('users', 'users.id = properties.owner_id')
            ->join('users AS deleted_by_user', 'deleted_by_user.id = properties.deleted_by', 'left')
            ->join('users AS reviewer', 'reviewer.id = properties.reviewed_by', 'left')
            ->where('properties.id', $id);

        if (! $withDeleted) {
            $builder->where('properties.deleted_at', null);
        }

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Tells whether the property currently has a lease contract in force (HU-09 Scenario 8).
     */
    public function hasContractInForce(int $propertyId): bool
    {
        return $this->db->table('contracts')
            ->where('property_id', $propertyId)
            ->where('status', ContractModel::STATUS_IN_FORCE)
            ->countAllResults() > 0;
    }

    /**
     * Logically deactivates an active property that has no contract in force, recording `deleted_at` and `deleted_by`
     * atomically while keeping all physical data and historical references intact (HU-09 Scenarios 2, 7, 8).
     *
     * `$onlyOwnerId` is given when an owner deactivates their own property (HU-32: cancelling a pending request or
     * taking an approved property down): the property of anybody else is then reported as not found, checked on
     * the locked row so it holds even if the property changed hands since the caller read it.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function deactivate(int $id, int $deletedByUserId, ?int $onlyOwnerId = null): array
    {
        if ($deletedByUserId <= 0) {
            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        $this->db->transBegin();

        try {
            $table = $this->db->protectIdentifiers('properties', true);
            $row   = $this->db
                ->query("SELECT id, owner_id, status, deleted_at FROM {$table} WHERE id = ? FOR UPDATE", [$id])
                ->getRowArray();

            if ($row === null || ($onlyOwnerId !== null && (int) $row['owner_id'] !== $onlyOwnerId)) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
            }

            if ($row['deleted_at'] !== null) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_ALREADY_DELETED];
            }

            if ((string) $row['status'] === self::STATUS_RENTED || $this->hasContractInForce($id)) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_CONTRACT_IN_FORCE];
            }

            if ($this->builder()->where('id', $id)->update(['deleted_by' => $deletedByUserId]) === false) {
                throw new RuntimeException('The user responsible for the property deactivation could not be stored.');
            }

            if ($this->delete($id) === false) {
                throw new RuntimeException('The property could not be soft-deleted.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The property deactivation transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Property deactivation failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Finds a property of the given owner (not deactivated), whatever its review state. Returns null when it does not
     * exist or belongs to someone else, so the caller answers 404 in both cases and never reveals which one it was
     * (HU-32 Scenario 6).
     *
     * @return array<string, mixed>|null
     */
    public function findForOwner(int $propertyId, int $ownerId): ?array
    {
        $row = $this->builder()
            ->where('id', $propertyId)
            ->where('owner_id', $ownerId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Stores a property an owner publishes: linked to the owner, "available" and "pending" until the administration
     * reviews it (HU-32 Scenario 1). The owner always comes from the caller (the session), never from the form.
     *
     * @param array<string, mixed> $payload Validated columns built by the controller
     *
     * @return int|false The new id, or false when it could not be stored
     */
    public function submitForApproval(int $ownerId, array $payload): int|false
    {
        $payload['owner_id']         = $ownerId;
        $payload['status']           = self::STATUS_AVAILABLE;
        $payload['approval_status']  = self::APPROVAL_PENDING;
        $payload['submitted_by']     = $ownerId;
        $payload['rejection_reason'] = null;
        $payload['reviewed_by']      = null;
        $payload['reviewed_at']      = null;

        $id = $this->insert($payload);

        return $id === false ? false : (int) $id;
    }

    /**
     * Updates a pending or rejected property of its owner and leaves it "pending" again, clearing the previous
     * rejection (HU-32 Scenario 4). An approved property is never touched from here (Scenario 5).
     *
     * The row is locked and checked again inside the transaction, so an approval that lands between the owner opening
     * the form and saving it cannot be turned back into "pending" by accident.
     *
     * @param array<string, mixed> $payload Validated columns built by the controller (without owner or review fields)
     *
     * @return array{ok: bool, error: string|null}
     */
    public function resubmitForApproval(int $propertyId, int $ownerId, array $payload): array
    {
        $this->db->transBegin();

        try {
            $row = $this->lockRow($propertyId);

            if ($row === null || (int) $row['owner_id'] !== $ownerId || $row['deleted_at'] !== null) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
            }

            if ((string) $row['approval_status'] === self::APPROVAL_APPROVED) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_ALREADY_APPROVED];
            }

            unset($payload['owner_id'], $payload['status'], $payload['submitted_by']);

            $payload['approval_status']  = self::APPROVAL_PENDING;
            $payload['rejection_reason'] = null;
            $payload['reviewed_by']      = null;
            $payload['reviewed_at']      = null;

            if ($this->update($propertyId, $payload) === false) {
                throw new RuntimeException('The property could not be updated.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The property resubmission transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            log_message('error', 'Property resubmission failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Resolves the review of a pending property: approves it, or rejects it with a reason, recording who did it and
     * when (HU-33 Scenarios 2, 3 and 5). Approving does not change the commercial status.
     *
     * The row is locked first, so when two users resolve the same property at the same time the second one finds it
     * already resolved and changes nothing.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function resolveApproval(int $propertyId, int $reviewerId, bool $approve, ?string $rejectionReason = null): array
    {
        if ($reviewerId <= 0 || (! $approve && trim((string) $rejectionReason) === '')) {
            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        $this->db->transBegin();

        try {
            $row = $this->lockRow($propertyId);

            if ($row === null) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_NOT_FOUND];
            }

            if ($row['deleted_at'] !== null) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_ALREADY_DELETED];
            }

            if ((string) $row['approval_status'] !== self::APPROVAL_PENDING) {
                $this->db->transRollback();

                return ['ok' => false, 'error' => self::ERROR_ALREADY_RESOLVED];
            }

            $updated = $this->update($propertyId, [
                'approval_status'  => $approve ? self::APPROVAL_APPROVED : self::APPROVAL_REJECTED,
                'rejection_reason' => $approve ? null : trim((string) $rejectionReason),
                'reviewed_by'      => $reviewerId,
                'reviewed_at'      => Time::now()->toDateTimeString(),
            ]);

            if ($updated === false) {
                throw new RuntimeException('The property review could not be stored.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('The property review transaction could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            $this->db->resetTransStatus();
            log_message('error', 'Property review failed and was rolled back: {message}', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Counts the properties waiting for the administration to review them (the badge of the menu, HU-33 Scenario 1).
     * Deactivated ones are left out: they can no longer be resolved.
     */
    public function countPendingApproval(): int
    {
        return (int) $this->builder()
            ->where('approval_status', self::APPROVAL_PENDING)
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Reads the review columns of a property locking the row (`FOR UPDATE`); deactivated rows are returned too so the
     * caller can tell them apart from missing ones.
     *
     * @return array<string, mixed>|null
     */
    private function lockRow(int $propertyId): ?array
    {
        $table = $this->db->protectIdentifiers('properties', true);
        $row   = $this->db
            ->query("SELECT id, owner_id, approval_status, deleted_at FROM {$table} WHERE id = ? FOR UPDATE", [$propertyId])
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Returns the most recently created properties that are active, approved and available, for the public
     * landing page (HU-28). Only properties whose owner is also active are returned.
     *
     * @return list<array<string, mixed>>
     */
    public function findLatestPublic(int $limit = 6): array
    {
        return $this->builder()
            ->select($this->listingSelect())
            ->join('users AS public_owner', 'public_owner.id = properties.owner_id')
            ->where('public_owner.deleted_at', null)
            ->where('properties.deleted_at', null)
            ->where('properties.status', self::STATUS_AVAILABLE)
            ->where('properties.approval_status', self::APPROVAL_APPROVED)
            ->orderBy('properties.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Summary shown on the landing: how many properties the public catalog offers and in how many cities. Same
     * visibility rule as findLatestPublic().
     *
     * @return array{properties: int, cities: int}
     */
    public function publicSummary(): array
    {
        $row = $this->builder()
            ->select('COUNT(*) AS properties, COUNT(DISTINCT properties.city) AS cities')
            ->join('users AS public_owner', 'public_owner.id = properties.owner_id')
            ->where('public_owner.deleted_at', null)
            ->where('properties.deleted_at', null)
            ->where('properties.status', self::STATUS_AVAILABLE)
            ->where('properties.approval_status', self::APPROVAL_APPROVED)
            ->get()
            ->getRowArray();

        return ['properties' => (int) ($row['properties'] ?? 0), 'cities' => (int) ($row['cities'] ?? 0)];
    }

    /**
     * Finds a single property for public display (HU-29): must be active, approved, available,
     * and owned by an active owner. Excludes owner identity, contracts and audit fields.
     *
     * @return array<string, mixed>|null
     */
    public function findPublicDetail(int $id): ?array
    {
        $row = $this->builder()
            ->select($this->listingSelect())
            ->join('users AS public_owner', 'public_owner.id = properties.owner_id')
            ->where('properties.id', $id)
            ->where('public_owner.deleted_at', null)
            ->where('properties.deleted_at', null)
            ->where('properties.status', self::STATUS_AVAILABLE)
            ->where('properties.approval_status', self::APPROVAL_APPROVED)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Lists the properties that can be offered for a new contract (HU-10): not deactivated, approved by the
     * administration, in status "available" and owned by an active owner. Ordered by address.
     *
     * @return list<array<string, mixed>>
     */
    public function findAvailableForContract(): array
    {
        return $this->builder()
            ->select($this->ownerSelect())
            ->join('users', 'users.id = properties.owner_id')
            ->join('users AS deleted_by_user', 'deleted_by_user.id = properties.deleted_by', 'left')
            ->where('properties.status', self::STATUS_AVAILABLE)
            ->where('properties.approval_status', self::APPROVAL_APPROVED)
            ->where('properties.deleted_at', null)
            ->where('users.deleted_at', null)
            ->orderBy('properties.address', 'ASC')
            ->orderBy('properties.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Lists the properties newest first, paginated, with their owner's data (including deactivated owners).
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
    public function paginateWithOwner(int $perPage = 10, int $page = 1, array $filters = []): array
    {
        return $this->paginateProperties($perPage, $page, $filters, true);
    }

    /**
     * Lists only a specific owner's active properties, whatever their review state (the owner sees their pending and
     * rejected ones too, HU-32 Scenario 3), and excludes internal owner/audit fields.
     *
     * @param array<string, string|int|float> $filters
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
    public function paginateForOwner(int $ownerId, int $perPage = 10, int $page = 1, array $filters = []): array
    {
        return $this->paginateProperties($perPage, $page, $filters, false, $ownerId);
    }

    /**
     * Lists only active, approved and available properties, returning public property data only. A property that is
     * pending or rejected is never published (HU-32 Scenario 3).
     *
     * @param array<string, string|int|float> $filters
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
    public function paginatePublic(int $perPage = 10, int $page = 1, array $filters = []): array
    {
        return $this->paginateProperties($perPage, $page, $filters, false, null, true);
    }

    /**
     * Owners associated with active (or inactive, when filtering by inactive status) properties, including owners
     * deactivated after those properties were created.
     *
     * @return list<array{id: int, first_name: string, last_name: string, deleted_at: string|null}>
     */
    public function getOwnerFilterOptions(bool $onlyInactive = false): array
    {
        $builder = $this->db->table('properties')
            ->select('users.id, users.first_name, users.last_name, users.deleted_at')
            ->join('users', 'users.id = properties.owner_id');

        if ($onlyInactive) {
            $builder->where('properties.deleted_at IS NOT NULL');
        } else {
            $builder->where('properties.deleted_at', null);
        }

        return $builder
            ->groupBy(['users.id', 'users.first_name', 'users.last_name', 'users.deleted_at'])
            ->orderBy('users.last_name', 'ASC')
            ->orderBy('users.first_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, string|int|float> $filters
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
    private function paginateProperties(
        int $perPage,
        int $page,
        array $filters,
        bool $includeOwner,
        ?int $ownerId = null,
        bool $publicOnly = false
    ): array {
        $perPage      = max(1, min(100, $perPage));
        $currentPage  = max(1, $page);
        $onlyInactive = $includeOwner
            && ! $publicOnly
            && $ownerId === null
            && ($filters['status'] ?? '') === self::FILTER_STATUS_INACTIVE;

        $builder = $this->builder()
            ->select($includeOwner ? $this->ownerSelect() : $this->listingSelect($ownerId !== null));

        if ($onlyInactive) {
            $builder->where('properties.deleted_at IS NOT NULL');
        } else {
            $builder->where('properties.deleted_at', null);
        }

        if ($includeOwner) {
            $builder
                ->join('users', 'users.id = properties.owner_id')
                ->join('users AS deleted_by_user', 'deleted_by_user.id = properties.deleted_by', 'left');
        }

        if ($ownerId !== null) {
            $builder->where('properties.owner_id', $ownerId);
        }

        if ($publicOnly) {
            $builder
                ->join('users AS public_owner', 'public_owner.id = properties.owner_id')
                ->where('public_owner.deleted_at', null)
                ->where('properties.status', self::STATUS_AVAILABLE)
                ->where('properties.approval_status', self::APPROVAL_APPROVED);
        }

        // The review filter is administrative only: neither the public catalog nor the owner can narrow by it.
        if (
            $includeOwner
            && ! $publicOnly
            && isset($filters['approval'])
            && array_key_exists((string) $filters['approval'], self::APPROVAL_LABELS)
        ) {
            $builder->where('properties.approval_status', (string) $filters['approval']);
        }

        if (
            ! $onlyInactive
            && isset($filters['status'])
            && in_array($filters['status'], array_keys(self::STATUS_LABELS), true)
        ) {
            $builder->where('properties.status', $filters['status']);
        }

        if (isset($filters['type']) && in_array($filters['type'], array_keys(self::TYPE_LABELS), true)) {
            $builder->where('properties.type', $filters['type']);
        }

        if (
            isset($filters['min_price'])
            && is_numeric($filters['min_price'])
            && is_finite((float) $filters['min_price'])
            && (float) $filters['min_price'] > 0
        ) {
            $builder->where('properties.base_rent >=', (float) $filters['min_price']);
        }

        if (
            isset($filters['max_price'])
            && is_numeric($filters['max_price'])
            && is_finite((float) $filters['max_price'])
            && (float) $filters['max_price'] > 0
        ) {
            $builder->where('properties.base_rent <=', (float) $filters['max_price']);
        }

        if ($includeOwner && isset($filters['owner_id']) && (int) $filters['owner_id'] > 0) {
            $builder->where('properties.owner_id', (int) $filters['owner_id']);
        }

        $total      = (int) $builder->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $items  = $builder
            ->orderBy('properties.id', 'DESC')
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
     * Counts the registered (not deactivated) properties per status, plus the total of deactivated ones. These are the
     * occupancy indicators, so only approved properties count: a pending or rejected one is reported apart, in
     * `pending_approval` (HU-32 Scenario 3).
     *
     * @return array{available: int, rented: int, maintenance: int, inactive: int, total: int, pending_approval: int}
     */
    public function getStatusCounts(): array
    {
        $counts = [
            self::STATUS_AVAILABLE   => 0,
            self::STATUS_RENTED      => 0,
            self::STATUS_MAINTENANCE => 0,
            self::FILTER_STATUS_INACTIVE => 0,
            'total'                  => 0,
            'pending_approval'       => $this->countPendingApproval(),
        ];

        $rows = $this->builder()
            ->select('status, COUNT(id) AS total')
            ->where('deleted_at', null)
            ->where('approval_status', self::APPROVAL_APPROVED)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');

            if (array_key_exists($status, self::STATUS_LABELS)) {
                $counts[$status]  = (int) $row['total'];
                $counts['total'] += (int) $row['total'];
            }
        }

        $counts[self::FILTER_STATUS_INACTIVE] = (int) $this->builder()
            ->where('deleted_at IS NOT NULL')
            ->countAllResults();

        return $counts;
    }

    /**
     * Columns of a property plus the data of its owner, the user who deactivated it (when applicable),
     * and whether it has a lease contract in force.
     */
    private function ownerSelect(): string
    {
        $contractsTable  = $this->db->prefixTable('contracts');
        $propertiesTable = $this->db->prefixTable('properties');
        $inForceStatus   = ContractModel::STATUS_IN_FORCE;

        return 'properties.*, '
            . 'users.first_name AS owner_first_name, users.last_name AS owner_last_name, '
            . 'users.document_number AS owner_document_number, users.deleted_at AS owner_deleted_at, '
            . 'deleted_by_user.first_name AS deleted_by_first_name, deleted_by_user.last_name AS deleted_by_last_name, '
            . "EXISTS(SELECT 1 FROM `{$contractsTable}` c WHERE c.property_id = `{$propertiesTable}`.`id` AND c.status = '{$inForceStatus}') AS has_active_contract";
    }

    /**
     * Columns of a listing without owner data. The review columns are only for the owner's own listing: the public
     * catalog never receives them.
     */
    private function listingSelect(bool $withApproval = false): string
    {
        $columns = 'properties.id, properties.address, properties.city, properties.type, properties.rooms, '
            . 'properties.surface, properties.included_services, properties.base_rent, properties.description, '
            . 'properties.status';

        return $withApproval
            ? $columns . ', properties.approval_status, properties.rejection_reason'
            : $columns;
    }
}
