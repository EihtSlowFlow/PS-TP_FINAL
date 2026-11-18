<?php
/**
 * Tenant contracts listing ("Mi contrato", HU-34 Scenarios 1 and 4): read only, the contracts in force first.
 *
 * The rows come from ContractModel::findForTenant(), which never selects the agency commission or any data of
 * the owner.
 *
 * @var list<array<string, mixed>> $contracts
 */
use App\Models\ContractModel;
use App\Models\PropertyModel;

$formatMoney = static function (mixed $amount): string {
    $value = (float) $amount;

    return '$ ' . number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
};

$formatDate = static fn (mixed $value): string => ! empty($value) ? date('d/m/Y', strtotime((string) $value)) : '—';

$plural = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Mis contratos</h1>
                        <span class="badge-pill badge-neutral"><?= esc($plural(count($contracts), 'contrato', 'contratos')) ?></span>
                    </div>
                    <p class="panel-subtitle">Plazo, canon mensual y estado de tus contratos de alquiler, vigentes e históricos.</p>
                </div>
            </div>

            <?php if ($contracts === []): ?>
                <section class="empty-state" id="tenant-contracts-empty" aria-label="Sin contratos">
                    <h2 class="empty-state-title">Todavía no tenés contratos</h2>
                    <p>Cuando la administración apruebe una solicitud tuya, el contrato aparecerá acá. Podés buscar un inmueble o seguir el estado de tus solicitudes.</p>
                    <div class="inline-actions-row tenant-contract-empty-actions">
                        <a href="<?= esc(site_url('catalogo-inmuebles')) ?>" class="btn btn-primary" id="btn-empty-contracts-search">Buscar inmuebles</a>
                        <a href="<?= esc(site_url('inquilino/solicitudes')) ?>" class="btn btn-secondary" id="btn-empty-contracts-requests">Mis solicitudes</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel-card table-card" aria-labelledby="tenant-contracts-heading">
                    <div class="section-header">
                        <h2 id="tenant-contracts-heading" class="section-title">Contratos de alquiler</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" aria-label="Contratos del inquilino">
                            <thead>
                                <tr>
                                    <th scope="col">Inmueble</th>
                                    <th scope="col">Vigencia</th>
                                    <th scope="col" class="text-right">Canon mensual</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contracts as $contract): ?>
                                    <?php $status = (string) $contract['status']; ?>
                                    <tr data-contract-id="<?= esc((string) $contract['id']) ?>">
                                        <td>
                                            <div class="cell-meta">
                                                <span class="cell-title"><?= esc((string) $contract['property_address']) ?></span>
                                                <span class="cell-sub"><?= esc((string) $contract['property_city']) ?> · <?= esc(PropertyModel::labelForType((string) $contract['property_type'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="mono-cell nowrap-cell"><?= esc($formatDate($contract['start_date'])) ?> – <?= esc($formatDate($contract['end_date'])) ?></td>
                                        <td class="mono-cell nowrap-cell text-right"><?= esc($formatMoney($contract['monthly_rent'])) ?></td>
                                        <td><span class="badge-pill badge-contract-<?= esc($status) ?>"><?= esc(ContractModel::labelForStatus($status)) ?></span></td>
                                        <td class="text-right nowrap-cell">
                                            <a href="<?= esc(site_url('inquilino/contratos/' . (int) $contract['id'])) ?>" class="btn btn-secondary btn-sm">Ver contrato</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
<?php $this->endSection() ?>
