<?php
/**
 * Emit installments: period selection and preview (screen ADM-09a, HU-13).
 *
 * @var string|null                $period        First day of the month being previewed ("YYYY-MM-01"), or null
 * @var string|null                $periodLabel   Period for people ("Diciembre 2026"), or null
 * @var string|null                $periodError   Error about the chosen period, or null
 * @var string                     $selectedYear  Year the selector shows
 * @var string                     $selectedMonth Month the selector shows (1 to 12)
 * @var list<int>                  $years         Years the selector offers
 * @var list<array<string, mixed>> $toIssue       Contracts in force that cover the period and have no installment for it
 * @var list<array<string, mixed>> $alreadyIssued Contracts in force that cover the period and already have one
 * @var string                     $totalToIssue  Sum of the amounts to issue, with two decimals
 * @var string|null                $firstDueDate  Earliest due date among the installments to issue
 * @var string|null                $lastDueDate   Latest due date among the installments to issue
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

// A year typed in the address bar that the selector does not offer is still shown, so the page never lies about it.
if (ctype_digit($selectedYear) && $selectedYear !== '' && ! in_array((int) $selectedYear, $years, true)) {
    $years[] = (int) $selectedYear;
    sort($years);
}

$toIssueCount   = count($toIssue);
$alreadyCount   = count($alreadyIssued);
$totalContracts = $toIssueCount + $alreadyCount;
$hasPreview     = $period !== null;
$fieldInvalid   = $periodError !== null ? ' is-invalid' : '';

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Emitir cuotas</h1>
                        <span class="badge-pill badge-neutral">Cierre mensual</span>
                    </div>
                    <p class="panel-subtitle">Generá las cuotas mensuales de los contratos vigentes para un período. Revisá la vista previa antes de confirmar.</p>
                </div>
            </div>

            <section class="panel-card form-section-card" aria-labelledby="section-emit-period">
                <div class="section-header">
                    <h2 id="section-emit-period" class="section-title">Período a emitir</h2>
                    <p class="section-subtitle">Elegí el mes y el año de las cuotas. El período es siempre un mes completo.</p>
                </div>

                <form action="<?= esc(site_url('admin/cuotas/emitir')) ?>" method="get" id="period-form" class="period-form">
                    <div class="form-field">
                        <label for="month" class="form-label">
                            <span>Mes <span class="required-mark" aria-hidden="true">*</span></span>
                        </label>
                        <select id="month" name="month" required class="form-control<?= esc($fieldInvalid) ?>" aria-invalid="<?= $periodError !== null ? 'true' : 'false' ?>">
                            <option value="">Seleccionar mes…</option>
                            <?php foreach (InstallmentModel::MONTH_NAMES as $number => $name): ?>
                                <option value="<?= esc((string) $number) ?>"<?= $selectedMonth === (string) $number ? ' selected' : '' ?>><?= esc($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="year" class="form-label">
                            <span>Año <span class="required-mark" aria-hidden="true">*</span></span>
                        </label>
                        <select id="year" name="year" required class="form-control<?= esc($fieldInvalid) ?>" aria-invalid="<?= $periodError !== null ? 'true' : 'false' ?>">
                            <option value="">Seleccionar año…</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= esc((string) $year) ?>"<?= $selectedYear === (string) $year ? ' selected' : '' ?>><?= esc((string) $year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <button type="submit" class="btn btn-secondary" id="btn-preview">Ver vista previa</button>
                    </div>
                </form>

                <?php if ($periodError !== null): ?>
                    <p class="field-error" role="alert" id="period-error"><?= esc($periodError) ?></p>
                <?php endif; ?>

                <p class="field-help">Se emite una cuota por cada contrato vigente cuyo día de vencimiento de ese mes cae dentro de su plazo y que todavía no tiene cuota para el período.</p>
            </section>

<?php if ($hasPreview): ?>
            <section class="users-kpi-grid emit-kpi-grid" aria-label="Resumen de la emisión">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Período destino</span>
                        <strong class="kpi-value"><?= esc((string) $periodLabel) ?></strong>
                        <span class="kpi-note kpi-note-info">
                            <?= $alreadyCount === 0 ? 'Sin cuotas emitidas' : esc((string) $alreadyCount) . ($alreadyCount === 1 ? ' cuota ya emitida' : ' cuotas ya emitidas') ?>
                        </span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Contratos a emitir</span>
                        <strong class="kpi-value"><?= esc((string) $toIssueCount) ?> / <?= esc((string) $totalContracts) ?></strong>
                        <span class="kpi-note kpi-note-success">Vigentes que cubren el período</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Total a emitir</span>
                        <strong class="kpi-value" id="preview-total"><?= esc($formatMoney($totalToIssue)) ?></strong>
                        <span class="kpi-note kpi-note-primary">Monto base de las cuotas nuevas</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Vencimientos</span>
                        <strong class="kpi-value">
                            <?php if ($firstDueDate === null): ?>
                                —
                            <?php elseif ($firstDueDate === $lastDueDate): ?>
                                <?= esc($formatDate($firstDueDate)) ?>
                            <?php else: ?>
                                <?= esc(date('d', strtotime($firstDueDate))) ?> al <?= esc($formatDate($lastDueDate)) ?>
                            <?php endif; ?>
                        </strong>
                        <span class="kpi-note kpi-note-warning">Según el día pactado en cada contrato</span>
                    </div>
                </div>
            </section>

            <form action="<?= esc(site_url('admin/cuotas/emitir')) ?>" method="post" id="emit-form">
                <?= csrf_field() ?>
                <input type="hidden" name="month" value="<?= esc((string) (int) substr($period, 5, 2)) ?>">
                <input type="hidden" name="year" value="<?= esc(substr($period, 0, 4)) ?>">

                <section class="panel-card table-card" aria-label="Vista previa de la emisión">
                    <div class="table-card-heading">
                        <h2 class="section-title">Vista previa — <?= esc((string) $periodLabel) ?></h2>
                        <p class="section-subtitle">Contratos vigentes que cubren el período. Podés emitir todos juntos o solo uno.</p>
                    </div>

                    <div class="table-responsive">
                        <table class="users-table" id="preview-table">
                            <thead>
                                <tr>
                                    <th scope="col">Contrato</th>
                                    <th scope="col">Inquilino</th>
                                    <th scope="col">Inmueble</th>
                                    <th scope="col">Vencimiento</th>
                                    <th scope="col" class="text-right">Monto base</th>
                                    <th scope="col">Estado de emisión</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($totalContracts === 0): ?>
                                    <tr>
                                        <td colspan="7" class="empty-table-cell">
                                            No hay contratos vigentes que cubran <?= esc((string) $periodLabel) ?>.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ([...$toIssue, ...$alreadyIssued] as $row): ?>
                                    <?php
                                    $contractId = (int) $row['id'];
                                    $isIssued   = $row['installment_id'] !== null;
                                    $tenantName = trim((string) $row['tenant_last_name'] . ', ' . (string) $row['tenant_first_name']);
                                    ?>
                                    <tr data-contract-id="<?= esc((string) $contractId) ?>">
                                        <td class="mono-cell nowrap-cell">
                                            <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>">#<?= esc((string) $contractId) ?></a>
                                        </td>
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
                                        <td class="mono-cell nowrap-cell"><?= esc($formatDate($row['due_date'])) ?></td>
                                        <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($row['monthly_rent'])) ?></td>
                                        <td>
                                            <?php if ($isIssued): ?>
                                                <span class="badge-pill badge-neutral" title="Ya tiene la cuota de este período: se omite sin modificarla">Ya emitida</span>
                                            <?php else: ?>
                                                <span class="badge-pill badge-status-active">Se emitirá</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="nowrap-cell text-right">
                                            <?php if (! $isIssued): ?>
                                                <button type="submit" name="contract_id" value="<?= esc((string) $contractId) ?>" class="btn btn-secondary btn-sm" title="Emitir solo la cuota del contrato #<?= esc((string) $contractId) ?>">
                                                    Emitir solo esta
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($toIssueCount > 0): ?>
                                <tfoot>
                                    <tr class="table-total-row">
                                        <td colspan="4">
                                            <?= esc((string) $toIssueCount) ?> <?= $toIssueCount === 1 ? 'cuota a emitir' : 'cuotas a emitir' ?>
                                        </td>
                                        <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($totalToIssue)) ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </section>

                <section class="panel-card form-section-card emit-confirm" aria-labelledby="section-emit-confirm">
                    <div>
                        <h2 id="section-emit-confirm" class="section-title">Confirmar emisión</h2>
                        <?php if ($toIssueCount > 0): ?>
                            <p class="section-subtitle">
                                Se generarán <strong><?= esc((string) $toIssueCount) ?></strong> <?= $toIssueCount === 1 ? 'cuota' : 'cuotas' ?>
                                «Pendiente» de <?= esc((string) $periodLabel) ?> por un total de <strong><?= esc($formatMoney($totalToIssue)) ?></strong>.
                                <?= $alreadyCount > 0 ? 'Los contratos que ya tienen cuota de este período no se tocan.' : '' ?>
                            </p>
                        <?php elseif ($totalContracts > 0): ?>
                            <p class="section-subtitle">Todas las cuotas de <?= esc((string) $periodLabel) ?> ya fueron emitidas: no hay nada para generar.</p>
                        <?php else: ?>
                            <p class="section-subtitle">No hay contratos para emitir en este período.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions-footer">
                        <a href="<?= esc(site_url('admin/cuotas/emitir')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-emit-all"<?= $toIssueCount === 0 ? ' disabled' : '' ?>>
                            <?= $toIssueCount === 1 ? 'Emitir 1 cuota' : 'Emitir ' . esc((string) $toIssueCount) . ' cuotas' ?>
                        </button>
                    </div>
                </section>
            </form>
<?php endif; ?>
<?php $this->endSection() ?>
