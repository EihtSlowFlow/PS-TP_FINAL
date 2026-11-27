<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\PropertyModel;
use App\Models\RentalRequestModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /** Shared by the public registration (HU-02) and the user management of the administration (HU-04). */
    public const NAME_FORMAT_MESSAGE_FIRST = 'El nombre solo puede tener letras, espacios, apóstrofos, guiones y puntos.';
    public const NAME_FORMAT_MESSAGE_LAST  = 'El apellido solo puede tener letras, espacios, apóstrofos, guiones y puntos.';
    public const DNI_FORMAT_MESSAGE        = 'El DNI solo puede tener números (podés escribirlo con puntos o guiones).';

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /** Guards against syncing on-demand alerts (HU-23, HU-24) more than once per request. */
    private bool $roleAlertsSynced = false;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Trims a name and collapses runs of whitespace into one space, so "Juan   Pérez" is stored as "Juan Pérez".
     */
    protected static function collapseSpaces(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    /**
     * Builds the data shared by every page rendered with the `layouts/panel` layout
     * (page headings, sidebar user card and role label).
     *
     * @return array{
     *     pageTitle: string,
     *     breadcrumb: string,
     *     activeMenu: string,
     *     userName: string,
     *     userEmail: string,
     *     userRole: string,
     *     roleLabel: string,
     *     initials: string,
     *     pendingPropertyCount: int,
     *     pendingRequestCount: int,
     *     pendingPaymentCount: int
     * }
     */
    protected function panelContext(string $pageTitle, string $breadcrumb, string $activeMenu): array
    {
        $session  = session();
        $userId   = (int) $session->get('user_id', 0);
        $userRole = (string) $session->get('user_role', '');
        $userName = (string) $session->get('user_name', 'Usuario');
        $isStaff  = in_array($userRole, [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE], true);

        $parts    = preg_split('/\s+/', trim($userName)) ?: [];
        $initials = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'U';
        if (isset($parts[1][0])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }

        $this->syncRoleAlerts($userId, $userRole);

        $notificationModel = $userId > 0 ? model(NotificationModel::class) : null;

        return [
            'pageTitle'  => $pageTitle,
            'breadcrumb' => $breadcrumb,
            'activeMenu' => $activeMenu,
            'userName'   => $userName,
            'userEmail'  => (string) $session->get('user_email', ''),
            'userRole'   => $userRole,
            'roleLabel'  => UserModel::labelForRole($userRole),
            'initials'   => $initials,
            // The "Inmuebles", "Solicitudes" and "Pagos" menu entries of the administration show how many items wait
            // for review (HU-31, HU-33, HU-17): a reported payment shows up there as a notice to go and review it.
            'pendingPropertyCount'    => $isStaff ? model(PropertyModel::class)->countPendingApproval() : 0,
            'pendingRequestCount'     => $isStaff ? model(RentalRequestModel::class)->countPending() : 0,
            'pendingPaymentCount'     => $isStaff ? model(PaymentModel::class)->countPendingValidation() : 0,
            // HU-22: unread counter and recent items for the topbar notification bell of every authenticated role.
            'unreadNotificationCount' => $notificationModel !== null ? $notificationModel->countUnreadForUser($userId) : 0,
            'recentNotifications'     => $notificationModel !== null ? $notificationModel->findRecentForUser($userId, 5) : [],
        ];
    }

    /**
     * Detects and persists on-demand alerts once per request (HU-23 due-soon installments for tenants;
     * ready to plug in HU-24 expiring contracts for staff and owners).
     */
    protected function syncRoleAlerts(int $userId, string $userRole): void
    {
        if ($this->roleAlertsSynced || $userId <= 0) {
            return;
        }

        $this->roleAlertsSynced = true;

        if ($userRole === UserModel::ROLE_TENANT) {
            service('notifications')->syncDueSoonInstallmentAlertsForTenant($userId);
        } elseif (in_array($userRole, [UserModel::ROLE_ADMIN, UserModel::ROLE_EMPLOYEE], true)) {
            service('notifications')->syncExpiringContractAlertsForStaff($userId);
        } elseif ($userRole === UserModel::ROLE_OWNER) {
            service('notifications')->syncExpiringContractAlertsForOwner($userId);
        }
    }

    /**
     * Tells whether a GET route is registered, so views never link to pages that do not exist yet.
     *
     * @param string $path Route path without leading slash, e.g. `auth/register`
     */
    protected function routeExists(string $path): bool
    {
        // The route collection indexes routes by upper-case HTTP verb.
        return array_key_exists(trim($path, '/'), service('routes')->getRoutes('GET'));
    }
}
