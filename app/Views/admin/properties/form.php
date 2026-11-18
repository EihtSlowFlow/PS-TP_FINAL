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

// The data fields and the description are shared with the owner's form (HU-32): `properties/form_fields` and
// `properties/form_description`, which read the same `$mode` and `$property`.
$ownerValue  = (string) old('owner_id', $isEdit ? (string) $property['owner_id'] : '');
$statusValue = (string) old('status', $currentStatus);

// Review of the properties published by owners (HU-33): only these have something to resolve.
$approvalStatus   = $isEdit ? (string) ($property['approval_status'] ?? PropertyModel::APPROVAL_APPROVED) : PropertyModel::APPROVAL_APPROVED;
$isPendingReview  = $isEdit && empty($property['deleted_at']) && $approvalStatus === PropertyModel::APPROVAL_PENDING;
$submittedByOwner = $isEdit && ! empty($property['submitted_by']);

$canDeactivate = $isEdit
    && empty($property['deleted_at'])
    && $currentStatus !== PropertyModel::STATUS_RENTED
    && empty($property['has_active_contract']);
$ownerFullName = $isEdit
    ? trim(((string) ($property['owner_first_name'] ?? '')) . ' ' . ((string) ($property['owner_last_name'] ?? '')))
    : '';

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

                <?php if ($canDeactivate): ?>
                    <div class="form-header-status-actions">
                        <form
                            action="<?= esc(site_url('admin/inmuebles/baja/' . $propertyId)) ?>"
                            method="post"
                            class="inline-action-form js-deactivate-property-form"
                            data-property-address="<?= esc((string) $property['address'] . ', ' . (string) $property['city']) ?>"
                            data-property-type="<?= esc(PropertyModel::labelForType((string) $property['type'])) ?>"
                            data-property-owner="<?= esc($ownerFullName) ?>"
                        >
                            <?= csrf_field() ?>
                            <input type="hidden" name="confirmed" value="0">
                            <button type="submit" class="btn btn-danger-outline btn-sm" id="btn-deactivate-property">
                                <span>Dar de baja inmueble</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <?php if ($isEdit): ?>
                        <div class="page-heading-row">
                            <h1 class="panel-title">Editar inmueble: <?= esc((string) $property['address']) ?></h1>
                            <span class="badge-pill badge-property-<?= esc($currentStatus) ?>"><?= esc(PropertyModel::labelForStatus($currentStatus)) ?></span>
                            <?php if ($approvalStatus !== PropertyModel::APPROVAL_APPROVED): ?>
                                <span class="badge-pill badge-approval-<?= esc($approvalStatus) ?>" id="approval-badge"><?= esc(PropertyModel::labelForApproval($approvalStatus)) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="panel-subtitle">
                            <?php if ($isPendingReview): ?>
                                Revisá los datos del inmueble. Podés corregirlos antes de aprobarlo: seguirá pendiente hasta que lo resuelvas.
                            <?php else: ?>
                                Modificá los datos de la unidad. Los cambios se reflejan de inmediato en el listado.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <h1 class="panel-title">Nuevo inmueble</h1>
                        <p class="panel-subtitle">Registrá una nueva unidad locativa en la cartera y asignale un propietario titular.</p>
                    <?php endif; ?>
                </div>
            </section>

            <div class="form-layout has-sidebar">
                <form action="<?= esc($formAction) ?>" method="post" enctype="multipart/form-data" id="property-form" class="form-main property-form">
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
                                <?php if ($submittedByOwner): ?>
                                    <input type="hidden" name="owner_id" value="<?= esc((string) $property['owner_id']) ?>">
                                    <select
                                        id="owner_id"
                                        disabled
                                        aria-disabled="true"
                                        class="form-control<?= isset($errors['owner_id']) ? ' is-invalid' : '' ?>"
                                        aria-invalid="<?= isset($errors['owner_id']) ? 'true' : 'false' ?>"
                                    >
                                        <?php foreach ($owners as $owner): ?>
                                            <?php if ((int) $owner['id'] === (int) $property['owner_id']): ?>
                                                <option value="<?= esc((string) $owner['id']) ?>" selected>
                                                    <?= esc($owner['label']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="field-help">El propietario titular envió la solicitud de alta de este inmueble y no se puede modificar.</span>
                                <?php else: ?>
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
                                <?php endif; ?>
                                <?php if (isset($errors['owner_id'])): ?>
                                    <p class="field-error" role="alert"><?= esc((string) $errors['owner_id']) ?></p>
                                <?php endif; ?>
                            </div>

                            <?= $this->include('properties/form_fields') ?>
                        </div>
                    </section>

                    <?= $this->include('properties/form_description') ?>

                    <?= $this->include('properties/form_images') ?>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-save-property">
                            <span><?= $isEdit ? 'Guardar cambios' : 'Guardar inmueble' ?></span>
                        </button>
                    </div>
                </form>

                <aside class="form-aside" aria-label="Estado del inmueble">
                    <?php if ($isEdit && ($submittedByOwner || $approvalStatus !== PropertyModel::APPROVAL_APPROVED)): ?>
                        <?php
                        $reviewerName = trim(
                            ((string) ($property['reviewed_by_first_name'] ?? '')) . ' ' . ((string) ($property['reviewed_by_last_name'] ?? ''))
                        );
                        $rejectionError = $errors['rejection_reason'] ?? null;
                        ?>
                        <section class="panel-card audit-card" id="property-review-card" aria-labelledby="property-review-title">
                            <h3 class="section-title" id="property-review-title">Revisión del inmueble</h3>
                            <p class="section-subtitle">
                                <span class="badge-pill badge-approval-<?= esc($approvalStatus) ?>"><?= esc(PropertyModel::labelForApproval($approvalStatus)) ?></span>
                            </p>

                            <?php if ($submittedByOwner): ?>
                                <p class="field-help">Lo publicó el propietario: <?= esc($ownerFullName) ?>.</p>
                            <?php endif; ?>

                            <?php if ($isPendingReview): ?>
                                <p class="field-help">Mientras esté pendiente no figura en el catálogo ni se puede elegir en un contrato.</p>

                                <div class="inline-actions-row stacked">
                                    <form action="<?= esc(site_url('admin/inmuebles/' . $propertyId . '/aprobacion')) ?>" method="post" class="inline-form-action">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-primary" id="btn-approve-property">Aprobar inmueble</button>
                                    </form>
                                </div>

                                <form action="<?= esc(site_url('admin/inmuebles/' . $propertyId . '/rechazo')) ?>" method="post" class="form-section-card nested-form" id="property-rejection-form">
                                    <?= csrf_field() ?>
                                    <div class="form-field">
                                        <label for="rejection_reason" class="form-label">Motivo del rechazo <span class="required-mark" aria-hidden="true">*</span></label>
                                        <textarea
                                            id="rejection_reason"
                                            name="rejection_reason"
                                            rows="4"
                                            maxlength="255"
                                            required
                                            class="form-control<?= $rejectionError !== null ? ' is-invalid' : '' ?>"
                                            aria-invalid="<?= $rejectionError !== null ? 'true' : 'false' ?>"
                                            placeholder="Contale al propietario qué debe corregir."
                                        ><?= esc((string) old('rejection_reason', '')) ?></textarea>
                                        <span class="field-help">Obligatorio, hasta 255 caracteres. El propietario lo ve en «Mis inmuebles».</span>
                                        <?php if ($rejectionError !== null): ?>
                                            <p class="field-error" role="alert"><?= esc((string) $rejectionError) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-danger" id="btn-reject-property">Rechazar inmueble</button>
                                </form>
                            <?php elseif ($approvalStatus === PropertyModel::APPROVAL_REJECTED): ?>
                                <?php if ((string) ($property['rejection_reason'] ?? '') !== ''): ?>
                                    <p class="review-reason"><strong>Motivo del rechazo</strong><?= esc((string) $property['rejection_reason']) ?></p>
                                <?php endif; ?>
                                <p class="field-help">El propietario puede corregirlo y reenviarlo: volverá a quedar pendiente.</p>
                            <?php endif; ?>

                            <?php if (! empty($property['reviewed_at'])): ?>
                                <dl class="audit-list">
                                    <div class="audit-item">
                                        <dt>Resuelto el</dt>
                                        <dd><?= esc($formatDateTime($property['reviewed_at'])) ?></dd>
                                    </div>
                                    <?php if ($reviewerName !== ''): ?>
                                        <div class="audit-item">
                                            <dt>Resuelto por</dt>
                                            <dd><?= esc($reviewerName) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

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

<?php $this->section('modals') ?>
    <?php if ($canDeactivate): ?>
        <div class="confirm-modal" id="deactivate-property-modal" role="alertdialog" aria-modal="true" aria-labelledby="deactivate-property-title" aria-describedby="deactivate-property-desc" hidden>
            <div class="confirm-modal-dialog">
                <h2 class="confirm-modal-title" id="deactivate-property-title">¿Confirmás la baja lógica del inmueble?</h2>
                <p class="confirm-modal-text" id="deactivate-property-desc">
                    El inmueble ubicado en <strong id="deactivate-property-address"><?= esc((string) $property['address'] . ', ' . (string) $property['city']) ?></strong>
                    (<span id="deactivate-property-type"><?= esc(PropertyModel::labelForType((string) $property['type'])) ?></span> · Propietario: <span id="deactivate-property-owner"><?= esc($ownerFullName) ?></span>)
                    pasará a estado inactivo: se retirará de la operatoria activa y no podrá seleccionarse ni asociarse a nuevos contratos
                    de locación, pero se conservarán todos sus datos y su registro histórico (contratos, cuotas y pagos asociados) para
                    consulta y auditoría.
                </p>
                <div class="confirm-modal-actions">
                    <button type="button" class="btn btn-secondary" id="deactivate-property-cancel">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="deactivate-property-confirm">Confirmar baja</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/properties.js')) ?>"></script>
    <script src="<?= esc(base_url('js/property-images.js')) ?>"></script>
<?php $this->endSection() ?>
