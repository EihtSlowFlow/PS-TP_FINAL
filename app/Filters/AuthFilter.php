<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Ensures the request has a valid authenticated session belonging to an active user.
 */
class AuthFilter implements FilterInterface
{
    public const AUTH_REQUIRED_MESSAGE      = 'Debés iniciar sesión para continuar.';
    public const ACCOUNT_DISABLED_MESSAGE   = 'Tu cuenta se encuentra deshabilitada. Contactá a la administración.';

    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        $isLoggedIn = $session->get('is_logged_in') === true;
        $userId     = (int) $session->get('user_id', 0);

        if (! $isLoggedIn || $userId <= 0) {
            return redirect()->to('/auth/login')
                ->with('error', self::AUTH_REQUIRED_MESSAGE);
        }

        $activeUser = model(UserModel::class)->findActiveById($userId);

        if ($activeUser === null) {
            $session->remove(['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role']);

            return redirect()->to('/auth/login')
                ->with('error', self::ACCOUNT_DISABLED_MESSAGE);
        }

        // Keep the server-side role synchronized with the database record.
        $session->set('user_role', (string) $activeUser['role_name']);

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');

        return $response;
    }
}

