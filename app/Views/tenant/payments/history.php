<?php
/**
 * Tenant payment history (HU-18).
 *
 * @var list<array<string, mixed>> $payments
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

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Mis pagos</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) count($payments)) ?> <?= count($payments) === 1 ? 'pago' : 'pagos' ?></span>
                    </div>
                    <p class="panel-subtitle">Historial de pagos informados y sus comprobantes con su estado actual.</p>
                </div>
            </div>

            <?php if ($payments === []): ?>
                <section class="panel-card" aria-live="polite">
                    <div class="section-header">
                        <h2 class="section-title">Todavía no informaste pagos</h2>
                        <p class="section-subtitle">Cuando registres un comprobante, aparecerá aquí con su estado y el archivo adjunto.</p>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel-card" aria-labelledby="tenant-payment-history-heading">
                    <div class="section-header">
                        <h2 id="tenant-payment-history-heading" class="section-title">Historial</h2>
                    </div>

                    <div class="responsive-table-wrap">
                        <table class="data-table" aria-label="Historial de pagos del inquilino">
                            <thead>
                                <tr>
                                    <th>Pago</th>
                                    <th>Período</th>
                                    <th>Monto</th>
                                    <th>Medio</th>
                                    <th>Estado</th>
                                    <th>Comprobante</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <?php
                                        $status = (string) ($payment['status'] ?? '');
                                        $periodLabel = InstallmentModel::labelForPeriod((string) ($payment['period'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><strong>#<?= esc((string) ($payment['payment_id'] ?? '')) ?></strong></td>
                                        <td><?= esc($periodLabel) ?> · Cuota <?= esc((string) ($payment['installment_number'] ?? '')) ?></td>
                                        <td><?= esc($formatMoney((string) ($payment['amount'] ?? '0.00'))) ?></td>
                                        <td><?= esc(PaymentModel::labelForMethod((string) ($payment['method'] ?? ''))) ?></td>
                                        <td>
                                            <span class="status-inline status-inline-<?= esc($status === PaymentModel::STATUS_REJECTED ? 'danger' : ($status === PaymentModel::STATUS_APPROVED ? 'success' : 'warning')) ?>"><?= esc(PaymentModel::labelForStatus($status)) ?></span>
                                            <?php if ($status === PaymentModel::STATUS_REJECTED && ! empty($payment['rejection_reason'])): ?>
                                                <br><span class="field-help">Motivo: <?= esc((string) $payment['rejection_reason']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (! empty($payment['receipt_id'])): ?>
                                                <a href="<?= esc(site_url('comprobantes/' . (int) $payment['receipt_id'])) ?>" class="link-button" target="_blank" rel="noopener noreferrer">Ver comprobante</a>
                                            <?php else: ?>
                                                <span class="field-help">Sin archivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($formatDate((string) ($payment['paid_on'] ?? $payment['created_at'] ?? ''))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
<?php $this->endSection(); ?>
