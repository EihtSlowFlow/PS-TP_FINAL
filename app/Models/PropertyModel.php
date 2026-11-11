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
    public function paginateWithOwner(int $perPage = 10, int $page = 1): array
    {
        $perPage     = max(1, min(100, $perPage));
        $currentPage = max(1, $page);

        $builder = $this->builder()
            ->select($this->ownerSelect())
            ->join('users', 'users.id = properties.owner_id')
            ->where('properties.deleted_at', null);

        $total      = (int) $builder->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;

        $items = $builder
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
}
