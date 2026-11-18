<?php
/**
 * Fields of the property form that the administration (HU-07) and the owner (HU-32) fill in the same way:
 * address, city, type, rooms, surface, included services and base rent. It is included inside the two-column grid
 * of `admin/properties/form` and `properties/owner_form`, so both forms stay identical and share one set of messages.
 *
 * @var string                    $mode     'create' or 'edit'
 * @var array<string, mixed>|null $property Property being edited, or null
 */
use App\Models\PropertyModel;

$isEdit = $mode === 'edit' && $property !== null;
$errors = session()->getFlashdata('errors');
if (! is_array($errors)) {
    $errors = [];
}

/** Removes the useless zeros of a stored decimal ("420000.00" becomes "420000"). */
$plainDecimal = static function (mixed $value): string {
    $text = (string) $value;

    return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
};

$addressValue  = (string) old('address', $isEdit ? (string) $property['address'] : '');
$cityValue     = (string) old('city', $isEdit ? (string) $property['city'] : '');
$typeValue     = (string) old('type', $isEdit ? (string) $property['type'] : PropertyModel::TYPE_APARTMENT);
$roomsValue    = (string) old('rooms', $isEdit ? (string) ($property['rooms'] ?? '') : '');
$surfaceValue  = (string) old('surface', $isEdit ? $plainDecimal($property['surface'] ?? '') : '');
$oldServices      = old('included_services', $isEdit ? (string) ($property['included_services'] ?? '') : '');
$selectedServices = PropertyModel::splitServices(is_array($oldServices) ? $oldServices : (string) $oldServices);
$baseRentValue = (string) old('base_rent', $isEdit ? $plainDecimal($property['base_rent']) : '');
$isLand        = $typeValue === PropertyModel::TYPE_LAND;
?>
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

                            <fieldset class="form-field form-field-full service-options" id="included_services"<?= isset($errors['included_services']) ? ' aria-invalid="true"' : '' ?>>
                                <legend class="form-label-row">
                                    <span class="form-label">Servicios incluidos</span>
                                    <span class="optional-tag">Opcional</span>
                                </legend>
                                <div class="service-options-grid">
                                    <?php foreach (PropertyModel::SERVICE_OPTIONS as $index => $service): ?>
                                        <label class="filter-checkbox-label" for="service-<?= esc((string) $index) ?>">
                                            <input
                                                type="checkbox"
                                                id="service-<?= esc((string) $index) ?>"
                                                name="included_services[]"
                                                value="<?= esc($service) ?>"
                                                <?= in_array($service, $selectedServices, true) ? 'checked' : '' ?>
                                            >
                                            <span><?= esc($service) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (isset($errors['included_services'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['included_services']) ?></p>
                                <?php endif; ?>
                            </fieldset>

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
