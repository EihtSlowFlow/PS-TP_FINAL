<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Home extends BaseController
{
    public function index(): RedirectResponse
    {
        $session = session();

        if ($session->get('is_logged_in') === true && (int) $session->get('user_id', 0) > 0) {
            $activeUser = model(UserModel::class)->findActiveById((int) $session->get('user_id'));

            if ($activeUser !== null) {
                $role = (string) $activeUser['role_name'];
                $session->set('user_role', $role);

                return redirect()->to(UserModel::homeRouteForRole($role));
            }

            $session->remove(['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role']);
        }

        return redirect()->to('/auth/login');
    }
}
