<?php
/**
 * Request form for password recovery (HU-03b).
 */
$errors       = session()->getFlashdata('errors') ?? [];
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/auth.css')) ?>">
</head>
<body class="auth-page register-page">
    <main class="auth-main register-main">
        <section class="register-card" aria-labelledby="recover-heading">
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
                <h1 id="recover-heading" class="auth-title">Restablecer contraseña</h1>
                <p class="auth-subtitle">Ingresá el correo de tu cuenta para definir una nueva contraseña.</p>
            </header>

            <?php if (is_string($flashError) && $flashError !== ''): ?>
                <div class="flash-alert flash-alert-danger" role="alert">
                    <span><?= esc($flashError) ?></span>
                </div>
            <?php endif; ?>

            <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                <div class="flash-alert flash-alert-success" role="status">
                    <span><?= esc($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="<?= esc(site_url('auth/recuperar-password')) ?>" method="post">
                <?= csrf_field() ?>

                <div class="field-group">
                    <label for="email" class="field-label">Correo electrónico <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="m3 7 9 6 9-6"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" class="auth-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('email', '')) ?>" required maxlength="255" autocomplete="email"
                            <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                    </div>
                    <?php if (isset($errors['email'])): ?><span id="email-error" class="field-error" role="alert"><?= esc($errors['email']) ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Enviar instrucciones</button>
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
