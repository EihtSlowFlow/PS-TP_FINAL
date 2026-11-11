<?php

namespace App\Models;

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

    public const FILTER_STATUS_ACTIVE   = 'active';
    public const FILTER_STATUS_INACTIVE = 'inactive';

    public const ERROR_NOT_FOUND         = 'not_found';
    public const ERROR_ALREADY_DELETED   = 'already_deleted';
    public const ERROR_CONTRACT_IN_FORCE = 'contract_in_force';
    public const ERROR_PERSISTENCE       = 'persistence';

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
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

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
     * Finds a property by id together with its owner's data (including deactivated owners).
     * By default it leaves out deactivated properties unless `$withDeleted` is set to true.
     *
     * @return array<string, mixed>|null
     */
    public function findWithOwner(int $id, bool $withDeleted = false): ?array
    {
        $builder = $this->builder()
            ->select($this->ownerSelect())
            ->join('users', 'users.id = properties.owner_id')
            ->join('users AS deleted_by_user', 'deleted_by_user.id = properties.deleted_by', 'left')
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
     * @return array{ok: bool, error: string|null}
     */
    public function deactivate(int $id, int $deletedByUserId): array
    {
        if ($deletedByUserId <= 0) {
            return ['ok' => false, 'error' => self::ERROR_PERSISTENCE];
        }

        $this->db->transBegin();

        try {
            $table = $this->db->protectIdentifiers('properties', true);
            $row   = $this->db
                ->query("SELECT id, status, deleted_at FROM {$table} WHERE id = ? FOR UPDATE", [$id])
                ->getRowArray();

            if ($row === null) {
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
     * Lists the properties that can be offered for a new contract (HU-10): not deactivated, in status "available"
     * and owned by an active owner. Ordered by address.
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
     * Lists only a specific owner's active properties and excludes internal owner/audit fields.
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
     * Lists only active and available properties, returning public property data only.
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
            ->select($includeOwner ? $this->ownerSelect() : $this->listingSelect());

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
            $builder->where('properties.status', self::STATUS_AVAILABLE);
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
     * Counts the registered (not deactivated) properties per status, plus the total of deactivated ones.
     *
     * @return array{available: int, rented: int, maintenance: int, inactive: int, total: int}
     */
    public function getStatusCounts(): array
    {
        $counts = [
            self::STATUS_AVAILABLE   => 0,
            self::STATUS_RENTED      => 0,
            self::STATUS_MAINTENANCE => 0,
            self::FILTER_STATUS_INACTIVE => 0,
            'total'                  => 0,
        ];

        $rows = $this->builder()
            ->select('status, COUNT(id) AS total')
            ->where('deleted_at', null)
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

    private function listingSelect(): string
    {
        return 'properties.id, properties.address, properties.city, properties.type, properties.rooms, '
            . 'properties.surface, properties.included_services, properties.base_rent, properties.description, '
            . 'properties.status';
    }
}
