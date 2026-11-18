<?php
/**
 * Top bar of the public pages: landing, catalog and property sheet (layouts/public) and the sign-in and sign-up
 * pages, so a visitor can always go back to the landing. Its styles are in public/css/public-nav.css.
 *
 * @var string $activeNav Which link to highlight: 'inicio' | 'catalogo' | 'ingresar' | 'registrarse' | ''
 */
$activeNav  = (string) ($activeNav ?? '');
$isLoggedIn = (bool) session()->get('is_logged_in');
$userRole   = (string) session()->get('user_role');
$isTenant   = $isLoggedIn && $userRole === \App\Models\UserModel::ROLE_TENANT;

$navLink = static function (string $url, string $label, string $key) use ($activeNav): string {
    $current = $activeNav === $key;

    return '<a href="' . esc($url) . '"' . ($current ? ' class="is-active" aria-current="page"' : '') . '>' . esc($label) . '</a>';
};
?>
    <header class="public-catalog-header">
        <a class="public-catalog-brand" href="<?= esc(site_url('/')) ?>">InmoGestión</a>
        <nav aria-label="Navegación pública">
            <?= $navLink(site_url('/'), 'Inicio', 'inicio') ?>
            <?= $navLink(site_url('catalogo-inmuebles'), 'Propiedades', 'catalogo') ?>
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
                <?= $navLink(site_url('auth/login'), 'Ingresar', 'ingresar') ?>
                <?= $navLink(site_url('auth/register'), 'Registrarse', 'registrarse') ?>
            <?php endif; ?>
        </nav>
    </header>
