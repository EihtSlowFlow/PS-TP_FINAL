<?php
/**
 * New contract form (screen ADM-07, HU-10).
 *
 * @var list<array{id: int, label: string, address: string, city: string, type: string, rooms: int|null, base_rent: string, owner_name: string, owner_dni: string}> $properties Available properties
 * @var list<array{id: int, label: string}> $tenants Active tenants
 * @var string $preselectedProperty Property id taken from the query string, or an empty string
 */
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$propertyValue = (string) old('property_id', $preselectedProperty);
$tenantValue   = (string) old('tenant_id', '');
$startValue    = (string) old('start_date', '');
$endValue      = (string) old('end_date', '');
$rentValue     = (string) old('monthly_rent', '');
$updateValue   = (string) old('update_percentage', '');
$commissionValue = (string) old('commission_percentage', '');
$dueDayValue   = (string) old('due_day', '');
$lateFeeValue  = (string) old('late_fee_daily_percentage', '');

$selectedProperty = null;
foreach ($properties as $candidate) {
    if ((string) $candidate['id'] === $propertyValue) {
        $selectedProperty = $candidate;
        break;
    }
}

// The rent is suggested from the property the first time it is picked (the property's base rent).
if ($rentValue === '' && $selectedProperty !== null) {
    $baseRent  = (string) $selectedProperty['base_rent'];
    $rentValue = str_contains($baseRent, '.') ? rtrim(rtrim($baseRent, '0'), '.') : $baseRent;
}

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al listado de inmuebles</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <h1 class="panel-title">Nuevo contrato de locación</h1>
                    <p class="panel-subtitle">Vinculá un inmueble disponible con un inquilino y definí el plazo, el monto mensual, la comisión y las condiciones de mora.</p>
                </div>
            </section>

            <div class="user-form-layout has-sidebar">
                <form action="<?= esc(site_url('admin/contratos')) ?>" method="post" id="contract-form" class="user-main-form contract-form">
                    <?= csrf_field() ?>

                    <section class="panel-card form-section-card" aria-labelledby="section-contract-parties">
                        <div class="section-header">
                            <h2 id="section-contract-parties" class="section-title">1. Partes intervinientes</h2>
                            <p class="section-subtitle">El inmueble disponible en cartera y el inquilino titular del contrato.</p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field form-field-full">
                                <label for="property_id" class="form-label">
                                    <span>Inmueble en locación <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <select
                                    id="property_id"
                                    name="property_id"
                                    required
                                    class="form-control<?= isset($errors['property_id']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['property_id']) ? 'true' : 'false' ?>"
                                >
                                    <option value="">Seleccionar inmueble…</option>
                                    <?php foreach ($properties as $property): ?>
                                        <option
                                            value="<?= esc((string) $property['id']) ?>"
                                            data-address="<?= esc($property['address'] . ', ' . $property['city']) ?>"
                                            data-type="<?= esc($property['type'] . ($property['rooms'] !== null ? ' · ' . $property['rooms'] . ' amb.' : '')) ?>"
                                            data-base-rent="<?= esc((string) $property['base_rent']) ?>"
                                            data-owner="<?= esc($property['owner_name']) ?>"
                                            data-owner-dni="<?= esc($property['owner_dni']) ?>"
                                            <?= $propertyValue === (string) $property['id'] ? ' selected' : '' ?>
                                        >
                                            <?= esc($property['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-help">Solo se ofrecen inmuebles disponibles, sin baja y con propietario activo.</span>
                                <?php if ($properties === []): ?>
                                    <p class="field-help">No hay inmuebles disponibles para alquilar en este momento.</p>
                                <?php endif; ?>
                                <?php if (isset($errors['property_id'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['property_id']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <span class="form-label" id="owner-preview-label">Propietario titular</span>
                                <div class="linked-party-box" id="owner-preview" role="status" aria-labelledby="owner-preview-label">
                                    <?php if ($selectedProperty !== null): ?>
                                        <strong><?= esc($selectedProperty['owner_name']) ?></strong>
                                        <span>DNI <?= esc($selectedProperty['owner_dni']) ?></span>
                                    <?php else: ?>
                                        <span class="field-help">Se completa solo al elegir el inmueble: el propietario del contrato es el del inmueble.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="tenant_id" class="form-label">
                                    <span>Inquilino titular <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <select
                                    id="tenant_id"
                                    name="tenant_id"
                                    required
                                    class="form-control<?= isset($errors['tenant_id']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['tenant_id']) ? 'true' : 'false' ?>"
                                >
                                    <option value="">Seleccionar inquilino…</option>
                                    <?php foreach ($tenants as $tenant): ?>
                                        <option value="<?= esc((string) $tenant['id']) ?>"<?= $tenantValue === (string) $tenant['id'] ? ' selected' : '' ?>>
                                            <?= esc($tenant['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-help">Solo usuarios con rol Inquilino activo.</span>
                                <?php if (isset($errors['tenant_id'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['tenant_id']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-contract-term">
                        <div class="section-header">
                            <h2 id="section-contract-term" class="section-title">2. Plazo de vigencia</h2>
                            <p class="section-subtitle">Fecha de inicio y de finalización del contrato (la de finalización debe ser posterior).</p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label for="start_date" class="form-label">
                                    <span>Fecha de inicio <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    value="<?= esc($startValue) ?>"
                                    required
                                    class="form-control<?= isset($errors['start_date']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['start_date']) ? 'true' : 'false' ?>"
                                >
                                <?php if (isset($errors['start_date'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['start_date']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="end_date" class="form-label">
                                    <span>Fecha de finalización <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    value="<?= esc($endValue) ?>"
                                    required
                                    class="form-control<?= isset($errors['end_date']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['end_date']) ? 'true' : 'false' ?>"
                                >
                                <?php if (isset($errors['end_date'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['end_date']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <p class="field-help" id="duration-preview" role="status" aria-live="polite">Duración: se calcula al cargar las dos fechas.</p>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-contract-terms">
                        <div class="section-header">
                            <h2 id="section-contract-terms" class="section-title">3. Condiciones económicas</h2>
                            <p class="section-subtitle">Monto mensual, actualización, comisión de la inmobiliaria, vencimiento y recargo por mora.</p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label for="monthly_rent" class="form-label">
                                    <span>Monto mensual (ARS) <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <div class="input-prefix-group">
                                    <span class="input-prefix" aria-hidden="true">$</span>
                                    <input
                                        type="number"
                                        id="monthly_rent"
                                        name="monthly_rent"
                                        value="<?= esc($rentValue) ?>"
                                        required
                                        min="0.01"
                                        step="0.01"
                                        placeholder="450000"
                                        class="form-control<?= isset($errors['monthly_rent']) ? ' is-invalid' : '' ?>"
                                        aria-invalid="<?= isset($errors['monthly_rent']) ? 'true' : 'false' ?>"
                                    >
                                </div>
                                <span class="field-help">Mayor a cero. Se sugiere el precio base del inmueble.</span>
                                <?php if (isset($errors['monthly_rent'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['monthly_rent']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="update_percentage" class="form-label">
                                    <span>Incremento por actualización (%) <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="number"
                                    id="update_percentage"
                                    name="update_percentage"
                                    value="<?= esc($updateValue) ?>"
                                    required
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0"
                                    class="form-control<?= isset($errors['update_percentage']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['update_percentage']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Entre 0 y 100. Usá 0 si el contrato no se actualiza.</span>
                                <?php if (isset($errors['update_percentage'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['update_percentage']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="commission_percentage" class="form-label">
                                    <span>Comisión de la inmobiliaria (%) <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="number"
                                    id="commission_percentage"
                                    name="commission_percentage"
                                    value="<?= esc($commissionValue) ?>"
                                    required
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="10"
                                    class="form-control<?= isset($errors['commission_percentage']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['commission_percentage']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Entre 0 y 100. Se descuenta al liquidar al propietario.</span>
                                <?php if (isset($errors['commission_percentage'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['commission_percentage']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="due_day" class="form-label">
                                    <span>Día de vencimiento mensual <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <select
                                    id="due_day"
                                    name="due_day"
                                    required
                                    class="form-control<?= isset($errors['due_day']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['due_day']) ? 'true' : 'false' ?>"
                                >
                                    <option value="">Seleccionar día…</option>
                                    <?php for ($day = 1; $day <= 28; $day++): ?>
                                        <option value="<?= $day ?>"<?= $dueDayValue === (string) $day ? ' selected' : '' ?>>Día <?= $day ?> de cada mes</option>
                                    <?php endfor; ?>
                                </select>
                                <span class="field-help">Del 1 al 28, para evitar los meses cortos.</span>
                                <?php if (isset($errors['due_day'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['due_day']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="late_fee_daily_percentage" class="form-label">
                                    <span>Recargo diario por mora (%) <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="number"
                                    id="late_fee_daily_percentage"
                                    name="late_fee_daily_percentage"
                                    value="<?= esc($lateFeeValue) ?>"
                                    required
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0.5"
                                    class="form-control<?= isset($errors['late_fee_daily_percentage']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['late_fee_daily_percentage']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Entre 0 y 100. Se aplica por cada día de atraso desde el día siguiente al vencimiento.</span>
                                <?php if (isset($errors['late_fee_daily_percentage'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['late_fee_daily_percentage']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-save-contract">
                            <span>Registrar contrato</span>
                        </button>
                    </div>
                </form>

                <aside class="user-audit-sidebar" aria-label="Resumen del contrato">
                    <section class="panel-card audit-card">
                        <h3 class="section-title">Resumen</h3>
                        <p class="section-subtitle" id="summary-property"><?= $selectedProperty !== null ? esc($selectedProperty['address'] . ', ' . $selectedProperty['city']) : 'Todavía no elegiste un inmueble.' ?></p>
                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Estado al guardar</dt>
                                <dd><span class="badge-pill badge-contract-in_force">Vigente</span></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Monto mensual</dt>
                                <dd id="summary-gross">—</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Comisión</dt>
                                <dd id="summary-commission">—</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Neto al propietario</dt>
                                <dd id="summary-net">—</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Vencimiento</dt>
                                <dd id="summary-due">—</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Mora diaria</dt>
                                <dd id="summary-late-fee">—</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card audit-card">
                        <h3 class="section-title">Al registrar el contrato</h3>
                        <p class="field-help">
                            El contrato queda <strong>Vigente</strong> y el inmueble pasa automáticamente a
                            <strong>Alquilado</strong>. Ambos cambios se guardan juntos: si algo falla, no se guarda ninguno.
                        </p>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/contracts.js')) ?>"></script>
<?php $this->endSection() ?>
