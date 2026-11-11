<?php

namespace App\Controllers;

use App\Filters\AuthFilter;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Displays and updates the authenticated owner's or tenant's own profile (HU-06).
 */
class ProfileController extends BaseController
{
    public const CONTACT_UPDATED_MESSAGE       = 'Tus datos de contacto se actualizaron correctamente.';
    public const PASSWORD_UPDATED_MESSAGE      = 'Tu contraseña se actualizó correctamente.';
    public const CONTACT_VALIDATION_MESSAGE    = 'Revisá los campos marcados antes de guardar tus datos.';
    public const PASSWORD_VALIDATION_MESSAGE   = 'Revisá los campos marcados antes de cambiar tu contraseña.';
    public const CURRENT_PASSWORD_ERROR       = 'La contraseña actual ingresada no es correcta.';
    public const PASSWORD_REUSE_ERROR         = 'La nueva contraseña debe ser distinta de la contraseña actual.';

    /**
     * Shows only the profile information that the owner or tenant is allowed to see.
     */
    public function show(): RedirectResponse|string
    {
        $user = $this->currentUser();

        if ($user === null) {
            return $this->redirectToLogin();
        }

        return view('profile/show', array_merge($this->panelContext(
            pageTitle: 'Mi perfil',
            breadcrumb: 'Mi perfil',
            activeMenu: 'perfil'
        ), [
            'profile' => [
                'first_name'      => (string) $user['first_name'],
                'last_name'       => (string) $user['last_name'],
                'document_number' => (string) $user['document_number'],
                'email'           => (string) $user['email'],
                'phone'           => $user['phone'],
                'address'         => $user['address'],
                'role_label'      => UserModel::labelForRole((string) $user['role_name']),
            ],
        ]));
    }

    /**
     * Updates only the contact fields belonging to the authenticated user.
     */
    public function updateContact(): RedirectResponse
    {
        $user = $this->currentUser();

        if ($user === null) {
            return $this->redirectToLogin();
        }

        $input = [
            'phone'   => trim((string) $this->request->getPost('phone')),
            'address' => trim((string) $this->request->getPost('address')),
        ];

        service('superglobals')->unsetPost('password');
        service('superglobals')->unsetPost('password_confirm');
        service('superglobals')->unsetPost('current_password');
        service('superglobals')->unsetPost('new_password');

        if (! $this->validateData($input, [
            'phone' => [
                'label'  => 'Teléfono',
                'rules'  => 'permit_empty|max_length[30]|valid_phone',
                'errors' => [
                    'max_length' => 'El teléfono no puede superar los 30 caracteres.',
                    'valid_phone' => 'Ingresá un teléfono válido con entre 6 y 15 dígitos. Podés usar +, espacios, paréntesis, puntos y guiones.',
                ],
            ],
            'address' => [
                'label'  => 'Dirección',
                'rules'  => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede superar los 255 caracteres.',
                ],
            ],
        ])) {
            return redirect()->to('/perfil')
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::CONTACT_VALIDATION_MESSAGE);
        }

        model(UserModel::class)->update((int) $user['id'], [
            'phone'   => $input['phone'] !== '' ? $input['phone'] : null,
            'address' => $input['address'] !== '' ? $input['address'] : null,
        ]);

        return redirect()->to('/perfil')
            ->with('success', self::CONTACT_UPDATED_MESSAGE);
    }

    /**
     * Verifies the current password before storing a new password hash.
     */
    public function changePassword(): RedirectResponse
    {
        $user = $this->currentUser();

        if ($user === null) {
            return $this->redirectToLogin();
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword     = (string) $this->request->getPost('new_password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        service('superglobals')->unsetPost('current_password');
        service('superglobals')->unsetPost('new_password');
        service('superglobals')->unsetPost('password_confirm');

        $validationData = [
            'current_password' => $currentPassword,
            'new_password'     => $newPassword,
            'password_confirm' => $passwordConfirm,
        ];

        $rules = [
            'current_password' => [
                'label'  => 'Contraseña actual',
                'rules'  => 'required|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá tu contraseña actual.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                ],
            ],
            'new_password' => [
                'label'  => 'Nueva contraseña',
                'rules'  => 'required|min_length[8]|max_length[255]|strong_password',
                'errors' => [
                    'required'   => 'Ingresá una nueva contraseña.',
                    'min_length' => 'La nueva contraseña debe tener al menos 8 caracteres.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                    'strong_password' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.',
                ],
            ],
            'password_confirm' => [
                'label'  => 'Repetir nueva contraseña',
                'rules'  => 'required|matches[new_password]',
                'errors' => [
                    'required' => 'Repetí la nueva contraseña.',
                    'matches'  => 'Las contraseñas no coinciden.',
                ],
            ],
        ];

        if (! $this->validateData($validationData, $rules)) {
            return redirect()->to('/perfil')
                ->with('errors', $this->validator->getErrors())
                ->with('error', self::PASSWORD_VALIDATION_MESSAGE);
        }

        if (! password_verify($currentPassword, (string) $user['password_hash'])) {
            return redirect()->to('/perfil')
                ->with('errors', ['current_password' => self::CURRENT_PASSWORD_ERROR])
                ->with('error', self::CURRENT_PASSWORD_ERROR);
        }

        if (hash_equals($currentPassword, $newPassword)) {
            return redirect()->to('/perfil')
                ->with('errors', ['new_password' => self::PASSWORD_REUSE_ERROR])
                ->with('error', self::PASSWORD_REUSE_ERROR);
        }

        model(UserModel::class)->update((int) $user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);
        session()->regenerate();

        return redirect()->to('/perfil')
            ->with('success', self::PASSWORD_UPDATED_MESSAGE);
    }

    /**
     * Resolves the active account using only the authenticated session identity.
     *
     * @return array<string, mixed>|null
     */
    private function currentUser(): ?array
    {
        $userId = (int) session()->get('user_id', 0);

        return $userId > 0 ? model(UserModel::class)->findActiveById($userId) : null;
    }

    private function redirectToLogin(): RedirectResponse
    {
        session()->remove(['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role']);

        return redirect()->to('/auth/login')
            ->with('error', AuthFilter::ACCOUNT_DISABLED_MESSAGE);
    }
}
