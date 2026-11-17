<?php
/**
 * Server-side confirmation step for the logical deactivation of a property (HU-09 Scenario 1).
 * Shown when the deactivation request arrives without `confirmed=1`, e.g. with JavaScript disabled.
 *
 * @var array<string, mixed> $property Target property row (with owner data)
 */
use App\Models\PropertyModel;

$targetPropertyId = (int) $property['id'];
$targetAddress    = (string) $property['address'];
$targetCity       = (string) $property['city'];
$targetTypeLabel  = PropertyModel::labelForType((string) $property['type']);
$ownerFullName    = trim(((string) ($property['owner_first_name'] ?? '')) . ' ' . ((string) ($property['owner_last_name'] ?? '')));

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

            <section class="panel-card form-section-card" aria-labelledby="deactivate-property-heading">
                <div class="section-header">
                    <h1 id="deactivate-property-heading" class="panel-title">¿Confirmás la baja lógica del inmueble?</h1>
                </div>

                <p class="confirm-modal-text">
                    El inmueble ubicado en <strong><?= esc($targetAddress) ?>, <?= esc($targetCity) ?></strong>
                    (<span><?= esc($targetTypeLabel) ?></span> · Propietario: <span><?= esc($ownerFullName) ?></span>)
                    pasará a estado inactivo: se retirará de la operatoria activa y no podrá seleccionarse ni asociarse a nuevos contratos
                    de locación, pero se conservarán todos sus datos y su registro histórico (contratos, cuotas y pagos asociados) para
                    consulta y auditoría.
                </p>

                <form action="<?= esc(site_url('admin/inmuebles/baja/' . $targetPropertyId)) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirmed" value="1">
                    <div class="confirm-modal-actions">
                        <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-danger" id="btn-confirm-property-deactivation">Confirmar baja</button>
                    </div>
                </form>
            </section>
<?php $this->endSection() ?>

