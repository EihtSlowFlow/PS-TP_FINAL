<?php
/**
 * @var string $pageTitle
 * @var string $breadcrumb
 * @var string $activeMenu
 * @var string $heading
 * @var string $subtitle
 * @var string $allowedNote
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
                <?php elseif ($userRole === 'owner'): ?>
                    <a href="<?= esc(site_url('propietario/dashboard')) ?>" class="sidebar-link<?= $activeMenu === 'propietario-dashboard' ? ' is-active' : '' ?>">
                        <span>Dashboard</span>
                    </a>
                <?php elseif ($userRole === 'tenant'): ?>
                    <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>" class="sidebar-link<?= $activeMenu === 'inquilino-estado-cuenta' ? ' is-active' : '' ?>">
                        <span>Estado de cuenta</span>
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

            <section class="panel-card" aria-labelledby="panel-heading">
                <h1 id="panel-heading" class="panel-title"><?= esc($heading) ?></h1>
                <p class="panel-subtitle"><?= esc($subtitle) ?></p>

                <div class="panel-session-summary">
                    <p><strong>Sesión activa:</strong> <?= esc($userName) ?> (<?= esc($userEmail) ?>) · Rol: <strong><?= esc($roleLabel) ?></strong></p>
                    <p><?= esc($allowedNote) ?></p>
                </div>
            </section>
        </main>
    </div>

</body>
</html>

