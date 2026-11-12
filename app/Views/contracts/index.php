<?php
/**
 * Contract listing shared by the administrative and owner portals.
 *
 * @var list<array<string, mixed>> $contracts
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{q: string, status: string} $filters
 * @var array<string, string> $filterErrors
 * @var bool $isOwner
 * @var string $listingBaseUrl
 */
use App\Models\ContractModel;

$buildPageUrl = static function (int $targetPage) use ($pagination, $filters, $listingBaseUrl): string {
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

    return site_url($listingBaseUrl) . '?' . http_build_query($query);
};

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$fullName = static fn (array $row, string $prefix): string => trim(
    (string) $row[$prefix . '_last_name'] . ', ' . (string) $row[$prefix . '_first_name']
);

$formatDate = static fn (mixed $value): string => ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';

$formatPercentage = static function (mixed $value): string {
    $text = (string) $value;
    $text = str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;

    return str_replace('.', ',', $text) . ' %';
};

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="users-page-header">
                <div>
                    <div class="users-heading-row">
                        <h1 class="panel-title"><?= $isOwner ? 'Mis contratos' : 'Contratos' ?></h1>
                        <span class="badge-pill badge-neutral"><?= esc((string) $pagination['total']) ?> registrados</span>
                    </div>
                    <p class="panel-subtitle">
                        <?= $isOwner
                            ? 'Consultá la vigencia, los importes y los datos de contacto de tus inquilinos.'
                            : 'Consultá contratos vigentes e históricos de todos los inmuebles.' ?>
                    </p>
                </div>
                <?php if (! $isOwner): ?>
                    <a href="<?= esc(site_url('admin/contratos/nuevo')) ?>" class="btn btn-primary" id="btn-new-contract">
                        <span>+ Nuevo contrato</span>
                    </a>
                <?php endif; ?>
            </div>

            <section class="panel-card catalog-filters" aria-labelledby="contract-filters-heading">
                <h2 id="contract-filters-heading" class="section-title"><?= $isOwner ? 'Filtrar mis contratos' : 'Buscar y filtrar contratos' ?></h2>
                <form action="<?= esc(site_url($listingBaseUrl)) ?>" method="get" class="catalog-filter-form">
                    <div class="form-field">
                        <label for="filter-query">Inquilino, propietario o dirección</label>
                        <input
                            id="filter-query"
                            name="q"
                            type="search"
                            maxlength="100"
                            value="<?= esc($filters['q']) ?>"
                            class="form-control<?= isset($filterErrors['q']) ? ' is-invalid' : '' ?>"
                        >
                        <?php if (isset($filterErrors['q'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['q']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="filter-status">Estado</label>
                        <select id="filter-status" name="status" class="form-control<?= isset($filterErrors['status']) ? ' is-invalid' : '' ?>">
                            <option value="">Todos los estados</option>
                            <?php foreach (ContractModel::STATUS_LABELS as $statusValue => $statusLabel): ?>
                                <option value="<?= esc($statusValue) ?>"<?= $filters['status'] === $statusValue ? ' selected' : '' ?>>
                                    <?= esc($statusLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($filterErrors['status'])): ?><span class="field-error" role="alert"><?= esc($filterErrors['status']) ?></span><?php endif; ?>
                    </div>
                    <div class="catalog-filter-actions">
                        <a href="<?= esc(site_url($listingBaseUrl)) ?>" class="btn btn-secondary">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    </div>
                </form>
            </section>

            <section class="panel-card table-card" aria-label="<?= $isOwner ? 'Mis contratos' : 'Listado de contratos' ?>">
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th scope="col">Inmueble</th>
                                <th scope="col">Inquilino<?= $isOwner ? ' / contacto' : '' ?></th>
                                <?php if ($isOwner): ?>
                                    <th scope="col">Monto mensual</th>
                                    <th scope="col">Vigencia</th>
                                    <th scope="col">Comisión</th>
                                    <th scope="col">Estado</th>
                                <?php else: ?>
                                    <th scope="col">Propietario</th>
                                    <th scope="col">Vigencia</th>
                                    <th scope="col">Estado</th>
                                <?php endif; ?>
                                <th scope="col" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($contracts === []): ?>
                                <tr>
                                    <td colspan="<?= $isOwner ? '7' : '6' ?>" class="empty-table-cell">
                                        No se encontraron contratos con los filtros seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contracts as $contract): ?>
                                    <?php
                                    $contractId = (int) $contract['id'];
                                    $status     = (string) $contract['status'];
                                    $detailUrl  = $listingBaseUrl . '/' . $contractId;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell-meta">
                                                <span class="user-cell-name"><?= esc((string) $contract['property_address']) ?></span>
                                                <span class="user-cell-sub"><?= esc((string) $contract['property_city']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-cell-meta">
                                                <span class="user-cell-name"><?= esc($fullName($contract, 'tenant')) ?></span>
                                                <span class="user-cell-sub"><?= esc((string) $contract['tenant_email']) ?><?= $isOwner && ! empty($contract['tenant_phone']) ? ' · ' . esc((string) $contract['tenant_phone']) : '' ?></span>
                                            </div>
                                        </td>
                                        <?php if ($isOwner): ?>
                                            <td class="mono-cell"><?= esc($formatMoney($contract['monthly_rent'])) ?></td>
                                            <td class="mono-cell"><?= esc($formatDate($contract['start_date'])) ?> – <?= esc($formatDate($contract['end_date'])) ?></td>
                                            <td class="mono-cell"><?= esc($formatPercentage($contract['commission_percentage'])) ?></td>
                                            <td><span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span></td>
                                        <?php else: ?>
                                            <td><?= esc($fullName($contract, 'owner')) ?></td>
                                            <td class="mono-cell"><?= esc($formatDate($contract['start_date'])) ?> – <?= esc($formatDate($contract['end_date'])) ?></td>
                                            <td><span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span></td>
                                        <?php endif; ?>
                                        <td class="text-right">
                                            <a href="<?= esc(site_url($detailUrl)) ?>" class="btn btn-secondary">Ver contrato</a>
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
                        de <strong><?= esc((string) $pagination['total']) ?></strong> contratos
                    </div>
                    <?php if ($pagination['totalPages'] > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginación de contratos">
                            <?php if ($pagination['currentPage'] > 1): ?>
                                <a href="<?= esc($buildPageUrl($pagination['currentPage'] - 1)) ?>" class="pagination-btn">Anterior</a>
                            <?php else: ?>
                                <span class="pagination-btn is-disabled" aria-disabled="true">Anterior</span>
                            <?php endif; ?>
                            <?php for ($page = 1; $page <= $pagination['totalPages']; $page++): ?>
                                <a
                                    href="<?= esc($buildPageUrl($page)) ?>"
                                    class="pagination-page<?= $page === $pagination['currentPage'] ? ' is-current' : '' ?>"
                                    <?= $page === $pagination['currentPage'] ? 'aria-current="page"' : '' ?>
                                ><?= esc((string) $page) ?></a>
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
