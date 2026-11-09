<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Administrative user management controller (HU-04, HU-05):
 * listing, searching, filtering, creation, edition, and logical deactivation of system users.
 */
class UserController extends BaseController
{
    public const USER_CREATED_MESSAGE                = 'El usuario fue creado exitosamente.';
    public const USER_UPDATED_MESSAGE                = 'Los datos del usuario fueron actualizados exitosamente.';
    public const USER_DEACTIVATED_MESSAGE            = 'El usuario fue dado de baja exitosamente.';
    public const USER_NOT_FOUND_MESSAGE              = 'El usuario solicitado no existe.';
    public const USER_ALREADY_DEACTIVATED_MESSAGE    = 'El usuario seleccionado ya se encuentra dado de baja.';
    public const SELF_DEACTIVATION_FORBIDDEN_MESSAGE = 'No podés dar de baja tu propio usuario activo.';
    public const LAST_ADMIN_DEACTIVATION_MESSAGE     = 'No se puede dar de baja al único administrador activo del sistema.';
    public const DUPLICATE_EMAIL_MESSAGE             = 'Ya existe un usuario activo registrado con este correo electrónico.';
    public const DUPLICATE_DNI_MESSAGE               = 'Ya existe un usuario activo registrado con este DNI.';
    public const VALIDATION_ERROR_MESSAGE            = 'Revisá los campos marcados antes de guardar el usuario.';

    /**
     * Renders the paginated user directory with search, role filter, and soft-deleted toggle (HU-04 Scenarios 3 & 5).
     */
    public function index(): string
    {
        $userModel = model(UserModel::class);

        $searchQuery = trim((string) $this->request->getGet('q'));
        $roleFilter  = trim((string) $this->request->getGet('role'));

        if ($roleFilter === 'todos' || ! array_key_exists($roleFilter, UserModel::ROLE_LABELS)) {
            $roleFilter = '';
        }

        $includeDeleted = (string) $this->request->getGet('include_deleted') === '1';
        $page           = max(1, (int) $this->request->getGet('page'));
        $perPage        = (int) $this->request->getGet('per_page');
        $perPage        = $perPage > 0 ? min(50, $perPage) : 10;

        $filters = [
            'q'               => $searchQuery,
            'role'            => $roleFilter,
            'include_deleted' => $includeDeleted,
        ];

        $pagination = $userModel->searchAndPaginate($filters, $perPage, $page);
        $roleCounts = $userModel->getRoleCounts();

        return view('admin/users/index', array_merge($this->buildLayoutContext(
            pageTitle: 'Gestión de usuarios',
            breadcrumb: 'Usuarios',
            activeMenu: 'usuarios'
        ), [
            'users'          => $pagination['items'],
            'pagination'     => $pagination,
            'filters'        => $filters,
            'roleCounts'     => $roleCounts,
            'roleLabels'     => UserModel::ROLE_LABELS,
            'currentAdminId' => (int) session()->get('user_id', 0),
        ]));
    }

    /**
     * Renders the new user registration form for administrators (HU-04 Scenario 1).
     */
    public function create(): string
    {
        return view('admin/users/form', array_merge($this->buildLayoutContext(
            pageTitle: 'Nuevo usuario',
            breadcrumb: 'Usuarios / Nuevo usuario',
            activeMenu: 'usuarios'
        ), [
            'mode'             => 'create',
            'user'             => null,
            'operationalStats' => null,
            'roleLabels'       => UserModel::ROLE_LABELS,
            'roleDescriptions' => UserModel::ROLE_DESCRIPTIONS,
        ]));
    }

    /**
     * Validates and stores a new user with any of the four system roles (HU-04 Scenarios 1 & 2).
     */
    public function store(): RedirectResponse
    {
        $input    = $this->collectFormInput();
        $password = (string) $this->request->getPost('password');

        // Never persist plaintext passwords in session old-input flashdata.
        service('superglobals')->unsetPost('password');

        $validationData  = array_merge($input, ['password' => $password]);
        $validationRules = $this->buildValidationRules(isUpdate: false);

        $fieldErrors = [];
        if (! $this->validateData($validationData, $validationRules)) {
            $fieldErrors = $this->validator->getErrors();
        }

        $userModel       = model(UserModel::class);
        $duplicateErrors = $this->checkActiveUniqueness($userModel, $input['email'], $input['document_number'], null);
        $allErrors       = array_merge($fieldErrors, $duplicateErrors);

        if ($allErrors !== []) {
            $flashError = $duplicateErrors !== []
                ? implode(' ', array_values($duplicateErrors))
                : self::VALIDATION_ERROR_MESSAGE;

            return redirect()->to('/admin/usuarios/nuevo')
                ->withInput()
                ->with('errors', $allErrors)
                ->with('error', $flashError);
        }

        $roleId = $userModel->findRoleIdByName($input['role']);
        if ($roleId === null) {
            return redirect()->to('/admin/usuarios/nuevo')
                ->withInput()
                ->with('errors', ['role' => 'Seleccioná un rol válido.'])
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $userModel->insert([
            'role_id'         => $roleId,
            'first_name'      => $input['first_name'],
            'last_name'       => $input['last_name'],
            'document_number' => $input['document_number'],
            'email'           => $input['email'],
            'phone'           => $input['phone'],
            'address'         => $input['address'] !== '' ? $input['address'] : null,
            'password_hash'   => password_hash($password, PASSWORD_BCRYPT),
        ]);

        return redirect()->to('/admin/usuarios')
            ->with('success', self::USER_CREATED_MESSAGE);
    }

    /**
     * Renders the user edition form, including audit metadata and operational context (HU-04 Scenarios 4 & 5).
     */
    public function edit(int $id): RedirectResponse|string
    {
        $userModel = model(UserModel::class);
        $user      = $userModel->findByIdWithRole($id, true);

        if ($user === null) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::USER_NOT_FOUND_MESSAGE);
        }

        $operationalStats = $userModel->getUserOperationalSummary($id);

        return view('admin/users/form', array_merge($this->buildLayoutContext(
            pageTitle: 'Editar usuario',
            breadcrumb: 'Usuarios / Editar usuario',
            activeMenu: 'usuarios'
        ), [
            'mode'             => 'edit',
            'user'             => $user,
            'operationalStats' => $operationalStats,
            'roleLabels'       => UserModel::ROLE_LABELS,
            'roleDescriptions' => UserModel::ROLE_DESCRIPTIONS,
        ]));
    }

    /**
     * Validates and updates an existing user, keeping the current password hash when password is left blank (HU-04 Scenario 4).
     */
    public function update(int $id): RedirectResponse
    {
        $userModel = model(UserModel::class);
        $existing  = $userModel->findByIdWithRole($id, true);

        if ($existing === null) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::USER_NOT_FOUND_MESSAGE);
        }

        $input    = $this->collectFormInput();
        $password = (string) $this->request->getPost('password');

        // Never persist plaintext passwords in session old-input flashdata.
        service('superglobals')->unsetPost('password');

        $validationData  = array_merge($input, ['password' => $password]);
        $validationRules = $this->buildValidationRules(isUpdate: true);

        $fieldErrors = [];
        if (! $this->validateData($validationData, $validationRules)) {
            $fieldErrors = $this->validator->getErrors();
        }

        $duplicateErrors = [];
        // Uniqueness applies among active users (HU-04 business rule).
        if ($existing['deleted_at'] === null) {
            $duplicateErrors = $this->checkActiveUniqueness($userModel, $input['email'], $input['document_number'], $id);
        }

        $allErrors = array_merge($fieldErrors, $duplicateErrors);

        if ($allErrors !== []) {
            $flashError = $duplicateErrors !== []
                ? implode(' ', array_values($duplicateErrors))
                : self::VALIDATION_ERROR_MESSAGE;

            return redirect()->to('/admin/usuarios/editar/' . $id)
                ->withInput()
                ->with('errors', $allErrors)
                ->with('error', $flashError);
        }

        $roleId = $userModel->findRoleIdByName($input['role']);
        if ($roleId === null) {
            return redirect()->to('/admin/usuarios/editar/' . $id)
                ->withInput()
                ->with('errors', ['role' => 'Seleccioná un rol válido.'])
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $updatePayload = [
            'role_id'         => $roleId,
            'first_name'      => $input['first_name'],
            'last_name'       => $input['last_name'],
            'document_number' => $input['document_number'],
            'email'           => $input['email'],
            'phone'           => $input['phone'],
            'address'         => $input['address'] !== '' ? $input['address'] : null,
        ];

        // Only replace password_hash when a non-empty password was explicitly provided (HU-04 Scenario 4).
        if ($password !== '') {
            $updatePayload['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $userModel->update($id, $updatePayload);

        // If the administrator edited their own active account, keep session attributes synchronized.
        $session = session();
        if ((int) $session->get('user_id', 0) === $id) {
            $session->set([
                'user_email' => $input['email'],
                'user_name'  => trim($input['first_name'] . ' ' . $input['last_name']),
                'user_role'  => $input['role'],
            ]);
        }

        return redirect()->to('/admin/usuarios')
            ->with('success', self::USER_UPDATED_MESSAGE);
    }

    /**
     * Logically deactivates an active user while preserving their record and historical references (HU-05).
     */
    public function deactivate(int $id): RedirectResponse
    {
        $userModel = model(UserModel::class);
        $existing  = $userModel->findByIdWithRole($id, true);

        if ($existing === null) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::USER_NOT_FOUND_MESSAGE);
        }

        if ((int) session()->get('user_id', 0) === $id) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::SELF_DEACTIVATION_FORBIDDEN_MESSAGE);
        }

        if ($existing['deleted_at'] !== null) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::USER_ALREADY_DEACTIVATED_MESSAGE);
        }

        if (
            (string) ($existing['role_name'] ?? '') === UserModel::ROLE_ADMIN
            && $userModel->countActiveByRole(UserModel::ROLE_ADMIN) <= 1
        ) {
            return redirect()->to('/admin/usuarios')
                ->with('error', self::LAST_ADMIN_DEACTIVATION_MESSAGE);
        }

        $userModel->delete($id);

        return redirect()->to('/admin/usuarios')
            ->with('success', self::USER_DEACTIVATED_MESSAGE);
    }

    /**
     * Collects and normalizes user form fields from the POST request.
     *
     * @return array{
     *     first_name: string,
     *     last_name: string,
     *     document_number: string,
     *     email: string,
     *     phone: string,
     *     address: string,
     *     role: string
     * }
     */
    private function collectFormInput(): array
    {
        $rawDni        = trim((string) $this->request->getPost('document_number'));
        $normalizedDni = preg_replace('/[\.\s-]+/', '', $rawDni) ?? $rawDni;

        return [
            'first_name'      => trim((string) $this->request->getPost('first_name')),
            'last_name'       => trim((string) $this->request->getPost('last_name')),
            'document_number' => $normalizedDni,
            'email'           => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'           => trim((string) $this->request->getPost('phone')),
            'address'         => trim((string) $this->request->getPost('address')),
            'role'            => trim((string) $this->request->getPost('role')),
        ];
    }

    /**
     * Builds server-side validation rules for creating or updating a user.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildValidationRules(bool $isUpdate): array
    {
        $allowedRoles = implode(',', array_keys(UserModel::ROLE_LABELS));

        $rules = [
            'first_name' => [
                'label'  => 'Nombre',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Ingresá el nombre del usuario.',
                    'min_length' => 'El nombre debe tener al menos 2 caracteres.',
                    'max_length' => 'El nombre no puede superar los 100 caracteres.',
                ],
            ],
            'last_name' => [
                'label'  => 'Apellido',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Ingresá el apellido del usuario.',
                    'min_length' => 'El apellido debe tener al menos 2 caracteres.',
                    'max_length' => 'El apellido no puede superar los 100 caracteres.',
                ],
            ],
            'document_number' => [
                'label'  => 'DNI',
                'rules'  => 'required|alpha_numeric|min_length[6]|max_length[20]',
                'errors' => [
                    'required'      => 'Ingresá el DNI del usuario.',
                    'alpha_numeric' => 'El DNI solo debe contener letras o números, sin símbolos.',
                    'min_length'    => 'El DNI debe tener al menos 6 caracteres.',
                    'max_length'    => 'El DNI no puede superar los 20 caracteres.',
                ],
            ],
            'email' => [
                'label'  => 'Correo electrónico',
                'rules'  => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required'    => 'Ingresá el correo electrónico.',
                    'valid_email' => 'Ingresá un correo electrónico con formato válido.',
                    'max_length'  => 'El correo electrónico no puede superar los 255 caracteres.',
                ],
            ],
            'phone' => [
                'label'  => 'Teléfono',
                'rules'  => 'required|min_length[6]|max_length[30]',
                'errors' => [
                    'required'   => 'Ingresá el teléfono de contacto.',
                    'min_length' => 'El teléfono debe tener al menos 6 caracteres.',
                    'max_length' => 'El teléfono no puede superar los 30 caracteres.',
                ],
            ],
            'address' => [
                'label'  => 'Dirección',
                'rules'  => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede superar los 255 caracteres.',
                ],
            ],
            'role' => [
                'label'  => 'Rol',
                'rules'  => "required|in_list[{$allowedRoles}]",
                'errors' => [
                    'required' => 'Seleccioná un rol para el usuario.',
                    'in_list'  => 'El rol seleccionado no es válido.',
                ],
            ],
        ];

        if ($isUpdate) {
            $rules['password'] = [
                'label'  => 'Contraseña',
                'rules'  => 'permit_empty|min_length[8]|max_length[255]',
                'errors' => [
                    'min_length' => 'La nueva contraseña debe tener al menos 8 caracteres.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                ],
            ];
        } else {
            $rules['password'] = [
                'label'  => 'Contraseña',
                'rules'  => 'required|min_length[8]|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá una contraseña inicial.',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
                    'max_length' => 'La contraseña supera la longitud permitida.',
                ],
            ];
        }

        return $rules;
    }

    /**
     * Checks email and document_number uniqueness across active users (HU-04 Scenario 2).
     *
     * @return array<string, string>
     */
    private function checkActiveUniqueness(
        UserModel $userModel,
        string $email,
        string $documentNumber,
        ?int $excludeUserId
    ): array {
        $errors = [];

        if ($email !== '' && $userModel->existsActiveEmail($email, $excludeUserId)) {
            $errors['email'] = self::DUPLICATE_EMAIL_MESSAGE;
        }

        if ($documentNumber !== '' && $userModel->existsActiveDocumentNumber($documentNumber, $excludeUserId)) {
            $errors['document_number'] = self::DUPLICATE_DNI_MESSAGE;
        }

        return $errors;
    }

    /**
     * Builds the shared sidebar/topbar context for administrative views.
     *
     * @return array<string, string>
     */
    private function buildLayoutContext(string $pageTitle, string $breadcrumb, string $activeMenu): array
    {
        $session  = session();
        $userRole = (string) $session->get('user_role', UserModel::ROLE_ADMIN);
        $userName = (string) $session->get('user_name', 'Administrador');

        $parts = preg_split('/\s+/', trim($userName)) ?: [];
        $first = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'A';
        $last  = isset($parts[1][0]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';

        return [
            'pageTitle'  => $pageTitle,
            'breadcrumb' => $breadcrumb,
            'activeMenu' => $activeMenu,
            'userName'   => $userName,
            'userEmail'  => (string) $session->get('user_email', ''),
            'userRole'   => $userRole,
            'roleLabel'  => UserModel::labelForRole($userRole),
            'initials'   => $first . $last,
        ];
    }
}

