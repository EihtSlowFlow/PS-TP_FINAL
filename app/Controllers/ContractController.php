<?php

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Lease contract management for administrators and employees, plus owner-scoped consultation (HU-10/11).
 * Closing a contract belongs to HU-12.
 */
class ContractController extends BaseController
{
    public const CONTRACT_CREATED_MESSAGE         = 'El contrato fue registrado exitosamente. El inmueble pasó a estado «Alquilado».';
    public const CONTRACT_NOT_FOUND_MESSAGE       = 'El contrato solicitado no existe.';
    public const OWNER_CONTRACT_NOT_FOUND_MESSAGE = 'El contrato solicitado no existe o no está disponible para tu cuenta.';
    public const VALIDATION_ERROR_MESSAGE         = 'Revisá los campos marcados antes de guardar el contrato.';
    public const PROPERTY_UNAVAILABLE_MESSAGE     = 'El inmueble seleccionado no está disponible: ya tiene un contrato vigente, está en mantenimiento o fue dado de baja.';
    public const INVALID_PROPERTY_MESSAGE         = 'Seleccioná el inmueble del contrato.';
    public const INACTIVE_OWNER_MESSAGE           = 'El propietario de este inmueble está dado de baja. Asignale un propietario activo desde la edición del inmueble antes de crear el contrato.';
    public const INVALID_TENANT_MESSAGE           = 'Seleccioná un inquilino activo. El usuario elegido no existe, no es inquilino o está dado de baja.';
    public const INVALID_DATE_ORDER_MESSAGE       = 'La fecha de finalización debe ser posterior a la fecha de inicio.';
    public const SAVE_ERROR_MESSAGE               = 'No pudimos registrar el contrato por un error interno. No se guardó ningún cambio: el inmueble sigue como estaba. Intentá nuevamente.';
    public const CONTRACT_FINISHED_MESSAGE        = 'El contrato fue finalizado correctamente y el inmueble volvió a estar disponible.';
    public const CONTRACT_RESCINDED_MESSAGE      = 'El contrato fue rescindido correctamente y el inmueble volvió a estar disponible.';
    public const CONTRACT_ALREADY_CLOSED_MESSAGE  = 'El contrato no está vigente y no puede cerrarse nuevamente.';
    public const INVALID_CLOSE_STATUS_MESSAGE     = 'Seleccioná si se finaliza o se rescinde el contrato.';
    public const CLOSURE_ERROR_MESSAGE            = 'No pudimos cerrar el contrato por un error interno. No se guardó ningún cambio.';

    /**
     * Lists all contracts for administrators and employees with optional search and status filters.
     */
    public function index(): string
    {
        $listing = $this->contractListingData();

        return view('contracts/index', array_merge($this->panelContext(
            pageTitle: 'Contratos',
            breadcrumb: 'Contratos',
            activeMenu: 'contratos'
        ), $listing, [
            'isOwner'        => false,
            'listingBaseUrl' => 'admin/contratos',
        ]));
    }

    /**
     * Lists only contracts owned by the authenticated owner.
     */
    public function ownerIndex(): string
    {
        $listing = $this->contractListingData((int) session()->get('user_id'));

        return view('contracts/index', array_merge($this->panelContext(
            pageTitle: 'Mis contratos',
            breadcrumb: 'Mis contratos',
            activeMenu: 'propietario-contratos'
        ), $listing, [
            'isOwner'        => true,
            'listingBaseUrl' => 'propietario/contratos',
        ]));
    }

    /**
     * Renders the new contract form (screen ADM-07). `?property_id=` preselects a property.
     */
    public function create(): string
    {
        return $this->renderForm();
    }

    /**
     * Validates the data and registers a contract "in force", setting the property to "rented" atomically
     * (HU-10 Scenarios 1 to 4).
     */
    public function store(): RedirectResponse
    {
        $input  = $this->collectInput();
        $errors = $this->validateInput($input);

        if ($errors !== []) {
            return $this->backToForm($errors, $this->headlineFor($errors));
        }

        $result = model(ContractModel::class)->createAndRentProperty($this->buildPayload($input));

        if ($result['error'] === ContractModel::ERROR_PROPERTY_UNAVAILABLE) {
            return $this->backToForm(['property_id' => self::PROPERTY_UNAVAILABLE_MESSAGE], self::PROPERTY_UNAVAILABLE_MESSAGE);
        }

        if ($result['error'] !== null || $result['id'] === null) {
            return redirect()->to('/admin/contratos/nuevo')
                ->withInput()
                ->with('error', self::SAVE_ERROR_MESSAGE);
        }

        return redirect()->to('/admin/contratos/' . $result['id'])
            ->with('success', self::CONTRACT_CREATED_MESSAGE);
    }

    /**
     * Renders the contract file (screen ADM-08) with the data of the contract, the property, the tenant and the
     * owner. Deactivated users are shown too: the history stays consultable.
     */
    public function show(int $id): RedirectResponse|string
    {
        $contract = model(ContractModel::class)->findWithDetails($id);

        if ($contract === null) {
            return redirect()->to('/admin/contratos')
                ->with('error', self::CONTRACT_NOT_FOUND_MESSAGE);
        }

        return $this->renderContractFile($contract, $id, false);
    }

    /**
     * Renders the file only when the contract belongs to the authenticated owner.
     */
    public function ownerShow(int $id): RedirectResponse|string
    {
        $contract = model(ContractModel::class)->findWithDetails($id, (int) session()->get('user_id'));

        if ($contract === null) {
            return redirect()->to('/propietario/contratos')
                ->with('error', self::OWNER_CONTRACT_NOT_FOUND_MESSAGE);
        }

        return $this->renderContractFile($contract, $id, true);
    }

    /**
     * Confirms or executes a contract closure for the administrative screens.
     */
    public function close(int $id): RedirectResponse|string
    {
        return $this->closeContractStatus($id, trim((string) $this->request->getPost('status')));
    }

    /**
     * Finalizes a contract in force.
     */
    public function finalize(int $id): RedirectResponse|string
    {
        return $this->closeContractStatus($id, ContractModel::STATUS_FINISHED);
    }

    /**
     * Rescinds a contract in force.
     */
    public function rescind(int $id): RedirectResponse|string
    {
        return $this->closeContractStatus($id, ContractModel::STATUS_RESCINDED);
    }

    /**
     * Executes or confirms a contract closure for the selected status.
     */
    private function closeContractStatus(int $id, string $requestedStatus): RedirectResponse|string
    {
        $contract = model(ContractModel::class)->findWithDetails($id);

        if ($contract === null) {
            return redirect()->to('/admin/contratos')
                ->with('error', self::CONTRACT_NOT_FOUND_MESSAGE);
        }

        $requestedStatus = trim($requestedStatus);
        if (in_array($requestedStatus, [ContractModel::STATUS_FINISHED, ContractModel::STATUS_RESCINDED], true) === false) {
            return redirect()->to('/admin/contratos/' . $id)
                ->with('error', self::INVALID_CLOSE_STATUS_MESSAGE);
        }

        if ((string) $this->request->getPost('confirmed') !== '1') {
            return view('admin/contracts/close_confirm', array_merge($this->panelContext(
                pageTitle: 'Confirmar cierre de contrato',
                breadcrumb: 'Contratos / Cierre del contrato',
                activeMenu: 'contratos'
            ), [
                'contract' => $contract,
                'status'   => $requestedStatus,
            ]));
        }

        $result = model(ContractModel::class)->closeContract($id, $requestedStatus, (string) $this->request->getPost('closing_reason'));

        if ($result['error'] === ContractModel::ERROR_NOT_IN_FORCE) {
            return redirect()->to('/admin/contratos/' . $id)
                ->with('error', self::CONTRACT_ALREADY_CLOSED_MESSAGE);
        }

        if ($result['error'] !== null) {
            return redirect()->to('/admin/contratos/' . $id)
                ->with('error', self::CLOSURE_ERROR_MESSAGE);
        }

        return redirect()->to('/admin/contratos/' . $id)
            ->with('success', $requestedStatus === ContractModel::STATUS_FINISHED
                ? self::CONTRACT_FINISHED_MESSAGE
                : self::CONTRACT_RESCINDED_MESSAGE);
    }

    /**
     * Builds the shared contract file for the administrator and owner portals.
     *
     * @param array<string, mixed> $contract
     */
    private function renderContractFile(array $contract, int $id, bool $isOwner): string
    {
        return view('admin/contracts/show', array_merge($this->panelContext(
            pageTitle: 'Contrato #' . $id,
            breadcrumb: ($isOwner ? 'Mis contratos' : 'Contratos') . ' / Contrato #' . $id,
            activeMenu: $isOwner ? 'propietario-contratos' : 'contratos'
        ), [
            'contract' => $contract,
            'isOwner'  => $isOwner,
        ]));
    }

    /**
     * Returns validated filters and paginated records for either portal.
     *
     * @return array{
     *     contracts: list<array<string, mixed>>,
     *     pagination: array{items: list<array<string, mixed>>, total: int, perPage: int, currentPage: int, totalPages: int, from: int, to: int},
     *     filters: array{q: string, status: string},
     *     filterErrors: array<string, string>
     * }
     */
    private function contractListingData(?int $ownerId = null): array
    {
        $queryRaw  = $this->request->getGet('q');
        $statusRaw = $this->request->getGet('status');
        $query     = is_string($queryRaw) ? trim($queryRaw) : '';
        $status    = is_string($statusRaw) ? trim($statusRaw) : '';
        $errors    = [];
        $filters   = [];

        if ($queryRaw !== null && ! is_string($queryRaw) && ! is_numeric($queryRaw)) {
            $errors['q'] = 'Ingresá un término de búsqueda válido.';
        }

        if ($query !== '' && mb_strlen($query) > 100) {
            $errors['q'] = 'La búsqueda no puede superar los 100 caracteres.';
        } elseif ($query !== '') {
            $filters['q'] = $query;
        }

        if ($statusRaw !== null && ! is_string($statusRaw) && ! is_numeric($statusRaw)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        }

        if ($status !== '') {
            if (! array_key_exists($status, ContractModel::STATUS_LABELS)) {
                $errors['status'] = 'Seleccioná un estado válido.';
            } else {
                $filters['status'] = $status;
            }
        }

        $pageRaw    = $this->request->getGet('page');
        $perPageRaw = $this->request->getGet('per_page');
        $page       = is_scalar($pageRaw) && ctype_digit((string) $pageRaw) ? max(1, (int) $pageRaw) : 1;
        $perPage    = is_scalar($perPageRaw) && ctype_digit((string) $perPageRaw) ? (int) $perPageRaw : 10;
        $perPage    = $perPage > 0 ? min(50, $perPage) : 10;

        $pagination = model(ContractModel::class)->paginateWithDetails($perPage, $page, $filters, $ownerId);

        return [
            'contracts'    => $pagination['items'],
            'pagination'   => $pagination,
            'filters'      => ['q' => $query, 'status' => $status],
            'filterErrors' => $errors,
        ];
    }

    /**
     * Renders the form with the properties and tenants that can be selected.
     */
    private function renderForm(): string
    {
        $properties = [];

        foreach (model(PropertyModel::class)->findAvailableForContract() as $property) {
            $properties[] = [
                'id'          => (int) $property['id'],
                'label'       => $property['address'] . ', ' . $property['city']
                    . ' · ' . PropertyModel::labelForType((string) $property['type']),
                'address'     => (string) $property['address'],
                'city'        => (string) $property['city'],
                'type'        => PropertyModel::labelForType((string) $property['type']),
                'rooms'       => $property['rooms'] !== null ? (int) $property['rooms'] : null,
                'base_rent'   => (string) $property['base_rent'],
                'owner_name'  => $property['owner_last_name'] . ', ' . $property['owner_first_name'],
                'owner_dni'   => (string) $property['owner_document_number'],
            ];
        }

        $tenants = [];

        foreach (model(UserModel::class)->findActiveTenantsForContract() as $tenant) {
            $tenants[] = [
                'id'    => (int) $tenant['id'],
                'label' => $tenant['last_name'] . ', ' . $tenant['first_name'] . ' — DNI ' . $tenant['document_number']
                    . ' · ' . $tenant['email'],
            ];
        }

        return view('admin/contracts/form', array_merge($this->panelContext(
            pageTitle: 'Nuevo contrato',
            breadcrumb: 'Contratos / Nuevo contrato',
            activeMenu: 'contratos'
        ), [
            'properties'         => $properties,
            'tenants'            => $tenants,
            'preselectedProperty' => trim((string) $this->request->getGet('property_id')),
        ]));
    }

    /**
     * Sends the user back to the form keeping what was typed and showing the errors.
     *
     * @param array<string, string> $errors
     */
    private function backToForm(array $errors, string $headline): RedirectResponse
    {
        return redirect()->to('/admin/contratos/nuevo')
            ->withInput()
            ->with('errors', $errors)
            ->with('error', $headline);
    }

    /**
     * The message at the top of the form: the unavailability of the property is the one the user must see first.
     *
     * @param array<string, string> $errors
     */
    private function headlineFor(array $errors): string
    {
        return ($errors['property_id'] ?? null) === self::PROPERTY_UNAVAILABLE_MESSAGE
            ? self::PROPERTY_UNAVAILABLE_MESSAGE
            : self::VALIDATION_ERROR_MESSAGE;
    }

    /**
     * Collects and normalizes the form fields from the POST request. The owner is deliberately not read: it is the
     * owner of the selected property.
     *
     * @return array<string, string>
     */
    private function collectInput(): array
    {
        return [
            'property_id'               => trim((string) $this->request->getPost('property_id')),
            'tenant_id'                 => trim((string) $this->request->getPost('tenant_id')),
            'start_date'                => trim((string) $this->request->getPost('start_date')),
            'end_date'                  => trim((string) $this->request->getPost('end_date')),
            'monthly_rent'              => $this->normalizeDecimal((string) $this->request->getPost('monthly_rent')),
            'update_percentage'         => $this->normalizeDecimal((string) $this->request->getPost('update_percentage')),
            'commission_percentage'     => $this->normalizeDecimal((string) $this->request->getPost('commission_percentage')),
            'due_day'                   => trim((string) $this->request->getPost('due_day')),
            'late_fee_daily_percentage' => $this->normalizeDecimal((string) $this->request->getPost('late_fee_daily_percentage')),
        ];
    }

    /**
     * Accepts "$ 420000", "420000,50" or "420000.50" and returns a plain decimal string; anything else is left
     * untouched so the validation rejects it.
     */
    private function normalizeDecimal(string $value): string
    {
        $value = trim(str_replace(['$', '%', ' '], '', $value));

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    /**
     * Validates the input on the server and returns the error message of every invalid field.
     *
     * @param array<string, string> $input
     *
     * @return array<string, string>
     */
    private function validateInput(array $input): array
    {
        $percentageErrors = static fn (string $name): array => [
            'required'                 => "Ingresá {$name}.",
            'decimal'                  => ucfirst($name) . ' debe ser un número, por ejemplo 10 o 0,5.',
            'greater_than_equal_to'    => ucfirst($name) . ' no puede ser menor a 0.',
            'less_than_equal_to'       => ucfirst($name) . ' no puede ser mayor a 100.',
        ];

        $rules = [
            'property_id' => [
                'label'  => 'Inmueble',
                'rules'  => 'required|is_natural_no_zero',
                'errors' => [
                    'required'           => self::INVALID_PROPERTY_MESSAGE,
                    'is_natural_no_zero' => self::INVALID_PROPERTY_MESSAGE,
                ],
            ],
            'tenant_id' => [
                'label'  => 'Inquilino',
                'rules'  => 'required|is_natural_no_zero',
                'errors' => [
                    'required'           => 'Seleccioná el inquilino del contrato.',
                    'is_natural_no_zero' => 'Seleccioná el inquilino del contrato.',
                ],
            ],
            'start_date' => [
                'label'  => 'Fecha de inicio',
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Ingresá la fecha de inicio del contrato.',
                    'valid_date' => 'La fecha de inicio no es válida.',
                ],
            ],
            'end_date' => [
                'label'  => 'Fecha de finalización',
                'rules'  => 'required|valid_date[Y-m-d]',
                'errors' => [
                    'required'   => 'Ingresá la fecha de finalización del contrato.',
                    'valid_date' => 'La fecha de finalización no es válida.',
                ],
            ],
            'monthly_rent' => [
                'label'  => 'Monto mensual',
                'rules'  => 'required|decimal|greater_than[0]|less_than[10000000000]',
                'errors' => [
                    'required'     => 'Ingresá el monto mensual del alquiler.',
                    'decimal'      => 'El monto mensual debe ser un número, por ejemplo 420000 o 420000,50.',
                    'greater_than' => 'El monto mensual debe ser mayor a cero.',
                    'less_than'    => 'El monto mensual es demasiado grande.',
                ],
            ],
            'update_percentage' => [
                'label'  => 'Incremento por actualización',
                'rules'  => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
                'errors' => $percentageErrors('el porcentaje de incremento por actualización'),
            ],
            'commission_percentage' => [
                'label'  => 'Comisión',
                'rules'  => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
                'errors' => $percentageErrors('el porcentaje de comisión'),
            ],
            'due_day' => [
                'label'  => 'Día de vencimiento',
                'rules'  => 'required|is_natural_no_zero|less_than_equal_to[28]',
                'errors' => [
                    'required'           => 'Ingresá el día de vencimiento mensual.',
                    'is_natural_no_zero' => 'El día de vencimiento debe ser un número entero entre 1 y 28.',
                    'less_than_equal_to' => 'El día de vencimiento debe estar entre 1 y 28.',
                ],
            ],
            'late_fee_daily_percentage' => [
                'label'  => 'Recargo diario por mora',
                'rules'  => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
                'errors' => $percentageErrors('el porcentaje de recargo diario por mora'),
            ],
        ];

        $errors = [];

        if (! $this->validateData($input, $rules)) {
            $errors = $this->validator->getErrors();
        }

        foreach ([
            'monthly_rent'              => 'El monto mensual',
            'update_percentage'         => 'El porcentaje de incremento por actualización',
            'commission_percentage'     => 'El porcentaje de comisión',
            'late_fee_daily_percentage' => 'El porcentaje de recargo diario por mora',
        ] as $field => $label) {
            if (! isset($errors[$field]) && preg_match('/\.[0-9]{3,}$/', $input[$field]) === 1) {
                $errors[$field] = "{$label} admite hasta dos decimales.";
            }
        }

        if (! isset($errors['start_date']) && ! isset($errors['end_date']) && $input['end_date'] <= $input['start_date']) {
            $errors['end_date'] = self::INVALID_DATE_ORDER_MESSAGE;
        }

        if (! isset($errors['property_id'])) {
            $propertyError = $this->checkProperty((int) $input['property_id']);

            if ($propertyError !== null) {
                $errors['property_id'] = $propertyError;
            }
        }

        if (! isset($errors['tenant_id'])) {
            $tenant = model(UserModel::class)->findActiveById((int) $input['tenant_id']);

            if ($tenant === null || (string) $tenant['role_name'] !== UserModel::ROLE_TENANT) {
                $errors['tenant_id'] = self::INVALID_TENANT_MESSAGE;
            }
        }

        return $errors;
    }

    /**
     * Checks that the property can be linked to a new contract: it exists, is not deactivated, is "available" and
     * belongs to an active owner (HU-10 Scenario 2 and business rules). The atomic operation checks the status
     * again under a lock, so two simultaneous requests cannot both succeed.
     */
    private function checkProperty(int $propertyId): ?string
    {
        $property = model(PropertyModel::class)->findWithOwner($propertyId);

        if ($property === null || (string) $property['status'] !== PropertyModel::STATUS_AVAILABLE) {
            return self::PROPERTY_UNAVAILABLE_MESSAGE;
        }

        $owner = model(UserModel::class)->findActiveById((int) $property['owner_id']);

        if ($owner === null || (string) $owner['role_name'] !== UserModel::ROLE_OWNER) {
            return self::INACTIVE_OWNER_MESSAGE;
        }

        return null;
    }

    /**
     * Converts validated input into the columns to store. The owner and the status are set by the model.
     *
     * @param array<string, string> $input
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $input): array
    {
        return [
            'property_id'               => (int) $input['property_id'],
            'tenant_id'                 => (int) $input['tenant_id'],
            'start_date'                => $input['start_date'],
            'end_date'                  => $input['end_date'],
            'monthly_rent'              => $input['monthly_rent'],
            'update_percentage'         => $input['update_percentage'],
            'due_day'                   => (int) $input['due_day'],
            'commission_percentage'     => $input['commission_percentage'],
            'late_fee_daily_percentage' => $input['late_fee_daily_percentage'],
        ];
    }
}
