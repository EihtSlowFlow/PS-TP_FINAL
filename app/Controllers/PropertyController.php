<?php

namespace App\Controllers;

use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Property management for administrators and employees (HU-07): listing, creation and edition.
 *
 * The listing is intentionally minimal (it exists to show the result of the creation); HU-08 adds the filters
 * and HU-09 the logical deactivation.
 */
class PropertyController extends BaseController
{
    public const PROPERTY_CREATED_MESSAGE     = 'El inmueble fue creado exitosamente.';
    public const PROPERTY_UPDATED_MESSAGE     = 'Los datos del inmueble fueron actualizados exitosamente.';
    public const PROPERTY_NOT_FOUND_MESSAGE   = 'El inmueble solicitado no existe.';
    public const VALIDATION_ERROR_MESSAGE     = 'Revisá los campos marcados antes de guardar el inmueble.';
    public const INVALID_OWNER_MESSAGE        = 'Seleccioná un propietario válido.';
    public const INACTIVE_OWNER_MESSAGE       = 'El propietario seleccionado está dado de baja. Elegí un propietario activo.';
    public const INVALID_STATUS_MESSAGE       = 'Seleccioná un estado válido: Disponible o En mantenimiento.';
    public const RENTED_STATUS_LOCKED_MESSAGE = 'El estado «Alquilado» lo asigna el contrato y no se puede modificar desde el inmueble.';

    /**
     * Renders the paginated property listing (HU-07 Scenario 1 shows new properties here).
     */
    public function index(): string
    {
        $propertyModel = model(PropertyModel::class);

        $page    = max(1, (int) $this->request->getGet('page'));
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = $perPage > 0 ? min(50, $perPage) : 10;

        $pagination = $propertyModel->paginateWithOwner($perPage, $page);

        return view('admin/properties/index', array_merge($this->panelContext(
            pageTitle: 'Inmuebles',
            breadcrumb: 'Inmuebles',
            activeMenu: 'inmuebles'
        ), [
            'properties'   => $pagination['items'],
            'pagination'   => $pagination,
            'statusCounts' => $propertyModel->getStatusCounts(),
        ]));
    }

    /**
     * Renders the new property form (screen ADM-05).
     */
    public function create(): string
    {
        return view('admin/properties/form', array_merge($this->panelContext(
            pageTitle: 'Nuevo inmueble',
            breadcrumb: 'Inmuebles / Nuevo inmueble',
            activeMenu: 'inmuebles'
        ), [
            'mode'     => 'create',
            'property' => null,
            'owners'   => $this->ownerOptions(null),
        ]));
    }

    /**
     * Validates and stores a new property linked to an active owner, with the initial status "available"
     * (HU-07 Scenarios 1 and 2).
     */
    public function store(): RedirectResponse
    {
        $input  = $this->collectInput();
        $errors = $this->validateInput($input, null);

        if ($errors !== []) {
            return redirect()->to('/admin/inmuebles/nuevo')
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $payload           = $this->buildPayload($input);
        $payload['status'] = PropertyModel::STATUS_AVAILABLE;

        model(PropertyModel::class)->insert($payload);

        return redirect()->to('/admin/inmuebles')
            ->with('success', self::PROPERTY_CREATED_MESSAGE);
    }

    /**
     * Renders the edition form of an existing property.
     */
    public function edit(int $id): RedirectResponse|string
    {
        $property = model(PropertyModel::class)->findWithOwner($id);

        if ($property === null) {
            return redirect()->to('/admin/inmuebles')
                ->with('error', self::PROPERTY_NOT_FOUND_MESSAGE);
        }

        return view('admin/properties/form', array_merge($this->panelContext(
            pageTitle: 'Editar inmueble',
            breadcrumb: 'Inmuebles / Editar inmueble',
            activeMenu: 'inmuebles'
        ), [
            'mode'     => 'edit',
            'property' => $property,
            'owners'   => $this->ownerOptions($property),
        ]));
    }

    /**
     * Validates and updates a property; the modification date is recorded by the model (HU-07 Scenario 3).
     */
    public function update(int $id): RedirectResponse
    {
        $propertyModel = model(PropertyModel::class);
        $existing      = $propertyModel->findWithOwner($id);

        if ($existing === null) {
            return redirect()->to('/admin/inmuebles')
                ->with('error', self::PROPERTY_NOT_FOUND_MESSAGE);
        }

        $input  = $this->collectInput();
        $errors = $this->validateInput($input, $existing);

        if ($errors !== []) {
            return redirect()->to('/admin/inmuebles/editar/' . $id)
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $payload = $this->buildPayload($input);

        // The status only changes by hand between "available" and "maintenance"; a rented property keeps its status.
        if ($input['status'] !== '' && (string) $existing['status'] !== PropertyModel::STATUS_RENTED) {
            $payload['status'] = $input['status'];
        }

        $propertyModel->update($id, $payload);

        return redirect()->to('/admin/inmuebles')
            ->with('success', self::PROPERTY_UPDATED_MESSAGE);
    }

    /**
     * Collects and normalizes the form fields from the POST request.
     *
     * @return array{
     *     owner_id: string,
     *     address: string,
     *     city: string,
     *     type: string,
     *     rooms: string,
     *     surface: string,
     *     included_services: string,
     *     base_rent: string,
     *     description: string,
     *     status: string
     * }
     */
    private function collectInput(): array
    {
        return [
            'owner_id'          => trim((string) $this->request->getPost('owner_id')),
            'address'           => trim((string) $this->request->getPost('address')),
            'city'              => trim((string) $this->request->getPost('city')),
            'type'              => trim((string) $this->request->getPost('type')),
            'rooms'             => trim((string) $this->request->getPost('rooms')),
            'surface'           => $this->normalizeDecimal((string) $this->request->getPost('surface')),
            'included_services' => trim((string) $this->request->getPost('included_services')),
            'base_rent'         => $this->normalizeDecimal((string) $this->request->getPost('base_rent')),
            'description'       => trim((string) $this->request->getPost('description')),
            'status'            => trim((string) $this->request->getPost('status')),
        ];
    }

    /**
     * Accepts "$ 420000", "420000,50" or "420000.50" and returns a plain decimal string; anything else is
     * left untouched so the validation rejects it.
     */
    private function normalizeDecimal(string $value): string
    {
        $value = trim(str_replace(['$', ' '], '', $value));

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    /**
     * Validates the input on the server and returns the error message of every invalid field.
     *
     * @param array<string, string>     $input
     * @param array<string, mixed>|null $existing Property being edited, or null when creating
     *
     * @return array<string, string>
     */
    private function validateInput(array $input, ?array $existing): array
    {
        $allowedTypes = implode(',', array_keys(PropertyModel::TYPE_LABELS));
        $roomsRules   = $input['type'] === PropertyModel::TYPE_LAND
            ? 'permit_empty|is_natural_no_zero|less_than_equal_to[255]'
            : 'required|is_natural_no_zero|less_than_equal_to[255]';

        $rules = [
            'owner_id' => [
                'label'  => 'Propietario',
                'rules'  => 'required|is_natural_no_zero',
                'errors' => [
                    'required'         => self::INVALID_OWNER_MESSAGE,
                    'is_natural_no_zero' => self::INVALID_OWNER_MESSAGE,
                ],
            ],
            'address' => [
                'label'  => 'Dirección',
                'rules'  => 'required|min_length[3]|max_length[255]',
                'errors' => [
                    'required'   => 'Ingresá la dirección del inmueble.',
                    'min_length' => 'La dirección debe tener al menos 3 caracteres.',
                    'max_length' => 'La dirección no puede superar los 255 caracteres.',
                ],
            ],
            'city' => [
                'label'  => 'Ciudad',
                'rules'  => 'required|min_length[2]|max_length[100]',
                'errors' => [
                    'required'   => 'Ingresá la ciudad.',
                    'min_length' => 'La ciudad debe tener al menos 2 caracteres.',
                    'max_length' => 'La ciudad no puede superar los 100 caracteres.',
                ],
            ],
            'type' => [
                'label'  => 'Tipo de inmueble',
                'rules'  => "required|in_list[{$allowedTypes}]",
                'errors' => [
                    'required' => 'Seleccioná el tipo de inmueble.',
                    'in_list'  => 'El tipo de inmueble seleccionado no es válido.',
                ],
            ],
            'rooms' => [
                'label'  => 'Ambientes',
                'rules'  => $roomsRules,
                'errors' => [
                    'required'               => 'Ingresá la cantidad de ambientes (solo los terrenos no la necesitan).',
                    'is_natural_no_zero'     => 'Los ambientes deben ser un número entero mayor a cero.',
                    'less_than_equal_to'     => 'Los ambientes no pueden ser más de 255.',
                ],
            ],
            'surface' => [
                'label'  => 'Superficie',
                'rules'  => 'permit_empty|decimal|greater_than[0]|less_than[1000000]',
                'errors' => [
                    'decimal'      => 'La superficie debe ser un número, por ejemplo 54 o 54,5.',
                    'greater_than' => 'La superficie debe ser mayor a cero.',
                    'less_than'    => 'La superficie es demasiado grande.',
                ],
            ],
            'included_services' => [
                'label'  => 'Servicios incluidos',
                'rules'  => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'Los servicios incluidos no pueden superar los 255 caracteres.',
                ],
            ],
            'base_rent' => [
                'label'  => 'Precio base',
                'rules'  => 'required|decimal|greater_than[0]|less_than[10000000000]',
                'errors' => [
                    'required'     => 'Ingresá el precio base de alquiler.',
                    'decimal'      => 'El precio base debe ser un número, por ejemplo 420000 o 420000,50.',
                    'greater_than' => 'El precio base debe ser mayor a cero.',
                    'less_than'    => 'El precio base es demasiado grande.',
                ],
            ],
            'description' => [
                'label'  => 'Descripción',
                'rules'  => 'permit_empty|max_length[1000]',
                'errors' => [
                    'max_length' => 'La descripción no puede superar los 1.000 caracteres.',
                ],
            ],
        ];

        $errors = [];

        if (! $this->validateData($input, $rules)) {
            $errors = $this->validator->getErrors();
        }

        foreach (['base_rent' => 'El precio base', 'surface' => 'La superficie'] as $field => $label) {
            if (! isset($errors[$field]) && preg_match('/\.[0-9]{3,}$/', $input[$field]) === 1) {
                $errors[$field] = "{$label} admite hasta dos decimales.";
            }
        }

        if (! isset($errors['owner_id'])) {
            $ownerError = $this->checkOwner((int) $input['owner_id'], $existing);

            if ($ownerError !== null) {
                $errors['owner_id'] = $ownerError;
            }
        }

        if ($existing !== null && $input['status'] !== '') {
            if ((string) $existing['status'] === PropertyModel::STATUS_RENTED) {
                if ($input['status'] !== PropertyModel::STATUS_RENTED) {
                    $errors['status'] = self::RENTED_STATUS_LOCKED_MESSAGE;
                }
            } elseif (! in_array($input['status'], PropertyModel::MANUAL_STATUSES, true)) {
                $errors['status'] = self::INVALID_STATUS_MESSAGE;
            }
        }

        return $errors;
    }

    /**
     * Checks that the owner is a user with the owner role who is active (HU-07 business rule).
     *
     * Keeping the current owner of an existing property is always accepted, even if that owner was deactivated
     * afterwards (HU-05: the property keeps its owner); only newly assigned owners must be active.
     *
     * @param array<string, mixed>|null $existing
     */
    private function checkOwner(int $ownerId, ?array $existing): ?string
    {
        if ($existing !== null && $ownerId === (int) $existing['owner_id']) {
            return null;
        }

        $owner = model(UserModel::class)->findByIdWithRole($ownerId, true);

        if ($owner === null || (string) $owner['role_name'] !== UserModel::ROLE_OWNER) {
            return self::INVALID_OWNER_MESSAGE;
        }

        if ($owner['deleted_at'] !== null) {
            return self::INACTIVE_OWNER_MESSAGE;
        }

        return null;
    }

    /**
     * Converts validated input into the columns to store.
     *
     * @param array<string, string> $input
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $input): array
    {
        return [
            'owner_id'          => (int) $input['owner_id'],
            'address'           => $input['address'],
            'city'              => $input['city'],
            'type'              => $input['type'],
            'rooms'             => $input['rooms'] !== '' ? (int) $input['rooms'] : null,
            'surface'           => $input['surface'] !== '' ? $input['surface'] : null,
            'included_services' => $input['included_services'] !== '' ? $input['included_services'] : null,
            'base_rent'         => $input['base_rent'],
            'description'       => $input['description'] !== '' ? $input['description'] : null,
        ];
    }

    /**
     * Owners that can be selected in the form: only active ones (HU-05 Scenario 2), plus the current owner of the
     * property being edited even if that owner was deactivated, so the form can keep submitting it.
     *
     * @param array<string, mixed>|null $property Property being edited, or null when creating
     *
     * @return list<array{id: int, label: string, inactive: bool}>
     */
    private function ownerOptions(?array $property): array
    {
        $options = [];

        foreach (model(UserModel::class)->findActiveOwnersForProperty() as $owner) {
            $options[] = [
                'id'       => (int) $owner['id'],
                'label'    => $owner['last_name'] . ', ' . $owner['first_name'] . ' — DNI ' . $owner['document_number'],
                'inactive' => false,
            ];
        }

        if ($property !== null && ! empty($property['owner_deleted_at'])) {
            array_unshift($options, [
                'id'       => (int) $property['owner_id'],
                'label'    => $property['owner_last_name'] . ', ' . $property['owner_first_name']
                    . ' — DNI ' . $property['owner_document_number'] . ' (dado de baja)',
                'inactive' => true,
            ]);
        }

        return $options;
    }
}
