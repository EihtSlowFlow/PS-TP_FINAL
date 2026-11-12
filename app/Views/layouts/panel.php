<?php
/**
 * Shared layout of every authenticated panel page (sidebar, topbar, flash messages).
 *
 * Child views extend it and fill the `content` section; they may also define `modals`
 * (rendered after the page wrapper) and `scripts` (rendered right before </body>).
 *
 * @var string $pageTitle
 * @var string $breadcrumb
 * @var string $activeMenu
 * @var string $userName
 * @var string $userEmail
 * @var string $userRole
 * @var string $roleLabel
 * @var string $initials
 */
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');
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
                <?php if ($userRole === 'admin' || $userRole === 'employee'): ?>
                    <a href="<?= esc(site_url('admin/dashboard')) ?>" class="sidebar-link<?= $activeMenu === 'dashboard' ? ' is-active' : '' ?>">
                        <span>Dashboard</span>
                    </a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="<?= esc(site_url('admin/usuarios')) ?>" class="sidebar-link<?= $activeMenu === 'usuarios' ? ' is-active' : '' ?>">
                            <span>Usuarios</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="sidebar-link<?= $activeMenu === 'inmuebles' ? ' is-active' : '' ?>">
                        <span>Inmuebles</span>
                    </a>
                    <a href="<?= esc(site_url('admin/contratos')) ?>" class="sidebar-link<?= $activeMenu === 'contratos' ? ' is-active' : '' ?>">
                        <span>Contratos</span>
                    </a>
                <?php elseif ($userRole === 'owner'): ?>
                    <a href="<?= esc(site_url('propietario/dashboard')) ?>" class="sidebar-link<?= $activeMenu === 'propietario-dashboard' ? ' is-active' : '' ?>">
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= esc(site_url('propietario/inmuebles')) ?>" class="sidebar-link<?= $activeMenu === 'propietario-inmuebles' ? ' is-active' : '' ?>">
                        <span>Mis inmuebles</span>
                    </a>
                    <a href="<?= esc(site_url('propietario/contratos')) ?>" class="sidebar-link<?= $activeMenu === 'propietario-contratos' ? ' is-active' : '' ?>">
                        <span>Mis contratos</span>
                    </a>
                    <a href="<?= esc(site_url('perfil')) ?>" class="sidebar-link<?= $activeMenu === 'perfil' ? ' is-active' : '' ?>">
                        <span>Mi perfil</span>
                    </a>
                <?php elseif ($userRole === 'tenant'): ?>
                    <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>" class="sidebar-link<?= $activeMenu === 'inquilino-estado-cuenta' ? ' is-active' : '' ?>">
                        <span>Estado de cuenta</span>
                    </a>
                    <a href="<?= esc(site_url('perfil')) ?>" class="sidebar-link<?= $activeMenu === 'perfil' ? ' is-active' : '' ?>">
                        <span>Mi perfil</span>
                    </a>
                <?php endif; ?>
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

                <?php /* HU-03: logout is a POST with CSRF token; available from every panel through this layout. */ ?>
                <form action="<?= esc(site_url('auth/logout')) ?>" method="post" class="logout-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="action-icon-btn action-icon-btn-danger" id="btn-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
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

            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <?= $this->renderSection('modals') ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
