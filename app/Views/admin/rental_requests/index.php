<?php
/**
 * Administrative tray of tenant rental requests (HU-31 Scenario 1).
 *
 * @var list<array<string, mixed>> $requests
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{pending: int, approved: int, rejected: int, cancelled: int, total: int} $statusCounts
 * @var array{q: string, status: string} $filters
 * @var array<string, string> $filterErrors
 * @var array<string, string> $statusOptions
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

$buildPageUrl = static function (int $targetPage) use ($filters): string {
    $query = [];

    foreach (['q', 'status'] as $key) {
        if (($filters[$key] ?? '') !== '') {
            $query[$key] = $filters[$key];
        }
    }

    $query['page'] = (string) $targetPage;

    return site_url('admin/solicitudes') . '?' . http_build_query($query);
};

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Solicitudes de alquiler</h1>
                        <span class="badge-pill badge-neutral">
                            <?= esc($plural((int) $statusCounts['pending'], 'pendiente', 'pendientes')) ?>
                        </span>
                    </div>
                    <p class="panel-subtitle">Revisá las solicitudes enviadas por los inquilinos y convertí una aprobada en contrato.</p>
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Pendientes</span>
                        <span class="kpi-value"><?= esc((string) $statusCounts['pending']) ?></span>
                        <span class="kpi-note kpi-note-warning">Esperando resolución</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Aprobadas</span>
                        <span class="kpi-value"><?= esc((string) $statusCounts['approved']) ?></span>
                        <span class="kpi-note kpi-note-success">Convertidas en contrato</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Rechazadas</span>
                        <span class="kpi-value"><?= esc((string) $statusCounts['rejected']) ?></span>
                        <span class="kpi-note kpi-note-info">Con motivo informado</span>
                    </div>
                </div>
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Total registradas</span>
                        <span class="kpi-value"><?= esc((string) $statusCounts['total']) ?></span>
                        <span class="kpi-note kpi-note-primary"><?= esc((string) $statusCounts['cancelled']) ?> canceladas por inquilinos</span>
                    </div>
                </div>
            </div>

            <section class="panel-card catalog-filters" aria-labelledby="admin-requests-filter-heading">
                <h2 id="admin-requests-filter-heading" class="section-title">Filtrar solicitudes</h2>
                <form action="<?= esc(site_url('admin/solicitudes')) ?>" method="get" class="catalog-filter-form">
                    <div class="form-field">
                        <label for="filter-q">Buscar por inquilino o inmueble</label>
                        <input
                            type="text"
                            id="filter-q"
                            name="q"
                            maxlength="100"
                            placeholder="Nombre, DNI, email, dirección o ciudad…"
                            value="<?= esc($filters['q']) ?>"
                            class="form-control<?= isset($filterErrors['q']) ? ' is-invalid' : '' ?>"
                        >
                        <?php if (isset($filterErrors['q'])): ?>
                            <span class="field-error" role="alert"><?= esc($filterErrors['q']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="filter-status">Estado</label>
                        <select id="filter-status" name="status" class="form-control<?= isset($filterErrors['status']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los estados</option>
                            <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                                <option value="<?= esc($statusValue) ?>"<?= $filters['status'] === $statusValue ? ' selected' : '' ?>>
                                    <?= esc($statusLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['status'])): ?>
                            <span class="field-error" role="alert"><?= esc($filterErrors['status']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="catalog-filter-actions">
                        <a href="<?= esc(site_url('admin/solicitudes')) ?>" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="Bandeja de solicitudes de alquiler">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Solicitud</th>
                                <th scope="col">Inquilino</th>
                                <th scope="col">Inmueble</th>
                                <th scope="col">Inicio y plazo deseados</th>
                                <th scope="col">Fecha de envío</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests === []): ?>
                                <tr>
                                    <td colspan="7" class="empty-table-cell">No se encontraron solicitudes de alquiler con los criterios seleccionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $item): ?>
                                    <?php
                                    $requestId  = (int) $item['id'];
                                    $status     = (string) $item['status'];
                                    $tenantName = trim((string) $item['tenant_last_name'] . ', ' . (string) $item['tenant_first_name']);
                                    $months     = (int) $item['desired_months'];
                                    $contractId = ! empty($item['contract_id']) ? (int) $item['contract_id'] : null;
                                    ?>
                                    <tr data-request-id="<?= esc((string) $requestId) ?>">
                                        <td class="mono-cell"><strong>#<?= esc((string) $requestId) ?></strong></td>
                                        <td>
                                            <div class="cell-meta">
                                                <span class="cell-title"><?= esc($tenantName) ?></span>
                                                <span class="cell-sub">DNI <?= esc((string) $item['tenant_document_number']) ?> · Tel. <?= esc((string) $item['contact_phone']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-meta">
                                                <span class="cell-title"><?= esc((string) $item['property_address']) ?></span>
                                                <span class="cell-sub">
                                                    <?= esc((string) $item['property_city']) ?> ·
                                                    <?= esc(PropertyModel::labelForType((string) $item['property_type'])) ?> ·
                                                    <?= esc($formatMoney($item['property_base_rent'])) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cell-title">Desde <?= esc($formatDate($item['desired_start_date'])) ?></span>
                                            <span class="cell-note"><?= esc($plural($months, 'mes', 'meses')) ?></span>
                                        </td>
                                        <td class="nowrap-cell"><?= esc($formatDate($item['created_at'])) ?></td>
                                        <td>
                                            <span class="badge-pill badge-rental-request-<?= esc($status) ?>">
                                                <?= esc(RentalRequestModel::labelForStatus($status)) ?>
                                            </span>
                                            <?php if ($status === RentalRequestModel::STATUS_REJECTED && ! empty($item['rejection_reason'])): ?>
                                                <p class="review-reason"><strong>Motivo</strong><?= esc((string) $item['rejection_reason']) ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right nowrap-cell">
                                            <div class="table-actions">
                                                <a href="<?= esc(site_url('admin/solicitudes/' . $requestId)) ?>" class="btn btn-secondary btn-sm">
                                                    <?= $status === RentalRequestModel::STATUS_PENDING ? 'Revisar' : 'Ver ficha' ?>
                                                </a>
                                                <?php if ($status === RentalRequestModel::STATUS_PENDING): ?>
                                                    <a href="<?= esc(site_url('admin/contratos/nuevo?request_id=' . $requestId)) ?>" class="btn btn-primary btn-sm">
                                                        Aprobar y crear contrato
                                                    </a>
                                                <?php elseif ($status === RentalRequestModel::STATUS_APPROVED && $contractId !== null): ?>
                                                    <a href="<?= esc(site_url('admin/contratos/' . $contractId)) ?>" class="btn btn-secondary btn-sm">
                                                        Contrato #<?= esc((string) $contractId) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-footer">
                    <div class="pagination-summary">
                        Mostrando <strong><?= esc((string) $pagination['from']) ?>–<?= esc((string) $pagination['to']) ?></strong>
                        de <strong><?= esc((string) $pagination['total']) ?></strong> solicitudes
                    </div>
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de solicitudes">
                            <?php if ($pagination['currentPage'] > 1): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] - 1)) ?>" class="pagination-btn">Anterior</a>
                            <?php endif; ?>
                            <?php for ($page = 1; $page <= $pagination['totalPages']; $page++): ?>
                                <a href="<?= esc($buildPageUrl($page)) ?>" class="pagination-page<?= $page === $pagination['currentPage'] ? ' is-current' : '' ?>"<?= $page === $pagination['currentPage'] ? ' aria-current="page"' : '' ?>>
                                    <?= esc((string) $page) ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] + 1)) ?>" class="pagination-btn">Siguiente</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>
<?php $this->endSection() ?>

