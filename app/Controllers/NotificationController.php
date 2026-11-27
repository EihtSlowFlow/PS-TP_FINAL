<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * In-app notifications center and read-state transitions for all authenticated roles (HU-22).
 */
class NotificationController extends BaseController
{
    public const NOT_FOUND_MESSAGE = 'La notificación solicitada no existe o no te pertenece.';

    /**
     * Lists the authenticated user's notifications with read-state and category filters.
     */
    public function index(): string
    {
        $userId     = (int) session()->get('user_id');
        $readFilter = $this->normalizeReadFilter($this->request->getGet('estado'));
        $category   = $this->normalizeCategoryFilter($this->request->getGet('categoria'));
        $model      = model(NotificationModel::class);

        $notifications = $model->findForUser($userId, $readFilter, $category);
        $totalCount    = $model->countAllForUser($userId);
        $unreadCount   = $model->countUnreadForUser($userId);

        return view('notifications/index', array_merge(
            $this->panelContext('Notificaciones', 'Notificaciones', 'notificaciones'),
            [
                'notifications'   => $notifications,
                'totalCount'      => $totalCount,
                'unreadCount'     => $unreadCount,
                'readFilter'      => $readFilter,
                'categoryFilter'  => $category,
                'categoryOptions' => NotificationModel::CATEGORY_LABELS,
            ]
        ));
    }

    /**
     * Marks a single owned notification as read (POST with CSRF).
     * When `open_target=1` is submitted, redirects directly to the linked screen after marking as read.
     */
    public function markRead(int|string $notificationId): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $id     = (int) $notificationId;
        $model  = model(NotificationModel::class);

        $notification = $model->findOwnedByUser($id, $userId);

        if ($notification === null) {
            return redirect()->to(site_url('notificaciones'))
                ->with('error', self::NOT_FOUND_MESSAGE);
        }

        $model->markAsRead($id, $userId);

        if ((string) $this->request->getPost('open_target') === '1') {
            $userRole   = (string) session()->get('user_role', '');
            $targetPath = NotificationModel::targetPathFor($notification, $userRole);

            if ($targetPath !== null) {
                return redirect()->to(site_url($targetPath));
            }
        }

        return redirect()->to($this->safeReturnUrl())
            ->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marks all unread notifications of the authenticated user as read (POST with CSRF).
     */
    public function markAllRead(): RedirectResponse
    {
        $userId  = (int) session()->get('user_id');
        $updated = model(NotificationModel::class)->markAllAsRead($userId);

        $message = $updated > 0
            ? 'Todas las notificaciones fueron marcadas como leídas.'
            : 'No tenías notificaciones pendientes por leer.';

        return redirect()->to($this->safeReturnUrl())
            ->with('success', $message);
    }

    /**
     * @return 'all'|'unread'|'read'
     */
    private function normalizeReadFilter(mixed $value): string
    {
        $raw = strtolower(trim((string) $value));

        return match ($raw) {
            'unread', 'no-leidas' => 'unread',
            'read', 'leidas'      => 'read',
            default               => 'all',
        };
    }

    private function normalizeCategoryFilter(mixed $value): ?string
    {
        $raw = strtolower(trim((string) $value));

        if ($raw === '' || ! array_key_exists($raw, NotificationModel::CATEGORY_LABELS)) {
            return null;
        }

        return $raw;
    }

    /**
     * Keeps redirects on internal pages: actions from the topbar popover return to the page the user was on
     * (CI4's previous_url(), which only stores this site's URLs); actions from the center keep its filters.
     */
    private function safeReturnUrl(): string
    {
        if ((string) $this->request->getPost('origin') === 'popover') {
            $previous = previous_url();
            $siteBase = rtrim(site_url(), '/');

            if (is_string($previous) && str_starts_with($previous, $siteBase)) {
                return $previous;
            }
        }

        $estado    = $this->normalizeReadFilter($this->request->getPost('estado'));
        $categoria = $this->normalizeCategoryFilter($this->request->getPost('categoria'));
        $query     = [];

        if ($estado !== 'all') {
            $query['estado'] = $estado;
        }

        if ($categoria !== null) {
            $query['categoria'] = $categoria;
        }

        $base = site_url('notificaciones');

        return $query === [] ? $base : $base . '?' . http_build_query($query);
    }
}

