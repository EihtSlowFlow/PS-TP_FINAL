<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistence and lookup queries for system users and their assigned role.
 */
class UserModel extends Model
{
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_OWNER    = 'owner';
    public const ROLE_TENANT   = 'tenant';

    /**
     * Human-readable Spanish labels for each role identifier.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        self::ROLE_ADMIN    => 'Administrador',
        self::ROLE_EMPLOYEE => 'Empleado',
        self::ROLE_OWNER    => 'Propietario',
        self::ROLE_TENANT   => 'Inquilino',
    ];

    /**
     * Default landing route after login for each role.
     *
     * @var array<string, string>
     */
    public const ROLE_HOME_ROUTES = [
        self::ROLE_ADMIN    => '/admin/dashboard',
        self::ROLE_EMPLOYEE => '/admin/dashboard',
        self::ROLE_OWNER    => '/propietario/dashboard',
        self::ROLE_TENANT   => '/inquilino/estado-cuenta',
    ];

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id',
        'first_name',
        'last_name',
        'document_number',
        'email',
        'phone',
        'address',
        'password_hash',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Returns the landing route corresponding to a given role name.
     */
    public static function homeRouteForRole(string $role): string
    {
        return self::ROLE_HOME_ROUTES[$role] ?? '/auth/login';
    }

    /**
     * Returns the Spanish display label for a given role name.
     */
    public static function labelForRole(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? 'Usuario';
    }

    /**
     * Finds an active user (deleted_at IS NULL) by email, including their role name.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByEmail(string $email): ?array
    {
        $row = $this->builder()
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $email)
            ->where('users.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Finds the most recent soft-deleted user (deleted_at IS NOT NULL) by email.
     *
     * @return array<string, mixed>|null
     */
    public function findDeactivatedByEmail(string $email): ?array
    {
        $row = $this->builder()
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $email)
            ->where('users.deleted_at IS NOT NULL', null, false)
            ->orderBy('users.id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Finds an active user (deleted_at IS NULL) by primary key, including their role name.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveById(int $id): ?array
    {
        $row = $this->builder()
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id)
            ->where('users.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}

