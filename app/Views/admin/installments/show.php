<?php
/**
 * Detail of one installment with the breakdown of what is due: base amount, late fee and total (HU-14).
 *
 * @var string               $today       Day the late fee was calculated for ("YYYY-MM-DD")
 * @var array<string, mixed> $installment Row from InstallmentModel::findCollectionDetail() plus `days_late`, `days_to_due` and `total`
 * @var string               $periodLabel Period for people ("Diciembre 2026")
 */
use App\Models\ContractModel;
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

$status     = (string) $installment['status'];
$daysLate   = (int) $installment['days_late'];
$daysToDue  = (int) $installment['days_to_due'];
$lateFee    = (string) $installment['late_fee'];
$hasLateFee = (float) $lateFee > 0;
$tenantName = trim((string) $installment['tenant_last_name'] . ', ' . (string) $installment['tenant_first_name']);

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/cuotas')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a cobranzas</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="users-heading-row">
                        <h1 class="panel-title">Cuota <?= esc((string) $installment['number']) ?> · <?= esc($periodLabel) ?></h1>
                        <span class="badge-pill badge-installment-<?= esc($status) ?>" id="installment-status"><?= esc(InstallmentModel::labelForStatus($status)) ?></span>
                    </div>
                    <p class="panel-subtitle">
                        Contrato <a href="<?= esc(site_url('admin/contratos/' . (int) $installment['contract_id'])) ?>">#<?= esc((string) $installment['contract_id']) ?></a>
                        · <?= esc((string) $installment['property_address']) ?>, <?= esc((string) $installment['property_city']) ?>
                        · Estado calculado al <?= esc($formatDate($today)) ?>.
                    </p>
                </div>
            </section>

            <div class="user-form-layout has-sidebar">
                <div class="user-main-form">
                    <section class="panel-card form-section-card" aria-labelledby="section-breakdown">
                        <div class="section-header">
                            <h2 id="section-breakdown" class="section-title">Monto exigible</h2>
                            <p class="section-subtitle">Desglose del importe de la cuota en monto base, recargo por mora y total.</p>
                        </div>

                        <dl class="breakdown-list" id="breakdown">
                            <div class="breakdown-row">
                                <dt>Monto base</dt>
                                <dd id="breakdown-base"><?= esc($formatMoney($installment['base_amount'])) ?></dd>
                            </div>
                            <div class="breakdown-row">
                                <dt>
                                    Recargo por mora
                                    <?php if ($status === InstallmentModel::STATUS_OVERDUE): ?>
                                        <span class="breakdown-hint" id="breakdown-formula">
                                            <?= esc($formatMoney($installment['base_amount'])) ?> × <?= esc($formatPercentage($installment['late_fee_daily_percentage'])) ?> diario × <?= esc($plural($daysLate, 'día', 'días')) ?> de atraso
                                        </span>
                                    <?php elseif ($status === InstallmentModel::STATUS_UNDER_REVIEW): ?>
                                        <span class="breakdown-hint" id="breakdown-formula">No cambia mientras el pago está en revisión.</span>
                                    <?php elseif ($status === InstallmentModel::STATUS_PAID): ?>
                                        <span class="breakdown-hint" id="breakdown-formula">La cuota está paga: el recargo ya no se modifica.</span>
                                    <?php else: ?>
                                        <span class="breakdown-hint" id="breakdown-formula">Sin atraso: la cuota todavía está en plazo.</span>
                                    <?php endif; ?>
                                </dt>
                                <dd id="breakdown-late-fee"<?= $status === InstallmentModel::STATUS_OVERDUE ? ' class="text-danger"' : '' ?>><?= esc($hasLateFee ? $formatMoney($lateFee) : '$ 0') ?></dd>
                            </div>
                            <div class="breakdown-row breakdown-total">
                                <dt>Total exigible</dt>
                                <dd id="breakdown-total"><?= esc($formatMoney($installment['total'])) ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-installment-data">
                        <div class="section-header">
                            <h2 id="section-installment-data" class="section-title">Datos de la cuota</h2>
                            <p class="section-subtitle">Período, vencimiento y partes del contrato.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Período</dt>
                                <dd><?= esc($periodLabel) ?> · cuota <?= esc((string) $installment['number']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Vencimiento</dt>
                                <dd>
                                    <?= esc($formatDate($installment['due_date'])) ?>
                                    <?php if ($status === InstallmentModel::STATUS_PENDING): ?>
                                        · <?= $daysToDue === 0 ? 'vence hoy' : 'vence en ' . esc($plural($daysToDue, 'día', 'días')) ?>
                                    <?php elseif ($daysLate > 0 && in_array($status, [InstallmentModel::STATUS_OVERDUE, InstallmentModel::STATUS_UNDER_REVIEW], true)): ?>
                                        · pasada hace <?= esc($plural($daysLate, 'día', 'días')) ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Mora pactada</dt>
                                <dd><?= esc($formatPercentage($installment['late_fee_daily_percentage'])) ?> por día de atraso</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Inquilino</dt>
                                <dd>
                                    <?= esc($tenantName) ?> · DNI <?= esc((string) $installment['tenant_document_number']) ?>
                                    <?php if (! empty($installment['tenant_deleted_at'])): ?>
                                        <span class="badge-pill badge-danger">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Contacto</dt>
                                <dd><?= esc((string) $installment['tenant_email']) ?> · <?= esc((string) ($installment['tenant_phone'] ?? '—')) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Inmueble</dt>
                                <dd>
                                    <?= esc((string) $installment['property_address']) ?>, <?= esc((string) $installment['property_city']) ?>
                                    · <?= esc(PropertyModel::labelForType((string) $installment['property_type'])) ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Contrato</dt>
                                <dd>
                                    <a href="<?= esc(site_url('admin/contratos/' . (int) $installment['contract_id'])) ?>">#<?= esc((string) $installment['contract_id']) ?></a>
                                    <span class="badge-pill badge-contract-<?= esc((string) $installment['contract_status']) ?>"><?= esc(ContractModel::labelForStatus((string) $installment['contract_status'])) ?></span>
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <aside class="user-audit-sidebar" aria-label="Cómo se calcula el recargo">
                    <section class="panel-card form-section-card">
                        <h2 class="section-title">Cómo se calcula</h2>
                        <p class="section-subtitle">
                            Recargo = monto base × (porcentaje diario ÷ 100) × días de atraso. Los días se cuentan desde el día
                            siguiente al vencimiento y el resultado se redondea al centavo. Una cuota pagada no genera ni modifica recargos,
                            y una cuota en revisión conserva el que tenía.
                        </p>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>
