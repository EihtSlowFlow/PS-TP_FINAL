<?php

namespace App\Controllers;

use App\Libraries\PropertyImageProcessor;
use App\Models\PropertyImageModel;
use App\Models\PropertyModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Property management for administrators and employees (HU-07, HU-08, HU-09): listing, filters, creation, edition and
 * logical deactivation; the review of the properties that owners publish (HU-33); and the owner's own portal to
 * publish and correct them (HU-32).
 *
 * The owner's flow reuses the same input collection, validation and payload as HU-07: the only differences are
 * that the owner comes from the session and that what the owner saves is left pending for the administration.
 */
class PropertyController extends BaseController
{
    public const PROPERTY_CREATED_MESSAGE             = 'El inmueble fue creado exitosamente.';
    public const PROPERTY_UPDATED_MESSAGE             = 'Los datos del inmueble fueron actualizados exitosamente.';
    public const PROPERTY_DEACTIVATED_MESSAGE         = 'El inmueble fue dado de baja exitosamente.';
    public const PROPERTY_NOT_FOUND_MESSAGE           = 'El inmueble solicitado no existe.';
    public const PROPERTY_ALREADY_DEACTIVATED_MESSAGE = 'El inmueble seleccionado ya se encuentra dado de baja.';
    public const ACTIVE_CONTRACT_DEACTIVATION_MESSAGE = 'No es posible dar de baja un inmueble con contrato de locación vigente.';
    public const DEACTIVATION_ERROR_MESSAGE           = 'No pudimos dar de baja el inmueble por un error interno. Intentá nuevamente.';
    public const VALIDATION_ERROR_MESSAGE             = 'Revisá los campos marcados antes de guardar el inmueble.';
    public const INVALID_OWNER_MESSAGE                = 'Seleccioná un propietario válido.';
    public const INACTIVE_OWNER_MESSAGE               = 'El propietario seleccionado está dado de baja. Elegí un propietario activo.';
    public const SUBMITTED_OWNER_LOCKED_MESSAGE       = 'El propietario de un inmueble enviado por su titular no se puede modificar.';
    public const INVALID_STATUS_MESSAGE               = 'Seleccioná un estado válido: Disponible o En mantenimiento.';
    public const RENTED_STATUS_LOCKED_MESSAGE         = 'El estado «Alquilado» lo asigna el contrato y no se puede modificar desde el inmueble.';

    // HU-32: the owner publishes and corrects their own properties.
    public const OWNER_PROPERTY_SUBMITTED_MESSAGE   = 'Registramos tu inmueble. Quedó pendiente de aprobación: la administración lo revisa antes de publicarlo.';
    public const OWNER_PROPERTY_RESUBMITTED_MESSAGE = 'Guardamos los cambios. El inmueble volvió a quedar pendiente de aprobación.';
    public const OWNER_PROPERTY_APPROVED_LOCKED_MESSAGE = 'Este inmueble ya fue aprobado y no se puede editar desde tu portal. Pedile los cambios a la administración.';
    public const OWNER_SUBMISSION_ERROR_MESSAGE     = 'No pudimos guardar el inmueble por un error interno. Intentá nuevamente.';
    public const OWNER_REQUEST_CANCELED_MESSAGE     = 'Cancelaste la solicitud: el inmueble se dio de baja y la administración ya no lo revisa.';
    public const OWNER_PROPERTY_DEACTIVATED_MESSAGE = 'Diste de baja el inmueble. Ya no figura en el catálogo ni se puede usar en contratos nuevos.';
    public const OWNER_CONTRACT_IN_FORCE_MESSAGE    = 'No podés dar de baja un inmueble con un contrato de locación vigente. Pedile a la administración que primero finalice o rescinda el contrato.';

    // HU-33: the administration approves or rejects them.
    public const PROPERTY_APPROVED_MESSAGE          = 'El inmueble fue aprobado. Ya se puede publicar y usar en contratos, y el propietario lo ve aprobado.';
    public const PROPERTY_REJECTED_MESSAGE          = 'El inmueble fue rechazado. El propietario verá el motivo en «Mis inmuebles» y podrá corregirlo.';
    public const APPROVAL_ALREADY_RESOLVED_MESSAGE  = 'Este inmueble ya fue aprobado o rechazado por otro usuario. No se modificó nada.';
    public const APPROVAL_DEACTIVATED_MESSAGE       = 'El inmueble está dado de baja y no se puede aprobar ni rechazar.';
    public const APPROVAL_ERROR_MESSAGE             = 'No pudimos resolver la revisión por un error interno. Intentá nuevamente.';
    public const REJECTION_REASON_REQUIRED_MESSAGE  = 'Ingresá el motivo del rechazo.';
    public const REJECTION_REASON_TOO_LONG_MESSAGE  = 'El motivo del rechazo no puede superar los 255 caracteres.';

    // Photos of the property, loaded from the same forms (shown on the landing, the catalog and the sheet).
    public const IMAGES_PARTIAL_FAILURE_MESSAGE = 'El inmueble se guardó, pero %d foto(s) no se pudieron guardar. Volvé a subirlas desde «Editar».';

    /**
     * Renders the administrator and employee catalog with filters.
     */
    public function index(): string
    {
        $propertyModel = model(PropertyModel::class);
        $pageData      = $this->catalogPage();
        $filterData    = $this->catalogFilters(true, true, true, true);
        $pagination    = $propertyModel->paginateWithOwner(
            $pageData['perPage'],
            $pageData['page'],
            $filterData['filters']
        );
        $onlyInactive  = ($filterData['filters']['status'] ?? '') === PropertyModel::FILTER_STATUS_INACTIVE;

        return view('admin/properties/index', array_merge($this->panelContext(
            pageTitle: 'Inmuebles',
            breadcrumb: 'Inmuebles',
            activeMenu: 'inmuebles'
        ), [
            'properties'   => $pagination['items'],
            'pagination'   => $pagination,
            'statusCounts' => $propertyModel->getStatusCounts(),
            'filters'      => $filterData['values'],
            'filterErrors' => $filterData['errors'],
            'owners'       => $propertyModel->getOwnerFilterOptions($onlyInactive),
        ]));
    }

    /**
     * Renders the authenticated owner's own properties only.
     */
    public function ownerIndex(): string
    {
        $propertyModel = model(PropertyModel::class);
        $pageData      = $this->catalogPage();
        $filterData    = $this->catalogFilters(true, false);
        $pagination    = $propertyModel->paginateForOwner(
            (int) session()->get('user_id'),
            $pageData['perPage'],
            $pageData['page'],
            $filterData['filters']
        );

        return view('properties/owner_catalog', array_merge($this->panelContext(
            pageTitle: 'Mis inmuebles',
            breadcrumb: 'Mis inmuebles',
            activeMenu: 'propietario-inmuebles'
        ), [
            'properties'   => $pagination['items'],
            'pagination'   => $pagination,
            'filters'      => $filterData['values'],
            'filterErrors' => $filterData['errors'],
        ]));
    }

    /**
     * Renders the public catalog with available properties and public fields only.
     */
    public function publicIndex(): string
    {
        $propertyModel = model(PropertyModel::class);
        $pageData      = $this->catalogPage();
        $filterData    = $this->catalogFilters(false, false);
        $pagination    = $propertyModel->paginatePublic(
            $pageData['perPage'],
            $pageData['page'],
            $filterData['filters']
        );

        return view('properties/public_catalog', array_merge([
            'pageTitle'    => 'Propiedades disponibles',
            'activeNav'    => 'catalogo',
            'properties'   => $pagination['items'],
            'covers'       => model(PropertyImageModel::class)->coversFor(array_column($pagination['items'], 'id')),
            'pagination'   => $pagination,
            'filters'      => $filterData['values'],
            'filterErrors' => $filterData['errors'],
        ], $this->tenantCatalogPanel('Propiedades disponibles', 'Buscar inmuebles')));
    }

    /**
     * Renders the public property detail sheet (HU-29).
     * Only available, approved and active properties of active owners are visible;
     * anything else throws PageNotFoundException (404).
     */
    public function publicShow(string $id): string
    {
        // The route accepts any run of digits; one too long for an integer must answer 404 like any other unknown id.
        $propertyId = ctype_digit($id) && strlen($id) <= 18 ? (int) $id : 0;
        $property   = $propertyId > 0 ? model(PropertyModel::class)->findPublicDetail($propertyId) : null;

        if ($property === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $session    = session();
        $isLoggedIn = (bool) $session->get('is_logged_in');
        $userRole   = (string) $session->get('user_role', '');

        // Inside the tenant panel the address is already in the page's own "Volver al catálogo / {address}" line, so
        // the topbar breadcrumb stays short enough for a phone.
        return view('properties/public_show', array_merge([
            'pageTitle'  => $property['address'] . ' - Detalle',
            'activeNav'  => 'catalogo',
            'property'   => $property,
            'images'     => model(PropertyImageModel::class)->forProperty($propertyId),
            'isLoggedIn' => $isLoggedIn,
            'userRole'   => $userRole,
            'catalogBackUrl' => $this->publicCatalogBackUrl(),
        ], $this->tenantCatalogPanel($property['address'] . ' - Detalle', 'Buscar inmuebles / Ficha')));
    }

    /**
     * A signed-in tenant reaches the catalog from the "Buscar inmuebles" entry of their panel, so the catalog and the
     * property sheet stay inside that panel (sidebar, topbar) instead of switching to the public layout. Visitors and
     * the other roles keep the public layout.
     *
     * @return array<string, mixed> `useTenantPanel` plus, for a tenant, the data the panel layout needs
     */
    private function tenantCatalogPanel(string $pageTitle, string $breadcrumb): array
    {
        $session = session();

        if (! $session->get('is_logged_in') || (string) $session->get('user_role') !== UserModel::ROLE_TENANT) {
            return ['useTenantPanel' => false];
        }

        return array_merge(
            $this->panelContext($pageTitle, $breadcrumb, 'inquilino-buscar-inmuebles'),
            ['useTenantPanel' => true]
        );
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
        $input        = $this->collectInput();
        $errors       = $this->validateInput($input, null);
        $imageChanges = $this->collectImageChanges();

        if ($imageChanges['error'] !== null) {
            $errors['images'] = $imageChanges['error'];
        }

        if ($errors !== []) {
            return redirect()->to('/admin/inmuebles/nuevo')
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $payload                    = $this->buildPayload($input);
        $payload['status']          = PropertyModel::STATUS_AVAILABLE;
        // What the administration loads is approved on the spot; only an owner's own submission waits for review.
        $payload['approval_status'] = PropertyModel::APPROVAL_APPROVED;

        $propertyId = (int) model(PropertyModel::class)->insert($payload);
        $warning    = $this->applyImageChanges($propertyId, $imageChanges);

        return $this->savedRedirect('/admin/inmuebles', self::PROPERTY_CREATED_MESSAGE, $warning);
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
            'images'   => model(PropertyImageModel::class)->forProperty($id),
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

        $input        = $this->collectInput();
        $errors       = $this->validateInput($input, $existing);
        $imageChanges = $this->collectImageChanges($id);

        if ($imageChanges['error'] !== null) {
            $errors['images'] = $imageChanges['error'];
        }

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
        $warning = $this->applyImageChanges($id, $imageChanges);

        return $this->savedRedirect('/admin/inmuebles', self::PROPERTY_UPDATED_MESSAGE, $warning);
    }

    /**
     * Redirect after saving a property: the success message, plus a warning when some photo could not be stored.
     */
    private function savedRedirect(string $to, string $success, ?string $warning): RedirectResponse
    {
        $redirect = redirect()->to($to)->with('success', $success);

        return $warning === null ? $redirect : $redirect->with('error', $warning);
    }

    /**
     * Logically deactivates an active property without a contract in force, recording the date and the responsible
     * user while preserving its physical record and historical references (HU-09 Scenarios 1, 2, 3, 7, 8).
     *
     * The deactivation only happens when the request carries `confirmed=1`. Without it (for example when JavaScript
     * is disabled and the in-page confirmation modal never runs) a server-rendered confirmation page is returned,
     * so the confirmation step of Scenario 1 never depends on the client.
     */
    public function deactivate(int $id): RedirectResponse|string
    {
        $propertyModel = model(PropertyModel::class);
        $existing      = $propertyModel->findWithOwner($id, true);

        if ($existing === null) {
            return redirect()->to('/admin/inmuebles')
                ->with('error', self::PROPERTY_NOT_FOUND_MESSAGE);
        }

        if ($existing['deleted_at'] !== null) {
            return redirect()->to('/admin/inmuebles')
                ->with('error', self::PROPERTY_ALREADY_DEACTIVATED_MESSAGE);
        }

        if ((string) $existing['status'] === PropertyModel::STATUS_RENTED || ! empty($existing['has_active_contract'])) {
            return redirect()->to('/admin/inmuebles')
                ->with('error', self::ACTIVE_CONTRACT_DEACTIVATION_MESSAGE);
        }

        if ((string) $this->request->getPost('confirmed') !== '1') {
            return view('admin/properties/deactivate_confirm', array_merge($this->panelContext(
                pageTitle: 'Confirmar baja de inmueble',
                breadcrumb: 'Inmuebles / Baja',
                activeMenu: 'inmuebles'
            ), [
                'property' => $existing,
            ]));
        }

        $actorId = (int) session()->get('user_id', 0);
        $result  = $propertyModel->deactivate($id, $actorId);

        if (! $result['ok']) {
            $message = match ($result['error']) {
                PropertyModel::ERROR_NOT_FOUND         => self::PROPERTY_NOT_FOUND_MESSAGE,
                PropertyModel::ERROR_ALREADY_DELETED   => self::PROPERTY_ALREADY_DEACTIVATED_MESSAGE,
                PropertyModel::ERROR_CONTRACT_IN_FORCE => self::ACTIVE_CONTRACT_DEACTIVATION_MESSAGE,
                default                                => self::DEACTIVATION_ERROR_MESSAGE,
            };

            return redirect()->to('/admin/inmuebles')
                ->with('error', $message);
        }

        return redirect()->to('/admin/inmuebles')
            ->with('success', self::PROPERTY_DEACTIVATED_MESSAGE);
    }

    /**
     * Renders the form where an owner publishes a new property (HU-32 Scenario 1). It is the HU-07 form without the
     * owner selector: the owner is always the one in the session.
     */
    public function ownerCreate(): string
    {
        return view('properties/owner_form', array_merge($this->panelContext(
            pageTitle: 'Registrar inmueble',
            breadcrumb: 'Mis inmuebles / Registrar inmueble',
            activeMenu: 'propietario-inmuebles'
        ), [
            'mode'     => 'create',
            'property' => null,
        ]));
    }

    /**
     * Validates with the HU-07 rules and stores a property for the owner in the session, "pending" until the
     * administration reviews it (HU-32 Scenarios 1 and 2). The owner is never read from the form.
     */
    public function ownerStore(): RedirectResponse
    {
        $ownerId      = $this->sessionUserId();
        $input        = $this->collectInput($ownerId);
        $errors       = $this->validateInput($input, null);
        $imageChanges = $this->collectImageChanges();

        if ($imageChanges['error'] !== null) {
            $errors['images'] = $imageChanges['error'];
        }

        if ($errors !== []) {
            return redirect()->to('/propietario/inmuebles/nuevo')
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $propertyId = model(PropertyModel::class)->submitForApproval($ownerId, $this->buildPayload($input));

        if ($propertyId === false) {
            return redirect()->to('/propietario/inmuebles/nuevo')
                ->withInput()
                ->with('error', self::OWNER_SUBMISSION_ERROR_MESSAGE);
        }

        $warning = $this->applyImageChanges($propertyId, $imageChanges);

        return $this->savedRedirect('/propietario/inmuebles', self::OWNER_PROPERTY_SUBMITTED_MESSAGE, $warning);
    }

    /**
     * Renders the form to correct a pending or rejected property of the owner (HU-32 Scenarios 4 to 6): somebody
     * else's property answers 404 and an approved one cannot be edited from the portal.
     */
    public function ownerEdit(int $id): RedirectResponse|string
    {
        $property = $this->findOwnProperty($id);

        if ((string) $property['approval_status'] === PropertyModel::APPROVAL_APPROVED) {
            return $this->ownerApprovedLockedRedirect();
        }

        return view('properties/owner_form', array_merge($this->panelContext(
            pageTitle: 'Corregir inmueble',
            breadcrumb: 'Mis inmuebles / Corregir inmueble',
            activeMenu: 'propietario-inmuebles'
        ), [
            'mode'     => 'edit',
            'property' => $property,
            'images'   => model(PropertyImageModel::class)->forProperty($id),
        ]));
    }

    /**
     * Saves the corrections of a pending or rejected property and leaves it pending again, without the previous
     * rejection reason (HU-32 Scenario 4).
     */
    public function ownerUpdate(int $id): RedirectResponse
    {
        $ownerId  = $this->sessionUserId();
        $property = $this->findOwnProperty($id);

        if ((string) $property['approval_status'] === PropertyModel::APPROVAL_APPROVED) {
            return $this->ownerApprovedLockedRedirect();
        }

        $input        = $this->collectInput($ownerId);
        $errors       = $this->validateInput($input, $property);
        $imageChanges = $this->collectImageChanges($id);

        if ($imageChanges['error'] !== null) {
            $errors['images'] = $imageChanges['error'];
        }

        if ($errors !== []) {
            return redirect()->to('/propietario/inmuebles/editar/' . $id)
                ->withInput()
                ->with('errors', $errors)
                ->with('error', self::VALIDATION_ERROR_MESSAGE);
        }

        $result = model(PropertyModel::class)->resubmitForApproval($id, $ownerId, $this->buildPayload($input));

        if (! $result['ok']) {
            return match ($result['error']) {
                PropertyModel::ERROR_NOT_FOUND        => throw PageNotFoundException::forPageNotFound(),
                PropertyModel::ERROR_ALREADY_APPROVED => $this->ownerApprovedLockedRedirect(),
                default                               => redirect()->to('/propietario/inmuebles/editar/' . $id)
                    ->withInput()
                    ->with('error', self::OWNER_SUBMISSION_ERROR_MESSAGE),
            };
        }

        $warning = $this->applyImageChanges($id, $imageChanges);

        return $this->savedRedirect('/propietario/inmuebles', self::OWNER_PROPERTY_RESUBMITTED_MESSAGE, $warning);
    }

    /**
     * Lets an owner deactivate their own property: cancelling the request while it is pending or rejected, or taking an
     * approved one down. It is the logical deactivation of HU-09 (so the same rule applies: not with a contract in
     * force), asking for confirmation on a server-rendered page first, and the owner is recorded as the one who did it.
     * Somebody else's property answers 404.
     */
    public function ownerDeactivate(int $id): RedirectResponse|string
    {
        $ownerId   = $this->sessionUserId();
        $property  = $this->findOwnProperty($id);
        $isRequest = (string) $property['approval_status'] !== PropertyModel::APPROVAL_APPROVED;
        $model     = model(PropertyModel::class);

        if ((string) $property['status'] === PropertyModel::STATUS_RENTED || $model->hasContractInForce($id)) {
            return redirect()->to('/propietario/inmuebles')
                ->with('error', self::OWNER_CONTRACT_IN_FORCE_MESSAGE);
        }

        if ((string) $this->request->getPost('confirmed') !== '1') {
            return view('properties/owner_deactivate_confirm', array_merge($this->panelContext(
                pageTitle: $isRequest ? 'Cancelar solicitud' : 'Dar de baja inmueble',
                breadcrumb: 'Mis inmuebles / ' . ($isRequest ? 'Cancelar solicitud' : 'Baja'),
                activeMenu: 'propietario-inmuebles'
            ), [
                'property'  => $property,
                'isRequest' => $isRequest,
            ]));
        }

        $result = $model->deactivate($id, $ownerId, $ownerId);

        if (! $result['ok']) {
            return match ($result['error']) {
                PropertyModel::ERROR_NOT_FOUND         => throw PageNotFoundException::forPageNotFound(),
                PropertyModel::ERROR_ALREADY_DELETED   => redirect()->to('/propietario/inmuebles')
                    ->with('error', self::PROPERTY_ALREADY_DEACTIVATED_MESSAGE),
                PropertyModel::ERROR_CONTRACT_IN_FORCE => redirect()->to('/propietario/inmuebles')
                    ->with('error', self::OWNER_CONTRACT_IN_FORCE_MESSAGE),
                default                                => redirect()->to('/propietario/inmuebles')
                    ->with('error', self::DEACTIVATION_ERROR_MESSAGE),
            };
        }

        return redirect()->to('/propietario/inmuebles')
            ->with('success', $isRequest ? self::OWNER_REQUEST_CANCELED_MESSAGE : self::OWNER_PROPERTY_DEACTIVATED_MESSAGE);
    }

    /**
     * Approves a property published by an owner; it stays "available" and becomes visible in the catalog and in the
     * contract selector (HU-33 Scenario 2).
     */
    public function approve(int $id): RedirectResponse
    {
        $result = model(PropertyModel::class)->resolveApproval($id, $this->sessionUserId(), true);

        return $this->afterReview($id, $result, self::PROPERTY_APPROVED_MESSAGE);
    }

    /**
     * Rejects a property published by an owner with a mandatory reason of up to 255 characters, which the owner sees
     * in "Mis inmuebles" (HU-33 Scenario 3).
     */
    public function reject(int $id): RedirectResponse
    {
        $reason = trim((string) $this->request->getPost('rejection_reason'));

        if ($reason === '' || mb_strlen($reason) > PropertyModel::REJECTION_REASON_MAX_LENGTH) {
            return redirect()->to('/admin/inmuebles/editar/' . $id)
                ->withInput()
                ->with('errors', ['rejection_reason' => $reason === ''
                    ? self::REJECTION_REASON_REQUIRED_MESSAGE
                    : self::REJECTION_REASON_TOO_LONG_MESSAGE])
                ->with('error', $reason === ''
                    ? self::REJECTION_REASON_REQUIRED_MESSAGE
                    : self::REJECTION_REASON_TOO_LONG_MESSAGE);
        }

        $result = model(PropertyModel::class)->resolveApproval($id, $this->sessionUserId(), false, $reason);

        return $this->afterReview($id, $result, self::PROPERTY_REJECTED_MESSAGE);
    }

    /**
     * Turns the outcome of a review into the redirect and the message the reviewer sees. A property somebody else
     * already resolved is reported and nothing changes (HU-33 Scenario 5).
     *
     * @param array{ok: bool, error: string|null} $result
     */
    private function afterReview(int $id, array $result, string $successMessage): RedirectResponse
    {
        if ($result['ok']) {
            // Keep reviewing the queue while there is something left in it.
            $target = model(PropertyModel::class)->countPendingApproval() > 0
                ? '/admin/inmuebles?approval=' . PropertyModel::APPROVAL_PENDING
                : '/admin/inmuebles';

            return redirect()->to($target)->with('success', $successMessage);
        }

        $message = match ($result['error']) {
            PropertyModel::ERROR_NOT_FOUND        => self::PROPERTY_NOT_FOUND_MESSAGE,
            PropertyModel::ERROR_ALREADY_DELETED  => self::APPROVAL_DEACTIVATED_MESSAGE,
            PropertyModel::ERROR_ALREADY_RESOLVED => self::APPROVAL_ALREADY_RESOLVED_MESSAGE,
            default                               => self::APPROVAL_ERROR_MESSAGE,
        };

        $target = $result['error'] === PropertyModel::ERROR_PERSISTENCE
            ? '/admin/inmuebles/editar/' . $id
            : '/admin/inmuebles';

        return redirect()->to($target)->with('error', $message);
    }

    /**
     * Loads a property of the owner in the session, or answers 404 when it does not exist, is deactivated or belongs
     * to somebody else, without telling which one it was (HU-32 Scenario 6).
     *
     * @return array<string, mixed>
     */
    private function findOwnProperty(int $id): array
    {
        $property = model(PropertyModel::class)->findForOwner($id, $this->sessionUserId());

        if ($property === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $property;
    }

    private function ownerApprovedLockedRedirect(): RedirectResponse
    {
        return redirect()->to('/propietario/inmuebles')
            ->with('error', self::OWNER_PROPERTY_APPROVED_LOCKED_MESSAGE);
    }

    /**
     * Keeps supported catalog filters when the detail page is reached from the public catalog (HU-29).
     *
     * The page of the catalog is recognized by its own address (`site_url()`, so it also works with `index.php` in the
     * URL or with the app in a subfolder), and only the known filters are copied: anything else in the `Referer`,
     * including a page of another site, is ignored.
     */
    private function publicCatalogBackUrl(): string
    {
        $catalogUrl = site_url('catalogo-inmuebles');
        $referer    = trim($this->request->getHeaderLine('Referer'));

        if ($referer === '') {
            return $catalogUrl;
        }

        $parts   = parse_url($referer);
        $catalog = parse_url($catalogUrl);

        if (
            ! is_array($parts)
            || ! is_array($catalog)
            || ($parts['host'] ?? null) !== ($catalog['host'] ?? null)
            || ($parts['port'] ?? null) !== ($catalog['port'] ?? null)
            || rtrim((string) ($parts['path'] ?? ''), '/') !== rtrim((string) ($catalog['path'] ?? ''), '/')
        ) {
            return $catalogUrl;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $filters = [];

        foreach (['type', 'min_price', 'max_price', 'page'] as $field) {
            if (isset($query[$field]) && is_string($query[$field]) && trim($query[$field]) !== '') {
                $filters[$field] = trim($query[$field]);
            }
        }

        return $filters === [] ? $catalogUrl : $catalogUrl . '?' . http_build_query($filters);
    }

    private function sessionUserId(): int
    {
        return (int) session()->get('user_id', 0);
    }

    /**
     * Reads the photos sent with a property form (`images[]`) and the ones marked to be removed (`remove_images[]`),
     * and checks them before anything is saved: each photo must be a real JPG, PNG or WebP picture and the property
     * may end up with at most PropertyImageProcessor::MAX_IMAGES_PER_PROPERTY photos.
     *
     * @return array{files: list<array{mime_type: string, original_name: string, temp_path: string}>, remove: list<int>, error: string|null}
     */
    private function collectImageChanges(int $propertyId = 0): array
    {
        $storage = service('propertyImageStorage');
        $files   = [];

        foreach ($this->request->getFileMultiple('images') ?? [] as $file) {
            if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $check = PropertyImageProcessor::validate($file, $storage);

            if (! $check['ok']) {
                return ['files' => [], 'remove' => [], 'error' => $check['error']];
            }

            unset($check['ok']);
            $files[] = $check;
        }

        $marked   = $this->request->getPost('remove_images');
        $existing = $propertyId > 0 ? array_map('intval', array_column(model(PropertyImageModel::class)->forProperty($propertyId), 'id')) : [];
        $remove   = array_values(array_intersect(is_array($marked) ? array_map('intval', $marked) : [], $existing));

        if (count($existing) - count($remove) + count($files) > PropertyImageProcessor::MAX_IMAGES_PER_PROPERTY) {
            return ['files' => [], 'remove' => [], 'error' => PropertyImageProcessor::TOO_MANY_MESSAGE];
        }

        return ['files' => $files, 'remove' => $remove, 'error' => null];
    }

    /**
     * Applies the photo changes of a property that was just saved: removes the marked photos and stores the new ones
     * after the existing ones (re-encoded, without metadata).
     *
     * @param array{files: list<array{mime_type: string, original_name: string, temp_path: string}>, remove: list<int>, error: string|null} $changes
     *
     * @return string|null A warning when some photo could not be stored (the property itself is already saved)
     */
    private function applyImageChanges(int $propertyId, array $changes): ?string
    {
        $images  = model(PropertyImageModel::class);
        $storage = service('propertyImageStorage');

        foreach ($images->deleteFromProperty($propertyId, $changes['remove']) as $storedName) {
            $storage->delete($storedName);
        }

        $position = $images->nextPosition($propertyId);
        $failed   = 0;

        foreach ($changes['files'] as $file) {
            $prepared   = PropertyImageProcessor::prepare($file['temp_path'], $file['mime_type']);
            $storedName = $prepared === null ? null : PropertyImageProcessor::generateStoredName($prepared['extension']);

            if ($prepared === null || ! $storage->write($storedName, $prepared['contents'])) {
                $failed++;

                continue;
            }

            try {
                $saved = $images->insert([
                    'property_id'   => $propertyId,
                    'original_name' => $file['original_name'],
                    'stored_name'   => $storedName,
                    'mime_type'     => $prepared['mime_type'],
                    'size_bytes'    => strlen($prepared['contents']),
                    'position'      => $position,
                ]) !== false;
            } catch (Throwable) {
                $saved = false;
            }

            if (! $saved) {
                $storage->delete($storedName);
                $failed++;

                continue;
            }

            $position++;
        }

        return $failed > 0 ? sprintf(self::IMAGES_PARTIAL_FAILURE_MESSAGE, $failed) : null;
    }

    /**
     * Collects and normalizes the form fields from the POST request.
     *
     * When `$ownerId` is given (the owner's own portal) it replaces whatever the form sent as owner, and the
     * commercial status is ignored: an owner cannot choose either.
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
    private function collectInput(?int $ownerId = null): array
    {
        return [
            'owner_id'          => $ownerId !== null ? (string) $ownerId : trim((string) $this->request->getPost('owner_id')),
            'address'           => trim((string) $this->request->getPost('address')),
            'city'              => trim((string) $this->request->getPost('city')),
            'type'              => trim((string) $this->request->getPost('type')),
            'rooms'             => trim((string) $this->request->getPost('rooms')),
            'surface'           => $this->normalizeDecimal((string) $this->request->getPost('surface')),
            // The form sends one checkbox per service (`included_services[]`); they are stored as "Agua, Luz".
            'included_services' => implode(', ', PropertyModel::splitServices($this->request->getPost('included_services'))),
            'base_rent'         => $this->normalizeDecimal((string) $this->request->getPost('base_rent')),
            'description'       => trim((string) $this->request->getPost('description')),
            'status'            => $ownerId !== null ? '' : trim((string) $this->request->getPost('status')),
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
                    'max_length' => 'Elegí los servicios de la lista.',
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

        if (! isset($errors['included_services']) && PropertyModel::unknownServices($input['included_services']) !== []) {
            $errors['included_services'] = 'Elegí los servicios de la lista.';
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
        if ($existing !== null && ! empty($existing['submitted_by']) && $ownerId !== (int) $existing['owner_id']) {
            return self::SUBMITTED_OWNER_LOCKED_MESSAGE;
        }

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

    /**
     * @return array{page: int, perPage: int}
     */
    private function catalogPage(): array
    {
        $pageValue    = $this->request->getGet('page');
        $perPageValue = $this->request->getGet('per_page');
        $page         = is_scalar($pageValue) && ctype_digit((string) $pageValue) ? max(1, (int) $pageValue) : 1;
        $perPage      = is_scalar($perPageValue) && ctype_digit((string) $perPageValue) ? (int) $perPageValue : 10;

        return [
            'page'    => $page,
            'perPage' => $perPage > 0 ? min(50, $perPage) : 10,
        ];
    }

    /**
     * Validates catalog query parameters and returns only supported filter values.
     *
     * @return array{
     *     filters: array<string, string|int|float>,
     *     values: array{status: string, type: string, min_price: string, max_price: string, owner_id: string, approval: string},
     *     errors: array<string, string>
     * }
     */
    private function catalogFilters(
        bool $allowStatus,
        bool $allowOwner,
        bool $allowLifecycleStatus = false,
        bool $allowApproval = false
    ): array {
        $values = [
            'status'    => '',
            'type'      => '',
            'min_price' => '',
            'max_price' => '',
            'owner_id'  => '',
            'approval'  => '',
        ];
        $filters = [];
        $errors  = [];
        $fields  = ['type', 'min_price', 'max_price'];

        if ($allowStatus) {
            $fields[] = 'status';
        }

        if ($allowOwner) {
            $fields[] = 'owner_id';
        }

        // The review filter (HU-33) is administrative: the public catalog and the owner never accept it.
        if ($allowApproval) {
            $fields[] = 'approval';
        }

        foreach ($fields as $field) {
            $raw = $this->request->getGet($field);

            if ($raw === null) {
                continue;
            }

            if (! is_string($raw) && ! is_numeric($raw)) {
                $errors[$field] = 'El filtro ingresado no es válido.';
                continue;
            }

            $value          = trim((string) $raw);
            $values[$field] = $value;

            if ($value === '') {
                continue;
            }

            if ($field === 'status') {
                $normalizedStatus = $value;
                if ($allowLifecycleStatus) {
                    $normalizedStatus = match (mb_strtolower($value)) {
                        'activo'   => PropertyModel::FILTER_STATUS_ACTIVE,
                        'inactivo' => PropertyModel::FILTER_STATUS_INACTIVE,
                        default    => $value,
                    };
                }

                $allowedStatuses = $allowLifecycleStatus
                    ? PropertyModel::ADMIN_FILTER_STATUS_LABELS
                    : PropertyModel::STATUS_LABELS;

                if (! array_key_exists($normalizedStatus, $allowedStatuses)) {
                    $errors[$field] = 'Seleccioná un estado válido.';
                    continue;
                }

                $values[$field]  = $normalizedStatus;
                $filters[$field] = $normalizedStatus;
            } elseif ($field === 'type') {
                if (! array_key_exists($value, PropertyModel::TYPE_LABELS)) {
                    $errors[$field] = 'Seleccioná un tipo de inmueble válido.';
                    continue;
                }

                $filters[$field] = $value;
            } elseif ($field === 'approval') {
                if (! array_key_exists($value, PropertyModel::APPROVAL_LABELS)) {
                    $errors[$field] = 'Seleccioná una revisión válida.';
                    continue;
                }

                $filters[$field] = $value;
            } elseif ($field === 'owner_id') {
                if (preg_match('/^[1-9][0-9]*$/D', $value) !== 1) {
                    $errors[$field] = 'Seleccioná un propietario válido.';
                    continue;
                }

                $filters[$field] = (int) $value;
            } else {
                if (
                    preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $value) !== 1
                    || ! is_finite((float) $value)
                    || (float) $value <= 0
                ) {
                    $errors[$field] = 'Ingresá un precio mayor a cero con hasta dos decimales.';
                    continue;
                }

                $filters[$field] = (float) $value;
            }
        }

        if (
            isset($filters['min_price'], $filters['max_price'])
            && (float) $filters['min_price'] > (float) $filters['max_price']
        ) {
            $errors['max_price'] = 'El precio máximo debe ser igual o mayor al precio mínimo.';
            unset($filters['min_price'], $filters['max_price']);
        }

        return ['filters' => $filters, 'values' => $values, 'errors' => $errors];
    }
}
