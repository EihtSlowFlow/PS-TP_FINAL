<?php
/**
 * Review page for one pending payment (HU-17).
 *
 * @var array<string, mixed> $payment
 * @var string               $periodLabel
 * @var string               $installmentTotal
 * @var string               $amountDifference
 * @var int                  $reviewerId
 */
use App\Models\InstallmentModel;
use App\Models\PaymentModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatDate = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';
};

$paymentId = (int) $payment['id'];
$tenantName = trim((string) ($payment['tenant_last_name'] ?? '') . ', ' . (string) ($payment['tenant_first_name'] ?? ''));

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/pagos')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a pagos</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="users-heading-row">
                        <h1 class="panel-title">Revisión de pago #<?= esc((string) $paymentId) ?></h1>
                        <span class="badge-pill badge-installment-<?= esc((string) $payment['status']) ?>"><?= esc(PaymentModel::labelForStatus((string) $payment['status'])) ?></span>
                    </div>
                    <p class="panel-subtitle">
                        Cuota <?= esc((string) ($payment['installment_number'] ?? '')) ?> · <?= esc($periodLabel) ?> ·
                        <?= esc((string) ($payment['property_address'] ?? '')) ?>, <?= esc((string) ($payment['property_city'] ?? '')) ?>
                    </p>
                </div>
            </section>

            <div class="user-form-layout has-sidebar">
                <div class="user-main-form">
                    <section class="panel-card form-section-card" aria-labelledby="payment-review-data">
                        <div class="section-header">
                            <h2 id="payment-review-data" class="section-title">Datos del pago informado</h2>
                            <p class="section-subtitle">Comprobación del importe y el medio de pago antes de aprobar o rechazar la operación.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Inquilino</dt>
                                <dd><?= esc($tenantName !== ',' ? $tenantName : 'Sin nombre') ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Cuota</dt>
                                <dd><?= esc((string) ($payment['installment_number'] ?? '')) ?> · <?= esc($periodLabel) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Fecha de operación</dt>
                                <dd><?= esc($formatDate((string) ($payment['paid_on'] ?? ''))) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Monto informado</dt>
                                <dd><?= esc($formatMoney((string) ($payment['amount'] ?? '0.00'))) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Medio de pago</dt>
                                <dd><?= esc(PaymentModel::labelForMethod((string) ($payment['method'] ?? ''))) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Número de comprobante</dt>
                                <dd><?= esc((string) ($payment['reference_number'] ?? '')) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Fecha de envío</dt>
                                <dd><?= esc($formatDate((string) ($payment['created_at'] ?? ''))) ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="payment-receipt-heading">
                        <div class="section-header">
                            <h2 id="payment-receipt-heading" class="section-title">Comprobante adjunto</h2>
                            <p class="section-subtitle">Se valida el archivo digital y el soporte que aportó el inquilino.</p>
                        </div>

                        <?php if (! empty($payment['receipt_id'])): ?>
                            <div class="inline-actions-row">
                                <a href="<?= esc(site_url('comprobantes/' . (int) $payment['receipt_id'])) ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Ver comprobante</a>
                            </div>
                            <p class="field-help">
                                Archivo: <?= esc((string) ($payment['receipt_original_name'] ?? '')) ?>
                                <?php if (! empty($payment['receipt_size_bytes'])): ?>
                                    · <?= esc((string) ((int) $payment['receipt_size_bytes'] / 1024)) ?> KB
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p class="field-help">No se adjuntó un comprobante para este pago.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="user-side-panel">
                    <section class="panel-card form-section-card" aria-labelledby="payment-resolution-heading">
                        <div class="section-header">
                            <h2 id="payment-resolution-heading" class="section-title">Resolver pago</h2>
                            <p class="section-subtitle">Confirmá el importe exigible y decidí si corresponde aprobarlo o rechazarlo.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Monto exigible</dt>
                                <dd><?= esc($formatMoney($installmentTotal)) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Importe informado</dt>
                                <dd><?= esc($formatMoney((string) ($payment['amount'] ?? '0.00'))) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Diferencia</dt>
                                <dd><?= esc((string) $amountDifference) ?></dd>
                            </div>
                        </dl>

                        <div class="inline-actions-row stacked">
                            <form action="<?= esc(site_url('admin/pagos/' . $paymentId . '/aprobacion')) ?>" method="post" class="inline-form-action">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary" id="btn-approve-payment">Aprobar pago</button>
                            </form>
                        </div>

                        <form action="<?= esc(site_url('admin/pagos/' . $paymentId . '/rechazo')) ?>" method="post" class="form-section-card nested-form" id="payment-rejection-form">
                            <?= csrf_field() ?>

                            <div class="form-field">
                                <label for="rejection_reason" class="form-label">Motivo del rechazo <span class="required-mark" aria-hidden="true">*</span></label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-control" maxlength="500" placeholder="Ingresá el motivo del rechazo para auditoría."></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger" id="btn-reject-payment">Rechazar pago</button>
                        </form>
                    </section>
                </aside>
            </div>
<?php $this->endSection(); ?>
