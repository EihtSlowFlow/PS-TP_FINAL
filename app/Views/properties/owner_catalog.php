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

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <h1 class="panel-title">Mis inmuebles</h1>
                    <p class="panel-subtitle">Consultá tus propiedades y su estado de disponibilidad</p>
                </div>
            </div>

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
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th scope="col">Dirección</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Ambientes</th>
                                <th scope="col">Precio base mensual</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($properties === []): ?>
                                <tr>
                                    <td colspan="5" class="empty-table-cell">No se encontraron inmuebles con los filtros seleccionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($properties as $item): ?>
                                    <?php $status = (string) $item['status']; ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell-meta">
                                                <span class="user-cell-name"><?= esc((string) $item['address']) ?></span>
                                                <span class="user-cell-sub"><?= esc((string) $item['city']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $item['type'])) ?></span></td>
                                        <td class="mono-cell"><?= $item['rooms'] !== null ? esc((string) $item['rooms']) . ' amb.' : '—' ?></td>
                                        <td class="mono-cell"><?= esc($formatMoney($item['base_rent'])) ?></td>
                                        <td><span class="badge-pill badge-property-<?= esc($status) ?>"><?= esc(PropertyModel::labelForStatus($status)) ?></span></td>
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
<?php $this->endSection() ?>
