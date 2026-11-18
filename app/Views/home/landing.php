<?php
/**
 * Public landing page view (HU-28).
 *
 * @var string $pageTitle
 * @var string $activeNav
 * @var list<array<string, mixed>> $properties
 * @var array<int, int>            $covers  Property id => id of its cover photo
 * @var array{properties: int, cities: int} $summary How many properties the catalog offers and in how many cities
 */
use App\Models\PropertyModel;

/** @var \Config\Agency $agency Contact data of the agency (app/Config/Agency.php) */
$agency = config('Agency');

$covers  = $covers ?? [];
$summary = $summary ?? ['properties' => count($properties), 'cities' => count(array_unique(array_column($properties, 'city')))];

// The hero shows the photos of the latest properties that have one (up to three), each linking to its sheet.
$heroPhotos = [];
foreach ($properties as $item) {
    if (isset($covers[(int) $item['id']]) && count($heroPhotos) < 3) {
        $heroPhotos[] = ['imageId' => $covers[(int) $item['id']], 'item' => $item];
    }
}
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="landing-page">
    <!-- Hero / Value Proposition -->
    <section class="landing-hero<?= $heroPhotos !== [] ? ' has-photos' : '' ?>" aria-labelledby="landing-hero-title">
        <div class="landing-hero-content">
            <span class="landing-hero-eyebrow">Alquileres administrados de punta a punta</span>
            <h1 id="landing-hero-title" class="landing-hero-title">Gestión transparente y moderna de alquileres</h1>
            <p class="landing-hero-subtitle">
                Conectamos propietarios e inquilinos en una plataforma segura. Administrá tus propiedades, gestioná contratos, emití y pagá cuotas mensuales con total claridad y seguimiento en tiempo real.
            </p>
            <div class="landing-hero-actions">
                <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-primary" id="btn-hero-catalog">Buscar alquiler</a>
                <a href="<?= esc(site_url('auth/register?role=owner')) ?>" class="btn btn-secondary" id="btn-hero-publish">Publicar mi inmueble</a>
                <a href="<?= esc(site_url('auth/register')) ?>" class="btn btn-secondary" id="btn-hero-register">Registrarme</a>
            </div>
            <?php if ($summary['properties'] > 0): ?>
                <dl class="landing-hero-summary" aria-label="El catálogo hoy">
                    <div>
                        <dt><?= $summary['properties'] === 1 ? 'inmueble disponible' : 'inmuebles disponibles' ?></dt>
                        <dd><?= esc((string) $summary['properties']) ?></dd>
                    </div>
                    <div>
                        <dt><?= $summary['cities'] === 1 ? 'ciudad' : 'ciudades' ?></dt>
                        <dd><?= esc((string) $summary['cities']) ?></dd>
                    </div>
                </dl>
            <?php endif; ?>
        </div>

        <?php if ($heroPhotos !== []): ?>
            <div class="landing-hero-media count-<?= esc((string) count($heroPhotos)) ?>">
                <?php foreach ($heroPhotos as $photo): ?>
                    <a class="landing-hero-photo" href="<?= esc(site_url('catalogo-inmuebles/' . (int) $photo['item']['id'])) ?>">
                        <img src="<?= esc(site_url('inmuebles/fotos/' . (int) $photo['imageId'])) ?>" alt="Foto de <?= esc((string) $photo['item']['address']) ?>, <?= esc((string) $photo['item']['city']) ?>" width="640" height="480">
                        <span class="landing-hero-photo-caption">
                            <strong><?= esc((string) $photo['item']['address']) ?></strong>
                            <span><?= esc((string) $photo['item']['city']) ?> · <?= esc(PropertyModel::labelForType((string) $photo['item']['type'])) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <ul class="landing-hero-features" aria-label="Lo que resuelve InmoGestión">
                <li><strong>Contratos claros</strong><span>Canon, comisión y actualización anual en un solo lugar.</span></li>
                <li><strong>Cuotas y mora automáticas</strong><span>Cada mes se emiten solas y el recargo se calcula al día.</span></li>
                <li><strong>Pagos con comprobante</strong><span>El inquilino informa el pago y la administración lo valida.</span></li>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Quick Search Bar -->
    <section class="panel-card landing-search-card" aria-labelledby="quick-search-heading">
        <h2 id="quick-search-heading" class="section-title">Buscador rápido</h2>
        <form action="<?= esc(site_url('catalogo-inmuebles')) ?>" method="get" class="landing-search-form">
            <div class="form-field">
                <label for="quick-filter-type">Tipo de inmueble</label>
                <select id="quick-filter-type" name="type" class="form-control">
                    <option value="">Todos los tipos</option>
                    <?php foreach (PropertyModel::TYPE_LABELS as $typeValue => $typeLabel): ?>
                        <option value="<?= esc($typeValue) ?>"><?= esc($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="quick-filter-max-price">Precio máximo</label>
                <input id="quick-filter-max-price" name="max_price" type="number" min="0.01" step="0.01" placeholder="Ej: 500000" class="form-control">
            </div>
            <div class="landing-search-actions">
                <button type="submit" class="btn btn-primary" id="btn-quick-search">Buscar en el catálogo</button>
            </div>
        </form>
    </section>

    <!-- Latest Available Properties -->
    <section class="landing-section" aria-labelledby="latest-properties-heading">
        <div class="section-header">
            <div>
                <h2 id="latest-properties-heading" class="panel-title">Últimas propiedades disponibles</h2>
                <p class="panel-subtitle">Explorá los inmuebles más recientes listos para alquilar.</p>
            </div>
            <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-secondary btn-sm">Ver todas las propiedades</a>
        </div>

        <?php if ($properties === []): ?>
            <div class="panel-card empty-state" role="status">
                <h3 class="empty-state-title">No hay propiedades disponibles</h3>
                <p class="empty-state-text">En este momento no contamos con inmuebles en alquiler publicados. ¡Volvé a consultar pronto o publicá tu propiedad!</p>
                <a href="<?= esc(site_url('auth/register?role=owner')) ?>" class="btn btn-primary btn-sm">Publicar inmueble</a>
            </div>
        <?php else: ?>
            <div class="public-property-grid">
                <?php foreach ($properties as $item): ?>
                    <?= view('properties/public_card', [
                        'item'     => $item,
                        'coverId'  => $covers[(int) $item['id']] ?? null,
                        'isTenant' => false,
                    ]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Explanation for Tenants and Owners -->
    <section class="landing-section" aria-labelledby="services-heading">
        <h2 id="services-heading" class="panel-title text-center">¿Cómo funciona InmoGestión?</h2>
        <p class="panel-subtitle text-center">Una solución pensada tanto para quienes buscan alquilar como para propietarios e inversores.</p>

        <div class="landing-cards-grid">
            <div class="panel-card landing-info-card">
                <div class="landing-info-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h3>Para Inquilinos</h3>
                <ol class="landing-steps">
                    <li>Encontrá tu próximo hogar en el catálogo público.</li>
                    <li>Registrate como inquilino y solicitá el alquiler en línea.</li>
                    <li>La administración revisa tu solicitud y registra el contrato.</li>
                    <li>Consultá tu estado de cuenta mes a mes e informá tus pagos con el comprobante.</li>
                </ol>
                <div class="landing-info-actions">
                    <a href="<?= esc(site_url('auth/register?role=tenant')) ?>" class="btn btn-primary btn-sm">Crear cuenta de Inquilino</a>
                </div>
            </div>

            <div class="panel-card landing-info-card">
                <div class="landing-info-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <h3>Para Propietarios</h3>
                <ol class="landing-steps">
                    <li>Registrate como propietario.</li>
                    <li>Publicá tu inmueble: nuestro equipo lo revisa y lo difunde en el catálogo.</li>
                    <li>Seguí el estado de los contratos de locación vigentes.</li>
                    <li>Recibí liquidaciones mensuales con la comisión y las transferencias detalladas.</li>
                </ol>
                <div class="landing-info-actions">
                    <a href="<?= esc(site_url('auth/register?role=owner')) ?>" class="btn btn-primary btn-sm">Publicar como Propietario</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Details -->
    <section class="landing-section" aria-labelledby="contact-heading">
        <div class="panel-card landing-contact-card">
            <div>
                <h2 id="contact-heading" class="section-title">Ponete en contacto</h2>
                <p class="panel-subtitle">¿Tenés dudas o necesitás asistencia personalizada? Nuestro equipo está a tu disposición.</p>
            </div>
            <div class="landing-contact-grid">
                <div class="landing-contact-item">
                    <span class="landing-contact-label">Dirección</span>
                    <span class="landing-contact-value"><?= esc($agency->address) ?></span>
                </div>
                <div class="landing-contact-item">
                    <span class="landing-contact-label">Teléfono</span>
                    <span class="landing-contact-value"><?= esc($agency->phone) ?></span>
                </div>
                <div class="landing-contact-item">
                    <span class="landing-contact-label">Correo electrónico</span>
                    <span class="landing-contact-value"><?= esc($agency->email) ?></span>
                </div>
                <div class="landing-contact-item">
                    <span class="landing-contact-label">Horario de atención</span>
                    <span class="landing-contact-value"><?= esc($agency->hours) ?></span>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
