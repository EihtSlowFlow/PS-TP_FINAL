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
    public const REGISTRATION_SUCCESS_MESSAGE = 'Tu cuenta fue creada exitosamente. Ya podés iniciar sesión.';
    public const DUPLICATE_EMAIL_MESSAGE      = 'Ya existe una cuenta registrada con este correo electrónico.';
    public const DUPLICATE_DNI_MESSAGE        = 'Ya existe una cuenta registrada con este DNI.';
    public const REGISTRATION_VALIDATION_MESSAGE = 'Revisá los campos marcados antes de continuar.';
    public const LOGOUT_SUCCESS_MESSAGE      = 'La sesión se cerró correctamente.';

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

        // Links to HU-02 / HU-03b screens are shown only once their routes exist (no 404 links meanwhile).
        return view('auth/login', [
            'showRegisterLink' => $this->routeExists('auth/register'),
            'showRecoverLink'  => $this->routeExists('auth/recuperar-password'),
        ]);
    }

    /**
     * Renders the public registration form for owners and tenants (HU-02).
     */
    public function register(): RedirectResponse|string
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

        return view('auth/register');
    }

    /**
     * Validates and creates a public owner or tenant account (HU-02).
     */
    public function storeRegistration(): RedirectResponse
    {
        $rawDni        = trim((string) $this->request->getPost('document_number'));
        $normalizedDni = preg_replace('/[\.\s-]+/', '', $rawDni) ?? $rawDni;
        $input         = [
            'first_name'      => trim((string) $this->request->getPost('first_name')),
            'last_name'       => trim((string) $this->request->getPost('last_name')),
            'document_number' => $normalizedDni,
            'email'           => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'           => trim((string) $this->request->getPost('phone')),
            'role'            => trim((string) $this->request->getPost('role')),
        ];
        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        service('superglobals')->unsetPost('password');
        service('superglobals')->unsetPost('password_confirm');

        $validationData = array_merge($input, [
            'password'         => $password,
            'password_confirm' => $passwordConfirm,
        ]);
        $rules = [
            'first_name' => [
                'label'  => 'Nombre',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Ingresá tu nombre.',
                    'min_length' => 'El nombre debe tener al menos 2 caracteres.',
                    'max_length' => 'El nombre no puede superar los 100 caracteres.',
                ],
            ],
            'last_name' => [
                'label'  => 'Apellido',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Ingresá tu apellido.',
                    'min_length' => 'El apellido debe tener al menos 2 caracteres.',
                    'max_length' => 'El apellido no puede superar los 100 caracteres.',
                ],
            ],
            'document_number' => [
                'label'  => 'DNI',
                'rules'  => 'required|alpha_numeric|min_length[6]|max_length[20]',
                'errors' => [
                    'required'      => 'Ingresá tu DNI.',
                    'alpha_numeric' => 'El DNI solo debe contener letras o números, sin símbolos.',
                    'min_length'    => 'El DNI debe tener al menos 6 caracteres.',
                    'max_length'    => 'El DNI no puede superar los 20 caracteres.',
                ],
            ],
            'email' => [
                'label'  => 'Correo electrónico',
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Ingresá tu correo electrónico.',
                    'valid_email' => 'Ingresá un correo electrónico con formato válido.',
                    'max_length'  => 'El correo electrónico no puede superar los 255 caracteres.',
                ],
            ],
            'phone' => [
                'label'  => 'Teléfono',
                'rules'  => 'required|min_length[6]|max_length[30]',
                'errors' => [
                    'required'   => 'Ingresá tu teléfono de contacto.',
                    'min_length' => 'El teléfono debe tener al menos 6 caracteres.',
                    'max_length' => 'El teléfono no puede superar los 30 caracteres.',
                ],
            ],
            'role' => [
                'label'  => 'Rol',
                'rules'  => 'required|in_list[owner,tenant]',
                'errors' => [
                    'required' => 'Elegí si querés registrarte como propietario o inquilino.',
                    'in_list'  => 'El registro solo está disponible para propietarios e inquilinos.',
                ],
            ],
            'password' => [
                'label'  => 'Contraseña',
                'rules'  => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá una contraseña.',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                ],
            ],
            'password_confirm' => [
                'label'  => 'Repetir contraseña',
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'Repetí tu contraseña.',
                    'matches'  => 'Las contraseñas no coinciden.',
                ],
            ],
        ];

        $errors = [];
        if (! $this->validateData($validationData, $rules)) {
            $errors = $this->validator->getErrors();
        }

        $userModel = model(UserModel::class);
        if ($input['email'] !== '' && $userModel->existsActiveEmail($input['email'])) {
            $errors['email'] = self::DUPLICATE_EMAIL_MESSAGE;
        }
        if ($input['document_number'] !== '' && $userModel->existsActiveDocumentNumber($input['document_number'])) {
            $errors['document_number'] = self::DUPLICATE_DNI_MESSAGE;
        }

        if ($errors !== []) {
            $duplicateError = isset($errors['email']) && $errors['email'] === self::DUPLICATE_EMAIL_MESSAGE
                || isset($errors['document_number']) && $errors['document_number'] === self::DUPLICATE_DNI_MESSAGE;

            return redirect()->to('/auth/register')
                ->withInput()
                ->with('errors', $errors)
                ->with('error', $duplicateError ? implode(' ', array_intersect($errors, [
                    self::DUPLICATE_EMAIL_MESSAGE,
                    self::DUPLICATE_DNI_MESSAGE,
                ])) : self::REGISTRATION_VALIDATION_MESSAGE);
        }

        $roleId = $userModel->findRoleIdByName($input['role']);
        if ($roleId === null) {
            return redirect()->to('/auth/register')
                ->withInput()
                ->with('errors', ['role' => 'El rol seleccionado no está disponible.'])
                ->with('error', self::REGISTRATION_VALIDATION_MESSAGE);
        }

        $userModel->insert([
            'role_id'         => $roleId,
            'first_name'      => $input['first_name'],
            'last_name'       => $input['last_name'],
            'document_number' => $input['document_number'],
            'email'           => $input['email'],
            'phone'           => $input['phone'],
            'address'         => null,
            'password_hash'   => password_hash($password, PASSWORD_BCRYPT),
        ]);

        return redirect()->to('/auth/login')
            ->with('success', self::REGISTRATION_SUCCESS_MESSAGE);
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
            // Disclose the disabled state only to someone who proves knowledge of the password;
            // otherwise answer like any other invalid attempt (no account-state enumeration).
            if (password_verify($password, (string) $deactivatedUser['password_hash'])) {
                return redirect()->to('/auth/login')
                    ->withInput()
                    ->with('error', self::ACCOUNT_DISABLED_MESSAGE);
            }

            return redirect()->to('/auth/login')
                ->withInput()
                ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
        }

        password_verify($password, self::DUMMY_PASSWORD_HASH);

        return redirect()->to('/auth/login')
            ->withInput()
            ->with('error', self::INVALID_CREDENTIALS_MESSAGE);
    }

    /**
     * Ends the current session completely on the server and sends the user back to the login screen (HU-03).
     *
     * It is deliberately not behind the `auth` filter: anyone holding a session, even a stale one (for example
     * of a user deactivated in the meantime), must always be able to leave it.
     */
    public function logout(): RedirectResponse
    {
        $session = session();

        $wasAuthenticated = $session->get('is_logged_in') === true && (int) $session->get('user_id', 0) > 0;

        if (! $wasAuthenticated) {
            // Nothing to close (expired session, double click): just show the login screen.
            return redirect()->to('/auth/login');
        }

        // Drop every value (user data, flash data and temp data) and delete the server-side record of the
        // old session id, issuing a new id for the next request, so no account data is left behind.
        $session->remove(array_keys($_SESSION ?? []));
        $session->regenerate(true);

        return redirect()->to('/auth/login')->with('success', self::LOGOUT_SUCCESS_MESSAGE);
    }
}