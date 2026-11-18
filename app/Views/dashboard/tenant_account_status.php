<?php
/**
 * Tenant account statement (HU-15), grouped by installment status.
 *
 * @var array<string, list<array<string, mixed>>> $byStatus
 * @var int $totalCount
 * @var list<string> $statusOrder
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

$plural = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Estado de cuenta</h1>
                        <span class="badge-pill badge-neutral"><?= esc($plural($totalCount, 'cuota', 'cuotas')) ?></span>
                    </div>
                    <p class="panel-subtitle">Tus cuotas, vencimientos, recargos y pagos informados.</p>
                </div>
            </div>

            <?php if ($totalCount === 0): ?>
                <section class="panel-card" aria-labelledby="tenant-empty-state">
                    <h2 id="tenant-empty-state" class="section-title">Todavía no tenés cuotas emitidas</h2>
                    <p class="panel-subtitle">Cuando se emitan cuotas de tus contratos, aparecerán aquí. Mientras tanto, podés explorar el catálogo de propiedades disponibles o seguir el estado de tus solicitudes de alquiler.</p>
                    <div class="inline-actions-row">
                        <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-primary" id="btn-empty-account-search-properties">Buscar inmuebles</a>
                        <a href="<?= esc(site_url('inquilino/solicitudes')) ?>" class="btn btn-secondary" id="btn-empty-account-my-requests">Mis solicitudes</a>
                    </div>
                </section>
            <?php else: ?>
                <?php foreach ($statusOrder as $status): ?>
                    <?php $items = $byStatus[$status]; ?>
                    <?php if ($items === []): continue; endif; ?>
                    <section class="panel-card table-card" aria-labelledby="tenant-status-<?= esc($status) ?>">
                        <div class="section-header">
                            <h2 id="tenant-status-<?= esc($status) ?>" class="section-title">
                                <?= esc(InstallmentModel::labelForStatus($status)) ?>
                                <span class="badge-pill badge-installment-<?= esc($status) ?>"><?= esc((string) count($items)) ?></span>
                            </h2>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" data-status="<?= esc($status) ?>">
                                <thead>
                                    <tr>
                                        <th scope="col">Cuota</th>
                                        <th scope="col">Inmueble</th>
                                        <th scope="col">Vencimiento</th>
                                        <th scope="col" class="text-right">Base</th>
                                        <th scope="col" class="text-right">Recargo</th>
                                        <th scope="col" class="text-right">Total exigible</th>
                                        <th scope="col">Pago</th>
                                        <th scope="col">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $payment = $item['payment'];
                                        $statusValue = (string) $item['status'];
                                        $canReport = in_array($statusValue, [InstallmentModel::STATUS_PENDING, InstallmentModel::STATUS_OVERDUE], true)
                                            && $item['contract_status'] === 'in_force';
                                        ?>
                                        <tr data-installment-id="<?= esc((string) $item['id']) ?>">
                                            <td>
                                                <span class="cell-title"><?= esc(InstallmentModel::labelForPeriod((string) $item['period'])) ?></span>
                                                <span class="cell-note">Cuota <?= esc((string) $item['number']) ?></span>
                                            </td>
                                            <td>
                                                <span class="cell-title"><?= esc((string) $item['property_address']) ?></span>
                                                <span class="cell-note"><?= esc((string) $item['property_city']) ?></span>
                                            </td>
                                            <td class="nowrap-cell">
                                                <span<?= $statusValue === InstallmentModel::STATUS_OVERDUE ? ' class="text-danger"' : '' ?>>
                                                    <?= esc($formatDate($item['due_date'])) ?>
                                                </span>
                                                <?php if ($statusValue === InstallmentModel::STATUS_OVERDUE): ?>
                                                    <span class="cell-note text-danger"><?= esc($plural((int) $item['days_late'], 'día', 'días')) ?> de atraso</span>
                                                <?php elseif ($statusValue === InstallmentModel::STATUS_PENDING): ?>
                                                    <span class="cell-note"><?= $item['days_to_due'] === 0 ? 'Vence hoy' : 'Vence en ' . esc($plural((int) $item['days_to_due'], 'día', 'días')) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($item['base_amount'])) ?></td>
                                            <td class="mono-cell nowrap-cell text-right">
                                                <?php if ((float) $item['late_fee'] > 0): ?>
                                                    <span<?= $statusValue === InstallmentModel::STATUS_OVERDUE ? ' class="text-danger"' : '' ?>><?= esc($formatMoney($item['late_fee'])) ?></span>
                                                    <?php if ($statusValue === InstallmentModel::STATUS_OVERDUE): ?>
                                                        <span class="cell-note"><?= esc($plural((int) $item['days_late'], 'día', 'días')) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    $ 0
                                                <?php endif; ?>
                                            </td>
                                            <td class="mono-cell nowrap-cell text-right"><strong><?= esc($formatMoney($item['total'])) ?></strong></td>
                                            <td>
                                                <?php if ($statusValue === InstallmentModel::STATUS_PAID && is_array($payment) && $payment['status'] === PaymentModel::STATUS_APPROVED): ?>
                                                    <span class="cell-title"><?= esc($formatDate($payment['paid_on'])) ?></span>
                                                    <span class="cell-note"><?= esc(PaymentModel::labelForMethod((string) $payment['method'])) ?></span>
                                                    <?php if (! empty($payment['receipt_id'])): ?>
                                                        <a class="cell-note" href="<?= esc(site_url('comprobantes/' . (int) $payment['receipt_id'])) ?>">Ver comprobante aprobado</a>
                                                    <?php endif; ?>
                                                <?php elseif ($statusValue === InstallmentModel::STATUS_UNDER_REVIEW): ?>
                                                    <span class="cell-title">Pago informado</span>
                                                    <?php if (is_array($payment)): ?>
                                                        <span class="cell-note"><?= esc(PaymentModel::labelForMethod((string) $payment['method'])) ?> · <?= esc($formatDate($payment['paid_on'])) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="cell-note">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="nowrap-cell">
                                                <?php if ($canReport): ?>
                                                    <a class="btn btn-secondary btn-sm" href="<?= esc(site_url('inquilino/cuotas/' . (int) $item['id'] . '/informar-pago')) ?>">
                                                        Informar pago
                                                    </a>
                                                <?php elseif ($statusValue === InstallmentModel::STATUS_UNDER_REVIEW): ?>
                                                    <span class="cell-note">En validación</span>
                                                <?php elseif (in_array($statusValue, [InstallmentModel::STATUS_PENDING, InstallmentModel::STATUS_OVERDUE], true)): ?>
                                                    <span class="cell-note">Contrato cerrado</span>
                                                <?php else: ?>
                                                    <span class="cell-note">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
<?php $this->endSection() ?>
