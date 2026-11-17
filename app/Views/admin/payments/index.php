<?php
/**
 * Payment review queue for admins/employees (HU-17).
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
                        <h1 class="panel-title">Pagos</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) count($payments)) ?> <?= count($payments) === 1 ? 'pendiente' : 'pendientes' ?></span>
                    </div>
                    <p class="panel-subtitle">Validación de comprobantes informados por inquilinos y resolución de pagos pendientes.</p>
                </div>
                <div class="panel-toolbar">
                    <a href="<?= esc(site_url('admin/pagos/historial')) ?>" class="btn btn-secondary">Ver historial</a>
                </div>
            </div>

            <?php if ($payments === []): ?>
                <section class="panel-card form-section-card" aria-live="polite">
                    <div class="section-header">
                        <h2 class="section-title">Sin pagos para revisar</h2>
                        <p class="section-subtitle">No hay pagos en estado "Pendiente de validación" en este momento.</p>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel-card" aria-labelledby="payment-queue-heading">
                    <div class="section-header">
                        <h2 id="payment-queue-heading" class="section-title">Pendientes de validación</h2>
                        <p class="section-subtitle">Se ordenan por antigüedad para priorizar la conciliación contable.</p>
                    </div>

                    <div class="responsive-table-wrap">
                        <table class="data-table" aria-label="Pagos pendientes de validación">
                            <thead>
                                <tr>
                                    <th>Pago</th>
                                    <th>Inquilino</th>
                                    <th>Cuota</th>
                                    <th>Inmueble</th>
                                    <th>Monto</th>
                                    <th>Comprobante</th>
                                    <th>Enviado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <?php
                                        $tenantName = trim((string) ($payment['tenant_last_name'] ?? '') . ', ' . (string) ($payment['tenant_first_name'] ?? ''));
                                        $periodLabel = InstallmentModel::labelForPeriod((string) ($payment['period'] ?? ''));
                                        $installmentTotal = InstallmentModel::sumAmounts([
                                            (string) ($payment['base_amount'] ?? '0.00'),
                                            (string) ($payment['late_fee'] ?? '0.00'),
                                        ]);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= esc((string) ($payment['payment_id'] ?? $payment['id'] ?? '')) ?></strong><br>
                                            <span class="status-inline status-inline-warning"><?= esc(PaymentModel::labelForStatus((string) ($payment['status'] ?? PaymentModel::STATUS_PENDING_VALIDATION))) ?></span>
                                        </td>
                                        <td><?= esc($tenantName !== ',' ? $tenantName : 'Sin nombre') ?></td>
                                        <td>
                                            <?= esc((string) ($payment['installment_number'] ?? '')) ?> · <?= esc($periodLabel) ?>
                                        </td>
                                        <td>
                                            <?= esc((string) ($payment['property_address'] ?? '')) ?><br>
                                            <span class="field-help"><?= esc((string) ($payment['property_city'] ?? '')) ?></span>
                                        </td>
                                        <td>
                                            <?= esc($formatMoney((string) ($payment['amount'] ?? '0.00'))) ?><br>
                                            <span class="field-help">Exigible: <?= esc($formatMoney($installmentTotal)) ?></span>
                                        </td>
                                        <td>
                                            <?php if (! empty($payment['receipt_id'])): ?>
                                                <a href="<?= esc(site_url('comprobantes/' . (int) $payment['receipt_id'])) ?>" class="link-button" target="_blank" rel="noopener noreferrer">Ver comprobante</a>
                                            <?php else: ?>
                                                <span class="field-help">Sin archivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($formatDate((string) ($payment['paid_on'] ?? $payment['submitted_at'] ?? ''))) ?></td>
                                        <td>
                                            <div class="action-list-inline">
                                                <a href="<?= esc(site_url('admin/pagos/' . (int) ($payment['payment_id'] ?? $payment['id']))) ?>" class="btn btn-secondary btn-small">Revisar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
<?php $this->endSection(); ?>
