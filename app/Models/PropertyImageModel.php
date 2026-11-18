<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Photos of the properties. The files live in the private directory of the `propertyImageStorage` service
 * (`writable/uploads/inmuebles/`) under a random `stored_name` and are served by PropertyImageController.
 */
class PropertyImageModel extends Model
{
    protected $table            = 'property_images';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'property_id',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
        'position',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * The photos of a property, cover first.
     *
     * @return list<array<string, mixed>>
     */
    public function forProperty(int $propertyId): array
    {
        return array_values($this->builder()
            ->select('id, property_id, original_name, mime_type, size_bytes, position')
            ->where('property_id', $propertyId)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray());
    }

    public function countForProperty(int $propertyId): int
    {
        return (int) $this->builder()->where('property_id', $propertyId)->countAllResults();
    }

    /**
     * The cover photo (lowest position) of each of the given properties.
     *
     * @param list<int> $propertyIds
     *
     * @return array<int, int> Property id => image id; properties without photos are left out
     */
    public function coversFor(array $propertyIds): array
    {
        $propertyIds = array_values(array_unique(array_map('intval', $propertyIds)));

        if ($propertyIds === []) {
            return [];
        }

        $rows = $this->builder()
            ->select('id, property_id')
            ->whereIn('property_id', $propertyIds)
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $covers = [];

        foreach ($rows as $row) {
            $covers[(int) $row['property_id']] ??= (int) $row['id'];
        }

        return $covers;
    }

    /**
     * Next free position of a property (photos added later go after the existing ones).
     */
    public function nextPosition(int $propertyId): int
    {
        $row = $this->builder()
            ->selectMax('position', 'max_position')
            ->where('property_id', $propertyId)
            ->get()
            ->getRowArray();

        return $row === null || $row['max_position'] === null ? 0 : (int) $row['max_position'] + 1;
    }

    /**
     * A photo together with what decides who may see it: the state of its property and of the property's owner.
     *
     * @return array<string, mixed>|null
     */
    public function findForAccess(int $imageId): ?array
    {
        $row = $this->builder()
            ->select(
                'property_images.id, property_images.stored_name, property_images.mime_type, property_images.original_name, '
                . 'properties.owner_id, properties.status AS property_status, properties.approval_status, '
                . 'properties.deleted_at AS property_deleted_at, owner.deleted_at AS owner_deleted_at'
            )
            ->join('properties', 'properties.id = property_images.property_id')
            ->join('users AS owner', 'owner.id = properties.owner_id')
            ->where('property_images.id', $imageId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Whether a photo belongs to a property everybody can see (the same rule as the public catalog, HU-08/HU-29).
     *
     * @param array<string, mixed> $access A row of findForAccess()
     */
    public static function isPublic(array $access): bool
    {
        return $access['property_deleted_at'] === null
            && $access['owner_deleted_at'] === null
            && (string) $access['property_status'] === PropertyModel::STATUS_AVAILABLE
            && (string) $access['approval_status'] === PropertyModel::APPROVAL_APPROVED;
    }

    /**
     * Deletes the given photos of a property; ids of other properties are ignored.
     *
     * @param list<int> $imageIds
     *
     * @return list<string> Stored names of the deleted rows, so the caller can remove the files
     */
    public function deleteFromProperty(int $propertyId, array $imageIds): array
    {
        $imageIds = array_values(array_unique(array_filter(array_map('intval', $imageIds), static fn (int $id): bool => $id > 0)));

        if ($imageIds === []) {
            return [];
        }

        $rows = $this->builder()
            ->select('id, stored_name')
            ->where('property_id', $propertyId)
            ->whereIn('id', $imageIds)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        $this->builder()->where('property_id', $propertyId)->whereIn('id', array_column($rows, 'id'))->delete();

        return array_values(array_map(static fn (array $row): string => (string) $row['stored_name'], $rows));
    }
}
