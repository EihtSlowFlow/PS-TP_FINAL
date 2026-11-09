<?php

namespace App\Controllers;

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
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

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
     *     initials: string
     * }
     */
    protected function panelContext(string $pageTitle, string $breadcrumb, string $activeMenu): array
    {
        $session  = session();
        $userRole = (string) $session->get('user_role', '');
        $userName = (string) $session->get('user_name', 'Usuario');

        $parts    = preg_split('/\s+/', trim($userName)) ?: [];
        $initials = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'U';
        if (isset($parts[1][0])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }

        return [
            'pageTitle'  => $pageTitle,
            'breadcrumb' => $breadcrumb,
            'activeMenu' => $activeMenu,
            'userName'   => $userName,
            'userEmail'  => (string) $session->get('user_email', ''),
            'userRole'   => $userRole,
            'roleLabel'  => UserModel::labelForRole($userRole),
            'initials'   => $initials,
        ];
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
