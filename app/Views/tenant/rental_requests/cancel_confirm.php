<?php
/**
 * Server-side confirmation step for a tenant cancelling a pending rental request (HU-30 Scenario 6).
 *
 * @var array<string, mixed> $rentalRequest
 */
use App\Models\PropertyModel;

$requestId     = (int) $rentalRequest['id'];
$targetAddress = (string) $rentalRequest['property_address'] . ', ' . (string) $rentalRequest['property_city'];
$targetType    = PropertyModel::labelForType((string) $rentalRequest['property_type']);
$startDate     = ! empty($rentalRequest['desired_start_date'])
    ? date('d/m/Y', strtotime((string) $rentalRequest['desired_start_date']))
    : '—';
$months        = (int) $rentalRequest['desired_months'];
$monthsLabel   = $months === 1 ? '1 mes' : $months . ' meses';

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('inquilino/solicitudes')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a mis solicitudes</span>
                </a>
            </div>

            <section class="panel-card form-section-card" aria-labelledby="tenant-cancel-request-heading">
                <div class="section-header">
                    <h1 id="tenant-cancel-request-heading" class="panel-title">¿Cancelás tu solicitud de alquiler?</h1>
                </div>

                <p class="confirm-modal-text">
                    Tu solicitud para el inmueble ubicado en <strong><?= esc($targetAddress) ?></strong> (<?= esc($targetType) ?>),
                    con inicio deseado el <strong><?= esc($startDate) ?></strong> por <strong><?= esc($monthsLabel) ?></strong>,
                    pasará a estado <strong>Cancelada</strong> y dejará de aparecer en la bandeja de revisión de la administración.
                </p>

                <form action="<?= esc(site_url('inquilino/solicitudes/' . $requestId . '/cancelar')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirmed" value="1">
                    <div class="confirm-modal-actions">
                        <a href="<?= esc(site_url('inquilino/solicitudes')) ?>" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-danger" id="btn-confirm-cancel-request">
                            Sí, cancelar solicitud
                        </button>
                    </div>
                </form>
            </section>
<?php $this->endSection() ?>

