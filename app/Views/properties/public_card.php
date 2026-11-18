<?php
/**
 * Card of an available property, shared by the landing (HU-28) and the public catalog (HU-08, HU-30): cover photo,
 * type, address, city, rooms, surface, description, rent and the actions.
 *
 * @var array<string, mixed> $item     A row of the public listing
 * @var int|null             $coverId  Id of its cover photo, or null when it has none
 * @var bool                 $isTenant A signed-in tenant can ask for the rental straight from the card (HU-30)
 */
use App\Models\PropertyModel;

$propertyId = (int) $item['id'];
$detailUrl  = site_url('catalogo-inmuebles/' . $propertyId);
$address    = (string) $item['address'];
$rent       = (float) $item['base_rent'];
$rentLabel  = '$ ' . number_format($rent, fmod($rent, 1.0) === 0.0 ? 0 : 2, ',', '.');
?>
<article class="panel-card public-property-card">
    <a class="public-property-photo" href="<?= esc($detailUrl) ?>" tabindex="-1" aria-hidden="true">
        <?php if (! empty($coverId)): ?>
            <img src="<?= esc(site_url('inmuebles/fotos/' . (int) $coverId)) ?>" alt="" loading="lazy" width="640" height="480">
        <?php else: ?>
            <span class="public-property-photo-empty">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Sin fotos todavía</span>
            </span>
        <?php endif; ?>
        <span class="public-property-card-heading">
            <span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $item['type'])) ?></span>
            <span class="badge-pill badge-property-available">Disponible</span>
        </span>
    </a>
    <div class="public-property-body">
        <h2><a href="<?= esc($detailUrl) ?>"><?= esc($address) ?></a></h2>
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
        <div class="public-property-footer">
            <p class="public-property-price"><?= esc($rentLabel) ?><span> / mes</span></p>
            <div class="public-property-actions">
                <a href="<?= esc($detailUrl) ?>" class="btn <?= $isTenant ? 'btn-secondary' : 'btn-primary' ?> btn-sm">Ver detalle</a>
                <?php if ($isTenant): ?>
                    <a href="<?= esc(site_url('inquilino/inmuebles/' . $propertyId . '/solicitar')) ?>" class="btn btn-primary btn-sm">Solicitar alquiler</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>
