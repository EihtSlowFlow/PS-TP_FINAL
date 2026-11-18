<?php

namespace App\Controllers;

use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Public landing page and authenticated entry point (HU-28).
 *
 * Visitors see the landing page with a value proposition, a quick search, the latest available properties,
 * role-based explanations and contact information. Authenticated users are redirected to their panel.
 */
class Home extends BaseController
{
    /**
     * Shows the public landing page to visitors or redirects authenticated users to their panel (HU-28).
     */
    public function index(): RedirectResponse|string
    {
        $session = session();

        // Escenario 3: authenticated users go to their role-based panel.
        if ($session->get('is_logged_in') === true && (int) $session->get('user_id', 0) > 0) {
            $activeUser = model(UserModel::class)->findActiveById((int) $session->get('user_id'));

            if ($activeUser !== null) {
                $role = (string) $activeUser['role_name'];
                $session->set('user_role', $role);

                return redirect()->to(UserModel::homeRouteForRole($role));
            }

            $session->remove(['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role']);
        }

        // Escenario 1: public landing page with the latest available properties.
        $properties = model(PropertyModel::class)->findLatestPublic(6);

        return view('home/landing', [
            'pageTitle'  => 'Inicio',
            'activeNav'  => 'inicio',
            'properties' => $properties,
        ]);
    }
}
