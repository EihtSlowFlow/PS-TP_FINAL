<?php
/**
 * Server-side confirmation step for an owner who cancels the request of a pending (or rejected) property or takes an
 * approved one down (HU-32). It does not depend on JavaScript.
 *
 * @var array<string, mixed> $property  The owner's property
 * @var bool                 $isRequest true while it was never approved: the deactivation cancels the request
 */
use App\Models\PropertyModel;

$targetPropertyId = (int) $property['id'];
$targetAddress    = (string) $property['address'] . ', ' . (string) $property['city'];
$targetType       = PropertyModel::labelForType((string) $property['type']);

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

            <section class="panel-card form-section-card" aria-labelledby="owner-deactivate-heading">
                <div class="section-header">
                    <h1 id="owner-deactivate-heading" class="panel-title">
                        <?= $isRequest ? '¿Cancelás la solicitud de alta?' : '¿Das de baja este inmueble?' ?>
                    </h1>
                </div>

                <p class="confirm-modal-text">
                    El inmueble ubicado en <strong><?= esc($targetAddress) ?></strong> (<?= esc($targetType) ?>)
                    <?php if ($isRequest): ?>
                        todavía no se publicó. Si cancelás la solicitud se da de baja y la administración deja de revisarlo.
                    <?php else: ?>
                        dejará de figurar en el catálogo y no se podrá elegir en contratos nuevos. Conservamos sus datos y su
                        historial. Si más adelante querés ofrecerlo de nuevo, comunicate con la administración.
                    <?php endif; ?>
                </p>

                <form action="<?= esc(site_url('propietario/inmuebles/baja/' . $targetPropertyId)) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirmed" value="1">
                    <div class="confirm-modal-actions">
                        <a href="<?= esc(site_url('propietario/inmuebles')) ?>" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-danger" id="btn-confirm-owner-deactivation">
                            <?= $isRequest ? 'Sí, cancelar solicitud' : 'Confirmar baja' ?>
                        </button>
                    </div>
                </form>
            </section>
<?php $this->endSection() ?>
