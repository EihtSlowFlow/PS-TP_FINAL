<?php
/**
 * Reusable expiring-contracts alert block (HU-24).
 * Included in admin/owner dashboards (also reusable in HU-25 / HU-26) and contract listings.
 *
 * @var list<array<string, mixed>> $expiringContracts
 * @var string                     $expiringContractBaseUrl 'admin/contratos' or 'propietario/contratos'
 * @var bool                       $isOwnerExpiringView
 */
$expiringContracts       = is_array($expiringContracts ?? null) ? $expiringContracts : [];
$expiringContractBaseUrl = (string) ($expiringContractBaseUrl ?? 'admin/contratos');
$isOwnerExpiringView     = (bool) ($isOwnerExpiringView ?? false);

if ($expiringContracts === []) {
    return;
}

$count = count($expiringContracts);
?>
            <section class="panel-card due-soon-alert-card" id="expiring-contracts-alerts" aria-labelledby="expiring-contracts-alerts-heading">
                <div class="due-soon-alert-header">
                    <div class="due-soon-alert-heading-row">
                        <span class="badge-pill badge-notification-warning"><?= $count === 1 ? 'Contrato por vencer' : 'Contratos por vencer' ?></span>
                        <h2 id="expiring-contracts-alerts-heading" class="section-title">
                            <?php if ($isOwnerExpiringView): ?>
                                <?= $count === 1
                                    ? 'Tenés 1 contrato que vence en los próximos 60 días'
                                    : 'Tenés ' . esc((string) $count) . ' contratos que vencen en los próximos 60 días' ?>
                            <?php else: ?>
                                <?= $count === 1
                                    ? 'Hay 1 contrato que vence en los próximos 60 días'
                                    : 'Hay ' . esc((string) $count) . ' contratos que vencen en los próximos 60 días' ?>
                            <?php endif; ?>
                        </h2>
                    </div>
                    <p class="panel-subtitle">Revisá la ficha de cada contrato para coordinar a tiempo la renovación o restitución del inmueble.</p>
                </div>

                <ul class="due-soon-alert-list">
                    <?php foreach ($expiringContracts as $expiringContract): ?>
                        <?php
                            $contractId   = (int) $expiringContract['id'];
                            $tenantName   = trim(
                                (string) ($expiringContract['tenant_first_name'] ?? '') . ' ' . (string) ($expiringContract['tenant_last_name'] ?? '')
                            );
                            $endDateLabel = ! empty($expiringContract['end_date'])
                                ? date('d/m/Y', strtotime((string) $expiringContract['end_date']))
                                : '—';
                            $daysToEnd    = (int) ($expiringContract['days_to_end'] ?? 0);
                            $countdown    = match ($daysToEnd) {
                                0       => 'Vence hoy',
                                1       => 'Vence mañana',
                                default => 'Vence en ' . $daysToEnd . ' días',
                            };
                        ?>
                        <li class="due-soon-alert-item" data-expiring-contract-id="<?= esc((string) $contractId) ?>">
                            <div class="due-soon-alert-info">
                                <span class="cell-title">
                                    <?= esc((string) $expiringContract['property_address']) ?>
                                    <?php if (! empty($expiringContract['property_city'])): ?>
                                        · <?= esc((string) $expiringContract['property_city']) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="due-soon-alert-meta">
                                    Inquilino: <strong><?= esc($tenantName !== '' ? $tenantName : 'Sin inquilino') ?></strong>
                                    <span aria-hidden="true">·</span>
                                    Vencimiento: <strong><?= esc($endDateLabel) ?></strong>
                                    <span aria-hidden="true">·</span>
                                    <span class="due-soon-countdown"><?= esc($countdown) ?></span>
                                </span>
                            </div>
                            <a class="btn btn-primary btn-sm" href="<?= esc(site_url($expiringContractBaseUrl . '/' . $contractId)) ?>">
                                Ver contrato
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

