<?php
/**
 * Tenant contract file (HU-34 Scenario 2): the property and the terms agreed, read only.
 *
 * The row comes from ContractModel::findOneForTenant(), which never selects the agency commission, the owner or the
 * audit fields. Amounts and dates are the stored ones; the only derived value (`duration`) is built by the controller.
 *
 * @var array<string, mixed> $contract
 */
use App\Models\ContractModel;
use App\Models\PropertyModel;

$status = (string) $contract['status'];

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatPercentage = static function (mixed $value): string {
    $text = (string) $value;
    $text = str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;

    return str_replace('.', ',', $text) . ' %';
};

$formatDate = static fn (mixed $value): string => ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';

$closingReason = trim((string) ($contract['closing_reason'] ?? ''));

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('inquilino/contratos')) ?>" class="back-link" id="link-back-contracts">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a mis contratos</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="page-heading-row">
                        <h1 class="panel-title">Contrato #<?= esc((string) $contract['id']) ?> · <?= esc((string) $contract['property_address']) ?></h1>
                        <span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span>
                    </div>
                    <p class="panel-subtitle">
                        Contrato de locación de <?= esc((string) $contract['property_address']) ?>, <?= esc((string) $contract['property_city']) ?>,
                        con vigencia del <?= esc($formatDate($contract['start_date'])) ?> al <?= esc($formatDate($contract['end_date'])) ?>.
                    </p>
                </div>
            </section>

            <div class="form-layout has-sidebar">
                <div class="form-main">
                    <section class="panel-card form-section-card" aria-labelledby="section-contract-property">
                        <div class="section-header">
                            <h2 id="section-contract-property" class="section-title">Inmueble</h2>
                            <p class="section-subtitle">El inmueble que alquilás con este contrato.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Dirección</dt>
                                <dd><?= esc((string) $contract['property_address']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Ciudad</dt>
                                <dd><?= esc((string) $contract['property_city']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Tipo</dt>
                                <dd>
                                    <?= esc(PropertyModel::labelForType((string) $contract['property_type'])) ?>
                                    <?= $contract['property_rooms'] !== null ? ' · ' . esc((string) $contract['property_rooms']) . ' amb.' : '' ?>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-contract-terms">
                        <div class="section-header">
                            <h2 id="section-contract-terms" class="section-title">Plazo y condiciones</h2>
                            <p class="section-subtitle">Vigencia, canon, actualización, vencimiento y mora pactados.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Fecha de inicio</dt>
                                <dd><?= esc($formatDate($contract['start_date'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Fecha de finalización</dt>
                                <dd><?= esc($formatDate($contract['end_date'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Duración</dt>
                                <dd><?= esc((string) $contract['duration']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Canon mensual</dt>
                                <dd><?= esc($formatMoney($contract['monthly_rent'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Incremento por actualización</dt>
                                <dd><?= esc($formatPercentage($contract['update_percentage'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Día de vencimiento mensual</dt>
                                <dd>Día <?= esc((string) $contract['due_day']) ?> de cada mes</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Recargo diario por mora</dt>
                                <dd><?= esc($formatPercentage($contract['late_fee_daily_percentage'])) ?> por cada día de atraso</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <aside class="form-aside" aria-label="Estado del contrato">
                    <section class="panel-card audit-card">
                        <h3 class="section-title">Estado</h3>
                        <p class="section-subtitle">
                            <span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span>
                        </p>
                        <?php if ($status === ContractModel::STATUS_IN_FORCE): ?>
                            <p class="field-help">El contrato está vigente: las cuotas se emiten cada mes y se informan desde «Estado de cuenta».</p>
                        <?php else: ?>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Cerrado el</dt>
                                    <dd><?= esc($formatDate($contract['closed_at'] ?? null)) ?></dd>
                                </div>
                                <div class="audit-item">
                                    <dt>Motivo</dt>
                                    <dd><?= esc($closingReason !== '' ? $closingReason : 'Sin motivo registrado') ?></dd>
                                </div>
                            </dl>
                        <?php endif; ?>
                    </section>

                    <section class="panel-card audit-card">
                        <h3 class="section-title">Accesos</h3>
                        <div class="inline-actions-row stacked">
                            <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>" class="btn btn-primary" id="btn-contract-account-status">Estado de cuenta</a>
                            <a href="<?= esc(site_url('inquilino/mis-pagos')) ?>" class="btn btn-secondary" id="btn-contract-payments">Mis pagos</a>
                        </div>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>
