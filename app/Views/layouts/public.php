<?php
/**
 * Shared layout for public (unauthenticated) pages: landing, catalog and property detail (HU-28).
 *
 * Child views extend it and fill the `content` section; they may also define `scripts`
 * (rendered right before </body>).
 *
 * @var string $pageTitle  Browser tab title
 * @var string $activeNav  Which nav link to highlight: 'inicio' | 'catalogo' | ''
 */
$isLoggedIn   = (bool) session()->get('is_logged_in');
$userRole     = (string) session()->get('user_role');
$isTenant     = $isLoggedIn && $userRole === 'tenant';
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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('css/panel.css')) ?>">
</head>
<body class="panel-page public-catalog-page">
    <header class="public-catalog-header">
        <a class="public-catalog-brand" href="<?= esc(site_url('/')) ?>">InmoGestión</a>
        <nav aria-label="Navegación pública">
            <a href="<?= esc(site_url('/')) ?>"<?= ($activeNav ?? '') === 'inicio' ? ' class="is-active"' : '' ?>>Inicio</a>
            <a href="<?= esc(site_url('catalogo-inmuebles')) ?>"<?= ($activeNav ?? '') === 'catalogo' ? ' class="is-active"' : '' ?>>Propiedades</a>
            <?php if ($isTenant): ?>
                <a href="<?= esc(site_url('inquilino/solicitudes')) ?>">Mis solicitudes</a>
                <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>">Estado de cuenta</a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <a href="<?= esc(site_url(\App\Models\UserModel::homeRouteForRole($userRole))) ?>">Mi panel</a>
                <?php /* HU-03: logout is a POST with CSRF token, also from the public pages. */ ?>
                <form action="<?= esc(site_url('auth/logout')) ?>" method="post" class="public-logout-form">
                    <?= csrf_field() ?>
                    <button type="submit" id="public-logout">Cerrar sesión</button>
                </form>
            <?php else: ?>
                <a href="<?= esc(site_url('auth/login')) ?>">Ingresar</a>
                <a href="<?= esc(site_url('auth/register')) ?>">Registrarse</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="public-catalog-main">
        <?php if (is_string($flashError) && $flashError !== ''): ?>
            <div class="panel-flash panel-flash-danger" role="alert">
                <span><?= esc($flashError) ?></span>
            </div>
        <?php endif; ?>

        <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
            <div class="panel-flash panel-flash-success" role="status">
                <span><?= esc($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>

    <footer class="public-footer">
        <div class="public-footer-inner">
            <span>© 2026 InmoGestión por Nico Tech. Todos los derechos reservados.</span>
            <?php /* HU-32: owners publish from their portal; a visitor is asked to sign in first (prototype AUT-04 footer).
                     Any other signed-in role would only reach an access denied, so the link is left out for them. */ ?>
            <?php if (! $isLoggedIn || $userRole === \App\Models\UserModel::ROLE_OWNER): ?>
                <a href="<?= esc(site_url('propietario/inmuebles/nuevo')) ?>" id="publish-property-link" class="public-footer-link">Publicar mi inmueble</a>
            <?php endif; ?>
            <span>Catálogo · Contratos · Cobranzas · Liquidaciones</span>
        </div>
    </footer>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
