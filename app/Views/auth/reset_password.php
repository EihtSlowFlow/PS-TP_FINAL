<?php
/**
 * New-password form reached through the emailed link (HU-03b).
 *
 * @var string $token
 */
$errors     = session()->getFlashdata('errors') ?? [];
$flashError = session()->getFlashdata('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Nueva contraseña - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/auth.css')) ?>">
</head>
<body class="auth-page register-page">
    <main class="auth-main register-main">
        <section class="register-card" aria-labelledby="reset-heading">
            <header class="register-header">
                <a class="register-brand" href="<?= esc(site_url('auth/login')) ?>" aria-label="InmoGestión, ir al inicio de sesión">
                    <span class="register-brand-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    <span>InmoGestión</span>
                </a>
                <h1 id="reset-heading" class="auth-title">Definir nueva contraseña</h1>
                <p class="auth-subtitle">Elegí una contraseña de al menos 8 caracteres. Este enlace solo puede usarse una vez.</p>
            </header>

            <?php if (is_string($flashError) && $flashError !== ''): ?>
                <div class="flash-alert flash-alert-danger" role="alert">
                    <span><?= esc($flashError) ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="<?= esc(site_url('auth/restablecer-password/' . $token)) ?>" method="post">
                <?= csrf_field() ?>

                <div class="field-group">
                    <label for="password" class="field-label">Nueva contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" class="auth-input auth-input-password<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                            required minlength="8" maxlength="255" autocomplete="new-password" data-password-confirmation
                            <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error password-help"' : 'aria-describedby="password-help"' ?>>
                        <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Mostrar u ocultar contraseña" aria-pressed="false">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <span id="password-help" class="field-help">Usá al menos 8 caracteres.</span>
                    <?php if (isset($errors['password'])): ?><span id="password-error" class="field-error" role="alert"><?= esc($errors['password']) ?></span><?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="password_confirm" class="field-label">Repetir contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password_confirm" name="password_confirm" class="auth-input auth-input-password<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                            required minlength="8" maxlength="255" autocomplete="new-password" data-password-confirmation
                            <?= isset($errors['password_confirm']) ? 'aria-invalid="true" aria-describedby="password_confirm-error"' : '' ?>>
                        <button type="button" class="password-toggle-btn" data-password-toggle="password_confirm" aria-label="Mostrar u ocultar repetición de contraseña" aria-pressed="false">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?><span id="password_confirm-error" class="field-error" role="alert"><?= esc($errors['password_confirm']) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Guardar nueva contraseña</button>
            </form>

            <p class="register-login-link"><a href="<?= esc(site_url('auth/login')) ?>" class="register-link">Volver al inicio de sesión</a></p>
        </section>
    </main>

    <footer class="auth-footer">
        <div class="auth-footer-inner">
            <span>© 2026 InmoGestión por Nico Tech. Todos los derechos reservados.</span>
            <span>Catálogo · Contratos · Cobranzas · Liquidaciones</span>
        </div>
    </footer>

    <script src="<?= esc(base_url('js/auth.js')) ?>" defer></script>
</body>
</html>
