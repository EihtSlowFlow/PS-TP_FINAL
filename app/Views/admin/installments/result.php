<?php
/**
 * Installment issuing report (screen ADM-09b, HU-13): installments issued, contracts skipped and the total to collect.
 *
 * @var array{period: string, single: bool, issued_ids: list<int>, issued_count: int, issued_total: string, skipped_count: int, issued_at: string, operator: string} $report
 * @var string                     $periodLabel  Period for people ("Diciembre 2026")
 * @var list<array<string, mixed>> $installments The installments issued by this operation, with contract, property and tenant data
 */
use App\Models\InstallmentModel;
use App\Models\PropertyModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatDate = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';
};

$issuedCount  = (int) $report['issued_count'];
$skippedCount = (int) $report['skipped_count'];
$nothingNew   = $issuedCount === 0;
$month        = (string) (int) substr($report['period'], 5, 2);
$year         = substr($report['period'], 0, 4);

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Resultado de la emisión</h1>
                        <span class="badge-pill <?= $nothingNew ? 'badge-neutral' : 'badge-status-active' ?>">
                            <?= $nothingNew ? 'Sin cuotas nuevas' : 'Emisión completada' ?>
                        </span>
                    </div>
                    <p class="panel-subtitle">
                        Período <strong><?= esc($periodLabel) ?></strong>
                        · <?= esc(date('d/m/Y H:i', strtotime($report['issued_at']))) ?> hs
                        <?php if ($report['operator'] !== ''): ?>· Operador: <strong><?= esc($report['operator']) ?></strong><?php endif; ?>
                        <?= $report['single'] ? '· Emisión de un solo contrato' : '· Emisión de todos los contratos' ?>
                    </p>
                </div>
            </div>

            <?php if ($nothingNew): ?>
                <div class="panel-flash panel-flash-info" role="status" id="nothing-new-notice">
                    <span>No se generó ninguna cuota nueva: <?= $skippedCount === 1 ? 'el contrato ya tenía' : 'los contratos ya tenían' ?> su cuota de <?= esc($periodLabel) ?> y no se modificó.</span>
                </div>
            <?php endif; ?>

            <section class="users-kpi-grid result-kpi-grid" aria-label="Resumen de la emisión">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Cuotas emitidas</span>
                        <strong class="kpi-value" id="report-issued-count"><?= esc((string) $issuedCount) ?></strong>
                        <span class="kpi-note kpi-note-success"><?= $issuedCount === 1 ? 'Cuota generada' : 'Cuotas generadas' ?> en estado «Pendiente»</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Omitidas por ya existir</span>
                        <strong class="kpi-value" id="report-skipped-count"><?= esc((string) $skippedCount) ?></strong>
                        <span class="kpi-note kpi-note-info">Contratos con cuota previa en el período</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Monto total a recaudar</span>
                        <strong class="kpi-value" id="report-total"><?= esc($formatMoney($report['issued_total'])) ?></strong>
                        <span class="kpi-note kpi-note-primary">Suma de las cuotas emitidas</span>
                    </div>
                </div>
            </section>

            <section class="panel-card table-card" aria-label="Detalle de las cuotas emitidas">
                <div class="table-card-heading">
                    <h2 class="section-title">Detalle de las cuotas emitidas — <?= esc($periodLabel) ?></h2>
                </div>

                <div class="table-responsive">
                    <table class="users-table" id="report-table">
                        <thead>
                            <tr>
                                <th scope="col">Cuota</th>
                                <th scope="col">Contrato</th>
                                <th scope="col">Inquilino</th>
                                <th scope="col">Inmueble</th>
                                <th scope="col">Vencimiento</th>
                                <th scope="col" class="text-right">Monto</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($installments === []): ?>
                                <tr>
                                    <td colspan="7" class="empty-table-cell">No se emitió ninguna cuota en esta operación.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($installments as $installment): ?>
                                <?php
                                $contractId = (int) $installment['contract_id'];
                                $status     = (string) $installment['status'];
                                $tenantName = trim((string) $installment['tenant_last_name'] . ', ' . (string) $installment['tenant_first_name']);
                                ?>
                                <tr data-contract-id="<?= esc((string) $contractId) ?>">
                                    <td class="mono-cell nowrap-cell">N.º <?= esc((string) $installment['number']) ?></td>
                                    <td class="mono-cell nowrap-cell">
                                        <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>">#<?= esc((string) $contractId) ?></a>
                                    </td>
                                    <td>
                                        <div class="user-cell-meta">
                                            <span class="user-cell-name"><?= esc($tenantName) ?></span>
                                            <span class="user-cell-sub">DNI <?= esc((string) $installment['tenant_document_number']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-cell-meta">
                                            <span class="user-cell-name"><?= esc((string) $installment['property_address']) ?></span>
                                            <span class="user-cell-sub">
                                                <?= esc((string) $installment['property_city']) ?> · <?= esc(PropertyModel::labelForType((string) $installment['property_type'])) ?><?= $installment['property_rooms'] !== null ? ' · ' . esc((string) $installment['property_rooms']) . ' amb.' : '' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="mono-cell nowrap-cell"><?= esc($formatDate($installment['due_date'])) ?></td>
                                    <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($installment['base_amount'])) ?></td>
                                    <td class="nowrap-cell"><span class="badge-pill badge-installment-<?= esc($status) ?>"><?= esc(InstallmentModel::labelForStatus($status)) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if ($installments !== []): ?>
                            <tfoot>
                                <tr class="table-total-row">
                                    <td colspan="5">Total consolidado: <?= esc((string) $issuedCount) ?> <?= $issuedCount === 1 ? 'cuota emitida' : 'cuotas emitidas' ?></td>
                                    <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($report['issued_total'])) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </section>

            <section class="panel-card form-section-card emit-confirm" aria-label="Siguientes pasos">
                <div>
                    <h2 class="section-title">¿Qué sigue?</h2>
                    <p class="section-subtitle">Las cuotas emitidas ya figuran como «Pendiente». El cálculo del recargo por mora y el estado de cuenta del inquilino llegan con las próximas historias.</p>
                </div>
                <div class="form-actions-footer">
                    <a href="<?= esc(site_url('admin/cuotas/emitir') . '?' . http_build_query(['month' => $month, 'year' => $year])) ?>" class="btn btn-secondary" id="btn-back-to-preview">Ver el período otra vez</a>
                    <a href="<?= esc(site_url('admin/cuotas/emitir')) ?>" class="btn btn-primary" id="btn-emit-another">Emitir otro período</a>
                </div>
            </section>
<?php $this->endSection() ?>
