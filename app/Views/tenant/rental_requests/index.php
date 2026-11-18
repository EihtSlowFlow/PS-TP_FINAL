<?php
/**
 * Tenant rental requests listing ("Mis solicitudes", HU-30 Scenario 5).
 *
 * @var list<array<string, mixed>> $requests
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

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Mis solicitudes</h1>
                        <span class="badge-pill badge-neutral"><?= esc($plural(count($requests), 'solicitud', 'solicitudes')) ?></span>
                    </div>
                    <p class="panel-subtitle">Consultá el estado de tus solicitudes de alquiler, el motivo en caso de rechazo y el acceso a tu contrato aprobado.</p>
                </div>
                <div>
                    <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-primary" id="btn-search-properties">
                        <span>Buscar inmuebles</span>
                    </a>
                </div>
            </div>

            <?php if ($requests === []): ?>
                <section class="empty-state" id="tenant-requests-empty" aria-label="Sin solicitudes de alquiler">
                    <h2 class="empty-state-title">Todavía no enviaste solicitudes de alquiler</h2>
                    <p>Explorá el catálogo de propiedades disponibles y enviá una solicitud para iniciar la contratación.</p>
                    <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-primary" id="btn-first-rental-request">Buscar inmuebles disponibles</a>
                </section>
            <?php else: ?>
                <section class="panel-card table-card" aria-labelledby="tenant-rental-requests-heading">
                    <div class="section-header">
                        <h2 id="tenant-rental-requests-heading" class="section-title">Solicitudes enviadas</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" aria-label="Solicitudes de alquiler del inquilino">
                            <thead>
                                <tr>
                                    <th scope="col">Inmueble</th>
                                    <th scope="col">Inicio y plazo deseados</th>
                                    <th scope="col">Fecha de envío</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $item): ?>
                                    <?php
                                    $requestId       = (int) $item['id'];
                                    $status          = (string) $item['status'];
                                    $rejectionReason = trim((string) ($item['rejection_reason'] ?? ''));
                                    $contractId      = ! empty($item['contract_id']) ? (int) $item['contract_id'] : null;
                                    $months          = (int) $item['desired_months'];
                                    $messageText     = trim((string) ($item['message'] ?? ''));
                                    ?>
                                    <tr data-request-id="<?= esc((string) $requestId) ?>">
                                        <td>
                                            <div class="cell-meta">
                                                <span class="cell-title"><?= esc((string) $item['property_address']) ?></span>
                                                <span class="cell-sub">
                                                    <?= esc((string) $item['property_city']) ?> ·
                                                    <?= esc(PropertyModel::labelForType((string) $item['property_type'])) ?> ·
                                                    <?= esc($formatMoney($item['property_base_rent'])) ?>/mes
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cell-title">Desde <?= esc($formatDate($item['desired_start_date'])) ?> · <?= esc($plural($months, 'mes', 'meses')) ?></span>
                                            <span class="cell-note">Tel.: <?= esc((string) $item['contact_phone']) ?></span>
                                            <?php if ($messageText !== ''): ?>
                                                <span class="cell-note">Mensaje: <?= esc($messageText) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="nowrap-cell">
                                            <?= esc($formatDate($item['created_at'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge-pill badge-rental-request-<?= esc($status) ?>">
                                                <?= esc(RentalRequestModel::labelForStatus($status)) ?>
                                            </span>
                                            <?php if ($status === RentalRequestModel::STATUS_REJECTED && $rejectionReason !== ''): ?>
                                                <p class="review-reason"><strong>Motivo del rechazo</strong><?= esc($rejectionReason) ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right nowrap-cell">
                                            <div class="table-actions">
                                                <?php if ($status === RentalRequestModel::STATUS_PENDING): ?>
                                                    <form action="<?= esc(site_url('inquilino/solicitudes/' . $requestId . '/cancelar')) ?>" method="post" class="inline-action-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="confirmed" value="0">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-danger-outline btn-sm"
                                                            aria-label="Cancelar solicitud de <?= esc((string) $item['property_address']) ?>"
                                                        >Cancelar solicitud</button>
                                                    </form>
                                                <?php elseif ($status === RentalRequestModel::STATUS_APPROVED): ?>
                                                    <?php $contractUrl = $contractId !== null ? site_url('inquilino/contratos/' . $contractId) : site_url('inquilino/contratos'); ?>
                                                    <a href="<?= esc($contractUrl) ?>" class="btn btn-secondary btn-sm">Ver contrato</a>
                                                <?php else: ?>
                                                    <span class="cell-note">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
<?php $this->endSection() ?>

