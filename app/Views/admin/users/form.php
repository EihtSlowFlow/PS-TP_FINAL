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
 * @var string $mode
 * @var array<string, mixed>|null $user
 * @var array{contracts_count: int, installments_count: int, payments_count: int, latest_contract: array<string, mixed>|null}|null $operationalStats
 * @var array<string, string> $roleLabels
 * @var array<string, string> $roleDescriptions
 */
$isEdit       = $mode === 'edit' && $user !== null;
$flashError   = session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('success');
$errors       = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$targetUserId  = $isEdit ? (int) $user['id'] : 0;
$formAction    = $isEdit
    ? site_url('admin/usuarios/editar/' . $targetUserId)
    : site_url('admin/usuarios');

$firstNameValue = (string) old('first_name', $isEdit ? (string) ($user['first_name'] ?? '') : '');
$lastNameValue  = (string) old('last_name', $isEdit ? (string) ($user['last_name'] ?? '') : '');
$dniValue       = (string) old('document_number', $isEdit ? (string) ($user['document_number'] ?? '') : '');
$emailValue     = (string) old('email', $isEdit ? (string) ($user['email'] ?? '') : '');
$phoneValue     = (string) old('phone', $isEdit ? (string) ($user['phone'] ?? '') : '');
$addressValue   = (string) old('address', $isEdit ? (string) ($user['address'] ?? '') : '');
$selectedRole   = (string) old('role', $isEdit ? (string) ($user['role_name'] ?? 'tenant') : 'tenant');

$targetFullName = $isEdit ? trim(((string) $user['first_name']) . ' ' . ((string) $user['last_name'])) : '';
$isDeleted      = $isEdit && ! empty($user['deleted_at']);
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
                <a href="<?= esc(site_url('admin/usuarios')) ?>" class="breadcrumb-link">Usuarios</a>
                <span aria-hidden="true">/</span>
                <span class="breadcrumb-current"><?= esc($isEdit ? 'Editar usuario' : 'Nuevo usuario') ?></span>
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
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/usuarios')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al listado de usuarios</span>
                </a>

                <?php if ($isEdit): ?>
                    <div>
                        <?php if ($isDeleted): ?>
                            <span class="badge-pill badge-status-deleted">Dado de baja</span>
                        <?php else: ?>
                            <span class="badge-pill badge-status-active">Activo</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

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

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <?php if ($isEdit): ?>
                        <div class="users-heading-row">
                            <h1 class="panel-title">Editar usuario: <?= esc($targetFullName) ?></h1>
                            <span class="badge-pill badge-neutral">ID #USR-<?= esc((string) $targetUserId) ?></span>
                        </div>
                        <p class="panel-subtitle">Modificá los datos identificatorios, perfil y permisos de acceso del usuario en el sistema.</p>
                    <?php else: ?>
                        <h1 class="panel-title">Nuevo usuario</h1>
                        <p class="panel-subtitle">Registrá un nuevo cliente u operador seleccionando su rol y contraseña inicial.</p>
                    <?php endif; ?>
                </div>
            </section>

            <div class="user-form-layout <?= $isEdit ? 'has-sidebar' : '' ?>">
                <form action="<?= esc($formAction) ?>" method="post" class="user-main-form" novalidate>
                    <?= csrf_field() ?>

                    <section class="panel-card form-section-card" aria-labelledby="section-personal-data">
                        <div class="section-header">
                            <h2 id="section-personal-data" class="section-title">Datos personales</h2>
                            <p class="section-subtitle">Información de contacto y datos identificatorios del usuario.</p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label for="first_name" class="form-label">
                                    <span>Nombre <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    value="<?= esc($firstNameValue) ?>"
                                    required
                                    maxlength="100"
                                    class="form-control<?= isset($errors['first_name']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['first_name']) ? 'true' : 'false' ?>"
                                >
                                <?php if (isset($errors['first_name'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['first_name']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="last_name" class="form-label">
                                    <span>Apellido <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    value="<?= esc($lastNameValue) ?>"
                                    required
                                    maxlength="100"
                                    class="form-control<?= isset($errors['last_name']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['last_name']) ? 'true' : 'false' ?>"
                                >
                                <?php if (isset($errors['last_name'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['last_name']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="document_number" class="form-label">
                                    <span>DNI <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    id="document_number"
                                    name="document_number"
                                    value="<?= esc($dniValue) ?>"
                                    required
                                    maxlength="20"
                                    class="form-control<?= isset($errors['document_number']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['document_number']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Formato estándar argentino sin puntos ni guiones</span>
                                <?php if (isset($errors['document_number'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['document_number']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="email" class="form-label">
                                    <span>Correo electrónico <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= esc($emailValue) ?>"
                                    required
                                    maxlength="255"
                                    class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Nombre de usuario para iniciar sesión en el portal</span>
                                <?php if (isset($errors['email'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['email']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="phone" class="form-label">
                                    <span>Teléfono <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?= esc($phoneValue) ?>"
                                    required
                                    maxlength="30"
                                    class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Canal prioritario de contacto</span>
                                <?php if (isset($errors['phone'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['phone']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <div class="form-label-row">
                                    <label for="address" class="form-label">Dirección legal</label>
                                    <span class="optional-tag">Opcional</span>
                                </div>
                                <input
                                    type="text"
                                    id="address"
                                    name="address"
                                    value="<?= esc($addressValue) ?>"
                                    maxlength="255"
                                    class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['address']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Domicilio constituido para notificaciones</span>
                                <?php if (isset($errors['address'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['address']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-access-roles">
                        <div class="section-header">
                            <h2 id="section-access-roles" class="section-title">Acceso y permisos</h2>
                            <p class="section-subtitle">Configuración de rol asignado y credenciales de acceso.</p>
                        </div>

                        <fieldset class="role-selector-fieldset">
                            <legend class="form-label">
                                Rol asignado en la plataforma <span class="required-mark" aria-hidden="true">*</span>
                            </legend>

                            <div class="role-cards-grid" id="role-selector-container">
                                <?php foreach ($roleLabels as $roleKey => $label): ?>
                                    <?php
                                    $isChecked   = $selectedRole === $roleKey;
                                    $description = $roleDescriptions[$roleKey] ?? '';
                                    ?>
                                    <label class="role-option-card<?= $isChecked ? ' is-selected' : '' ?>" for="role_<?= esc($roleKey) ?>">
                                        <input
                                            type="radio"
                                            id="role_<?= esc($roleKey) ?>"
                                            name="role"
                                            value="<?= esc($roleKey) ?>"
                                            class="role-radio-input"
                                            <?= $isChecked ? 'checked' : '' ?>
                                        >
                                        <div class="role-option-header">
                                            <span class="role-option-title"><?= esc($label) ?></span>
                                            <?php if ($isEdit && ($user['role_name'] ?? '') === $roleKey): ?>
                                                <span class="badge-pill badge-neutral">Actual</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="role-option-desc"><?= esc($description) ?></p>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <?php if (isset($errors['role'])): ?>
                                <p class="field-error" role="alert"><?= esc((string) $errors['role']) ?></p>
                            <?php endif; ?>
                        </fieldset>

                        <div class="password-box">
                            <div class="password-box-header">
                                <div>
                                    <label for="password_field" class="form-label">
                                        <?= $isEdit ? 'Contraseña nueva' : 'Contraseña inicial *' ?>
                                    </label>
                                    <p class="field-help">
                                        <?= $isEdit
                                            ? 'Dejá en blanco para no modificar la contraseña actual (mínimo 8 caracteres si deseás cambiarla).'
                                            : 'Mínimo 8 caracteres. Se almacenará cifrada con BCRYPT.' ?>
                                    </p>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-suggest-password">
                                    <span><?= $isEdit ? 'Sugerir nueva' : 'Sugerir contraseña' ?></span>
                                </button>
                            </div>

                            <div class="password-input-group">
                                <input
                                    type="password"
                                    id="password_field"
                                    name="password"
                                    value=""
                                    <?= $isEdit ? '' : 'required' ?>
                                    minlength="8"
                                    maxlength="255"
                                    placeholder="<?= $isEdit ? '•••••••• (sin cambios)' : 'Ingresá al menos 8 caracteres' ?>"
                                    class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>"
                                >
                                <button
                                    type="button"
                                    class="btn-password-toggle"
                                    id="btn-toggle-password"
                                    aria-label="Mostrar u ocultar contraseña"
                                >
                                    Mostrar
                                </button>
                            </div>

                            <?php if (isset($errors['password'])): ?>
                                <p class="field-error" role="alert"><?= esc((string) $errors['password']) ?></p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('admin/usuarios')) ?>" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btn-save-user">
                            <span><?= $isEdit ? 'Guardar cambios' : 'Guardar usuario' ?></span>
                        </button>
                    </div>
                </form>

                <?php if ($isEdit): ?>
                    <aside class="user-audit-sidebar" aria-label="Trazabilidad y contexto operativo">
                        <section class="panel-card audit-card">
                            <h3 class="section-title">Registro y trazabilidad</h3>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Estado en el padrón</dt>
                                    <dd>
                                        <?php if ($isDeleted): ?>
                                            <span class="badge-pill badge-status-deleted">Dado de baja</span>
                                        <?php else: ?>
                                            <span class="badge-pill badge-status-active">Activo</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div class="audit-item">
                                    <dt>Creado el</dt>
                                    <dd><?= esc(! empty($user['created_at']) ? date('d/m/Y H:i', strtotime((string) $user['created_at'])) : '—') ?></dd>
                                </div>
                                <div class="audit-item">
                                    <dt>Última modificación</dt>
                                    <dd><?= esc(! empty($user['updated_at']) ? date('d/m/Y H:i', strtotime((string) $user['updated_at'])) : '—') ?></dd>
                                </div>
                                <?php if ($isDeleted): ?>
                                    <div class="audit-item">
                                        <dt>Fecha de baja lógica</dt>
                                        <dd><?= esc(date('d/m/Y H:i', strtotime((string) $user['deleted_at']))) ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </section>

                        <?php if ($operationalStats !== null): ?>
                            <section class="panel-card audit-card">
                                <h3 class="section-title">Contexto operativo e historial</h3>
                                <p class="section-subtitle">Registros históricos asociados (conservados para consulta y auditoría).</p>

                                <dl class="audit-list">
                                    <div class="audit-item">
                                        <dt>Contratos asociados</dt>
                                        <dd><strong><?= esc((string) $operationalStats['contracts_count']) ?></strong></dd>
                                    </div>
                                    <div class="audit-item">
                                        <dt>Cuotas emitidas</dt>
                                        <dd><strong><?= esc((string) $operationalStats['installments_count']) ?></strong></dd>
                                    </div>
                                    <div class="audit-item">
                                        <dt>Pagos registrados</dt>
                                        <dd><strong><?= esc((string) $operationalStats['payments_count']) ?></strong></dd>
                                    </div>
                                </dl>

                                <?php if ($operationalStats['latest_contract'] !== null): ?>
                                    <?php
                                    $contract       = $operationalStats['latest_contract'];
                                    $contractStatus = match ((string) ($contract['status'] ?? '')) {
                                        'in_force'  => 'Vigente',
                                        'finished'  => 'Finalizado',
                                        'rescinded' => 'Rescindido',
                                        default     => (string) ($contract['status'] ?? ''),
                                    };
                                    ?>
                                    <div class="latest-contract-box">
                                        <span class="latest-contract-label">Último contrato vinculado</span>
                                        <strong class="latest-contract-id">
                                            #CTO-<?= esc((string) $contract['id']) ?> (<?= esc($contractStatus) ?>)
                                        </strong>
                                        <?php if (! empty($contract['property_address'])): ?>
                                            <span class="latest-contract-address">
                                                Inmueble: <?= esc((string) $contract['property_address']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                    </aside>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= esc(base_url('js/users.js')) ?>"></script>
</body>
</html>

