<?php

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Lease contracts for administrators and employees (HU-10): creation and the contract file.
 *
 * The listing, search and filters belong to HU-11 and the closing of a contract to HU-12.
 */
class ContractController extends BaseController
{
    public const CONTRACT_CREATED_MESSAGE      = 'El contrato fue registrado exitosamente. El inmueble pasó a estado «Alquilado».';
    public const CONTRACT_NOT_FOUND_MESSAGE    = 'El contrato solicitado no existe.';
    public const VALIDATION_ERROR_MESSAGE      = 'Revisá los campos marcados antes de guardar el contrato.';
    public const PROPERTY_UNAVAILABLE_MESSAGE  = 'El inmueble seleccionado no está disponible: ya tiene un contrato vigente, está en mantenimiento o fue dado de baja.';
    public const INVALID_PROPERTY_MESSAGE      = 'Seleccioná el inmueble del contrato.';
    public const INACTIVE_OWNER_MESSAGE        = 'El propietario de este inmueble está dado de baja. Asignale un propietario activo desde la edición del inmueble antes de crear el contrato.';
    public const INVALID_TENANT_MESSAGE        = 'Seleccioná un inquilino activo. El usuario elegido no existe, no es inquilino o está dado de baja.';
    public const INVALID_DATE_ORDER_MESSAGE    = 'La fecha de finalización debe ser posterior a la fecha de inicio.';
    public const SAVE_ERROR_MESSAGE            = 'No pudimos registrar el contrato por un error interno. No se guardó ningún cambio: el inmueble sigue como estaba. Intentá nuevamente.';

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
            return redirect()->to('/admin/dashboard')
                ->with('error', self::CONTRACT_NOT_FOUND_MESSAGE);
        }

        return view('admin/contracts/show', array_merge($this->panelContext(
            pageTitle: 'Contrato #' . $id,
            breadcrumb: 'Contratos / Contrato #' . $id,
            activeMenu: 'contratos'
        ), [
            'contract' => $contract,
        ]));
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
