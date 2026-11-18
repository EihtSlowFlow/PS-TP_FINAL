<?php
/**
 * Public property detail view (HU-29).
 *
 * Excludes internal owner identity, lease contract records and audit fields.
 *
 * @var string $pageTitle
 * @var string $activeNav
 * @var array<string, mixed> $property
 * @var bool   $isLoggedIn
 * @var string $userRole
 * @var string $catalogBackUrl
 */
use App\Models\PropertyModel;
use App\Models\UserModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

/** @var \Config\Agency $agency Contact data of the agency (app/Config/Agency.php) */
$agency = config('Agency');

$waMessage = rawurlencode('Hola, quisiera consultar por el inmueble en ' . $property['address'] . ', ' . $property['city']);
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="property-detail-page">
    <!-- Breadcrumb navigation -->
    <nav class="property-detail-nav" aria-label="Miga de pan">
        <a href="<?= esc($catalogBackUrl) ?>" class="property-back-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            <span>Volver al catálogo</span>
        </a>
        <span class="property-nav-separator" aria-hidden="true">/</span>
        <span class="property-nav-current"><?= esc((string) $property['address']) ?></span>
    </nav>

    <!-- Header section -->
    <header class="panel-card property-detail-header">
        <div class="property-detail-header-info">
            <div class="property-detail-badges">
                <span class="badge-pill badge-neutral"><?= esc(PropertyModel::labelForType((string) $property['type'])) ?></span>
                <span class="badge-pill badge-property-available">Disponible</span>
            </div>
            <h1 class="panel-title property-detail-title"><?= esc((string) $property['address']) ?></h1>
            <p class="property-detail-city">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span><?= esc((string) $property['city']) ?></span>
            </p>
        </div>
        <div class="property-detail-header-price">
            <span class="property-price-label">Precio mensual</span>
            <div class="property-price-value"><?= esc($formatMoney($property['base_rent'])) ?><span> / mes</span></div>
        </div>
    </header>

    <!-- Main Content Grid -->
    <div class="property-detail-grid">
        <!-- Main Info Column -->
        <div class="property-detail-main">
            <!-- Technical Sheet -->
            <section class="panel-card" aria-labelledby="technical-sheet-heading">
                <h2 id="technical-sheet-heading" class="section-title">Ficha técnica del inmueble</h2>
                <div class="property-specs-grid">
                    <div class="property-spec-item">
                        <span class="property-spec-label">Tipo de inmueble</span>
                        <span class="property-spec-value"><?= esc(PropertyModel::labelForType((string) $property['type'])) ?></span>
                    </div>
                    <div class="property-spec-item">
                        <span class="property-spec-label">Ambientes</span>
                        <span class="property-spec-value"><?= $property['rooms'] !== null ? esc((string) $property['rooms']) . ' ambientes' : 'No especificado' ?></span>
                    </div>
                    <div class="property-spec-item">
                        <span class="property-spec-label">Superficie</span>
                        <span class="property-spec-value"><?= $property['surface'] !== null ? esc((string) $property['surface']) . ' m²' : 'No especificada' ?></span>
                    </div>
                    <div class="property-spec-item">
                        <span class="property-spec-label">Servicios incluidos</span>
                        <span class="property-spec-value"><?= (string) ($property['included_services'] ?? '') !== '' ? esc((string) $property['included_services']) : 'Sin servicios incluidos' ?></span>
                    </div>
                    <div class="property-spec-item">
                        <span class="property-spec-label">Estado de disponibilidad</span>
                        <span class="property-spec-value">Disponible para alquilar</span>
                    </div>
                    <div class="property-spec-item">
                        <span class="property-spec-label">Canon mensual base</span>
                        <span class="property-spec-value"><?= esc($formatMoney($property['base_rent'])) ?></span>
                    </div>
                </div>
            </section>

            <!-- Description -->
            <section class="panel-card" aria-labelledby="description-heading">
                <h2 id="description-heading" class="section-title">Descripción</h2>
                <?php if ((string) ($property['description'] ?? '') !== ''): ?>
                    <div class="property-description-body">
                        <?= nl2br(esc((string) $property['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="property-description-empty">No se ha provisto una descripción adicional para esta propiedad.</p>
                <?php endif; ?>
            </section>

            <!-- General Rental Conditions -->
            <section class="panel-card" aria-labelledby="conditions-heading">
                <h2 id="conditions-heading" class="section-title">Condiciones de contratación</h2>
                <div class="property-conditions-list">
                    <div class="property-condition-item">
                        <strong>Pago del canon:</strong> Se realiza de forma bancaria mediante depósito o transferencia mensual con emisión de cuota digital.
                    </div>
                    <div class="property-condition-item">
                        <strong>Garantías requeridas:</strong> Recibos de sueldo o garantía propietaria sujeta a evaluación por la administración.
                    </div>
                    <div class="property-condition-item">
                        <strong>Gestión formal:</strong> Proceso administrado por InmoGestión con contrato formal y seguimiento transparente de cuotas y pagos.
                    </div>
                </div>
            </section>
        </div>

        <!-- Lateral Column / Action Card -->
        <aside class="property-detail-sidebar">
            <div class="panel-card property-action-card" aria-labelledby="interest-heading">
                <div class="property-action-header">
                    <h2 id="interest-heading" class="property-action-title">¿Te interesa este lugar?</h2>
                    <span class="badge-pill badge-property-available">Disponible</span>
                </div>

                <div class="property-action-price-box">
                    <span class="property-action-price-label">Valor mensual de referencia</span>
                    <div class="property-action-price"><?= esc($formatMoney($property['base_rent'])) ?><span> / mes</span></div>
                </div>

                <!-- Call to action based on user session role (HU-29 Escenario 2) -->
                <div class="property-role-action">
                    <?php if (! $isLoggedIn): ?>
                        <p class="property-action-help">Registrate como inquilino para solicitar este alquiler o iniciá sesión si ya tenés cuenta.</p>
                        <div class="property-action-buttons">
                            <a href="<?= esc(site_url('auth/login')) ?>" class="btn btn-primary w-full" id="btn-action-login">Ingresar</a>
                            <a href="<?= esc(site_url('auth/register?role=tenant')) ?>" class="btn btn-secondary w-full" id="btn-action-register">Registrarme</a>
                        </div>
                    <?php elseif ($userRole === UserModel::ROLE_TENANT): ?>
                        <p class="property-action-help">Podés enviar tu solicitud de alquiler directamente a la administración.</p>
                        <div class="property-action-buttons">
                            <a href="<?= esc(site_url('inquilino/inmuebles/' . (int) $property['id'] . '/solicitar')) ?>" class="btn btn-primary w-full" id="btn-action-request">Solicitar alquiler</a>
                        </div>
                    <?php else: ?>
                        <div class="property-role-notice" role="status">
                            <p>Estás viendo la ficha pública con rol <strong><?= esc(UserModel::labelForRole($userRole)) ?></strong>. La solicitud de alquiler en línea está disponible para inquilinos.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="property-action-separator" aria-hidden="true"></div>

                <!-- Direct Contact Channels (HU-29 Escenario 4) -->
                <div class="property-contact-box">
                    <h3 class="property-contact-title">Canales de atención directa</h3>
                    <a href="https://wa.me/<?= esc(preg_replace('/\D+/', '', $agency->whatsapp), 'url') ?>?text=<?= esc($waMessage, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary property-whatsapp-btn" id="btn-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        </svg>
                        <span>Consultar por WhatsApp</span>
                    </a>

                    <div class="property-contact-details">
                        <div class="property-contact-line">
                            <span class="property-contact-sub">Teléfono:</span>
                            <span class="property-contact-val"><?= esc($agency->phone) ?></span>
                        </div>
                        <div class="property-contact-line">
                            <span class="property-contact-sub">Correo:</span>
                            <span class="property-contact-val"><?= esc($agency->email) ?></span>
                        </div>
                        <div class="property-contact-line">
                            <span class="property-contact-sub">Horario:</span>
                            <span class="property-contact-val"><?= esc($agency->hours) ?></span>
                        </div>
                    </div>
                </div>

                <div class="property-action-separator" aria-hidden="true"></div>

                <a href="<?= esc($catalogBackUrl) ?>" class="btn btn-secondary w-full" id="btn-back-catalog">Volver al catálogo</a>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
