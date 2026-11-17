<?php
/**
 * Owner's own property catalog.
 *
 * @var list<array<string, mixed>> $properties
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{status: string, type: string, min_price: string, max_price: string, owner_id: string} $filters
 * @var array<string, string> $filterErrors
 */
use App\Models\PropertyModel;

$buildPageUrl = static function (int $targetPage) use ($pagination, $filters): string {
    $query = [];

    foreach (['status', 'type', 'min_price', 'max_price'] as $key) {
        if ($filters[$key] !== '') {
            $query[$key] = $filters[$key];
        }
    }

    $query['page'] = (string) $targetPage;

    return site_url('propietario/inmuebles') . '?' . http_build_query($query);
};

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

// The owner has no properties at all (no filter applied): offer to publish the first one (HU-32 Scenario 7).
$hasFilters  = array_filter(
    array_intersect_key($filters, array_flip(['status', 'type', 'min_price', 'max_price'])),
    static fn (string $value): bool => $value !== ''
) !== [];
$hasNoProperties = $pagination['total'] === 0 && ! $hasFilters;

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <h1 class="panel-title">Mis inmuebles</h1>
                    <p class="panel-subtitle">Consultá tus propiedades, su estado de disponibilidad y la revisión de la administración</p>
                </div>
                <div>
                    <a href="<?= esc(site_url('propietario/inmuebles/nuevo')) ?>" class="btn btn-primary" id="btn-new-owner-property">
                        <span>+ Registrar inmueble</span>
                    </a>
                </div>
            </div>

            <?php if ($hasNoProperties): ?>
                <section class="empty-state" id="owner-properties-empty" aria-label="Sin inmuebles">
                    <h2 class="empty-state-title">Todavía no registraste ningún inmueble</h2>
                    <p>Cargá tu propiedad y la administración la revisa: cuando la aprueba, se publica en el catálogo para alquilarla.</p>
                    <a href="<?= esc(site_url('propietario/inmuebles/nuevo')) ?>" class="btn btn-primary" id="btn-first-owner-property">Registrar mi primer inmueble</a>
                </section>
            <?php else: ?>
            <section class="panel-card catalog-filters" aria-labelledby="owner-property-filters-heading">
                <h2 id="owner-property-filters-heading" class="section-title">Filtrar mis inmuebles</h2>
                <form action="<?= esc(site_url('propietario/inmuebles')) ?>" method="get" class="catalog-filter-form">
                    <div class="form-field">
                        <label for="filter-status">Estado</label>
                        <select id="filter-status" name="status" class="form-control<?= isset($filterErrors['status']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los estados</option>
                            <?php foreach (PropertyModel::STATUS_LABELS as $statusValue => $statusLabel): ?>
                                <option value="<?= esc($statusValue) ?>"<?= $filters['status'] === $statusValue ? ' selected' : '' ?>>
                                    <?= esc($statusLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['status'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['status']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-type">Tipo</label>
                        <select id="filter-type" name="type" class="form-control<?= isset($filterErrors['type']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los tipos</option>
                            <?php foreach (PropertyModel::TYPE_LABELS as $typeValue => $typeLabel): ?>
                                <option value="<?= esc($typeValue) ?>"<?= $filters['type'] === $typeValue ? ' selected' : '' ?>>
                                    <?= esc($typeLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['type'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['type']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-min-price">Precio mínimo</label>
                        <input id="filter-min-price" name="min_price" type="number" min="0.01" step="0.01" value="<?= esc($filters['min_price']) ?>" class="form-control<?= isset($filterErrors['min_price']) ? ' is-invalid' : '' ?>">
                        <?php if (isset($filterErrors['min_price'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['min_price']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-max-price">Precio máximo</label>
                        <input id="filter-max-price" name="max_price" type="number" min="0.01" step="0.01" value="<?= esc($filters['max_price']) ?>" class="form-control<?= isset($filterErrors['max_price']) ? ' is-invalid' : '' ?>">
                        <?php if (isset($filterErrors['max_price'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['max_price']) ?></span><?php endif; ?>
                    </div>
                    <div class="catalog-filter-actions">
                        <a href="<?= esc(site_url('propietario/inmuebles')) ?>" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="Mis inmuebles">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Dirección</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Ambientes</th>
                                <th scope="col">Precio base mensual</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($properties === []): ?>
                                <tr>
                                    <td colspan="6" class="empty-table-cell">No se encontraron inmuebles con los filtros seleccionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($properties as $item): ?>
                                    <?php
                                    $status         = (string) $item['status'];
                                    $approvalStatus = (string) ($item['approval_status'] ?? PropertyModel::APPROVAL_APPROVED);
                                    $isApproved     = $approvalStatus === PropertyModel::APPROVAL_APPROVED;
                                    $rejectionText  = (string) ($item['rejection_reason'] ?? '');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="cell-meta">
                                                <span class="cell-title"><?= esc((string) $item['address']) ?></span>
                                                <span class="cell-sub"><?= esc((string) $item['city']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $item['type'])) ?></span></td>
                                        <td class="mono-cell"><?= $item['rooms'] !== null ? esc((string) $item['rooms']) . ' amb.' : '—' ?></td>
                                        <td class="mono-cell"><?= esc($formatMoney($item['base_rent'])) ?></td>
                                        <td>
                                            <?php if ($isApproved): ?>
                                                <span class="badge-pill badge-property-<?= esc($status) ?>"><?= esc(PropertyModel::labelForStatus($status)) ?></span>
                                            <?php else: ?>
                                                <?php /* Pending or rejected: not published yet, so the commercial status says nothing useful. */ ?>
                                                <span class="badge-pill badge-approval-<?= esc($approvalStatus) ?>"><?= esc(PropertyModel::labelForApproval($approvalStatus, true)) ?></span>
                                                <?php if ($approvalStatus === PropertyModel::APPROVAL_REJECTED && $rejectionText !== ''): ?>
                                                    <p class="review-reason"><strong>Motivo del rechazo</strong><?= esc($rejectionText) ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="table-actions">
                                                <?php if ($isApproved): ?>
                                                    <span class="cell-sub">Cambios: pedilos a la administración</span>
                                                <?php else: ?>
                                                    <a
                                                        href="<?= esc(site_url('propietario/inmuebles/editar/' . (int) $item['id'])) ?>"
                                                        class="btn btn-secondary btn-sm"
                                                        aria-label="Corregir <?= esc((string) $item['address']) ?>"
                                                    >Corregir</a>
                                                <?php endif; ?>

                                                <?php if ($status === PropertyModel::STATUS_RENTED): ?>
                                                    <?php /* A contract in force blocks the deactivation (HU-09); only the administration closes it. */ ?>
                                                    <span class="cell-sub">Con contrato vigente: no se puede dar de baja</span>
                                                <?php else: ?>
                                                    <?php /* Without `confirmed=1` the server answers a confirmation page: it never deactivates on the first click. */ ?>
                                                    <form action="<?= esc(site_url('propietario/inmuebles/baja/' . (int) $item['id'])) ?>" method="post" class="inline-action-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="confirmed" value="0">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-danger-outline btn-sm"
                                                            aria-label="<?= $isApproved ? 'Dar de baja' : 'Cancelar solicitud de' ?> <?= esc((string) $item['address']) ?>"
                                                        ><?= $isApproved ? 'Dar de baja' : 'Cancelar solicitud' ?></button>
                                                    </form>
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
                        de <strong><?= esc((string) $pagination['total']) ?></strong> inmuebles
                    </div>
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de mis inmuebles">
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
            <?php endif; ?>
<?php $this->endSection() ?>
