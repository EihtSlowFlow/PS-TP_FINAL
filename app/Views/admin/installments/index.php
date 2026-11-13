<?php
/**
 * Collections: installments with their base amount, automatic late fee and total (screen ADM-10, HU-14).
 *
 * @var string                     $today        Day the late fees were calculated for ("YYYY-MM-DD")
 * @var array<string, int>         $refresh      Result of InstallmentModel::refreshLateFees()
 * @var list<array<string, mixed>> $installments Rows of the current page, with `days_late`, `days_to_due` and `total`
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{q: string, status: string, period: string} $filters
 * @var array<string, string>      $filterErrors
 * @var list<string>               $periods      Periods ("YYYY-MM-01") the period filter offers
 * @var array{count: int, base: string, late_fee: string, total: string} $totals Totals of everything the filters match
 * @var array<string, array<string, mixed>> $kpis `to_collect`, `collected` and `overdue`
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

$formatPercentage = static function (mixed $value): string {
    $text = (string) $value;
    $text = str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;

    return str_replace('.', ',', $text) . ' %';
};

$plural = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);

$buildPageUrl = static function (int $targetPage) use ($filters): string {
    $query = array_filter($filters, static fn (string $value): bool => $value !== '');
    $query['page'] = (string) $targetPage;

    return site_url('admin/cuotas') . '?' . http_build_query($query);
};

$hasFilters = $filters['q'] !== '' || $filters['status'] !== '' || $filters['period'] !== '';
$markedNow  = (int) $refresh['marked_overdue'];

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="panel-flash panel-flash-info" role="status" id="late-fee-banner">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>
                    Recargos calculados al <strong><?= esc($formatDate($today)) ?></strong>: monto base × porcentaje diario de mora del contrato × días de atraso,
                    contados desde el día siguiente al vencimiento. Las cuotas en revisión conservan su recargo.
                    <?php if ($markedNow > 0): ?>
                        <strong id="marked-overdue-note">En esta consulta <?= esc($markedNow === 1 ? 'pasó 1 cuota' : 'pasaron ' . $markedNow . ' cuotas') ?> a «Vencida».</strong>
                    <?php endif; ?>
                </span>
            </div>

            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Cobranzas</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) $pagination['total']) ?> <?= $pagination['total'] === 1 ? 'cuota' : 'cuotas' ?></span>
                    </div>
                    <p class="panel-subtitle">Seguimiento de cuotas, vencimientos y recargos por mora de los contratos.</p>
                </div>
                <a href="<?= esc(site_url('admin/cuotas/emitir')) ?>" class="btn btn-secondary" id="btn-emit-installments">Emitir cuotas</a>
            </div>

            <section class="users-kpi-grid result-kpi-grid emit-kpi-grid" aria-label="Resumen de cobranzas">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">A cobrar (en plazo o en revisión)</span>
                        <strong class="kpi-value" id="kpi-to-collect"><?= esc($formatMoney($kpis['to_collect']['amount'])) ?></strong>
                        <span class="kpi-note kpi-note-info"><?= esc($plural((int) $kpis['to_collect']['count'], 'cuota', 'cuotas')) ?> sin mora</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Cobrado</span>
                        <strong class="kpi-value" id="kpi-collected"><?= esc($formatMoney($kpis['collected']['amount'])) ?></strong>
                        <span class="kpi-note kpi-note-success"><?= esc($plural((int) $kpis['collected']['count'], 'cuota pagada', 'cuotas pagadas')) ?></span>
                    </div>
                </div>

                <div class="kpi-card kpi-card-danger">
                    <div>
                        <span class="kpi-label">En mora (vencido)</span>
                        <strong class="kpi-value text-danger" id="kpi-overdue"><?= esc($formatMoney($kpis['overdue']['amount'])) ?></strong>
                        <span class="kpi-note text-danger">
                            <?= esc($plural((int) $kpis['overdue']['count'], 'cuota vencida', 'cuotas vencidas')) ?> · incluye
                            <?= esc($formatMoney($kpis['overdue']['late_fee'])) ?> de recargos
                        </span>
                    </div>
                </div>
            </section>

            <section class="panel-card catalog-filters" aria-labelledby="installment-filters-heading">
                <h2 id="installment-filters-heading" class="section-title">Buscar y filtrar cuotas</h2>
                <form action="<?= esc(site_url('admin/cuotas')) ?>" method="get" class="catalog-filter-form" id="installment-filter-form">
                    <div class="form-field">
                        <label for="filter-query">Inquilino, DNI o inmueble</label>
                        <input
                            id="filter-query"
                            name="q"
                            type="search"
                            maxlength="100"
                            value="<?= esc($filters['q']) ?>"
                            class="form-control<?= isset($filterErrors['q']) ? ' is-invalid' : '' ?>"
                        >
                        <?php if (isset($filterErrors['q'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['q']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-period">Período</label>
                        <select id="filter-period" name="period" class="form-control<?= isset($filterErrors['period']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los períodos</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= esc(substr($period, 0, 7)) ?>"<?= $filters['period'] === substr($period, 0, 7) ? ' selected' : '' ?>>
                                    <?= esc(InstallmentModel::labelForPeriod($period)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['period'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['period']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-status">Estado</label>
                        <select id="filter-status" name="status" class="form-control<?= isset($filterErrors['status']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los estados</option>
                            <?php foreach (InstallmentModel::STATUS_LABELS as $statusValue => $statusLabel): ?>
                                <option value="<?= esc($statusValue) ?>"<?= $filters['status'] === $statusValue ? ' selected' : '' ?>>
                                    <?= esc($statusLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['status'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['status']) ?></span><?php endif; ?>
                    </div>
                    <div class="catalog-filter-actions">
                        <a href="<?= esc(site_url('admin/cuotas')) ?>" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="Listado de cuotas">
                <div class="table-responsive">
                    <table class="users-table installments-table" id="installments-table">
                        <thead>
                            <tr>
                                <th scope="col">Inquilino</th>
                                <th scope="col">Inmueble</th>
                                <th scope="col">Período</th>
                                <th scope="col">Vencimiento</th>
                                <th scope="col" class="text-right">Base</th>
                                <th scope="col" class="text-right">Mora</th>
                                <th scope="col" class="text-right">Total</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($installments === []): ?>
                                <tr>
                                    <td colspan="9" class="empty-table-cell">
                                        <?= $hasFilters ? 'No se encontraron cuotas con los filtros seleccionados.' : 'Todavía no hay cuotas emitidas.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($installments as $row): ?>
                                <?php
                                $installmentId = (int) $row['id'];
                                $status        = (string) $row['status'];
                                $tenantName    = trim((string) $row['tenant_last_name'] . ', ' . (string) $row['tenant_first_name']);
                                $lateFee       = (string) $row['late_fee'];
                                $hasLateFee    = (float) $lateFee > 0;
                                $daysLate      = (int) $row['days_late'];
                                $daysToDue     = (int) $row['days_to_due'];
                                $isOverdue     = $status === InstallmentModel::STATUS_OVERDUE;
                                ?>
                                <tr data-installment-id="<?= esc((string) $installmentId) ?>" data-status="<?= esc($status) ?>"<?= $isOverdue ? ' class="row-overdue"' : '' ?>>
                                    <td>
                                        <div class="user-cell-meta">
                                            <span class="user-cell-name"><?= esc($tenantName) ?></span>
                                            <span class="user-cell-sub">
                                                DNI <?= esc((string) $row['tenant_document_number']) ?>
                                                <?= ! empty($row['tenant_deleted_at']) ? ' · dado de baja' : '' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-cell-meta">
                                            <span class="user-cell-name"><?= esc((string) $row['property_address']) ?></span>
                                            <span class="user-cell-sub">
                                                <?= esc((string) $row['property_city']) ?> · <?= esc(PropertyModel::labelForType((string) $row['property_type'])) ?><?= $row['property_rooms'] !== null ? ' · ' . esc((string) $row['property_rooms']) . ' amb.' : '' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nowrap-cell"><?= esc(InstallmentModel::labelForPeriod((string) $row['period'])) ?></span>
                                        <span class="cell-note">Cuota <?= esc((string) $row['number']) ?> · contrato #<?= esc((string) $row['contract_id']) ?></span>
                                    </td>
                                    <td class="mono-cell nowrap-cell">
                                        <span<?= $isOverdue ? ' class="text-danger"' : '' ?>><?= esc($formatDate($row['due_date'])) ?></span>
                                        <?php if ($status === InstallmentModel::STATUS_PENDING): ?>
                                            <span class="cell-note"><?= $daysToDue === 0 ? 'Vence hoy' : 'Vence en ' . esc($plural($daysToDue, 'día', 'días')) ?></span>
                                        <?php elseif ($isOverdue && $daysLate > 0): ?>
                                            <span class="cell-note text-danger"><?= $daysLate === 1 ? 'Venció ayer' : 'Venció hace ' . esc((string) $daysLate) . ' días' ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="mono-cell nowrap-cell text-right" data-cell="base"><?= esc($formatMoney($row['base_amount'])) ?></td>
                                    <td class="mono-cell nowrap-cell text-right" data-cell="late-fee">
                                        <?php if ($isOverdue): ?>
                                            <span class="text-danger"><?= esc($formatMoney($lateFee)) ?></span>
                                            <span class="cell-note"><?= esc($plural($daysLate, 'día', 'días')) ?> · <?= esc($formatPercentage($row['late_fee_daily_percentage'])) ?> diario</span>
                                        <?php elseif ($hasLateFee): ?>
                                            <?= esc($formatMoney($lateFee)) ?>
                                            <span class="cell-note"><?= $status === InstallmentModel::STATUS_UNDER_REVIEW ? 'Fijo durante la revisión' : 'Recargo registrado' ?></span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="mono-cell nowrap-cell text-right" data-cell="total"><strong><?= esc($formatMoney($row['total'])) ?></strong></td>
                                    <td>
                                        <span class="badge-pill badge-installment-<?= esc($status) ?>"><?= esc(InstallmentModel::labelForStatus($status)) ?></span>
                                    </td>
                                    <td class="nowrap-cell text-right">
                                        <a href="<?= esc(site_url('admin/cuotas/' . $installmentId)) ?>" class="btn btn-secondary btn-sm" title="Ver el desglose de la cuota <?= esc((string) $row['number']) ?> del contrato #<?= esc((string) $row['contract_id']) ?>">Ver detalle</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if ($totals['count'] > 0): ?>
                            <tfoot>
                                <tr class="table-total-row">
                                    <td colspan="4">
                                        Total de lo filtrado (<?= esc($plural((int) $totals['count'], 'cuota', 'cuotas')) ?>)
                                    </td>
                                    <td class="mono-cell nowrap-cell text-right" id="total-base"><?= esc($formatMoney($totals['base'])) ?></td>
                                    <td class="mono-cell nowrap-cell text-right" id="total-late-fee"><?= esc($formatMoney($totals['late_fee'])) ?></td>
                                    <td class="mono-cell nowrap-cell text-right" id="total-amount"><?= esc($formatMoney($totals['total'])) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="pagination-footer">
                    <div class="pagination-summary">
                        Mostrando <strong><?= esc((string) $pagination['from']) ?>–<?= esc((string) $pagination['to']) ?></strong>
                        de <strong><?= esc((string) $pagination['total']) ?></strong> cuotas
                    </div>
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de cuotas">
                            <?php if ($pagination['currentPage'] > 1): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] - 1)) ?>" class="pagination-btn">Anterior</a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Anterior</span>
                            <?php endif; ?>
                            <?php for ($page = 1; $page <= $pagination['totalPages']; $page++): ?>
                                <a
                                    href="<?= esc($buildPageUrl($page)) ?>"
                                    class="pagination-page<?= $page === $pagination['currentPage'] ? ' is-current' : '' ?>"
                                    <?= $page === $pagination['currentPage'] ? 'aria-current="page"' : '' ?>
                                ><?= esc((string) $page) ?></a>
                            <?php endfor; ?>
                            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] + 1)) ?>" class="pagination-btn">Siguiente</a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Siguiente</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>
<?php $this->endSection() ?>
