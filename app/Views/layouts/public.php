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
    <link rel="stylesheet" href="<?= esc(base_url('css/public-nav.css')) ?>">
</head>
<body class="panel-page public-catalog-page">
<?= view('partials/public_nav', ['activeNav' => $activeNav ?? '']) ?>

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
