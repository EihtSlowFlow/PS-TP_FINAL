<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistence and lookup queries for the properties managed by the agency (HU-07).
 *
 * Deactivation (HU-09) is a soft delete, so every query here leaves out deactivated properties. Owners are
 * always joined without filtering by `users.deleted_at`: a property keeps its owner even if the owner was
 * deactivated afterwards (HU-05).
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
     * Spanish labels for each property status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_AVAILABLE   => 'Disponible',
        self::STATUS_RENTED      => 'Alquilado',
        self::STATUS_MAINTENANCE => 'En mantenimiento',
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
     * Finds a (not deactivated) property by id together with its owner's data, including deactivated owners.
     *
     * @return array<string, mixed>|null
     */
    public function findWithOwner(int $id): ?array
    {
        $row = $this->builder()
            ->select($this->ownerSelect())
            ->join('users', 'users.id = properties.owner_id')
            ->where('properties.id', $id)
            ->where('properties.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
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
     * Owners associated with active properties, including owners deactivated after those properties were created.
     *
     * @return list<array{id: int, first_name: string, last_name: string, deleted_at: string|null}>
     */
    public function getOwnerFilterOptions(): array
    {
        return $this->db->table('properties')
            ->select('users.id, users.first_name, users.last_name, users.deleted_at')
            ->join('users', 'users.id = properties.owner_id')
            ->where('properties.deleted_at', null)
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
        $perPage     = max(1, min(100, $perPage));
        $currentPage = max(1, $page);
        $builder     = $this->builder()
            ->select($includeOwner ? $this->ownerSelect() : $this->listingSelect())
            ->where('properties.deleted_at', null);

        if ($includeOwner) {
            $builder->join('users', 'users.id = properties.owner_id');
        }

        if ($ownerId !== null) {
            $builder->where('properties.owner_id', $ownerId);
        }

        if ($publicOnly) {
            $builder->where('properties.status', self::STATUS_AVAILABLE);
        }

        if (isset($filters['status']) && in_array($filters['status'], array_keys(self::STATUS_LABELS), true)) {
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
     * Counts the registered (not deactivated) properties per status.
     *
     * @return array{available: int, rented: int, maintenance: int, total: int}
     */
    public function getStatusCounts(): array
    {
        $counts = [
            self::STATUS_AVAILABLE   => 0,
            self::STATUS_RENTED      => 0,
            self::STATUS_MAINTENANCE => 0,
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

        return $counts;
    }

    /**
     * Columns of a property plus the data of its owner needed by the listing and the form.
     */
    private function ownerSelect(): string
    {
        return 'properties.*, '
            . 'users.first_name AS owner_first_name, users.last_name AS owner_last_name, '
            . 'users.document_number AS owner_document_number, users.deleted_at AS owner_deleted_at';
    }

    private function listingSelect(): string
    {
        return 'properties.id, properties.address, properties.city, properties.type, properties.rooms, '
            . 'properties.surface, properties.included_services, properties.base_rent, properties.description, '
            . 'properties.status';
    }
}
