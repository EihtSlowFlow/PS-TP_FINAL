<?php
/**
 * Contract file (screen ADM-08, HU-10): data of the contract, the property, the tenant and the owner.
 *
 * Deactivated tenants, owners and properties are shown on purpose: a contract keeps its history (HU-04, HU-05).
 *
 * @var array<string, mixed> $contract Row returned by ContractModel::findWithDetails()
 */
use App\Models\ContractModel;
use App\Models\PropertyModel;

$status      = (string) $contract['status'];
$rent        = (float) $contract['monthly_rent'];
$commission  = round($rent * (float) $contract['commission_percentage'] / 100, 2);
$net         = $rent - $commission;
$lateFeeDaily = round($rent * (float) $contract['late_fee_daily_percentage'] / 100, 2);

$formatMoney = static function (float $amount): string {
    return '$ ' . number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatPercentage = static function (mixed $value): string {
    $text = (string) $value;
    $text = str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;

    return str_replace('.', ',', $text) . ' %';
};

$formatDate = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';
};

$formatDateTime = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y H:i', strtotime((string) $value)) : '—';
};

$fullName = static function (array $row, string $prefix): string {
    return trim($row[$prefix . '_last_name'] . ', ' . $row[$prefix . '_first_name']);
};

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al listado de inmuebles</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="users-heading-row">
                        <h1 class="panel-title">Contrato #<?= esc((string) $contract['id']) ?> · <?= esc((string) $contract['property_address']) ?></h1>
                        <span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span>
                    </div>
                    <p class="panel-subtitle">
                        Contrato de locación de <?= esc((string) $contract['property_address']) ?>, <?= esc((string) $contract['property_city']) ?>,
                        con vigencia del <?= esc($formatDate($contract['start_date'])) ?> al <?= esc($formatDate($contract['end_date'])) ?>.
                    </p>
                </div>
            </section>

            <div class="user-form-layout has-sidebar">
                <div class="user-main-form">
                    <section class="panel-card form-section-card" aria-labelledby="section-file-parties">
                        <div class="section-header">
                            <h2 id="section-file-parties" class="section-title">Partes e inmueble</h2>
                            <p class="section-subtitle">Inmueble alquilado, propietario titular e inquilino.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Inmueble</dt>
                                <dd>
                                    <?= esc((string) $contract['property_address']) ?>, <?= esc((string) $contract['property_city']) ?>
                                    <span class="badge-pill badge-property-<?= esc((string) $contract['property_status']) ?>"><?= esc(PropertyModel::labelForStatus((string) $contract['property_status'])) ?></span>
                                    <?php if (! empty($contract['property_deleted_at'])): ?>
                                        <span class="badge-pill badge-danger">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Tipo</dt>
                                <dd>
                                    <?= esc(PropertyModel::labelForType((string) $contract['property_type'])) ?>
                                    <?= $contract['property_rooms'] !== null ? ' · ' . esc((string) $contract['property_rooms']) . ' amb.' : '' ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Propietario</dt>
                                <dd>
                                    <?= esc($fullName($contract, 'owner')) ?> · DNI <?= esc((string) $contract['owner_document_number']) ?>
                                    <?php if (! empty($contract['owner_deleted_at'])): ?>
                                        <span class="badge-pill badge-danger">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Contacto del propietario</dt>
                                <dd><?= esc((string) $contract['owner_email']) ?> · <?= esc((string) ($contract['owner_phone'] ?? '—')) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Inquilino</dt>
                                <dd>
                                    <?= esc($fullName($contract, 'tenant')) ?> · DNI <?= esc((string) $contract['tenant_document_number']) ?>
                                    <?php if (! empty($contract['tenant_deleted_at'])): ?>
                                        <span class="badge-pill badge-danger">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Contacto del inquilino</dt>
                                <dd><?= esc((string) $contract['tenant_email']) ?> · <?= esc((string) ($contract['tenant_phone'] ?? '—')) ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="section-file-term">
                        <div class="section-header">
                            <h2 id="section-file-term" class="section-title">Plazo y condiciones</h2>
                            <p class="section-subtitle">Vigencia, monto mensual, actualización, comisión y mora pactados.</p>
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
                                <dd><?= esc(ContractModel::describeDuration((string) $contract['start_date'], (string) $contract['end_date'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Monto mensual</dt>
                                <dd><?= esc($formatMoney($rent)) ?></dd>
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
                                <dt>Comisión de la inmobiliaria</dt>
                                <dd><?= esc($formatPercentage($contract['commission_percentage'])) ?> (<?= esc($formatMoney($commission)) ?> por mes)</dd>
                            </div>
                            <div class="audit-item">
                                <dt>Neto mensual al propietario</dt>
                                <dd><?= esc($formatMoney($net)) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Recargo diario por mora</dt>
                                <dd><?= esc($formatPercentage($contract['late_fee_daily_percentage'])) ?> (<?= esc($formatMoney($lateFeeDaily)) ?> por día de atraso)</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <aside class="user-audit-sidebar" aria-label="Estado del contrato">
                    <section class="panel-card audit-card">
                        <h3 class="section-title">Estado</h3>
                        <p class="section-subtitle">
                            <span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span>
                        </p>
                        <?php if ($status === ContractModel::STATUS_IN_FORCE): ?>
                            <p class="field-help">Mientras el contrato esté vigente, el inmueble figura como «Alquilado» y no puede asociarse a otro contrato.</p>
                        <?php else: ?>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Cerrado el</dt>
                                    <dd><?= esc($formatDate($contract['closed_at'] ?? null)) ?></dd>
                                </div>
                                <div class="audit-item">
                                    <dt>Motivo</dt>
                                    <dd><?= esc((string) ($contract['closing_reason'] ?? '—')) ?></dd>
                                </div>
                            </dl>
                        <?php endif; ?>
                    </section>

                    <section class="panel-card audit-card">
                        <h3 class="section-title">Registro</h3>
                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Registrado el</dt>
                                <dd><?= esc($formatDateTime($contract['created_at'] ?? null)) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Última modificación</dt>
                                <dd><?= esc($formatDateTime($contract['updated_at'] ?? null)) ?></dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>
