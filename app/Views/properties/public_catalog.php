<?php
/**
 * Public catalog containing only available properties and fields intended for visitors.
 *
 * @var list<array<string, mixed>> $properties
 * @var array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int} $pagination
 * @var array{status: string, type: string, min_price: string, max_price: string, owner_id: string} $filters
 * @var array<string, string> $filterErrors
 */
use App\Models\PropertyModel;

$buildPageUrl = static function (int $targetPage) use ($pagination, $filters): string {
    $query = [];

    foreach (['type', 'min_price', 'max_price'] as $key) {
        if ($filters[$key] !== '') {
            $query[$key] = $filters[$key];
        }
    }

    $query['page'] = (string) $targetPage;

    return site_url('catalogo-inmuebles') . '?' . http_build_query($query);
};

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propiedades disponibles - InmoGestión 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= esc(base_url('css/panel.css')) ?>">
</head>
<body class="panel-page public-catalog-page">
    <header class="public-catalog-header">
        <a class="public-catalog-brand" href="<?= esc(site_url('catalogo-inmuebles')) ?>">InmoGestión</a>
        <nav aria-label="Navegación pública">
            <a href="<?= esc(site_url('auth/login')) ?>">Ingresar</a>
            <a href="<?= esc(site_url('auth/register')) ?>">Registrarse</a>
        </nav>
    </header>

    <main class="public-catalog-main">
        <div class="users-page-header">
            <div>
                <h1 class="panel-title">Propiedades disponibles</h1>
                <p class="panel-subtitle">Encontrá tu próximo lugar entre los inmuebles disponibles para alquilar.</p>
            </div>
        </div>

        <section class="panel-card catalog-filters" aria-labelledby="public-property-filters-heading">
            <h2 id="public-property-filters-heading" class="section-title">Filtrar propiedades</h2>
            <form action="<?= esc(site_url('catalogo-inmuebles')) ?>" method="get" class="catalog-filter-form public-catalog-filter-form">
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
                    <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-secondary">Limpiar</a>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>
        </section>

        <section aria-label="Resultados del catálogo">
            <?php if ($properties === []): ?>
                <div class="panel-card public-catalog-empty" role="status">No se encontraron propiedades disponibles con los filtros seleccionados.</div>
            <?php else: ?>
                <div class="public-property-grid">
                    <?php foreach ($properties as $item): ?>
                        <article class="panel-card public-property-card">
                            <div class="public-property-card-heading">
                                <span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $item['type'])) ?></span>
                                <span class="badge-pill badge-property-available">Disponible</span>
                            </div>
                            <h2><?= esc((string) $item['address']) ?></h2>
                            <p class="public-property-location"><?= esc((string) $item['city']) ?></p>
                            <dl class="public-property-details">
                                <?php if ($item['rooms'] !== null): ?>
                                    <div><dt>Ambientes</dt><dd><?= esc((string) $item['rooms']) ?></dd></div>
                                <?php endif; ?>
                                <?php if ($item['surface'] !== null): ?>
                                    <div><dt>Superficie</dt><dd><?= esc((string) $item['surface']) ?> m²</dd></div>
                                <?php endif; ?>
                            </dl>
                            <?php if ((string) ($item['description'] ?? '') !== ''): ?>
                                <p class="public-property-description"><?= esc((string) $item['description']) ?></p>
                            <?php endif; ?>
                            <p class="public-property-price"><?= esc($formatMoney($item['base_rent'])) ?><span> / mes</span></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="pagination-footer public-catalog-pagination">
            <div class="pagination-summary">
                Mostrando <strong><?= esc((string) $pagination['from']) ?>–<?= esc((string) $pagination['to']) ?></strong>
                de <strong><?= esc((string) $pagination['total']) ?></strong> propiedades disponibles
            </div>
            <?php if ($pagination['totalPages'] > 1): ?>
                <nav class="pagination-controls" aria-label="Paginación del catálogo">
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
    </main>
</body>
</html>
