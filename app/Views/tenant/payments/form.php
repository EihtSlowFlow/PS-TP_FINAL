<?php
/**
 * Tenant form to report a payment and attach its digital receipt (HU-16).
 *
 * @var array<string, mixed>  $installment Row from InstallmentModel::findCollectionDetail()
 * @var string                $periodLabel Human-readable period ("Octubre 2026")
 * @var string                $today       Current date ("YYYY-MM-DD")
 * @var int                   $daysLate    Days overdue (0 when not overdue)
 * @var string                $totalDue    Total due amount ("base_amount + late_fee")
 * @var array<string, string> $methods     Allowed payment methods keyed by DB enum value
 * @var string|null           $closedLabel "Contrato finalizado" / "Contrato rescindido" when the contract no longer takes payments, null otherwise
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

$errors         = session()->getFlashdata('errors') ?? [];
$status         = (string) $installment['status'];
$selectedMethod = (string) old('method', PaymentModel::METHOD_BANK_TRANSFER);
$hasLateFee     = (float) $installment['late_fee'] > 0;
$isClosed       = $closedLabel !== null;
$disabledAttr   = $isClosed ? ' disabled' : '';

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al estado de cuenta</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="page-heading-row">
                        <h1 class="panel-title">Informar pago</h1>
                        <span class="badge-pill badge-installment-<?= esc($status) ?>" id="installment-status">
                            <?= esc(InstallmentModel::labelForStatus($status)) ?> · <?= esc($periodLabel) ?>
                        </span>
                    </div>
                    <p class="panel-subtitle">
                        Registrá los datos de la operación y adjuntá el comprobante digital para que administración concilie la cuota.
                        Vencimiento: <?= esc($formatDate($installment['due_date'])) ?>.
                    </p>
                </div>
            </section>

            <?php if ($isClosed): ?>
                <div class="panel-flash panel-flash-danger" role="alert" id="contract-closed-notice">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><strong><?= esc($closedLabel) ?></strong> · Ya no se pueden informar pagos sobre las cuotas de este contrato.</span>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="panel-flash panel-flash-danger" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>Revisá los campos marcados antes de enviar el pago.</span>
                </div>
            <?php endif; ?>

            <div class="form-layout has-sidebar">
                <div class="form-main">
                    <form action="<?= esc(site_url('inquilino/cuotas/' . (int) $installment['id'] . '/informar-pago')) ?>"
                          method="post"
                          enctype="multipart/form-data"
                          class="panel-card form-section-card"
                          id="payment-report-form"
                          novalidate>
                        <?= csrf_field() ?>

                        <div class="section-header">
                            <h2 class="section-title">Datos de la operación</h2>
                            <p class="section-subtitle">
                                Cuota <?= esc((string) $installment['number']) ?> · <?= esc($periodLabel) ?> ·
                                <?= esc((string) $installment['property_address']) ?>, <?= esc((string) $installment['property_city']) ?>
                            </p>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label for="amount" class="form-label">Monto transferido (ARS) <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text"
                                       inputmode="decimal"
                                       id="amount"
                                       name="amount"
                                       value="<?= esc((string) old('amount', $totalDue)) ?>"
                                       class="form-control<?= isset($errors['amount']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['amount']) ? 'true' : 'false' ?>"
                                       required<?= $disabledAttr ?>>
                                <span class="field-help">
                                    Monto exigible hoy: <strong><?= esc($formatMoney($totalDue)) ?></strong>.
                                    Si abonaste un importe distinto, se registrará la diferencia para revisión.
                                </span>
                                <?php if (isset($errors['amount'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['amount']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="paid_on" class="form-label">Fecha de la operación <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="date"
                                       id="paid_on"
                                       name="paid_on"
                                       max="<?= esc($today) ?>"
                                       value="<?= esc((string) old('paid_on', $today)) ?>"
                                       class="form-control<?= isset($errors['paid_on']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['paid_on']) ? 'true' : 'false' ?>"
                                       required<?= $disabledAttr ?>>
                                <span class="field-help">No puede ser posterior a hoy (<?= esc($formatDate($today)) ?>).</span>
                                <?php if (isset($errors['paid_on'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['paid_on']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="method" class="form-label">Medio de pago <span class="required-mark" aria-hidden="true">*</span></label>
                                <select id="method"
                                        name="method"
                                        class="form-control<?= isset($errors['method']) ? ' is-invalid' : '' ?>"
                                        aria-invalid="<?= isset($errors['method']) ? 'true' : 'false' ?>"
                                        required<?= $disabledAttr ?>>
                                    <?php foreach ($methods as $key => $label): ?>
                                        <option value="<?= esc($key) ?>"<?= $selectedMethod === $key ? ' selected' : '' ?>>
                                            <?= esc($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['method'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['method']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field">
                                <label for="reference_number" class="form-label">Número de transacción / comprobante <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="text"
                                       id="reference_number"
                                       name="reference_number"
                                       maxlength="100"
                                       placeholder="Ej: TRF-99482104"
                                       value="<?= esc((string) old('reference_number')) ?>"
                                       class="form-control<?= isset($errors['reference_number']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['reference_number']) ? 'true' : 'false' ?>"
                                       required<?= $disabledAttr ?>>
                                <?php if (isset($errors['reference_number'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['reference_number']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-field form-field-full">
                                <label for="receipt" class="form-label">Comprobante digital (PDF, JPG o PNG · máx. 5 MB) <span class="required-mark" aria-hidden="true">*</span></label>
                                <input type="file"
                                       id="receipt"
                                       name="receipt"
                                       accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                       class="form-control<?= isset($errors['receipt']) ? ' is-invalid' : '' ?>"
                                       aria-invalid="<?= isset($errors['receipt']) ? 'true' : 'false' ?>"
                                       required<?= $disabledAttr ?>>
                                <span class="field-help">Se almacena de forma privada y solo puede ser consultado por vos y la administración.</span>
                                <?php if (isset($errors['receipt'])): ?>
                                    <p class="field-error" role="alert"><?= esc($errors['receipt']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="<?= esc(site_url('inquilino/estado-cuenta')) ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary" id="btn-submit-payment"<?= $disabledAttr ?>>Enviar pago</button>
                        </div>
                    </form>
                </div>

                <aside class="form-aside">
                    <section class="panel-card form-section-card" aria-labelledby="section-summary">
                        <div class="section-header">
                            <h2 id="section-summary" class="section-title">Resumen de cuota</h2>
                            <p class="section-subtitle">
                                <?php if ($daysLate > 0): ?>
                                    <?= esc((string) $daysLate) ?> <?= $daysLate === 1 ? 'día' : 'días' ?> de atraso al <?= esc($formatDate($today)) ?>.
                                <?php else: ?>
                                    Calculado al <?= esc($formatDate($today)) ?>.
                                <?php endif; ?>
                            </p>
                        </div>

                        <dl class="breakdown-list">
                            <div class="breakdown-row">
                                <dt>Período</dt>
                                <dd><?= esc($periodLabel) ?></dd>
                            </div>
                            <div class="breakdown-row">
                                <dt>Vencimiento</dt>
                                <dd><?= esc($formatDate($installment['due_date'])) ?></dd>
                            </div>
                            <div class="breakdown-row">
                                <dt>Monto base</dt>
                                <dd><?= esc($formatMoney($installment['base_amount'])) ?></dd>
                            </div>
                            <div class="breakdown-row">
                                <dt>Recargo por mora</dt>
                                <dd><?= $hasLateFee ? esc($formatMoney($installment['late_fee'])) : '$ 0' ?></dd>
                            </div>
                            <div class="breakdown-row breakdown-total">
                                <dt>Total exigible hoy</dt>
                                <dd><?= esc($formatMoney($totalDue)) ?></dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>

