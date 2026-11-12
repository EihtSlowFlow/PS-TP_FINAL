<?php
/**
 * Server-side confirmation step for closing a contract (HU-12 Scenario 1).
 *
 * @var array<string, mixed> $contract
 * @var string               $status
 */
$contractId = (int) $contract['id'];
$statusLabel = $status === App\Models\ContractModel::STATUS_FINISHED ? 'finalizar' : 'rescindir';

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al contrato</span>
                </a>
            </div>

            <section class="panel-card form-section-card" aria-labelledby="close-heading">
                <div class="section-header">
                    <h1 id="close-heading" class="panel-title">¿Confirmás que querés <?= esc($statusLabel) ?> el contrato?</h1>
                </div>

                <p class="confirm-modal-text">
                    El contrato de <strong><?= esc((string) $contract['property_address']) ?></strong>
                    pasará a estado <strong><?= esc(App\Models\ContractModel::labelForStatus($status)) ?></strong> y el inmueble volverá a estar disponible.
                    El historial financiero se conservará intacto.
                </p>

                <form action="<?= esc(site_url('admin/contratos/' . $contractId . '/cerrar')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= esc($status) ?>">
                    <input type="hidden" name="confirmed" value="1">
                    <div class="field-group">
                        <label for="closing_reason">Motivo del cierre</label>
                        <textarea id="closing_reason" name="closing_reason" rows="3" maxlength="255" placeholder="Ej.: vencimiento del contrato, rescisión por incumplimiento, etc."></textarea>
                    </div>
                    <div class="confirm-modal-actions">
                        <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning" id="btn-confirm-contract-close">Confirmar <?= esc($statusLabel) ?></button>
                    </div>
                </form>
            </section>
<?php $this->endSection() ?>
