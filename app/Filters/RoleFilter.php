<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Verifies that the authenticated user holds one of the roles allowed for the route.
 */
class RoleFilter implements FilterInterface
{
    public const FORBIDDEN_ROLE_MESSAGE = 'No estás autorizado para acceder a la sección solicitada.';

    /**
     * @param list<string>|null $arguments Allowed role names (e.g. ['admin', 'employee'])
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('is_logged_in') !== true || (int) $session->get('user_id', 0) <= 0) {
            return redirect()->to('/auth/login')
                ->with('error', AuthFilter::AUTH_REQUIRED_MESSAGE);
        }

        $userRole = (string) $session->get('user_role', '');

        if ($arguments !== null && $arguments !== [] && ! in_array($userRole, $arguments, true)) {
            $targetPanel = UserModel::homeRouteForRole($userRole);

            return redirect()->to($targetPanel)
                ->with('error', self::FORBIDDEN_ROLE_MESSAGE);
        }

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

