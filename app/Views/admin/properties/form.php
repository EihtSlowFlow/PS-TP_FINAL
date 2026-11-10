<?php
/**
 * Property form: creation (ADM-05) and edition (HU-07).
 *
 * @var string                                                  $mode     'create' or 'edit'
 * @var array<string, mixed>|null                               $property Property being edited (with owner data), or null
 * @var list<array{id: int, label: string, inactive: bool}>     $owners   Owners that can be selected
 */
use App\Models\PropertyModel;

$isEdit = $mode === 'edit' && $property !== null;
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

$propertyId   = $isEdit ? (int) $property['id'] : 0;
$formAction   = $isEdit ? site_url('admin/inmuebles/editar/' . $propertyId) : site_url('admin/inmuebles');
$currentStatus = $isEdit ? (string) $property['status'] : PropertyModel::STATUS_AVAILABLE;

/** Removes the useless zeros of a stored decimal ("420000.00" becomes "420000"). */
$plainDecimal = static function (mixed $value): string {
    $text = (string) $value;

    return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
};

$ownerValue       = (string) old('owner_id', $isEdit ? (string) $property['owner_id'] : '');
$addressValue     = (string) old('address', $isEdit ? (string) $property['address'] : '');
$cityValue        = (string) old('city', $isEdit ? (string) $property['city'] : '');
$typeValue        = (string) old('type', $isEdit ? (string) $property['type'] : PropertyModel::TYPE_APARTMENT);
$roomsValue       = (string) old('rooms', $isEdit ? (string) ($property['rooms'] ?? '') : '');
$surfaceValue     = (string) old('surface', $isEdit ? $plainDecimal($property['surface'] ?? '') : '');
$servicesValue    = (string) old('included_services', $isEdit ? (string) ($property['included_services'] ?? '') : '');
$baseRentValue    = (string) old('base_rent', $isEdit ? $plainDecimal($property['base_rent']) : '');
$descriptionValue = (string) old('description', $isEdit ? (string) ($property['description'] ?? '') : '');
$statusValue      = (string) old('status', $currentStatus);

$isLand = $typeValue === PropertyModel::TYPE_LAND;

$formatDateTime = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y H:i', strtotime((string) $value)) : '—';
};

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
                    <?php if ($isEdit): ?>
                        <div class="users-heading-row">
                            <h1 class="panel-title">Editar inmueble: <?= esc((string) $property['address']) ?></h1>
                            <span class="badge-pill badge-property-<?= esc($currentStatus) ?>"><?= esc(PropertyModel::labelForStatus($currentStatus)) ?></span>
                        </div>
                        <p class="panel-subtitle">Modificá los datos de la unidad. Los cambios se reflejan de inmediato en el listado.</p>
                    <?php else: ?>
                        <h1 class="panel-title">Nuevo inmueble</h1>
                        <p class="panel-subtitle">Registrá una nueva unidad locativa en la cartera y asignale un propietario titular.</p>
                    <?php endif; ?>
                </div>
            </section>

            <div class="user-form-layout has-sidebar">
                <form action="<?= esc($formAction) ?>" method="post" id="property-form" class="user-main-form property-form">
                    <?= csrf_field() ?>

                    <section class="panel-card form-section-card" aria-labelledby="section-property-data">
                        <div class="section-header">
                            <h2 id="section-property-data" class="section-title">Datos del inmueble</h2>
                            <p class="section-subtitle">Titularidad, ubicación y características básicas de la unidad.</p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field form-field-full">
                                <label for="owner_id" class="form-label">
                                    <span>Propietario titular <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <select
                                    id="owner_id"
                                    name="owner_id"
                                    required
                                    class="form-control<?= isset($errors['owner_id']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['owner_id']) ? 'true' : 'false' ?>"
                                >
                                    <option value="">Seleccionar propietario…</option>
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?= esc((string) $owner['id']) ?>"<?= $ownerValue === (string) $owner['id'] ? ' selected' : '' ?>>
                                            <?= esc($owner['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-help">Solo se pueden asignar usuarios con rol Propietario activo.</span>
                                <?php if (isset($errors['owner_id'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['owner_id']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="address" class="form-label">
                                    <span>Dirección exacta <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    id="address"
                                    name="address"
                                    value="<?= esc($addressValue) ?>"
                                    required
                                    minlength="3"
                                    maxlength="255"
                                    class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['address']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Incluí piso, departamento o número de local si corresponde.</span>
                                <?php if (isset($errors['address'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['address']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="city" class="form-label">
                                    <span>Ciudad <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    value="<?= esc($cityValue) ?>"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    class="form-control<?= isset($errors['city']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['city']) ? 'true' : 'false' ?>"
                                >
                                <?php if (isset($errors['city'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['city']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="type" class="form-label">
                                    <span>Tipo de inmueble <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <select
                                    id="type"
                                    name="type"
                                    required
                                    class="form-control<?= isset($errors['type']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['type']) ? 'true' : 'false' ?>"
                                >
                                    <?php foreach (PropertyModel::TYPE_LABELS as $typeKey => $typeLabel): ?>
                                        <option value="<?= esc($typeKey) ?>"<?= $typeValue === $typeKey ? ' selected' : '' ?>><?= esc($typeLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['type'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['type']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="rooms" class="form-label">
                                    <span>Cantidad de ambientes <span class="required-mark" id="rooms-required-mark" aria-hidden="true"<?= $isLand ? ' hidden' : '' ?>>*</span></span>
                                </label>
                                <input
                                    type="number"
                                    id="rooms"
                                    name="rooms"
                                    value="<?= esc($roomsValue) ?>"
                                    <?= $isLand ? '' : 'required' ?>
                                    min="1"
                                    max="255"
                                    step="1"
                                    class="form-control<?= isset($errors['rooms']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['rooms']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help" id="rooms-help">Número entero mayor a cero. No se exige en terrenos.</span>
                                <?php if (isset($errors['rooms'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['rooms']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <div class="form-label-row">
                                    <label for="surface" class="form-label">Superficie (m²)</label>
                                    <span class="optional-tag">Opcional</span>
                                </div>
                                <input
                                    type="number"
                                    id="surface"
                                    name="surface"
                                    value="<?= esc($surfaceValue) ?>"
                                    min="0.01"
                                    max="999999.99"
                                    step="0.01"
                                    class="form-control<?= isset($errors['surface']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['surface']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Si la informás, tiene que ser mayor a cero.</span>
                                <?php if (isset($errors['surface'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['surface']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <div class="form-label-row">
                                    <label for="included_services" class="form-label">Servicios incluidos</label>
                                    <span class="optional-tag">Opcional</span>
                                </div>
                                <input
                                    type="text"
                                    id="included_services"
                                    name="included_services"
                                    value="<?= esc($servicesValue) ?>"
                                    maxlength="255"
                                    class="form-control<?= isset($errors['included_services']) ? ' is-invalid' : '' ?>"
                                    aria-invalid="<?= isset($errors['included_services']) ? 'true' : 'false' ?>"
                                >
                                <span class="field-help">Por ejemplo: agua, luz, gas, internet.</span>
                                <?php if (isset($errors['included_services'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['included_services']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="base_rent" class="form-label">
                                    <span>Precio base de alquiler sugerido (ARS por mes) <span class="required-mark" aria-hidden="true">*</span></span>
                                </label>
                                <div class="input-prefix-group">
                                    <span class="input-prefix" aria-hidden="true">$</span>
                                    <input
                                        type="number"
                                        id="base_rent"
                                        name="base_rent"
                                        value="<?= esc($baseRentValue) ?>"
                                        required
                                        min="0.01"
                                        step="0.01"
                                        placeholder="450000"
                                        class="form-control<?= isset($errors['base_rent']) ? ' is-invalid' : '' ?>"
                                        aria-invalid="<?= isset($errors['base_rent']) ? 'true' : 'false' ?>"
                                    >
                                </div>
                                <span class="field-help">Monto mensual de referencia, mayor a cero. Se toma de base al confeccionar un contrato.</span>
                                <?php if (isset($errors['base_rent'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['base_rent']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-property-description">
                        <div class="section-header">
                            <h2 id="section-property-description" class="section-title">Descripción</h2>
                            <p class="section-subtitle">Estado de conservación y comodidades disponibles.</p>
                        </div>

                        <div class="form-field">
                            <div class="form-label-row">
                                <label for="description" class="form-label">Estado y comodidades</label>
                                <span class="char-counter" id="description-counter" aria-live="polite">
                                    <?= esc((string) mb_strlen($descriptionValue)) ?> / 1.000 caracteres
                                </span>
                            </div>
                            <textarea
                                id="description"
                                name="description"
                                rows="6"
                                maxlength="1000"
                                class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>"
                                aria-invalid="<?= isset($errors['description']) ? 'true' : 'false' ?>"
                            ><?= esc($descriptionValue) ?></textarea>
                            <span class="field-help">Opcional. Máximo 1.000 caracteres.</span>
                            <?php if (isset($errors['description'])): ?>
                                <p class="field-error" role="alert"><?= esc((string) $errors['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-save-property">
                            <span><?= $isEdit ? 'Guardar cambios' : 'Guardar inmueble' ?></span>
                        </button>
                    </div>
                </form>

                <aside class="user-audit-sidebar" aria-label="Estado del inmueble">
                    <?php if ($isEdit): ?>
                        <section class="panel-card audit-card">
                            <h3 class="section-title">Estado operativo</h3>

                            <?php if ($currentStatus === PropertyModel::STATUS_RENTED): ?>
                                <p class="section-subtitle">
                                    <span class="badge-pill badge-property-rented">Alquilado</span>
                                </p>
                                <p class="field-help">
                                    El estado «Alquilado» lo asigna el contrato de locación y no se modifica desde acá.
                                </p>
                            <?php else: ?>
                                <fieldset class="status-options">
                                    <legend class="field-help">Condición actual del inmueble</legend>
                                    <?php foreach (PropertyModel::MANUAL_STATUSES as $statusKey): ?>
                                        <label class="status-option" for="status_<?= esc($statusKey) ?>">
                                            <input
                                                type="radio"
                                                id="status_<?= esc($statusKey) ?>"
                                                name="status"
                                                value="<?= esc($statusKey) ?>"
                                                form="property-form"
                                                <?= $statusValue === $statusKey ? 'checked' : '' ?>
                                            >
                                            <span class="badge-pill badge-property-<?= esc($statusKey) ?>"><?= esc(PropertyModel::labelForStatus($statusKey)) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                                <?php if (isset($errors['status'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['status']) ?></p>
                                <?php endif; ?>
                                <p class="field-help">«Alquilado» se asigna automáticamente al firmar un contrato.</p>
                            <?php endif; ?>
                        </section>

                        <section class="panel-card audit-card">
                            <h3 class="section-title">Registro</h3>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Creado el</dt>
                                    <dd><?= esc($formatDateTime($property['created_at'] ?? null)) ?></dd>
                                </div>
                                <div class="audit-item">
                                    <dt>Última modificación</dt>
                                    <dd><?= esc($formatDateTime($property['updated_at'] ?? null)) ?></dd>
                                </div>
                            </dl>
                        </section>
                    <?php else: ?>
                        <section class="panel-card audit-card">
                            <h3 class="section-title">Estado inicial</h3>
                            <p class="section-subtitle">
                                <span class="badge-pill badge-property-available">Disponible</span>
                            </p>
                            <p class="field-help">Se asigna automáticamente al guardar. El inmueble figura de inmediato en el listado de unidades disponibles.</p>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Fecha de incorporación</dt>
                                    <dd><?= esc(date('d/m/Y')) ?></dd>
                                </div>
                            </dl>
                        </section>
                    <?php endif; ?>
                </aside>
            </div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/properties.js')) ?>"></script>
<?php $this->endSection() ?>
