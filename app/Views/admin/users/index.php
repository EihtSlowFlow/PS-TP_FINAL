<?php
/**
 * @var string $pageTitle
 * @var string $breadcrumb
 * @var string $activeMenu
 * @var string $userName
 * @var string $userEmail
 * @var string $userRole
 * @var string $roleLabel
 * @var string $initials
 * @var int $currentAdminId
 * @var list<array<string, mixed>> $users
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{q: string, role: string, include_deleted: bool} $filters
 * @var array{admin: int, employee: int, owner: int, tenant: int, total_active: int, total_deleted: int} $roleCounts
 * @var array<string, string> $roleLabels
 */
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');

$buildPageUrl = static function (int $targetPage) use ($filters, $pagination): string {
    $query = [];
    if ($filters['q'] !== '') {
        $query['q'] = $filters['q'];
    }
    if ($filters['role'] !== '') {
        $query['role'] = $filters['role'];
    }
    if ($filters['include_deleted']) {
        $query['include_deleted'] = '1';
    }
    if ($pagination['perPage'] !== 10) {
        $query['per_page'] = (string) $pagination['perPage'];
    }
    $query['page'] = (string) $targetPage;

    return site_url('admin/usuarios') . '?' . http_build_query($query);
};

$extractInitials = static function (string $firstName, string $lastName): string {
    $f = $firstName !== '' ? mb_strtoupper(mb_substr(trim($firstName), 0, 1)) : 'U';
    $l = $lastName !== '' ? mb_strtoupper(mb_substr(trim($lastName), 0, 1)) : '';

    return $f . $l;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/panel.css')) ?>">
</head>
<body class="panel-page">

    <aside class="panel-sidebar" aria-label="Menú principal">
        <div>
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <div>
                    <span class="sidebar-brand-title">InmoGestión</span>
                    <span class="sidebar-brand-sub">Plataforma Inmobiliaria 2026</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= esc(site_url('admin/dashboard')) ?>" class="sidebar-link<?= $activeMenu === 'dashboard' ? ' is-active' : '' ?>">
                    <span>Dashboard</span>
                </a>
                <a href="<?= esc(site_url('admin/usuarios')) ?>" class="sidebar-link<?= $activeMenu === 'usuarios' ? ' is-active' : '' ?>">
                    <span>Usuarios</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-user-footer">
            <div class="sidebar-user-card">
                <div class="sidebar-user-info">
                    <div class="user-avatar" aria-hidden="true"><?= esc($initials) ?></div>
                    <div>
                        <span class="sidebar-user-name"><?= esc($userName) ?></span>
                        <span class="sidebar-user-role"><?= esc($roleLabel) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div class="panel-wrapper">
        <header class="panel-topbar">
            <div class="breadcrumb">
                <span>InmoGestión</span>
                <span aria-hidden="true">/</span>
                <span class="breadcrumb-current"><?= esc($breadcrumb) ?></span>
            </div>
            <div class="topbar-meta">
                <span class="role-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span><?= esc($roleLabel) ?></span>
                </span>
            </div>
        </header>

        <main class="panel-content">
            <?php if (is_string($flashError) && $flashError !== ''): ?>
                <div class="panel-flash panel-flash-danger" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?= esc($flashError) ?></span>
                </div>
            <?php endif; ?>

            <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                <div class="panel-flash panel-flash-success" role="status">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span><?= esc($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Usuarios</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) $roleCounts['total_active']) ?> activos</span>
                        <?php if ($filters['include_deleted'] && $roleCounts['total_deleted'] > 0): ?>
                            <span class="badge-pill badge-danger"><?= esc((string) $roleCounts['total_deleted']) ?> dados de baja</span>
                        <?php endif; ?>
                    </div>
                    <p class="panel-subtitle">Padrón de clientes y operadores del sistema</p>
                </div>
                <div>
                    <a href="<?= esc(site_url('admin/usuarios/nuevo')) ?>" class="btn btn-primary" id="btn-new-user">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                        <span>+ Nuevo usuario</span>
                    </a>
                </div>
            </div>

            <section class="users-kpi-grid" aria-label="Resumen por rol">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Inquilinos</span>
                        <strong class="kpi-value"><?= esc((string) $roleCounts['tenant']) ?></strong>
                        <span class="kpi-note kpi-note-success">Clientes locatarios activos</span>
                    </div>
                    <div class="kpi-icon kpi-icon-success" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                        </svg>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Propietarios</span>
                        <strong class="kpi-value"><?= esc((string) $roleCounts['owner']) ?></strong>
                        <span class="kpi-note kpi-note-info">Titulares de cartera</span>
                    </div>
                    <div class="kpi-icon kpi-icon-info" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                            <path d="M9 22v-4h6v4"/>
                            <path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>
                        </svg>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Empleados</span>
                        <strong class="kpi-value"><?= esc((string) $roleCounts['employee']) ?></strong>
                        <span class="kpi-note kpi-note-warning">Operación inmobiliaria</span>
                    </div>
                    <div class="kpi-icon kpi-icon-warning" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Administradores</span>
                        <strong class="kpi-value"><?= esc((string) $roleCounts['admin']) ?></strong>
                        <span class="kpi-note kpi-note-primary">Gestión del sistema</span>
                    </div>
                    <div class="kpi-icon kpi-icon-primary" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                </div>
            </section>

            <section class="panel-card filter-card" aria-label="Filtros de búsqueda">
                <form action="<?= esc(site_url('admin/usuarios')) ?>" method="get" class="users-filter-form">
                    <div class="filter-search-group">
                        <label for="search-q" class="sr-only">Buscar por nombre, DNI o correo</label>
                        <input
                            type="search"
                            id="search-q"
                            name="q"
                            value="<?= esc($filters['q']) ?>"
                            placeholder="Buscar por nombre, DNI o email..."
                            class="form-control"
                        >
                    </div>

                    <div class="filter-controls-group">
                        <div class="filter-select-wrapper">
                            <label for="filter-role" class="sr-only">Filtrar por rol</label>
                            <select id="filter-role" name="role" class="form-control">
                                <option value="">Todos los roles</option>
                                <?php foreach ($roleLabels as $roleKey => $label): ?>
                                    <option value="<?= esc($roleKey) ?>"<?= $filters['role'] === $roleKey ? ' selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <label class="filter-checkbox-label" for="include-deleted">
                            <input
                                type="checkbox"
                                id="include-deleted"
                                name="include_deleted"
                                value="1"
                                <?= $filters['include_deleted'] ? 'checked' : '' ?>
                            >
                            <span>Incluir usuarios dados de baja</span>
                        </label>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <span>Filtrar</span>
                            </button>
                            <a href="<?= esc(site_url('admin/usuarios')) ?>" class="btn btn-secondary">
                                <span>Limpiar</span>
                            </a>
                        </div>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="Listado de usuarios">
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th scope="col">Usuario</th>
                                <th scope="col">DNI</th>
                                <th scope="col">Correo Electrónico</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Fecha de Alta</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users === []): ?>
                                <tr>
                                    <td colspan="8" class="empty-table-cell">
                                        No se encontraron usuarios que coincidan con los criterios de búsqueda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $item): ?>
                                    <?php
                                    $itemId       = (int) $item['id'];
                                    $firstName    = (string) ($item['first_name'] ?? '');
                                    $lastName     = (string) ($item['last_name'] ?? '');
                                    $fullName     = trim($firstName . ' ' . $lastName);
                                    $itemInitials = $extractInitials($firstName, $lastName);
                                    $itemRole     = (string) ($item['role_name'] ?? '');
                                    $itemRoleText = $roleLabels[$itemRole] ?? 'Usuario';
                                    $isDeleted    = ! empty($item['deleted_at']);
                                    $createdAt    = ! empty($item['created_at'])
                                        ? date('d/m/Y', strtotime((string) $item['created_at']))
                                        : '—';
                                    $deletedAt    = $isDeleted
                                        ? date('d/m/Y', strtotime((string) $item['deleted_at']))
                                        : '';
                                    $roleBadgeClass = match ($itemRole) {
                                        'admin'    => 'badge-role-admin',
                                        'employee' => 'badge-role-employee',
                                        'owner'    => 'badge-role-owner',
                                        default    => 'badge-role-tenant',
                                    };
                                    ?>
                                    <tr class="<?= $isDeleted ? 'row-deactivated' : '' ?>">
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-cell-avatar <?= esc($roleBadgeClass) ?>" aria-hidden="true">
                                                    <?= esc($itemInitials) ?>
                                                </div>
                                                <div class="user-cell-meta">
                                                    <span class="user-cell-name"><?= esc($fullName) ?></span>
                                                    <span class="user-cell-sub">
                                                        <?= esc((string) ($item['address'] ?: 'Sin dirección registrada')) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="mono-cell"><?= esc((string) $item['document_number']) ?></td>
                                        <td><?= esc((string) $item['email']) ?></td>
                                        <td class="mono-cell"><?= esc((string) ($item['phone'] ?: '—')) ?></td>
                                        <td>
                                            <span class="badge-pill <?= esc($roleBadgeClass) ?>">
                                                <span><?= esc($itemRoleText) ?></span>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($isDeleted): ?>
                                                <span class="badge-pill badge-status-deleted" title="Baja: <?= esc($deletedAt) ?>">
                                                    <span>Dado de baja</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill badge-status-active">
                                                    <span>Activo</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="mono-cell"><?= esc($createdAt) ?></td>
                                        <td class="text-right">
                                            <div class="table-actions">
                                                <a
                                                    href="<?= esc(site_url('admin/usuarios/editar/' . $itemId)) ?>"
                                                    class="action-icon-btn"
                                                    title="Editar usuario <?= esc($fullName) ?>"
                                                    aria-label="Editar usuario <?= esc($fullName) ?>"
                                                >
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                    <span class="sr-only">Editar</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-footer">
                    <div class="pagination-summary">
                        Mostrando <strong><?= esc((string) $pagination['from']) ?>–<?= esc((string) $pagination['to']) ?></strong>
                        de <strong><?= esc((string) $pagination['total']) ?></strong> usuarios registrados
                    </div>

                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de usuarios">
                            <?php if ($pagination['currentPage'] > 1): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] - 1)) ?>" class="pagination-btn">
                                    Anterior
                                </a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Anterior</span>
                            <?php endif; ?>

                            <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                                <a
                                    href="<?= esc($buildPageUrl($p)) ?>"
                                    class="pagination-page<?= $p === $pagination['currentPage'] ? ' is-current' : '' ?>"
                                    <?= $p === $pagination['currentPage'] ? 'aria-current="page"' : '' ?>
                                >
                                    <?= esc((string) $p) ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] + 1)) ?>" class="pagination-btn">
                                    Siguiente
                                </a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Siguiente</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>

