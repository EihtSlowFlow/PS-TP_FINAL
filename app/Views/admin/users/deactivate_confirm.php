<?php
/**
 * Server-side confirmation step for the logical deactivation of a user (HU-05 Scenario 1).
 * Shown when the deactivation request arrives without `confirmed=1`, e.g. with JavaScript disabled.
 *
 * @var array<string, mixed> $user            Target user row (with role_name)
 * @var string               $targetRoleLabel Spanish label of the target user's role
 */
$targetUserId   = (int) $user['id'];
$targetFullName = trim(((string) $user['first_name']) . ' ' . ((string) $user['last_name']));

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="form-back-row">
                <a href="<?= esc(site_url('admin/usuarios')) ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Volver al listado de usuarios</span>
                </a>
            </div>

            <section class="panel-card form-section-card" aria-labelledby="deactivate-heading">
                <div class="section-header">
                    <h1 id="deactivate-heading" class="panel-title">¿Confirmás la baja lógica del usuario?</h1>
                </div>

                <p class="confirm-modal-text">
                    El usuario <strong><?= esc($targetFullName) ?></strong> (<span><?= esc($targetRoleLabel) ?></span>)
                    pasará a estado inactivo: no podrá iniciar sesión ni ser seleccionado en nuevos contratos, pero se conservarán
                    su registro histórico y sus referencias contables asociadas para consulta y auditoría.
                </p>

                <form action="<?= esc(site_url('admin/usuarios/baja/' . $targetUserId)) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirmed" value="1">
                    <div class="confirm-modal-actions">
                        <a href="<?= esc(site_url('admin/usuarios')) ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-danger" id="btn-confirm-deactivation">Confirmar baja</button>
                    </div>
                </form>
            </section>
<?php $this->endSection() ?>
