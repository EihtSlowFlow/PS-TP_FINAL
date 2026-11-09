<?php
/**
 * Role landing placeholder (HU-01) rendered inside the shared panel layout.
 *
 * @var string $heading
 * @var string $subtitle
 * @var string $allowedNote
 * @var string $userName
 * @var string $userEmail
 * @var string $roleLabel
 */
$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <section class="panel-card" aria-labelledby="panel-heading">
                <h1 id="panel-heading" class="panel-title"><?= esc($heading) ?></h1>
                <p class="panel-subtitle"><?= esc($subtitle) ?></p>

                <div class="panel-session-summary">
                    <p><strong>Sesión activa:</strong> <?= esc($userName) ?> (<?= esc($userEmail) ?>) · Rol: <strong><?= esc($roleLabel) ?></strong></p>
                    <p><?= esc($allowedNote) ?></p>
                </div>
            </section>
<?php $this->endSection() ?>
