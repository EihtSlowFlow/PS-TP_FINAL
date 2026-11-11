<?php
/**
 * @var array<string, string> $errors
 */
$errors       = session()->getFlashdata('errors') ?? [];
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');
$selectedRole = old('role', '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/auth.css')) ?>">
</head>
<body class="auth-page register-page">
    <main class="auth-main register-main">
        <section class="register-card" aria-labelledby="register-heading">
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
                <h1 id="register-heading" class="auth-title">Crear cuenta</h1>
                <p class="auth-subtitle">Completá tus datos para registrarte como propietario o inquilino.</p>
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

            <form class="register-form" action="<?= esc(site_url('auth/register')) ?>" method="post">
                <?= csrf_field() ?>

                <div class="field-group">
                    <label for="first_name" class="field-label">Nombre <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="auth-input register-input<?= isset($errors['first_name']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('first_name', '')) ?>" required minlength="2" maxlength="100" autocomplete="given-name"
                        <?= isset($errors['first_name']) ? 'aria-invalid="true" aria-describedby="first_name-error"' : '' ?>>
                    <?php if (isset($errors['first_name'])): ?><span id="first_name-error" class="field-error" role="alert"><?= esc($errors['first_name']) ?></span><?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="last_name" class="field-label">Apellido <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="auth-input register-input<?= isset($errors['last_name']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('last_name', '')) ?>" required minlength="2" maxlength="100" autocomplete="family-name"
                        <?= isset($errors['last_name']) ? 'aria-invalid="true" aria-describedby="last_name-error"' : '' ?>>
                    <?php if (isset($errors['last_name'])): ?><span id="last_name-error" class="field-error" role="alert"><?= esc($errors['last_name']) ?></span><?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="document_number" class="field-label">DNI <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="document_number" name="document_number" class="auth-input register-input<?= isset($errors['document_number']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('document_number', '')) ?>" required minlength="6" maxlength="20" autocomplete="off"
                        aria-describedby="<?= isset($errors['document_number']) ? 'document_number-error document_number-help' : 'document_number-help' ?>"
                        <?= isset($errors['document_number']) ? 'aria-invalid="true"' : '' ?>>
                    <span id="document_number-help" class="field-help">Podés ingresarlo con puntos o guiones.</span>
                    <?php if (isset($errors['document_number'])): ?><span id="document_number-error" class="field-error" role="alert"><?= esc($errors['document_number']) ?></span><?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="email" class="field-label">Correo electrónico <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="auth-input register-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('email', '')) ?>" required maxlength="255" autocomplete="email"
                        <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                    <?php if (isset($errors['email'])): ?><span id="email-error" class="field-error" role="alert"><?= esc($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field-group register-field-full">
                    <label for="phone" class="field-label">Teléfono <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="tel" id="phone" name="phone" class="auth-input register-input<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                        value="<?= esc(old('phone', '')) ?>" required maxlength="30" pattern="\+?[0-9][0-9 ().-]{5,29}"
                        title="Ingresá entre 6 y 15 dígitos. Podés usar +, espacios, paréntesis, puntos y guiones." autocomplete="tel"
                        aria-describedby="<?= isset($errors['phone']) ? 'phone-help phone-error' : 'phone-help' ?>"
                        <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
                    <span id="phone-help" class="field-help">Ingresá entre 6 y 15 dígitos; se permiten formatos internacionales.</span>
                    <?php if (isset($errors['phone'])): ?><span id="phone-error" class="field-error" role="alert"><?= esc($errors['phone']) ?></span><?php endif; ?>
                </div>

                <fieldset class="register-role-fieldset register-field-full<?= isset($errors['role']) ? ' is-invalid' : '' ?>">
                    <legend class="field-label">Quiero registrarme como <span class="required-mark" aria-hidden="true">*</span></legend>
                    <div class="register-role-options">
                        <label class="register-role-option<?= $selectedRole === 'owner' ? ' is-selected' : '' ?>">
                            <input type="radio" name="role" value="owner" required<?= $selectedRole === 'owner' ? ' checked' : '' ?>>
                            <span class="register-role-title">Propietario</span>
                            <span class="register-role-description">Consultá tus inmuebles, contratos y liquidaciones.</span>
                        </label>
                        <label class="register-role-option<?= $selectedRole === 'tenant' ? ' is-selected' : '' ?>">
                            <input type="radio" name="role" value="tenant" required<?= $selectedRole === 'tenant' ? ' checked' : '' ?>>
                            <span class="register-role-title">Inquilino</span>
                            <span class="register-role-description">Consultá tus cuotas e informá tus pagos.</span>
                        </label>
                    </div>
                    <?php if (isset($errors['role'])): ?><span class="field-error" role="alert"><?= esc($errors['role']) ?></span><?php endif; ?>
                </fieldset>

                <div class="field-group">
                    <label for="password" class="field-label">Contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="auth-input register-input auth-input-password<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                            required minlength="8" maxlength="255" autocomplete="new-password" data-password-confirmation
                            <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error password-help"' : 'aria-describedby="password-help"' ?>>
                        <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Mostrar u ocultar contraseña" aria-pressed="false">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <span id="password-help" class="field-help">Debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.</span>
                    <?php if (isset($errors['password'])): ?><span id="password-error" class="field-error" role="alert"><?= esc($errors['password']) ?></span><?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="password_confirm" class="field-label">Repetir contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm" class="auth-input register-input auth-input-password<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
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

                <button type="submit" class="btn-submit register-submit register-field-full">Crear cuenta</button>
            </form>

            <p class="register-login-link">¿Ya tenés cuenta? <a href="<?= esc(site_url('auth/login')) ?>" class="register-link">Ingresá</a></p>
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
