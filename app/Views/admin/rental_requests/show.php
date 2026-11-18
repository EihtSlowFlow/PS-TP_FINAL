<?php
/**
 * Administrative file and resolution view of a single rental request (HU-31 Scenarios 2, 3 and 5).
 *
 * @var array<string, mixed>       $rentalRequest
 * @var list<array<string, mixed>> $otherPending
 */
use App\Models\PropertyModel;
use App\Models\RentalRequestModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatDate = static function (mixed $value): string {
    return ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';
};

$plural = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);

$errors       = session()->getFlashdata('errors') ?? [];
$requestId    = (int) $rentalRequest['id'];
$status       = (string) $rentalRequest['status'];
$isPending    = $status === RentalRequestModel::STATUS_PENDING;
$tenantName   = trim((string) $rentalRequest['tenant_last_name'] . ', ' . (string) $rentalRequest['tenant_first_name']);
$ownerName    = trim((string) $rentalRequest['owner_last_name'] . ', ' . (string) $rentalRequest['owner_first_name']);
$resolverName = trim((string) ($rentalRequest['resolved_by_last_name'] ?? '') . ', ' . (string) ($rentalRequest['resolved_by_first_name'] ?? ''));
$months       = (int) $rentalRequest['desired_months'];
$messageText  = trim((string) ($rentalRequest['message'] ?? ''));
$contractId   = ! empty($rentalRequest['contract_id']) ? (int) $rentalRequest['contract_id'] : null;

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/solicitudes')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver a solicitudes</span>
                </a>
            </div>

            <section class="panel-card form-banner-card">
                <div class="form-banner-heading">
                    <div class="page-heading-row">
                        <h1 class="panel-title">Solicitud #<?= esc((string) $requestId) ?></h1>
                        <span class="badge-pill badge-rental-request-<?= esc($status) ?>">
                            <?= esc(RentalRequestModel::labelForStatus($status)) ?>
                        </span>
                    </div>
                    <p class="panel-subtitle">
                        Enviada el <?= esc($formatDate($rentalRequest['created_at'])) ?> por <?= esc($tenantName) ?> para
                        <?= esc((string) $rentalRequest['property_address']) ?>, <?= esc((string) $rentalRequest['property_city']) ?>.
                    </p>
                </div>
            </section>

            <div class="form-layout has-sidebar">
                <div class="form-main">
                    <section class="panel-card form-section-card" aria-labelledby="request-tenant-heading">
                        <div class="section-header">
                            <h2 id="request-tenant-heading" class="section-title">Datos del inquilino y condiciones solicitadas</h2>
                            <p class="section-subtitle">Identidad, contacto y plazo propuestos por el interesado.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Inquilino</dt>
                                <dd>
                                    <?= esc($tenantName) ?>
                                    <?php if (! empty($rentalRequest['tenant_deleted_at'])): ?>
                                        <span class="badge-pill badge-status-deleted">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>DNI / CUIT</dt>
                                <dd><?= esc((string) $rentalRequest['tenant_document_number']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Correo electrónico</dt>
                                <dd><?= esc((string) $rentalRequest['tenant_email']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Teléfono de contacto</dt>
                                <dd><?= esc((string) $rentalRequest['contact_phone']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Fecha de inicio deseada</dt>
                                <dd><?= esc($formatDate($rentalRequest['desired_start_date'])) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Plazo deseado</dt>
                                <dd><?= esc($plural($months, 'mes', 'meses')) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Mensaje del interesado</dt>
                                <dd><?= $messageText !== '' ? esc($messageText) : 'Sin mensaje adicional.' ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="request-property-heading">
                        <div class="section-header">
                            <h2 id="request-property-heading" class="section-title">Datos del inmueble y propietario</h2>
                            <p class="section-subtitle">Inmueble solicitado, propietario titular y precio base vigente.</p>
                        </div>

                        <dl class="audit-list">
                            <div class="audit-item">
                                <dt>Inmueble</dt>
                                <dd><?= esc((string) $rentalRequest['property_address']) ?>, <?= esc((string) $rentalRequest['property_city']) ?></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Tipo</dt>
                                <dd>
                                    <?= esc(PropertyModel::labelForType((string) $rentalRequest['property_type'])) ?>
                                    <?php if ($rentalRequest['property_rooms'] !== null): ?>
                                        · <?= esc((string) $rentalRequest['property_rooms']) ?> amb.
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Estado comercial</dt>
                                <dd>
                                    <span class="badge-pill badge-property-<?= esc((string) $rentalRequest['property_status']) ?>">
                                        <?= esc(PropertyModel::labelForStatus((string) $rentalRequest['property_status'])) ?>
                                    </span>
                                    <?php if (! empty($rentalRequest['property_deleted_at'])): ?>
                                        <span class="badge-pill badge-status-deleted">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="audit-item">
                                <dt>Precio base mensual</dt>
                                <dd><strong><?= esc($formatMoney($rentalRequest['property_base_rent'])) ?></strong></dd>
                            </div>
                            <div class="audit-item">
                                <dt>Propietario</dt>
                                <dd>
                                    <?= esc($ownerName) ?> — DNI <?= esc((string) $rentalRequest['owner_document_number']) ?>
                                    <?php if (! empty($rentalRequest['owner_deleted_at'])): ?>
                                        <span class="badge-pill badge-status-deleted">Dado de baja</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="panel-card form-section-card" aria-labelledby="request-competing-heading">
                        <div class="section-header">
                            <h2 id="request-competing-heading" class="section-title">
                                Otras solicitudes pendientes sobre este inmueble
                                <span class="badge-pill badge-neutral"><?= esc((string) count($otherPending)) ?></span>
                            </h2>
                            <p class="section-subtitle">
                                Al crear el contrato sobre este inmueble, las demás solicitudes pendientes pasan automáticamente a «Rechazada».
                            </p>
                        </div>

                        <?php if ($otherPending === []): ?>
                            <p class="field-help">No hay otras solicitudes pendientes para este inmueble.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Solicitud</th>
                                            <th scope="col">Inquilino</th>
                                            <th scope="col">Inicio y plazo</th>
                                            <th scope="col">Teléfono</th>
                                            <th scope="col" class="text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($otherPending as $other): ?>
                                            <?php
                                            $otherId   = (int) $other['id'];
                                            $otherName = trim((string) $other['tenant_last_name'] . ', ' . (string) $other['tenant_first_name']);
                                            ?>
                                            <tr>
                                                <td class="mono-cell"><strong>#<?= esc((string) $otherId) ?></strong></td>
                                                <td><?= esc($otherName) ?> (DNI <?= esc((string) $other['tenant_document_number']) ?>)</td>
                                                <td>Desde <?= esc($formatDate($other['desired_start_date'])) ?> · <?= esc($plural((int) $other['desired_months'], 'mes', 'meses')) ?></td>
                                                <td><?= esc((string) $other['contact_phone']) ?></td>
                                                <td class="text-right">
                                                    <a href="<?= esc(site_url('admin/solicitudes/' . $otherId)) ?>" class="btn btn-secondary btn-sm">Ver ficha</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="form-aside">
                    <section class="panel-card form-section-card" aria-labelledby="request-resolution-heading">
                        <div class="section-header">
                            <h2 id="request-resolution-heading" class="section-title">
                                <?= $isPending ? 'Resolver solicitud' : 'Resolución de la solicitud' ?>
                            </h2>
                            <p class="section-subtitle">
                                <?= $isPending
                                    ? 'Aprobá para abrir el contrato prellenado o rechazá indicando el motivo.'
                                    : 'Estado final registrado para esta solicitud.' ?>
                            </p>
                        </div>

                        <?php if ($isPending): ?>
                            <div class="inline-actions-row stacked">
                                <a href="<?= esc(site_url('admin/contratos/nuevo?request_id=' . $requestId)) ?>" class="btn btn-primary" id="btn-approve-create-contract">
                                    Aprobar y crear contrato
                                </a>
                            </div>

                            <form action="<?= esc(site_url('admin/solicitudes/' . $requestId . '/rechazo')) ?>" method="post" class="nested-form" id="rental-request-rejection-form" novalidate>
                                <?= csrf_field() ?>

                                <div class="form-field">
                                    <label for="rejection_reason" class="form-label">Motivo del rechazo <span class="required-mark" aria-hidden="true">*</span></label>
                                    <textarea
                                        id="rejection_reason"
                                        name="rejection_reason"
                                        rows="4"
                                        maxlength="<?= esc((string) RentalRequestModel::REJECTION_REASON_MAX_LENGTH) ?>"
                                        placeholder="Ingresá el motivo del rechazo (hasta 255 caracteres)."
                                        class="form-control<?= isset($errors['rejection_reason']) ? ' is-invalid' : '' ?>"
                                        aria-invalid="<?= isset($errors['rejection_reason']) ? 'true' : 'false' ?>"
                                        required
                                    ><?= esc((string) old('rejection_reason', '')) ?></textarea>
                                    <?php if (isset($errors['rejection_reason'])): ?>
                                        <p class="field-error" role="alert"><?= esc($errors['rejection_reason']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn btn-danger" id="btn-reject-rental-request">Rechazar solicitud</button>
                            </form>
                        <?php else: ?>
                            <dl class="audit-list">
                                <div class="audit-item">
                                    <dt>Estado</dt>
                                    <dd>
                                        <span class="badge-pill badge-rental-request-<?= esc($status) ?>">
                                            <?= esc(RentalRequestModel::labelForStatus($status)) ?>
                                        </span>
                                    </dd>
                                </div>
                                <?php if ($resolverName !== ',' && $resolverName !== ''): ?>
                                    <div class="audit-item">
                                        <dt>Resuelta por</dt>
                                        <dd><?= esc($resolverName) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if (! empty($rentalRequest['resolved_at'])): ?>
                                    <div class="audit-item">
                                        <dt>Fecha de resolución</dt>
                                        <dd><?= esc($formatDate($rentalRequest['resolved_at'])) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($status === RentalRequestModel::STATUS_REJECTED && ! empty($rentalRequest['rejection_reason'])): ?>
                                    <div class="audit-item">
                                        <dt>Motivo del rechazo</dt>
                                        <dd><?= esc((string) $rentalRequest['rejection_reason']) ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($status === RentalRequestModel::STATUS_APPROVED && $contractId !== null): ?>
                                    <div class="audit-item">
                                        <dt>Contrato vinculado</dt>
                                        <dd>
                                            <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>" class="btn btn-secondary btn-sm">
                                                Ver contrato #<?= esc((string) $contractId) ?>
                                            </a>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        <?php endif; ?>
                    </section>
                </aside>
            </div>
<?php $this->endSection() ?>

