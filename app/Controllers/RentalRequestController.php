<?php

namespace App\Controllers;

use App\Filters\AuthFilter;
use App\Models\RentalRequestModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;

/**
 * Tenant rental request portal (HU-30): applying for an available property, tracking the status of submitted requests
 * in "Mis solicitudes" and cancelling a pending request.
 *
 * The tenant identity always comes from the session (`tenant_id` is never read from the request body), and any attempt
 * to access or cancel another tenant's request answers 404 without revealing its existence.
 */
class RentalRequestController extends BaseController
{
    public const REQUEST_CREATED_MESSAGE      = 'Tu solicitud de alquiler fue registrada en estado «Pendiente». La administración la revisará y podrás seguir su estado desde aquí.';
    public const REQUEST_CANCELLED_MESSAGE    = 'Cancelaste tu solicitud de alquiler.';
    public const PROPERTY_UNAVAILABLE_MESSAGE = 'El inmueble seleccionado ya no está disponible para solicitar su alquiler.';
    public const DUPLICATE_PENDING_MESSAGE    = 'Ya tenés una solicitud pendiente para este inmueble.';
    public const VALIDATION_ERROR_MESSAGE     = 'Revisá los campos marcados antes de enviar tu solicitud.';
    public const SUBMISSION_ERROR_MESSAGE     = 'No pudimos registrar la solicitud por un error interno. Intentá nuevamente.';
    public const CANCEL_NOT_PENDING_MESSAGE   = 'Solo podés cancelar solicitudes que estén en estado «Pendiente».';
    public const CANCEL_ERROR_MESSAGE         = 'No pudimos cancelar la solicitud por un error interno. Intentá nuevamente.';

    // HU-31: administration tray, file and rejection of tenant rental requests.
    public const REQUEST_NOT_FOUND_MESSAGE         = 'La solicitud de alquiler indicada no existe.';
    public const ALREADY_RESOLVED_MESSAGE          = 'Solo se pueden resolver solicitudes en estado «Pendiente».';
    public const REJECTION_REASON_REQUIRED_MESSAGE = 'Ingresá el motivo del rechazo.';
    public const REJECTION_REASON_TOO_LONG_MESSAGE = 'El motivo del rechazo no puede superar los 255 caracteres.';
    public const REQUEST_REJECTED_MESSAGE          = 'La solicitud fue rechazada. El inquilino verá el motivo en «Mis solicitudes».';
    public const REJECTION_ERROR_MESSAGE           = 'No pudimos rechazar la solicitud por un error interno. Intentá nuevamente.';

    /**
     * Renders the administrative tray of rental requests with pending requests first, status filter and search
     * by tenant or property (HU-31 Scenario 1).
     */
    public function adminIndex(): string
    {
        $requestModel = model(RentalRequestModel::class);
        $filterData   = $this->adminFilters();
        $pageData     = $this->adminPage();
        $pagination   = $requestModel->paginateForAdmin(
            $pageData['perPage'],
            $pageData['page'],
            $filterData['filters']
        );

        return view('admin/rental_requests/index', array_merge(
            $this->panelContext(
                pageTitle: 'Solicitudes de alquiler',
                breadcrumb: 'Solicitudes',
                activeMenu: 'solicitudes'
            ),
            [
                'requests'      => $pagination['items'],
                'pagination'    => $pagination,
                'statusCounts'  => $requestModel->getStatusCounts(),
                'filters'       => $filterData['values'],
                'filterErrors'  => $filterData['errors'],
                'statusOptions' => RentalRequestModel::STATUS_LABELS,
            ]
        ));
    }

    /**
     * Renders the administrative file of a single rental request with tenant, property, owner, message and competing
     * pending requests on the same property (HU-31 Scenario 2).
     */
    public function adminShow(int $requestId): RedirectResponse|string
    {
        $requestModel  = model(RentalRequestModel::class);
        $rentalRequest = $requestModel->findForAdmin($requestId);

        if ($rentalRequest === null) {
            return redirect()->to('/admin/solicitudes')
                ->with('error', self::REQUEST_NOT_FOUND_MESSAGE);
        }

        $otherPending = $requestModel->findOtherPendingForProperty(
            (int) $rentalRequest['property_id'],
            $requestId
        );

        return view('admin/rental_requests/show', array_merge(
            $this->panelContext(
                pageTitle: 'Solicitud #' . $requestId,
                breadcrumb: 'Solicitudes / Solicitud #' . $requestId,
                activeMenu: 'solicitudes'
            ),
            [
                'rentalRequest' => $rentalRequest,
                'otherPending'  => $otherPending,
            ]
        ));
    }

    /**
     * Rejects a pending rental request with a mandatory reason of up to 255 characters, recording the reviewer and
     * timestamp (HU-31 Scenario 5).
     */
    public function adminReject(int $requestId): RedirectResponse
    {
        $requestModel  = model(RentalRequestModel::class);
        $rentalRequest = $requestModel->findForAdmin($requestId);

        if ($rentalRequest === null) {
            return redirect()->to('/admin/solicitudes')
                ->with('error', self::REQUEST_NOT_FOUND_MESSAGE);
        }

        if ((string) $rentalRequest['status'] !== RentalRequestModel::STATUS_PENDING) {
            return redirect()->to('/admin/solicitudes')
                ->with('error', self::ALREADY_RESOLVED_MESSAGE);
        }

        $reason = trim((string) $this->request->getPost('rejection_reason'));

        if ($reason === '' || mb_strlen($reason) > RentalRequestModel::REJECTION_REASON_MAX_LENGTH) {
            $errorMessage = $reason === ''
                ? self::REJECTION_REASON_REQUIRED_MESSAGE
                : self::REJECTION_REASON_TOO_LONG_MESSAGE;

            return redirect()->to('/admin/solicitudes/' . $requestId)
                ->withInput()
                ->with('errors', ['rejection_reason' => $errorMessage])
                ->with('error', $errorMessage);
        }

        $reviewerId = (int) session()->get('user_id', 0);
        $result     = $requestModel->rejectByAdmin($requestId, $reviewerId, $reason);

        if (! $result['ok']) {
            return match ($result['error']) {
                RentalRequestModel::ERROR_NOT_FOUND        => redirect()->to('/admin/solicitudes')
                    ->with('error', self::REQUEST_NOT_FOUND_MESSAGE),
                RentalRequestModel::ERROR_ALREADY_RESOLVED => redirect()->to('/admin/solicitudes')
                    ->with('error', self::ALREADY_RESOLVED_MESSAGE),
                default                                    => redirect()->to('/admin/solicitudes/' . $requestId)
                    ->withInput()
                    ->with('error', self::REJECTION_ERROR_MESSAGE),
            };
        }

        return redirect()->to('/admin/solicitudes/' . $requestId)
            ->with('success', self::REQUEST_REJECTED_MESSAGE);
    }

    /**
     * Lists all rental requests submitted by the authenticated tenant (HU-30 Scenario 5).
     */
    public function index(): RedirectResponse|string
    {
        $tenant = $this->currentActiveTenant();

        if ($tenant === null) {
            return $this->redirectToLogin();
        }

        $tenantId = (int) $tenant['id'];
        $requests = model(RentalRequestModel::class)->findForTenant($tenantId);

        return view('tenant/rental_requests/index', array_merge(
            $this->panelContext(
                pageTitle: 'Mis solicitudes',
                breadcrumb: 'Mis solicitudes',
                activeMenu: 'inquilino-solicitudes'
            ),
            [
                'requests' => $requests,
            ]
        ));
    }

    /**
     * Shows the rental request form for an available and approved property, prefilling the contact phone from the
     * tenant's profile (HU-30 Scenarios 1 and 4).
     */
    public function create(int $propertyId): RedirectResponse|string
    {
        $tenant = $this->currentActiveTenant();

        if ($tenant === null) {
            return $this->redirectToLogin();
        }

        $tenantId     = (int) $tenant['id'];
        $requestModel = model(RentalRequestModel::class);
        $property     = $requestModel->findRequestableProperty($propertyId);

        if ($property === null) {
            return redirect()->to('/catalogo-inmuebles')
                ->with('error', self::PROPERTY_UNAVAILABLE_MESSAGE);
        }

        $today             = RentalRequestModel::today();
        $hasPendingRequest = $requestModel->hasPendingForTenantAndProperty($tenantId, $propertyId);

        return view('tenant/rental_requests/form', array_merge(
            $this->panelContext(
                pageTitle: 'Solicitar alquiler',
                breadcrumb: 'Mis solicitudes / Solicitar alquiler',
                activeMenu: 'inquilino-solicitudes'
            ),
            [
                'property'          => $property,
                'today'             => $today,
                'defaultPhone'      => (string) ($tenant['phone'] ?? ''),
                'hasPendingRequest' => $hasPendingRequest,
            ]
        ));
    }

    /**
     * Validates the desired terms and contact details on the server and registers a pending rental request on an
     * available property (HU-30 Scenarios 1, 2, 3 and 4).
     */
    public function store(int $propertyId): RedirectResponse
    {
        $tenant = $this->currentActiveTenant();

        if ($tenant === null) {
            return $this->redirectToLogin();
        }

        $tenantId     = (int) $tenant['id'];
        $requestModel = model(RentalRequestModel::class);
        $property     = $requestModel->findRequestableProperty($propertyId);

        if ($property === null) {
            return redirect()->to('/catalogo-inmuebles')
                ->with('error', self::PROPERTY_UNAVAILABLE_MESSAGE);
        }

        if ($requestModel->hasPendingForTenantAndProperty($tenantId, $propertyId)) {
            return redirect()->to('/inquilino/solicitudes')
                ->with('error', self::DUPLICATE_PENDING_MESSAGE);
        }

        $input  = $this->collectInput();
        $today  = RentalRequestModel::today();
        $errors = $this->validateInput($input, $today);

        if ($errors !== []) {
            return redirect()->to('/inquilino/inmuebles/' . $propertyId . '/solicitar')
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $result = $requestModel->createRequest($tenantId, $propertyId, $input);

        if (! $result['ok']) {
            return match ($result['error']) {
                RentalRequestModel::ERROR_PROPERTY_UNAVAILABLE => redirect()->to('/catalogo-inmuebles')
                    ->with('error', self::PROPERTY_UNAVAILABLE_MESSAGE),
                RentalRequestModel::ERROR_DUPLICATE_PENDING => redirect()->to('/inquilino/solicitudes')
                    ->with('error', self::DUPLICATE_PENDING_MESSAGE),
                RentalRequestModel::ERROR_INACTIVE_TENANT => $this->redirectToLogin(),
                default                                   => redirect()->to('/inquilino/inmuebles/' . $propertyId . '/solicitar')
                    ->withInput()
                    ->with('error', self::SUBMISSION_ERROR_MESSAGE),
            };
        }

        return redirect()->to('/inquilino/solicitudes')
            ->with('success', self::REQUEST_CREATED_MESSAGE);
    }

    /**
     * Cancels a pending rental request owned by the tenant after an explicit server-side confirmation step
     * (HU-30 Scenario 6). Another tenant's or a missing request answers 404.
     */
    public function cancel(int $requestId): RedirectResponse|string
    {
        $tenant = $this->currentActiveTenant();

        if ($tenant === null) {
            return $this->redirectToLogin();
        }

        $tenantId      = (int) $tenant['id'];
        $requestModel  = model(RentalRequestModel::class);
        $rentalRequest = $requestModel->findOneForTenant($requestId, $tenantId);

        if ($rentalRequest === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ((string) $rentalRequest['status'] !== RentalRequestModel::STATUS_PENDING) {
            return redirect()->to('/inquilino/solicitudes')
                ->with('error', self::CANCEL_NOT_PENDING_MESSAGE);
        }

        if ((string) $this->request->getPost('confirmed') !== '1') {
            return view('tenant/rental_requests/cancel_confirm', array_merge(
                $this->panelContext(
                    pageTitle: 'Cancelar solicitud',
                    breadcrumb: 'Mis solicitudes / Cancelar solicitud',
                    activeMenu: 'inquilino-solicitudes'
                ),
                [
                    'rentalRequest' => $rentalRequest,
                ]
            ));
        }

        $result = $requestModel->cancelByTenant($requestId, $tenantId);

        if (! $result['ok']) {
            return match ($result['error']) {
                RentalRequestModel::ERROR_NOT_FOUND   => throw PageNotFoundException::forPageNotFound(),
                RentalRequestModel::ERROR_NOT_PENDING => redirect()->to('/inquilino/solicitudes')
                    ->with('error', self::CANCEL_NOT_PENDING_MESSAGE),
                default                               => redirect()->to('/inquilino/solicitudes')
                    ->with('error', self::CANCEL_ERROR_MESSAGE),
            };
        }

        return redirect()->to('/inquilino/solicitudes')
            ->with('success', self::REQUEST_CANCELLED_MESSAGE);
    }

    /**
     * Collects and trims the fields submitted by the tenant. `tenant_id`, `property_id` and `status` are never read
     * from the POST payload.
     *
     * @return array{
     *     desired_start_date: string,
     *     desired_months: string,
     *     contact_phone: string,
     *     message: string
     * }
     */
    private function collectInput(): array
    {
        return [
            'desired_start_date' => trim((string) $this->request->getPost('desired_start_date')),
            'desired_months'     => trim((string) $this->request->getPost('desired_months')),
            'contact_phone'      => trim((string) $this->request->getPost('contact_phone')),
            'message'            => trim((string) $this->request->getPost('message')),
        ];
    }

    /**
     * Validates the rental request fields on the server and returns an error message per invalid field (HU-30 Scenario 2).
     *
     * @param array<string, string> $input
     *
     * @return array<string, string>
     */
    private function validateInput(array $input, string $today): array
    {
        $maxMonths = RentalRequestModel::DESIRED_MONTHS_MAX;
        $maxPhone  = RentalRequestModel::CONTACT_PHONE_MAX_LENGTH;
        $maxMsg    = RentalRequestModel::MESSAGE_MAX_LENGTH;

        $rules = [
            'desired_months' => [
                'label'  => 'Plazo deseado',
                'rules'  => "required|is_natural_no_zero|less_than_equal_to[{$maxMonths}]",
                'errors' => [
                    'required'            => 'Ingresá el plazo deseado en meses.',
                    'is_natural_no_zero'  => 'El plazo deseado debe ser un número entero mayor a cero.',
                    'less_than_equal_to'  => "El plazo deseado no puede superar los {$maxMonths} meses.",
                ],
            ],
            'contact_phone' => [
                'label'  => 'Teléfono de contacto',
                'rules'  => "required|max_length[{$maxPhone}]|valid_phone",
                'errors' => [
                    'required'    => 'Ingresá un teléfono de contacto.',
                    'max_length'  => "El teléfono no puede superar los {$maxPhone} caracteres.",
                    'valid_phone' => 'Ingresá un teléfono válido con entre 6 y 15 dígitos. Podés usar +, espacios, paréntesis, puntos y guiones.',
                ],
            ],
            'message' => [
                'label'  => 'Mensaje',
                'rules'  => "permit_empty|max_length[{$maxMsg}]",
                'errors' => [
                    'max_length' => "El mensaje no puede superar los {$maxMsg} caracteres.",
                ],
            ],
        ];

        $errors = [];

        if ($input['desired_start_date'] === '') {
            $errors['desired_start_date'] = 'Ingresá la fecha de inicio deseada.';
        } else {
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $input['desired_start_date']);

            if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $input['desired_start_date']) {
                $errors['desired_start_date'] = 'La fecha de inicio deseada no es válida.';
            } elseif ($input['desired_start_date'] < $today) {
                $errors['desired_start_date'] = 'La fecha de inicio deseada no puede ser anterior a hoy.';
            }
        }

        if (! $this->validateData($input, $rules)) {
            $errors = array_merge($errors, $this->validator->getErrors());
        }

        return $errors;
    }

    /**
     * Resolves the authenticated active tenant from the session.
     *
     * @return array<string, mixed>|null
     */
    private function currentActiveTenant(): ?array
    {
        $userId = (int) session()->get('user_id', 0);

        if ($userId <= 0) {
            return null;
        }

        $user = model(UserModel::class)->findActiveById($userId);

        if ($user === null || (string) $user['role_name'] !== UserModel::ROLE_TENANT) {
            return null;
        }

        return $user;
    }

    private function redirectToLogin(): RedirectResponse
    {
        session()->remove(['is_logged_in', 'user_id', 'user_email', 'user_name', 'user_role']);

        return redirect()->to('/auth/login')
            ->with('error', AuthFilter::ACCOUNT_DISABLED_MESSAGE);
    }

    /**
     * Collects and validates the search and status filters of the administrative tray (HU-31 Scenario 1).
     *
     * @return array{
     *     filters: array{q?: string, status?: string},
     *     values: array{q: string, status: string},
     *     errors: array<string, string>
     * }
     */
    private function adminFilters(): array
    {
        $queryRaw  = $this->request->getGet('q');
        $statusRaw = $this->request->getGet('status');
        $query     = is_string($queryRaw) || is_numeric($queryRaw) ? trim((string) $queryRaw) : '';
        $status    = is_string($statusRaw) || is_numeric($statusRaw) ? trim((string) $statusRaw) : '';
        $filters   = [];
        $errors    = [];

        if ($queryRaw !== null && ! is_string($queryRaw) && ! is_numeric($queryRaw)) {
            $errors['q'] = 'Ingresá un término de búsqueda válido.';
        } elseif ($query !== '' && mb_strlen($query) > 100) {
            $errors['q'] = 'La búsqueda no puede superar los 100 caracteres.';
        } elseif ($query !== '') {
            $filters['q'] = $query;
        }

        if ($statusRaw !== null && ! is_string($statusRaw) && ! is_numeric($statusRaw)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        } elseif ($status !== '') {
            if (! array_key_exists($status, RentalRequestModel::STATUS_LABELS)) {
                $errors['status'] = 'Seleccioná un estado válido.';
            } else {
                $filters['status'] = $status;
            }
        }

        return [
            'filters' => $filters,
            'values'  => ['q' => $query, 'status' => $status],
            'errors'  => $errors,
        ];
    }

    /**
     * @return array{page: int, perPage: int}
     */
    private function adminPage(): array
    {
        $pageRaw    = $this->request->getGet('page');
        $perPageRaw = $this->request->getGet('per_page');
        $page       = is_scalar($pageRaw) && ctype_digit((string) $pageRaw) ? max(1, (int) $pageRaw) : 1;
        $perPage    = is_scalar($perPageRaw) && ctype_digit((string) $perPageRaw) ? (int) $perPageRaw : 10;
        $perPage    = $perPage > 0 ? min(50, $perPage) : 10;

        return ['page' => $page, 'perPage' => $perPage];
    }
}

