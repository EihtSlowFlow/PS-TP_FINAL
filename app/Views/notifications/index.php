<?php
/**
 * In-app notifications center for every authenticated role (HU-22).
 *
 * @var list<array<string, mixed>> $notifications
 * @var int                        $totalCount
 * @var int                        $unreadCount
 * @var 'all'|'unread'|'read'      $readFilter
 * @var string|null                $categoryFilter
 * @var array<string, string>      $categoryOptions
 * @var string                     $userRole
 */
use App\Models\NotificationModel;

$formatDateTime = static function (mixed $value): string {
    if (empty($value)) {
        return '—';
    }

    $timestamp = strtotime((string) $value);

    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '—';
};

$buildFilterUrl = static function (string $estado, ?string $categoria): string {
    $params = [];

    if ($estado !== 'all') {
        $params['estado'] = $estado;
    }

    if ($categoria !== null && $categoria !== '') {
        $params['categoria'] = $categoria;
    }

    $base = site_url('notificaciones');

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$this->extend('layouts/panel');
?>
<?php $this->section('content') ?>
            <div class="page-header">
                <div>
                    <div class="page-heading-row">
                        <h1 class="panel-title">Notificaciones</h1>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge-pill badge-info" id="notifications-unread-summary">
                                <?= esc((string) $unreadCount) ?> sin leer
                            </span>
                        <?php else: ?>
                            <span class="badge-pill badge-neutral" id="notifications-unread-summary">
                                Todo al día
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="panel-subtitle">Historial de avisos sobre pagos, validaciones y novedades de tu cuenta.</p>
                </div>

                <?php if ($unreadCount > 0): ?>
                    <div class="panel-toolbar">
                        <form action="<?= esc(site_url('notificaciones/leer-todas')) ?>" method="post" class="inline-action-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="<?= esc($readFilter) ?>">
                            <input type="hidden" name="categoria" value="<?= esc((string) ($categoryFilter ?? '')) ?>">
                            <button type="submit" class="btn btn-secondary" id="btn-mark-all-read">
                                Marcar todas como leídas
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <section class="panel-card notification-filters-card" aria-label="Filtros de notificaciones">
                <div class="notification-filters-bar">
                    <div class="notification-tabs" role="tablist" aria-label="Filtrar por estado de lectura">
                        <a href="<?= esc($buildFilterUrl('all', $categoryFilter)) ?>"
                           class="notification-tab<?= $readFilter === 'all' ? ' is-active' : '' ?>"
                           <?= $readFilter === 'all' ? 'aria-current="page"' : '' ?>>
                            Todas <span class="notification-tab-count">(<?= esc((string) $totalCount) ?>)</span>
                        </a>
                        <a href="<?= esc($buildFilterUrl('unread', $categoryFilter)) ?>"
                           class="notification-tab<?= $readFilter === 'unread' ? ' is-active' : '' ?>"
                           <?= $readFilter === 'unread' ? 'aria-current="page"' : '' ?>>
                            No leídas <span class="notification-tab-count">(<?= esc((string) $unreadCount) ?>)</span>
                        </a>
                        <a href="<?= esc($buildFilterUrl('read', $categoryFilter)) ?>"
                           class="notification-tab<?= $readFilter === 'read' ? ' is-active' : '' ?>"
                           <?= $readFilter === 'read' ? 'aria-current="page"' : '' ?>>
                            Leídas <span class="notification-tab-count">(<?= esc((string) max(0, $totalCount - $unreadCount)) ?>)</span>
                        </a>
                    </div>

                    <form action="<?= esc(site_url('notificaciones')) ?>" method="get" class="notification-category-form">
                        <?php if ($readFilter !== 'all'): ?>
                            <input type="hidden" name="estado" value="<?= esc($readFilter) ?>">
                        <?php endif; ?>
                        <label for="notification-category-select" class="sr-only">Categoría de notificación</label>
                        <select id="notification-category-select" name="categoria" class="form-control notification-category-select">
                            <option value="">Todos los tipos de aviso</option>
                            <?php foreach ($categoryOptions as $key => $label): ?>
                                <option value="<?= esc($key) ?>"<?= $categoryFilter === $key ? ' selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
                    </form>
                </div>
            </section>

            <?php if ($notifications === []): ?>
                <section class="panel-card notification-empty-card" aria-live="polite">
                    <div class="notification-empty-state">
                        <h2 class="section-title">No hay notificaciones para mostrar</h2>
                        <p class="section-subtitle">
                            <?php if ($readFilter === 'unread'): ?>
                                No tenés avisos pendientes de lectura con los filtros seleccionados.
                            <?php else: ?>
                                Cuando se produzcan eventos relevantes en tus pagos o gestiones, aparecerán aquí.
                            <?php endif; ?>
                        </p>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel-card" aria-labelledby="notifications-list-heading">
                    <div class="section-header">
                        <h2 id="notifications-list-heading" class="section-title">Listado de avisos</h2>
                        <p class="section-subtitle">Las notificaciones leídas se conservan en el historial para referencia.</p>
                    </div>

                    <ul class="notification-list" id="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                                $id          = (int) $notification['id'];
                                $type        = (string) $notification['type'];
                                $isUnread    = empty($notification['read_at']);
                                $tone        = NotificationModel::toneForType($type);
                                $typeLabel   = NotificationModel::labelForType($type);
                                $targetPath  = NotificationModel::targetPathFor($notification, $userRole);
                                $actionLabel = NotificationModel::actionLabelFor($type);
                            ?>
                            <li class="notification-item<?= $isUnread ? ' is-unread' : ' is-read' ?>" data-notification-id="<?= esc((string) $id) ?>">
                                <div class="notification-item-body">
                                    <div class="notification-item-meta">
                                        <span class="badge-pill badge-notification-<?= esc($tone) ?>">
                                            <?= esc($typeLabel) ?>
                                        </span>
                                        <?php if ($isUnread): ?>
                                            <span class="notification-state-tag notification-state-unread">
                                                <span class="notification-dot" aria-hidden="true"></span>
                                                Sin leer
                                            </span>
                                        <?php else: ?>
                                            <span class="notification-state-tag notification-state-read">
                                                Leída el <?= esc($formatDateTime($notification['read_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="notification-meta-sep" aria-hidden="true">·</span>
                                        <span class="notification-timestamp">
                                            <?= esc($formatDateTime($notification['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="notification-item-message">
                                        <?= esc((string) $notification['message']) ?>
                                    </p>
                                </div>

                                <div class="notification-item-actions">
                                    <?php if ($isUnread): ?>
                                        <form action="<?= esc(site_url('notificaciones/' . $id . '/leer')) ?>" method="post" class="inline-action-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="estado" value="<?= esc($readFilter) ?>">
                                            <input type="hidden" name="categoria" value="<?= esc((string) ($categoryFilter ?? '')) ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                Marcar como leída
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($targetPath !== null): ?>
                                        <?php if ($isUnread): ?>
                                            <form action="<?= esc(site_url('notificaciones/' . $id . '/leer')) ?>" method="post" class="inline-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="open_target" value="1">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <?= esc($actionLabel) ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= esc(site_url($targetPath)) ?>" class="btn btn-secondary btn-sm">
                                                <?= esc($actionLabel) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
<?php $this->endSection(); ?>

