<?php
/**
 * Owner's form to publish a property (HU-32) and to correct it while it is pending or rejected.
 *
 * It is the administration form of HU-07 without the owner selector and without the commercial status: the owner is
 * the one in the session, and what is saved always waits for the administration to review it.
 *
 * @var string                    $mode     'create' or 'edit'
 * @var array<string, mixed>|null $property Property being corrected, or null when publishing a new one
 * @var list<array<string, mixed>>|null $images Photos already loaded (edit only)
 */
use App\Models\PropertyModel;

$isEdit         = $mode === 'edit' && $property !== null;
$formAction     = $isEdit ? site_url('propietario/inmuebles/editar/' . (int) $property['id']) : site_url('propietario/inmuebles');
$approvalStatus = $isEdit ? (string) $property['approval_status'] : PropertyModel::APPROVAL_PENDING;
$isRejected     = $approvalStatus === PropertyModel::APPROVAL_REJECTED;
$rejectionText  = $isEdit ? (string) ($property['rejection_reason'] ?? '') : '';

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('propietario/inmuebles')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a mis inmuebles</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <?php if ($isEdit): ?>
                        <div class="page-heading-row">
                            <h1 class="panel-title">Corregir inmueble: <?= esc((string) $property['address']) ?></h1>
                            <span class="badge-pill badge-approval-<?= esc($approvalStatus) ?>"><?= esc(PropertyModel::labelForApproval($approvalStatus, true)) ?></span>
                        </div>
                        <p class="panel-subtitle">Al guardar, el inmueble vuelve a quedar pendiente de aprobación de la administración.</p>
                    <?php else: ?>
                        <h1 class="panel-title">Registrar inmueble</h1>
                        <p class="panel-subtitle">Cargá los datos de tu propiedad. La administración la revisa y, si la aprueba, se publica en el catálogo.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($isRejected && $rejectionText !== ''): ?>
                <section class="panel-card" id="rejection-notice" role="status" aria-label="Motivo del rechazo">
                    <p class="review-reason"><strong>La administración rechazó este inmueble</strong><?= esc($rejectionText) ?></p>
                </section>
            <?php endif; ?>

            <div class="form-layout has-sidebar">
                <form action="<?= esc($formAction) ?>" method="post" enctype="multipart/form-data" id="property-form" class="form-main property-form">
                    <?= csrf_field() ?>

                    <section class="panel-card form-section-card" aria-labelledby="section-property-data">
                        <div class="section-header">
                            <h2 id="section-property-data" class="section-title">Datos del inmueble</h2>
                            <p class="section-subtitle">Ubicación y características básicas de tu propiedad.</p>
                        </div>

                        <div class="form-grid-two">
                            <?= $this->include('properties/form_fields') ?>
                        </div>
                    </section>

                    <?= $this->include('properties/form_description') ?>

                    <?= $this->include('properties/form_images') ?>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('propietario/inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-save-property">
                            <span><?= $isEdit ? 'Guardar y reenviar a revisión' : 'Enviar a revisión' ?></span>
                        </button>
                    </div>
                </form>

                <aside class="form-aside" aria-label="Revisión del inmueble">
                    <section class="panel-card audit-card">
                        <h3 class="section-title">Revisión</h3>
                        <p class="section-subtitle">
                            <span class="badge-pill badge-approval-<?= esc($isRejected ? PropertyModel::APPROVAL_REJECTED : PropertyModel::APPROVAL_PENDING) ?>">
                                <?= esc($isRejected ? 'Rechazado' : 'Pendiente de aprobación') ?>
                            </span>
                        </p>
                        <p class="field-help">
                            Hasta que la administración lo apruebe, el inmueble no figura en el catálogo público ni se puede
                            usar en un contrato. Vas a verlo en «Mis inmuebles» con su estado.
                        </p>
                        <p class="field-help">Una vez aprobado, los cambios se solicitan a la administración.</p>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script src="<?= esc(base_url('js/properties.js')) ?>"></script>
    <script src="<?= esc(base_url('js/property-images.js')) ?>"></script>
<?php $this->endSection() ?>
