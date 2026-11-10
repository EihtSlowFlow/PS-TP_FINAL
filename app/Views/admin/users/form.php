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
$errors       = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$targetUserId  = $isEdit ? (int) $user['id'] : 0;
$isSelfEdit    = $isEdit && $targetUserId === (int) session()->get('user_id', 0);
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

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/usuarios')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al listado de usuarios</span>
                </a>

                <?php if ($isEdit): ?>
                    <div class="form-header-status-actions">
                        <?php if ($isDeleted): ?>
                            <span class="badge-pill badge-status-deleted">Dado de baja</span>
                        <?php else: ?>
                            <span class="badge-pill badge-status-active">Activo</span>
                        <?php endif; ?>

                        <?php if (! $isDeleted && ! $isSelfEdit): ?>
                            <form
                                action="<?= esc(site_url('admin/usuarios/baja/' . $targetUserId)) ?>"
                                method="post"
                                class="inline-action-form js-deactivate-form"
                                data-user-name="<?= esc($targetFullName) ?>"
                                data-user-role="<?= esc($roleLabels[(string) ($user['role_name'] ?? '')] ?? 'Usuario') ?>"
                            >
                                <?= csrf_field() ?>
                                <?php /* "0" = ask for confirmation on the server; users.js sets "1" once the modal is accepted. */ ?>
                                <input type="hidden" name="confirmed" value="0">
                                <button type="submit" class="btn btn-danger-outline btn-sm" id="btn-deactivate-user">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <line x1="18" y1="8" x2="23" y2="13"/>
                                        <line x1="23" y1="8" x2="18" y2="13"/>
                                    </svg>
                                    <span>Dar de baja usuario</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

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
                <form action="<?= esc($formAction) ?>" method="post" class="user-main-form"<?php if ($isSelfEdit): ?> data-self-edit="1" data-original-role="<?= esc((string) ($user['role_name'] ?? '')) ?>"<?php endif; ?>>
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
                                    minlength="2"
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
                                    minlength="2"
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
                                    minlength="6"
                                    maxlength="20"
                                    pattern="[A-Za-z0-9\.\-\s]+"
                                    title="Solo letras, números, puntos y guiones (mínimo 6 caracteres)"
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
                                    minlength="6"
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
                                        <?php /* <label> only accepts phrasing content (HTML5): keep spans here, never div/p. */ ?>
                                        <span class="role-option-header">
                                            <span class="role-option-title"><?= esc($label) ?></span>
                                            <?php if ($isEdit && ($user['role_name'] ?? '') === $roleKey): ?>
                                                <span class="badge-pill badge-neutral">Actual</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="role-option-desc"><?= esc($description) ?></span>
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
                                        <?php if ($isEdit): ?>
                                            Contraseña nueva
                                        <?php else: ?>
                                            Contraseña inicial <span class="required-mark" aria-hidden="true">*</span>
                                        <?php endif; ?>
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
<?php $this->endSection() ?>

<?php $this->section('modals') ?>
    <?php if ($isSelfEdit): ?>
        <div class="confirm-modal" id="self-role-modal" role="alertdialog" aria-modal="true" aria-labelledby="self-role-title" aria-describedby="self-role-desc" hidden>
            <div class="confirm-modal-dialog">
                <h2 class="confirm-modal-title" id="self-role-title">¿Confirmás el cambio de tu propio rol?</h2>
                <p class="confirm-modal-text" id="self-role-desc">
                    Estás por cambiar el rol de tu propia cuenta. Si presionás Aceptar, tus permisos se actualizarán
                    de inmediato y podrías perder el acceso a la gestión de usuarios. Si ningún otro administrador
                    puede revertirlo, no vas a poder volver a asignarte el rol de Administrador.
                </p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn btn-secondary" id="self-role-cancel">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="self-role-accept" disabled>Aceptar (3)</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isEdit && ! $isDeleted && ! $isSelfEdit): ?>
        <div class="confirm-modal" id="deactivate-user-modal" role="alertdialog" aria-modal="true" aria-labelledby="deactivate-user-title" aria-describedby="deactivate-user-desc" hidden>
            <div class="confirm-modal-dialog">
                <h2 class="confirm-modal-title" id="deactivate-user-title">¿Confirmás la baja lógica del usuario?</h2>
                <p class="confirm-modal-text" id="deactivate-user-desc">
                    El usuario <strong id="deactivate-user-name"><?= esc($targetFullName) ?></strong> (<span id="deactivate-user-role"><?= esc($roleLabels[(string) ($user['role_name'] ?? '')] ?? 'Usuario') ?></span>)
                    pasará a estado inactivo: no podrá iniciar sesión ni ser seleccionado en nuevos contratos, pero se conservarán
                    su registro histórico y sus referencias contables asociadas para consulta y auditoría.
                </p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn btn-secondary" id="deactivate-user-cancel">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="deactivate-user-confirm">Confirmar baja</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/users.js')) ?>"></script>
<?php $this->endSection() ?>
