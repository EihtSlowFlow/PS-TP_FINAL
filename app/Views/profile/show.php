<?php
/**
 * Profile view for an authenticated owner or tenant (HU-06).
 *
 * @var array{
 *     first_name: string,
 *     last_name: string,
 *     document_number: string,
 *     email: string,
 *     phone: mixed,
 *     address: mixed,
 *     role_label: string
 * } $profile
 */
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$fullName      = trim($profile['first_name'] . ' ' . $profile['last_name']);
$phoneValue    = (string) old('phone', (string) ($profile['phone'] ?? ''));
$addressValue  = (string) old('address', (string) ($profile['address'] ?? ''));
$initials      = mb_strtoupper(mb_substr($profile['first_name'], 0, 1) . mb_substr($profile['last_name'], 0, 1));
$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <header class="profile-page-header">
                <div>
                    <h1 class="panel-title">Mi perfil <span class="profile-year-badge">2026</span></h1>
                    <p class="panel-subtitle">Gestioná tus datos personales de contacto y credenciales de acceso al portal.</p>
                </div>
                <span class="profile-role-badge"><?= esc($profile['role_label']) ?></span>
            </header>

            <section class="panel-card profile-card" aria-labelledby="profile-details-title">
                <header class="profile-card-header">
                    <div>
                        <h2 id="profile-details-title" class="section-title">Datos personales</h2>
                        <p class="section-subtitle">Información identificatoria registrada ante la administración inmobiliaria.</p>
                    </div>
                    <span class="profile-card-tag">Cuenta activa</span>
                </header>

                <form action="<?= esc(site_url('perfil/contacto')) ?>" method="post" class="profile-form">
                    <?= csrf_field() ?>

                    <div class="profile-user-summary">
                        <span class="profile-avatar" aria-hidden="true"><?= esc($initials) ?></span>
                        <div>
                            <strong><?= esc($fullName) ?></strong>
                            <span><?= esc($profile['role_label']) ?> · Cuenta activa</span>
                        </div>
                    </div>

                    <div class="profile-details-grid">
                        <div class="form-field">
                            <label for="profile_name" class="form-label">Nombre y apellido <span class="profile-protected-label">Protegido</span></label>
                            <input id="profile_name" type="text" value="<?= esc($fullName) ?>" readonly class="form-control profile-readonly">
                            <span class="field-help">Para modificarlo, contactá a la inmobiliaria.</span>
                        </div>

                        <div class="form-field">
                            <label for="profile_document" class="form-label">DNI / Identificación fiscal <span class="profile-protected-label">Protegido</span></label>
                            <input id="profile_document" type="text" value="<?= esc($profile['document_number']) ?>" readonly class="form-control profile-readonly">
                            <span class="field-help">Para modificarlo, contactá a la inmobiliaria.</span>
                        </div>

                        <div class="form-field">
                            <label for="profile_email" class="form-label">Correo electrónico <span class="profile-protected-label">Usuario de acceso</span></label>
                            <input id="profile_email" type="email" value="<?= esc($profile['email']) ?>" readonly class="form-control profile-readonly">
                            <span class="field-help">Para modificarlo, contactá a la inmobiliaria.</span>
                        </div>

                        <div class="form-field">
                            <label for="phone" class="form-label">Teléfono <span class="profile-editable-label">Editable</span></label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= esc($phoneValue) ?>"
                                maxlength="30"
                                pattern="\+?[0-9][0-9 ().-]{5,29}"
                                title="Ingresá entre 6 y 15 dígitos. Podés usar +, espacios, paréntesis, puntos y guiones."
                                autocomplete="tel"
                                class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>"
                                <?php if (isset($errors['phone'])): ?>aria-describedby="phone-help phone-error"<?php else: ?>aria-describedby="phone-help"<?php endif; ?>
                            >
                            <span id="phone-help" class="field-help">Número principal de contacto. Usá entre 6 y 15 dígitos.</span>
                            <?php if (isset($errors['phone'])): ?>
                                <p id="phone-error" class="field-error" role="alert"><?= esc((string) $errors['phone']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-field profile-field-full">
                            <label for="address" class="form-label">Dirección de correspondencia <span class="profile-editable-label">Editable</span></label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= esc($addressValue) ?>"
                                maxlength="255"
                                autocomplete="street-address"
                                class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['address']) ? 'true' : 'false' ?>"
                                <?php if (isset($errors['address'])): ?>aria-describedby="address-error"<?php endif; ?>
                            >
                            <span class="field-help">Domicilio constituido para notificaciones.</span>
                            <?php if (isset($errors['address'])): ?>
                                <p id="address-error" class="field-error" role="alert"><?= esc((string) $errors['address']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <footer class="profile-card-actions">
                        <p>Los cambios en datos de contacto se actualizarán de forma inmediata en el sistema.</p>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </footer>
                </form>
            </section>

            <section class="panel-card profile-card profile-password-card" aria-labelledby="password-title">
                <header class="profile-card-header">
                    <div>
                        <h2 id="password-title" class="section-title">Cambiar contraseña</h2>
                        <p class="section-subtitle">Actualizá tu clave de seguridad para proteger tu cuenta.</p>
                    </div>
                    <span class="profile-card-tag profile-card-tag-secure">Autenticación segura</span>
                </header>

                <form action="<?= esc(site_url('perfil/contrasena')) ?>" method="post" class="profile-form" data-profile-password-form>
                    <?= csrf_field() ?>

                    <div class="profile-security-notice" role="note">
                        <strong>Seguridad de la sesión</strong>
                        <span>Tu sesión actual seguirá activa luego de cambiar la clave.</span>
                    </div>

                    <div class="form-field profile-current-password">
                        <label for="current_password" class="form-label">Contraseña actual <span class="required-mark" aria-hidden="true">*</span></label>
                        <div class="profile-password-input">
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                required
                                maxlength="255"
                                autocomplete="current-password"
                                class="form-control<?= isset($errors['current_password']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['current_password']) ? 'true' : 'false' ?>"
                                <?php if (isset($errors['current_password'])): ?>aria-describedby="current-password-help current-password-error"<?php else: ?>aria-describedby="current-password-help"<?php endif; ?>
                            >
                        </div>
                        <span id="current-password-help" class="field-help">Ingresá la clave que utilizás actualmente para iniciar sesión.</span>
                        <?php if (isset($errors['current_password'])): ?>
                            <p id="current-password-error" class="field-error" role="alert"><?= esc((string) $errors['current_password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="profile-password-grid">
                        <div class="form-field">
                            <label for="new_password" class="form-label">Nueva contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                            <div class="profile-password-input">
                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    required
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    data-password-confirmation
                                    class="form-control<?= isset($errors['new_password']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['new_password']) ? 'true' : 'false' ?>"
                                    aria-describedby="<?= isset($errors['new_password']) ? 'password-policy new-password-error' : 'password-policy' ?>"
                                >
                            </div>
                            <span id="password-policy" class="profile-password-policy">Debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo; además, debe ser distinta de la actual.</span>
                            <?php if (isset($errors['new_password'])): ?>
                                <p id="new-password-error" class="field-error" role="alert"><?= esc((string) $errors['new_password']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="password_confirm" class="form-label">Repetir nueva contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                            <div class="profile-password-input">
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    required
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    data-password-confirmation
                                    class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['password_confirm']) ? 'true' : 'false' ?>"
                                    <?php if (isset($errors['password_confirm'])): ?>aria-describedby="password-confirm-help password-confirm-error"<?php else: ?>aria-describedby="password-confirm-help"<?php endif; ?>
                                >
                            </div>
                            <span id="password-confirm-help" class="field-help">Volvé a escribir exactamente la misma clave.</span>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <p id="password-confirm-error" class="field-error" role="alert"><?= esc((string) $errors['password_confirm']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <footer class="profile-card-actions">
                        <p>La contraseña se guarda cifrada y nunca se muestra en pantalla.</p>
                        <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                    </footer>
                </form>
            </section>

            <aside class="profile-admin-note">
                <strong>¿Necesitás cambiar datos de titularidad o DNI?</strong>
                <span>Por regulaciones contractuales, las modificaciones registrales requieren documentación respaldatoria vía administración.</span>
            </aside>
<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/auth.js')) ?>" defer></script>
<?php $this->endSection() ?>
<?php $this->endSection() ?>
