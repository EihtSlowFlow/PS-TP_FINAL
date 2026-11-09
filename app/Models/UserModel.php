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
     * Short Spanish descriptions for role selection cards in user forms (HU-04).
     *
     * @var array<string, string>
     */
    public const ROLE_DESCRIPTIONS = [
        self::ROLE_ADMIN    => 'Gestión total de la plataforma: usuarios, contratos, cobranzas, liquidaciones y configuración.',
        self::ROLE_EMPLOYEE => 'Operación diaria de inmuebles, contratos, emisión de cuotas, cobranzas y revisión de pagos.',
        self::ROLE_OWNER    => 'Acceso a su cartera de inmuebles, estado de contratos vigentes y liquidaciones.',
        self::ROLE_TENANT   => 'Consulta de estado de cuenta, descarga de cuotas emitidas e informe de comprobantes de pago.',
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
        return $this->findByIdWithRole($id, false);
    }

    /**
     * Finds a user by primary key including their role name, optionally including soft-deleted users.
     *
     * @return array<string, mixed>|null
     */
    public function findByIdWithRole(int $id, bool $includeDeleted = true): ?array
    {
        $builder = $this->builder()
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id);

        if (! $includeDeleted) {
            $builder->where('users.deleted_at', null);
        }

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Resolves the primary key of a role by its canonical name.
     */
    public function findRoleIdByName(string $roleName): ?int
    {
        $row = $this->db->table('roles')
            ->select('id')
            ->where('name', $roleName)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * Checks whether another active user (deleted_at IS NULL) already uses the given email.
     */
    public function existsActiveEmail(string $email, ?int $excludeUserId = null): bool
    {
        $builder = $this->builder()
            ->where('email', $email)
            ->where('deleted_at', null);

        if ($excludeUserId !== null) {
            $builder->where('id !=', $excludeUserId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Checks whether another active user (deleted_at IS NULL) already uses the given document number.
     */
    public function existsActiveDocumentNumber(string $documentNumber, ?int $excludeUserId = null): bool
    {
        $builder = $this->builder()
            ->where('document_number', $documentNumber)
            ->where('deleted_at', null);

        if ($excludeUserId !== null) {
            $builder->where('id !=', $excludeUserId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Returns summary counts of active users by role and soft-deleted users for the directory header.
     *
     * @return array{admin: int, employee: int, owner: int, tenant: int, total_active: int, total_deleted: int}
     */
    public function getRoleCounts(): array
    {
        $counts = [
            self::ROLE_ADMIN    => 0,
            self::ROLE_EMPLOYEE => 0,
            self::ROLE_OWNER    => 0,
            self::ROLE_TENANT   => 0,
            'total_active'      => 0,
            'total_deleted'     => 0,
        ];

        $rows = $this->builder()
            ->select('roles.name AS role_name, COUNT(users.id) AS total')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.deleted_at', null)
            ->groupBy('roles.name')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $roleName = (string) ($row['role_name'] ?? '');
            $total    = (int) ($row['total'] ?? 0);

            if (array_key_exists($roleName, self::ROLE_LABELS)) {
                $counts[$roleName]       = $total;
                $counts['total_active'] += $total;
            }
        }

        $counts['total_deleted'] = (int) $this->builder()
            ->where('deleted_at IS NOT NULL', null, false)
            ->countAllResults();

        return $counts;
    }

    /**
     * Counts active users (deleted_at IS NULL) assigned to a given canonical role name.
     */
    public function countActiveByRole(string $roleName): int
    {
        return (int) $this->builder()
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.name', $roleName)
            ->where('users.deleted_at', null)
            ->countAllResults();
    }

    /**
     * Returns active users (deleted_at IS NULL) for a specific role so that deactivated users
     * never appear as selectable options in new operations such as contracts or properties (HU-05 Scenario 2).
     *
     * @return list<array<string, mixed>>
     */
    public function findActiveSelectableByRole(string $roleName): array
    {
        if (! array_key_exists($roleName, self::ROLE_LABELS)) {
            return [];
        }

        return $this->builder()
            ->select('users.id, users.first_name, users.last_name, users.document_number, users.email, users.phone, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.name', $roleName)
            ->where('users.deleted_at', null)
            ->orderBy('users.last_name', 'ASC')
            ->orderBy('users.first_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Returns active tenants available to be linked to a new lease contract (HU-05 Scenario 2, HU-10).
     *
     * @return list<array<string, mixed>>
     */
    public function findActiveTenantsForContract(): array
    {
        return $this->findActiveSelectableByRole(self::ROLE_TENANT);
    }

    /**
     * Returns active owners available to be linked to a property or contract (HU-05 Scenario 2, HU-07).
     *
     * @return list<array<string, mixed>>
     */
    public function findActiveOwnersForProperty(): array
    {
        return $this->findActiveSelectableByRole(self::ROLE_OWNER);
    }

    /**
     * Searches and paginates users by free-text query, role, and soft-delete visibility (HU-04).
     *
     * @param array{q?: string, role?: string, include_deleted?: bool} $filters
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
    public function searchAndPaginate(array $filters, int $perPage = 10, int $page = 1): array
    {
        $perPage     = max(1, min(100, $perPage));
        $currentPage = max(1, $page);

        $builder = $this->builder()
            ->select('users.*, roles.name AS role_name')
            ->join('roles', 'roles.id = users.role_id');

        $includeDeleted = ! empty($filters['include_deleted']);
        if (! $includeDeleted) {
            $builder->where('users.deleted_at', null);
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '' && array_key_exists($role, self::ROLE_LABELS)) {
            $builder->where('roles.name', $role);
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $normalizedDniQuery = preg_replace('/[\.\s-]+/', '', $query) ?? '';

            $builder->groupStart()
                ->like('users.first_name', $query)
                ->orLike('users.last_name', $query)
                ->orLike('users.document_number', $query)
                ->orLike('users.email', $query)
                ->orWhere("CONCAT(users.first_name, ' ', users.last_name) LIKE", '%' . $this->db->escapeLikeString($query) . '%');

            if ($normalizedDniQuery !== '' && $normalizedDniQuery !== $query) {
                $builder->orLike('users.document_number', $normalizedDniQuery);
            }

            $builder->groupEnd();
        }

        $total      = (int) $builder->countAllResults(false);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;

        $items = $builder
            ->orderBy('users.id', 'DESC')
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
     * Returns a read-only summary of historical records (contracts, installments, payments)
     * associated with a user so audit history remains consultable even after deactivation (HU-04 Scenario 5).
     *
     * @return array{
     *     contracts_count: int,
     *     installments_count: int,
     *     payments_count: int,
     *     latest_contract: array<string, mixed>|null
     * }
     */
    public function getUserOperationalSummary(int $userId): array
    {
        $contractsCount = (int) $this->db->table('contracts')
            ->groupStart()
                ->where('tenant_id', $userId)
                ->orWhere('owner_id', $userId)
            ->groupEnd()
            ->countAllResults();

        $installmentsCount = (int) $this->db->table('installments')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->groupStart()
                ->where('contracts.tenant_id', $userId)
                ->orWhere('contracts.owner_id', $userId)
            ->groupEnd()
            ->countAllResults();

        $paymentsCount = (int) $this->db->table('payments')
            ->join('installments', 'installments.id = payments.installment_id')
            ->join('contracts', 'contracts.id = installments.contract_id')
            ->groupStart()
                ->where('contracts.tenant_id', $userId)
                ->orWhere('contracts.owner_id', $userId)
            ->groupEnd()
            ->countAllResults();

        $latestContract = $this->db->table('contracts')
            ->select('contracts.id, contracts.status, contracts.start_date, contracts.end_date, properties.address AS property_address')
            ->join('properties', 'properties.id = contracts.property_id', 'left')
            ->groupStart()
                ->where('contracts.tenant_id', $userId)
                ->orWhere('contracts.owner_id', $userId)
            ->groupEnd()
            ->orderBy('contracts.id', 'DESC')
            ->get(1)
            ->getRowArray();

        return [
            'contracts_count'    => $contractsCount,
            'installments_count' => $installmentsCount,
            'payments_count'     => $paymentsCount,
            'latest_contract'    => $latestContract ?: null,
        ];
    }
}

