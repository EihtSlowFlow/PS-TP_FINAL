<?php
/**
 * Property listing (HU-07; HU-08 adds the filters and HU-09 the deactivation action).
 *
 * @var list<array<string, mixed>> $properties
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{available: int, rented: int, maintenance: int, total: int} $statusCounts
 * @var array{status: string, type: string, min_price: string, max_price: string, owner_id: string} $filters
 * @var array<string, string> $filterErrors
 * @var list<array{id: int, first_name: string, last_name: string, deleted_at: string|null}> $owners
 */
use App\Models\PropertyModel;

$buildPageUrl = static function (int $targetPage) use ($pagination, $filters): string {
    $query = [];

    foreach ($filters as $key => $value) {
        if ($value !== '') {
            $query[$key] = $value;
        }
    }

    if ($pagination['perPage'] !== 10) {
        $query['per_page'] = (string) $pagination['perPage'];
    }
    $query['page'] = (string) $targetPage;

    return site_url('admin/inmuebles') . '?' . http_build_query($query);
};

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$initialsOf = static function (string $firstName, string $lastName): string {
    $first = $firstName !== '' ? mb_strtoupper(mb_substr(trim($firstName), 0, 1)) : 'P';
    $last  = $lastName !== '' ? mb_strtoupper(mb_substr(trim($lastName), 0, 1)) : '';

    return $first . $last;
};

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title">Inmuebles</h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) $statusCounts['total']) ?> registrados</span>
                    </div>
                    <p class="panel-subtitle">Gestión de cartera, unidades locativas y estados de disponibilidad</p>
                </div>
                <div>
                    <a href="<?= esc(site_url('admin/inmuebles/nuevo')) ?>" class="btn btn-primary" id="btn-new-property">
                        <span>+ Nuevo inmueble</span>
                    </a>
                </div>
            </div>

            <section class="users-kpi-grid" aria-label="Resumen por estado">
                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Disponibles</span>
                        <strong class="kpi-value"><?= esc((string) $statusCounts['available']) ?></strong>
                        <span class="kpi-note kpi-note-success">Listos para alquilar</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">Alquilados</span>
                        <strong class="kpi-value"><?= esc((string) $statusCounts['rented']) ?></strong>
                        <span class="kpi-note kpi-note-info">Con contrato en curso</span>
                    </div>
                </div>

                <div class="kpi-card">
                    <div>
                        <span class="kpi-label">En mantenimiento</span>
                        <strong class="kpi-value"><?= esc((string) $statusCounts['maintenance']) ?></strong>
                        <span class="kpi-note kpi-note-warning">Fuera de oferta</span>
                    </div>
                </div>
            </section>

            <section class="panel-card catalog-filters" aria-labelledby="property-filters-heading">
                <h2 id="property-filters-heading" class="section-title">Filtrar inmuebles</h2>
                <form action="<?= esc(site_url('admin/inmuebles')) ?>" method="get" class="catalog-filter-form">
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
                        <label for="filter-owner">Propietario</label>
                        <select id="filter-owner" name="owner_id" class="form-control<?= isset($filterErrors['owner_id']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los propietarios</option>
                            <?php foreach ($owners as $owner): ?>
                                <?php $ownerLabel = trim($owner['first_name'] . ' ' . $owner['last_name']); ?>
                                <option value="<?= esc((string) $owner['id']) ?>"<?= $filters['owner_id'] === (string) $owner['id'] ? ' selected' : '' ?>>
                                    <?= esc($ownerLabel) ?><?= $owner['deleted_at'] !== null ? ' (dado de baja)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['owner_id'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['owner_id']) ?></span><?php endif; ?>
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
                        <a href="<?= esc(site_url('admin/inmuebles')) ?>" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="Listado de inmuebles">
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th scope="col">Dirección</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Ambientes</th>
                                <th scope="col">Precio base mensual</th>
                                <th scope="col">Propietario</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($properties === []): ?>
                                <tr>
                                    <td colspan="7" class="empty-table-cell">
                                        No se encontraron inmuebles con los filtros seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($properties as $item): ?>
                                    <?php
                                    $itemId     = (int) $item['id'];
                                    $ownerFirst = (string) ($item['owner_first_name'] ?? '');
                                    $ownerLast  = (string) ($item['owner_last_name'] ?? '');
                                    $ownerName  = trim($ownerFirst . ' ' . $ownerLast);
                                    $status     = (string) $item['status'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell-meta">
                                                <span class="user-cell-name"><?= esc((string) $item['address']) ?></span>
                                                <span class="user-cell-sub"><?= esc((string) $item['city']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $item['type'])) ?></span>
                                        </td>
                                        <td class="mono-cell"><?= $item['rooms'] !== null ? esc((string) $item['rooms']) . ' amb.' : '—' ?></td>
                                        <td class="mono-cell"><?= esc($formatMoney($item['base_rent'])) ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-cell-avatar badge-role-owner" aria-hidden="true"><?= esc($initialsOf($ownerFirst, $ownerLast)) ?></div>
                                                <div class="user-cell-meta">
                                                    <span class="user-cell-name"><?= esc($ownerName) ?></span>
                                                    <?php if (! empty($item['owner_deleted_at'])): ?>
                                                        <span class="user-cell-sub">Dado de baja</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-pill badge-property-<?= esc($status) ?>"><?= esc(PropertyModel::labelForStatus($status)) ?></span>
                                        </td>
                                        <td class="text-right">
                                            <div class="table-actions">
                                                <a
                                                    href="<?= esc(site_url('admin/inmuebles/editar/' . $itemId)) ?>"
                                                    class="action-icon-btn"
                                                    title="Editar <?= esc((string) $item['address']) ?>"
                                                    aria-label="Editar <?= esc((string) $item['address']) ?>"
                                                >
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                    <span class="sr-only">Editar</span>
                                                </a>
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
                        de <strong><?= esc((string) $pagination['total']) ?></strong> inmuebles registrados
                    </div>

                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de inmuebles">
                            <?php if ($pagination['currentPage'] > 1): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] - 1)) ?>" class="pagination-btn">Anterior</a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Anterior</span>
                            <?php endif; ?>

                            <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                                <a
                                    href="<?= esc($buildPageUrl($p)) ?>"
                                    class="pagination-page<?= $p === $pagination['currentPage'] ? ' is-current' : '' ?>"
                                    <?= $p === $pagination['currentPage'] ? 'aria-current="page"' : '' ?>
                                >
                                    <?= esc((string) $p) ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] + 1)) ?>" class="pagination-btn">Siguiente</a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Siguiente</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>
<?php $this->endSection() ?>
