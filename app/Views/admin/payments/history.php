<?php
/**
 * Payment history dashboard for admins/employees (HU-18).
 *
 * @var list<array<string, mixed>> $payments
 * @var list<string> $periods
 * @var array<string, string|null> $filters
 * @var array<string, string> $statusOptions
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
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Historial de pagos</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) count($payments)) ?> <?= count($payments) === 1 ? 'registro' : 'registros' ?></span>
                    </div>
                    <p class="panel-subtitle">Consulta el historial completo con filtros por estado y período.</p>
                </div>
            </div>

            <section class="panel-card" aria-labelledby="history-filters-heading">
                <div class="section-header">
                    <h2 id="history-filters-heading" class="section-title">Filtros</h2>
                </div>
                <form method="get" action="<?= esc(site_url('admin/pagos/historial')) ?>" class="filter-grid">
                    <div class="form-field">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($statusOptions as $status => $label): ?>
                                <option value="<?= esc($status) ?>"<?= (string) ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="period" class="form-label">Período</label>
                        <select id="period" name="period" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($periods as $period): ?>
                                <?php $periodLabel = preg_match('/^\d{4}-\d{2}$/', (string) $period) === 1 ? InstallmentModel::labelForPeriod((string) $period . '-01') : InstallmentModel::labelForPeriod((string) $period); ?>
                                <option value="<?= esc($period) ?>"<?= (string) ($filters['period'] ?? '') === $period ? ' selected' : '' ?>><?= esc($periodLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions compact-actions">
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                        <a href="<?= esc(site_url('admin/pagos/historial')) ?>" class="btn btn-secondary">Limpiar</a>
                    </div>
                </form>
            </section>

            <?php if ($payments === []): ?>
                <section class="panel-card form-section-card" aria-live="polite">
                    <div class="section-header">
                        <h2 class="section-title">Sin movimientos</h2>
                        <p class="section-subtitle">No hay pagos registrados con los filtros aplicados.</p>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel-card" aria-labelledby="payment-history-heading">
                    <div class="section-header">
                        <h2 id="payment-history-heading" class="section-title">Resultados</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" aria-label="Historial de pagos">
                            <thead>
                                <tr>
                                    <th>Pago</th>
                                    <th>Inquilino</th>
                                    <th>Cuota</th>
                                    <th>Inmueble</th>
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
                                        $tenantName = trim((string) ($payment['tenant_last_name'] ?? '') . ', ' . (string) ($payment['tenant_first_name'] ?? ''));
                                        $periodLabel = InstallmentModel::labelForPeriod((string) ($payment['period'] ?? ''));
                                        $status = (string) ($payment['status'] ?? '');
                                    ?>
                                    <tr>
                                        <td><strong>#<?= esc((string) ($payment['payment_id'] ?? '')) ?></strong></td>
                                        <td><?= esc($tenantName !== ',' ? $tenantName : 'Sin nombre') ?></td>
                                        <td><?= esc((string) ($payment['installment_number'] ?? '')) ?> · <?= esc($periodLabel) ?></td>
                                        <td><?= esc((string) ($payment['property_address'] ?? '')) ?><br><span class="field-help"><?= esc((string) ($payment['property_city'] ?? '')) ?></span></td>
                                        <td><?= esc($formatMoney((string) ($payment['amount'] ?? '0.00'))) ?></td>
                                        <td>
                                            <?= esc(PaymentModel::labelForMethod((string) ($payment['method'] ?? ''))) ?>
                                            <?php if (! empty($payment['reference_number'])): ?>
                                                <br><span class="field-help">Ref. <?= esc((string) $payment['reference_number']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-pill badge-payment-<?= esc($status) ?>"><?= esc(PaymentModel::labelForStatus($status)) ?></span>
                                            <?php if ($status === PaymentModel::STATUS_REJECTED && ! empty($payment['rejection_reason'])): ?>
                                                <br><span class="field-help">Motivo: <?= esc((string) $payment['rejection_reason']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (! empty($payment['receipt_id'])): ?>
                                                <a href="<?= esc(site_url('comprobantes/' . (int) $payment['receipt_id'])) ?>" class="link-button" target="_blank" rel="noopener noreferrer">Ver</a>
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
