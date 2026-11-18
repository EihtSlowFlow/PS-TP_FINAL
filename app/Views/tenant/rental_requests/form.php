<?php
/**
 * Tenant form to request the rental of an available property (HU-30).
 *
 * @var array<string, mixed> $property          Public property fields from RentalRequestModel::findRequestableProperty()
 * @var string               $today             Current date ("YYYY-MM-DD")
 * @var string               $defaultPhone      Phone number from the authenticated tenant's profile
 * @var bool                 $hasPendingRequest Whether the tenant already has a pending request on this property
 */
use App\Models\PropertyModel;
use App\Models\RentalRequestModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatDate = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';
};

$errors      = session()->getFlashdata('errors') ?? [];
$propertyId  = (int) $property['id'];
$typeLabel   = PropertyModel::labelForType((string) $property['type']);
$startValue  = (string) old('desired_start_date', $today);
$monthsValue = (string) old('desired_months', '24');
$phoneValue  = (string) old('contact_phone', $defaultPhone);
$msgValue    = (string) old('message', '');

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al catálogo de inmuebles</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="page-heading-row">
                        <h1 class="panel-title">Solicitar alquiler</h1>
                        <span class="badge-pill badge-property-available">Disponible · <?= esc($typeLabel) ?></span>
                    </div>
                    <p class="panel-subtitle">
                        Indicá la fecha de inicio, el plazo deseado y tu teléfono de contacto para que la administración evalúe tu solicitud.
                        Enviar la solicitud no reserva el inmueble.
                    </p>
                </div>
            </section>

            <?php if ($hasPendingRequest): ?>
                <div class="panel-flash panel-flash-warning" role="status" id="pending-request-notice">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>
                        Ya tenés una solicitud pendiente para este inmueble. Podés seguir su estado o cancelarla en
                        <a href="<?= esc(site_url('inquilino/solicitudes')) ?>" class="link-button">Mis solicitudes</a>.
                    </span>
                </div>
            <?php endif; ?>

            <div class="form-layout has-sidebar">
                <div class="form-main">
                    <form action="<?= esc(site_url('inquilino/inmuebles/' . $propertyId . '/solicitar')) ?>"
                          method="post"
                          class="panel-card form-section-card"
                          id="rental-request-form"
                          novalidate>
                        <?= csrf_field() ?>

                        <div class="section-header">
                            <h2 class="section-title">Condiciones deseadas</h2>
                            <p class="section-subtitle">
                                <?= esc((string) $property['address']) ?>, <?= esc((string) $property['city']) ?>
                            </p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label for="desired_start_date" class="form-label">Fecha de inicio deseada <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="date"
                                       id="desired_start_date"
                                       name="desired_start_date"
                                       min="<?= esc($today) ?>"
                                       value="<?= esc($startValue) ?>"
                                       class="form-control<?= isset($errors['desired_start_date']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['desired_start_date']) ? 'true' : 'false' ?>"
                                       required>
                                <span class="field-help">No puede ser anterior a hoy (<?= esc($formatDate($today)) ?>).</span>
                                <?php if (isset($errors['desired_start_date'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['desired_start_date']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="desired_months" class="form-label">Plazo deseado (meses) <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="number"
                                       id="desired_months"
                                       name="desired_months"
                                       min="1"
                                       max="<?= esc((string) RentalRequestModel::DESIRED_MONTHS_MAX) ?>"
                                       step="1"
                                       value="<?= esc($monthsValue) ?>"
                                       class="form-control<?= isset($errors['desired_months']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['desired_months']) ? 'true' : 'false' ?>"
                                       required>
                                <span class="field-help">Número entero positivo de meses (por ejemplo, 12, 24 o 36).</span>
                                <?php if (isset($errors['desired_months'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['desired_months']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="contact_phone" class="form-label">Teléfono de contacto <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="tel"
                                       id="contact_phone"
                                       name="contact_phone"
                                       maxlength="<?= esc((string) RentalRequestModel::CONTACT_PHONE_MAX_LENGTH) ?>"
                                       placeholder="Ej: +54 9 299 412-3456"
                                       value="<?= esc($phoneValue) ?>"
                                       class="form-control<?= isset($errors['contact_phone']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['contact_phone']) ? 'true' : 'false' ?>"
                                       required>
                                <span class="field-help">Precargado desde tu perfil. La administración te contactará a este número.</span>
                                <?php if (isset($errors['contact_phone'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['contact_phone']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="message" class="form-label">Mensaje (opcional)</label>
                                <textarea id="message"
                                          name="message"
                                          rows="4"
                                          maxlength="<?= esc((string) RentalRequestModel::MESSAGE_MAX_LENGTH) ?>"
                                          placeholder="Consultas o comentarios adicionales para la administración (opcional, hasta 500 caracteres)."
                                          class="form-control<?= isset($errors['message']) ? ' is-invalid' : '' ?>"
                                          aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= esc($msgValue) ?></textarea>
                                <span class="field-help">Opcional · Máximo <?= esc((string) RentalRequestModel::MESSAGE_MAX_LENGTH) ?> caracteres.</span>
                                <?php if (isset($errors['message'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['message']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary" id="btn-submit-rental-request">Enviar solicitud</button>
                        </div>
                    </form>
                </div>

                <aside class="form-aside">
                    <section class="panel-card form-section-card" aria-labelledby="section-property-summary">
                        <div class="section-header">
                            <h2 id="section-property-summary" class="section-title">Resumen del inmueble</h2>
                            <p class="section-subtitle"><?= esc($typeLabel) ?> disponible</p>
                        </div>

                        <dl class="breakdown-list">
                            <div class="breakdown-row">
                                <dt>Dirección</dt>
                                <dd><?= esc((string) $property['address']) ?></dd>
                            </div>
                            <div class="breakdown-row">
                                <dt>Ciudad</dt>
                                <dd><?= esc((string) $property['city']) ?></dd>
                            </div>
                            <?php if ($property['rooms'] !== null): ?>
                                <div class="breakdown-row">
                                    <dt>Ambientes</dt>
                                    <dd><?= esc((string) $property['rooms']) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($property['surface'] !== null): ?>
                                <div class="breakdown-row">
                                    <dt>Superficie</dt>
                                    <dd><?= esc((string) $property['surface']) ?> m²</dd>
                                </div>
                            <?php endif; ?>
                            <?php if (! empty($property['included_services'])): ?>
                                <div class="breakdown-row">
                                    <dt>Servicios</dt>
                                    <dd><?= esc((string) $property['included_services']) ?></dd>
                                </div>
                            <?php endif; ?>
                            <div class="breakdown-row breakdown-total">
                                <dt>Alquiler mensual base</dt>
                                <dd><?= esc($formatMoney($property['base_rent'])) ?></dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>

