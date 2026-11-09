<?php

namespace App\Controllers;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Handles user login and role-based redirection (HU-01).
 */
class AuthController extends BaseController
{
    public const INVALID_CREDENTIALS_MESSAGE = 'Las credenciales ingresadas no son válidas.';
    public const ACCOUNT_DISABLED_MESSAGE    = AuthFilter::ACCOUNT_DISABLED_MESSAGE;

    /**
     * Precomputed bcrypt hash used when no active user exists for the submitted email,
     * keeping response timing uniform against account-enumeration timing probes.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    /**
     * Renders the login form or redirects already-authenticated active users to their panel.
     */
    public function login(): RedirectResponse|string
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

        return view('auth/login');
    }

    /**
     * Validates login credentials, checks soft-delete status, regenerates the session ID,
     * stores only minimal user attributes in session, and redirects according to role.
     */
    public function attemptLogin(): RedirectResponse
    {
        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        // Never persist the submitted password in session old-input flashdata.
        service('superglobals')->unsetPost('password');

        $rules = [
            'email' => [
                'label'  => 'Correo electrónico',
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Ingresá tu correo electrónico.',
                    'valid_email' => 'Ingresá un correo electrónico con formato válido.',
                    'max_length'  => 'El correo electrónico no puede superar los 255 caracteres.',
                ],
            ],
            'password' => [
                'label'  => 'Contraseña',
                'rules'  => 'required|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá tu contraseña.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                ],
            ],
        ];

        if (! $this->validateData(['email' => $email, 'password' => $password], $rules)) {
            return redirect()->to('/auth/login')
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
        }

        $userModel  = model(UserModel::class);
        $activeUser = $userModel->findActiveByEmail($email);

        if ($activeUser !== null) {
            if (! password_verify($password, (string) $activeUser['password_hash'])) {
                return redirect()->to('/auth/login')
                    ->withInput()
                    ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
            }

            $session = session();
            $session->regenerate();

            $role     = (string) $activeUser['role_name'];
            $fullName = trim(((string) $activeUser['first_name']) . ' ' . ((string) $activeUser['last_name']));

            // Store only the minimum required session state (HU-01 business rule).
            $session->set([
                'is_logged_in' => true,
                'user_id'      => (int) $activeUser['id'],
                'user_email'   => (string) $activeUser['email'],
                'user_name'    => $fullName,
                'user_role'    => $role,
            ]);

            return redirect()->to(UserModel::homeRouteForRole($role));
        }

        $deactivatedUser = $userModel->findDeactivatedByEmail($email);

        if ($deactivatedUser !== null) {
            return redirect()->to('/auth/login')
                ->withInput()
                ->with('error', self::ACCOUNT_DISABLED_MESSAGE);
        }

        password_verify($password, self::DUMMY_PASSWORD_HASH);

        return redirect()->to('/auth/login')
            ->withInput()
            ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
    }
}