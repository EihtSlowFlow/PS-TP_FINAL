<?php
/**
 * @var array<string, string> $errors
 * @var bool $showRegisterLink Whether the registration route (HU-02) is available
 * @var bool $showRecoverLink  Whether the password recovery route (HU-03b) is available
 */
$showRegisterLink = $showRegisterLink ?? false;
$showRecoverLink  = $showRecoverLink ?? false;
$errors       = session()->getFlashdata('errors') ?? [];
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');
$flashWarning = session()->getFlashdata('warning');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/auth.css')) ?>">
</head>
<body class="auth-page">

    <main class="auth-main">
        <div class="auth-shell">

            <!-- Left Column: Indigo Branding and Key Capabilities -->
            <section class="auth-brand" aria-label="Información de la plataforma">
                <div class="auth-brand-top">
                    <div class="brand-logo">
                        <div class="brand-logo-icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </div>
                        <div>
                            <span class="brand-logo-title">InmoGestión</span>
                            <span class="brand-logo-subtitle">Plataforma Inmobiliaria 2026</span>
                        </div>
                    </div>

                    <div class="brand-pill">
                        <span class="brand-pill-dot" aria-hidden="true"></span>
                        <span>Operaciones en tiempo real</span>
                    </div>

                    <h1 class="brand-headline">Gestión de alquileres, simple y transparente</h1>
                    <p class="brand-lead">
                        Centralizá contratos, cobranzas y liquidaciones con precisión contable y seguimiento paso a paso.
                    </p>

                    <ul class="brand-features">
                        <li class="brand-feature-item">
                            <div class="brand-feature-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <path d="m9 16 2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="brand-feature-title">Cuotas y mora automáticas</h2>
                                <p class="brand-feature-desc">
                                    Cálculo exacto por índice contractual y recargos puntuales sin errores manuales.
                                </p>
                            </div>
                        </li>

                        <li class="brand-feature-item">
                            <div class="brand-feature-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="brand-feature-title">Pagos con comprobante</h2>
                                <p class="brand-feature-desc">
                                    Carga directa del inquilino y validación en un clic por la administración.
                                </p>
                            </div>
                        </li>

                        <li class="brand-feature-item">
                            <div class="brand-feature-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M16 8h-4a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                                    <line x1="12" y1="6" x2="12" y2="18"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="brand-feature-title">Liquidaciones claras</h2>
                                <p class="brand-feature-desc">
                                    Rendición inmediata a propietarios discriminando comisión y montos netos.
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="auth-brand-footer">
                    <span>InmoGestión · Sistema Integral</span>
                    <span>Edición 2026</span>
                </div>
            </section>

            <!-- Right Column: Login Card -->
            <section class="auth-form-panel" aria-labelledby="login-heading">
                <div class="auth-form-container">

                    <div class="auth-header">
                        <div class="auth-header-meta">
                            <span class="auth-portal-badge">Acceso al Portal</span>
                            <span class="auth-security-note">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                Acceso protegido
                            </span>
                        </div>
                        <h2 id="login-heading" class="auth-title">Ingresar a InmoGestión</h2>
                        <p class="auth-subtitle">
                            Ingresá tus credenciales para acceder a tu panel según tus permisos.
                        </p>
                    </div>

                    <?php if (is_string($flashError) && $flashError !== ''): ?>
                        <div class="flash-alert flash-alert-danger" role="alert">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span><?= esc($flashError) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (is_string($flashWarning) && $flashWarning !== ''): ?>
                        <div class="flash-alert flash-alert-warning" role="alert">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span><?= esc($flashWarning) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
                        <div class="flash-alert flash-alert-success" role="status">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <span><?= esc($flashSuccess) ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="auth-form" action="<?= esc(site_url('auth/login')) ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="email" class="field-label">
                                    Correo electrónico <span class="required-mark" aria-hidden="true">*</span>
                                </label>
                            </div>
                            <div class="input-wrapper">
                                <span class="input-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                    </svg>
                                </span>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    required
                                    autocomplete="email"
                                    placeholder="ejemplo@inmogestion.com.ar"
                                    value="<?= esc(old('email', '')) ?>"
                                    class="auth-input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                    <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
                                >
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span id="email-error" class="field-error">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    <span><?= esc($errors['email']) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="password" class="field-label">
                                    Contraseña <span class="required-mark" aria-hidden="true">*</span>
                                </label>
                                <?php if ($showRecoverLink): ?>
                                    <a href="<?= esc(site_url('auth/recuperar-password')) ?>" class="forgot-link">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="input-wrapper">
                                <span class="input-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••••••"
                                    value=""
                                    class="auth-input auth-input-password<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                                    <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                                >
                                <button
                                    type="button"
                                    class="password-toggle-btn"
                                    data-password-toggle="password"
                                    aria-label="Mostrar u ocultar contraseña"
                                    aria-pressed="false"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span id="password-error" class="field-error">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    <span><?= esc($errors['password']) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-submit">
                            <span>Ingresar</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14"/>
                                <path d="m12 5 7 7-7 7"/>
                            </svg>
                        </button>
                    </form>

                    <?php if ($showRegisterLink): ?>
                        <div class="auth-register-box">
                            <span>¿No tenés cuenta?</span>
                            <a href="<?= esc(site_url('auth/register')) ?>" class="register-link">Registrate</a>
                        </div>
                    <?php endif; ?>

                    <div class="roles-summary-box" aria-label="Roles del sistema">
                        <p class="roles-summary-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <span>Acceso unificado por rol</span>
                        </p>
                        <div class="roles-summary-grid">
                            <div class="role-chip">
                                <span class="role-chip-name">Administrador</span>
                                <span class="role-chip-desc">Control total</span>
                            </div>
                            <div class="role-chip">
                                <span class="role-chip-name">Empleado</span>
                                <span class="role-chip-desc">Operaciones</span>
                            </div>
                            <div class="role-chip">
                                <span class="role-chip-name">Propietario</span>
                                <span class="role-chip-desc">Rendiciones</span>
                            </div>
                            <div class="role-chip">
                                <span class="role-chip-name">Inquilino</span>
                                <span class="role-chip-desc">Cuotas y pagos</span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

        </div>
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

