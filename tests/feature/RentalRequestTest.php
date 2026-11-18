<?php

use App\Controllers\RentalRequestController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\PropertyModel;
use App\Models\RentalRequestModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acceptance and security tests for HU-30: tenant rental requests.
 *
 * Covers Scenarios 1 to 7 of issue #55, model-level transaction locks, authorization by role and ownership (404),
 * and the empty account statement entry points.
 *
 * @internal
 */
final class RentalRequestTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY = '2026-11-17';

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION       = [];
        $this->sequence = 0;
        Time::setTestNow(self::TODAY . ' 10:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: Submitting a valid rental request on an available property
    // ---------------------------------------------------------------------------------------------

    public function testScenario1TenantSubmitsRequestForAvailablePropertyAndPropertyStaysAvailable(): void
    {
        $ownerId    = $this->makeUser('owner', null, 'Propietario', 'Reservado');
        $tenantId   = $this->makeUser('tenant', null, 'Lucía', 'Fernández', '+54 9 299 411-2233');
        $otherId    = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId, [
            'address'           => 'Av. Argentina 450, 3°A',
            'city'              => 'Neuquén',
            'type'              => 'apartment',
            'rooms'             => 3,
            'surface'           => 68.50,
            'included_services' => 'Agua, expensas',
            'base_rent'         => 650000.00,
        ]);

        // Public catalog offers the "Solicitar alquiler" call to action for the available property.
        $catalog = $this->withSession($session)->get('catalogo-inmuebles');
        $catalog->assertOK();
        $catalog->assertSee('Av. Argentina 450, 3°A');
        $catalog->assertSee(site_url("inquilino/inmuebles/{$propertyId}/solicitar"));

        // Opening the form prefills the tenant's phone and today's date, and hides internal owner data.
        $form = $this->withSession($session)->get("inquilino/inmuebles/{$propertyId}/solicitar");
        $form->assertOK();
        $form->assertSee('Solicitar alquiler');
        $form->assertSee('Av. Argentina 450, 3°A');
        $form->assertSee('Neuquén');
        $form->assertSee('$ 650.000');
        $form->assertSee('+54 9 299 411-2233');
        $form->assertSee(self::TODAY);
        $form->assertDontSee('Propietario Reservado');

        // Submitting the form registers the request as "pending" using the session tenant_id (ignoring spoofed fields).
        $response = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", [
            'desired_start_date' => '2026-12-01',
            'desired_months'     => '24',
            'contact_phone'      => '+54 9 299 455-6677',
            'message'            => 'Trabajo en relación de dependencia y cuento con recibos de sueldo.',
            'tenant_id'          => (string) $otherId,
            'property_id'        => '999999',
            'status'             => 'approved',
        ]);

        $response->assertRedirectTo('/inquilino/solicitudes');
        $this->assertSame(RentalRequestController::REQUEST_CREATED_MESSAGE, session()->getFlashdata('success'));

        $rows = $this->db->table('rental_requests')->get()->getResultArray();
        $this->assertCount(1, $rows);

        $saved = $rows[0];
        $this->assertSame($propertyId, (int) $saved['property_id']);
        $this->assertSame($tenantId, (int) $saved['tenant_id'], 'tenant_id must come from the session, never from POST.');
        $this->assertSame('2026-12-01', $saved['desired_start_date']);
        $this->assertSame(24, (int) $saved['desired_months']);
        $this->assertSame('+54 9 299 455-6677', $saved['contact_phone']);
        $this->assertSame('Trabajo en relación de dependencia y cuento con recibos de sueldo.', $saved['message']);
        $this->assertSame(RentalRequestModel::STATUS_PENDING, $saved['status']);
        $this->assertNull($saved['rejection_reason']);
        $this->assertNull($saved['resolved_by']);
        $this->assertNull($saved['resolved_at']);
        $this->assertNull($saved['contract_id']);

        // Business rule: requesting a property does NOT reserve it or alter its status.
        $property = $this->db->table('properties')->where('id', $propertyId)->get()->getRowArray();
        $this->assertSame(PropertyModel::STATUS_AVAILABLE, $property['status']);

        // The pending request is visible to the administration tray.
        $requestModel = model(RentalRequestModel::class);
        $this->assertSame(1, $requestModel->countPending());
        $adminQueue = $requestModel->findPendingQueueForAdmin();
        $this->assertCount(1, $adminQueue);
        $this->assertSame((int) $saved['id'], (int) $adminQueue[0]['id']);
    }

    public function testScenario1OptionalMessageCanBeOmittedAndIsStoredAsNull(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        $response = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", [
            'desired_start_date' => self::TODAY,
            'desired_months'     => '12',
            'contact_phone'      => '2994123456',
            'message'            => '   ',
        ]);

        $response->assertRedirectTo('/inquilino/solicitudes');

        $saved = $this->db->table('rental_requests')->where('tenant_id', $tenantId)->get()->getRowArray();
        $this->assertNotNull($saved);
        $this->assertNull($saved['message']);
        $this->assertSame(self::TODAY, $saved['desired_start_date']);
        $this->assertSame(12, (int) $saved['desired_months']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: Invalid data is rejected and field errors are reported
    // ---------------------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{array<string, string>, string, string}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'empty start date' => [
            ['desired_start_date' => ''],
            'desired_start_date',
            'Ingresá la fecha de inicio deseada.',
        ];

        yield 'malformed start date' => [
            ['desired_start_date' => '2026-02-30'],
            'desired_start_date',
            'La fecha de inicio deseada no es válida.',
        ];

        yield 'past start date' => [
            ['desired_start_date' => '2026-11-16'],
            'desired_start_date',
            'La fecha de inicio deseada no puede ser anterior a hoy.',
        ];

        yield 'empty desired months' => [
            ['desired_months' => ''],
            'desired_months',
            'Ingresá el plazo deseado en meses.',
        ];

        yield 'zero desired months' => [
            ['desired_months' => '0'],
            'desired_months',
            'El plazo deseado debe ser un número entero mayor a cero.',
        ];

        yield 'negative desired months' => [
            ['desired_months' => '-12'],
            'desired_months',
            'El plazo deseado debe ser un número entero mayor a cero.',
        ];

        yield 'decimal desired months' => [
            ['desired_months' => '12.5'],
            'desired_months',
            'El plazo deseado debe ser un número entero mayor a cero.',
        ];

        yield 'non-numeric desired months' => [
            ['desired_months' => 'veinticuatro'],
            'desired_months',
            'El plazo deseado debe ser un número entero mayor a cero.',
        ];

        yield 'desired months above 255' => [
            ['desired_months' => '256'],
            'desired_months',
            'El plazo deseado no puede superar los 255 meses.',
        ];

        yield 'empty contact phone' => [
            ['contact_phone' => ''],
            'contact_phone',
            'Ingresá un teléfono de contacto.',
        ];

        yield 'contact phone with letters' => [
            ['contact_phone' => '299-400-ABCD'],
            'contact_phone',
            'Ingresá un teléfono válido con entre 6 y 15 dígitos.',
        ];

        yield 'contact phone too short' => [
            ['contact_phone' => '12345'],
            'contact_phone',
            'Ingresá un teléfono válido con entre 6 y 15 dígitos.',
        ];

        yield 'contact phone exceeding 30 characters' => [
            ['contact_phone' => '+' . str_repeat('1 ', 16)],
            'contact_phone',
            'El teléfono no puede superar los 30 caracteres.',
        ];

        yield 'message exceeding 500 characters' => [
            ['message' => str_repeat('a', 501)],
            'message',
            'El mensaje no puede superar los 500 caracteres.',
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testScenario2InvalidDataIsRejectedAndEveryFieldErrorIsReported(
        array $overrides,
        string $field,
        string $expectedErrorFragment
    ): void {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        $payload = array_merge($this->validRequestPayload(), $overrides);

        $response = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $payload);

        $response->assertRedirectTo("/inquilino/inmuebles/{$propertyId}/solicitar");
        $this->assertSame(RentalRequestController::VALIDATION_ERROR_MESSAGE, session()->getFlashdata('error'));

        $errors = session()->getFlashdata('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey($field, $errors);
        $this->assertStringContainsString($expectedErrorFragment, (string) $errors[$field]);
        $this->assertSame(0, $this->db->table('rental_requests')->countAllResults());
    }

    public function testScenario2SeveralErrorsAreReportedTogetherAndTypedValuesAreKept(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        $response = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", [
            'desired_start_date' => '2025-01-01',
            'desired_months'     => '0',
            'contact_phone'      => 'invalido',
            'message'            => 'Mensaje escrito por el inquilino',
        ]);

        $response->assertRedirectTo("/inquilino/inmuebles/{$propertyId}/solicitar");

        $this->assertEqualsCanonicalizing(
            ['desired_start_date', 'desired_months', 'contact_phone'],
            array_keys(session()->getFlashdata('errors'))
        );

        $form = $this->withSession()->get("inquilino/inmuebles/{$propertyId}/solicitar");

        $form->assertOK();
        $form->assertSee('La fecha de inicio deseada no puede ser anterior a hoy.');
        $form->assertSee('El plazo deseado debe ser un número entero mayor a cero.');
        $form->assertSee('Ingresá un teléfono válido con entre 6 y 15 dígitos.');
        $form->assertSee('Mensaje escrito por el inquilino');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3: Duplicate pending request on the same property is rejected
    // ---------------------------------------------------------------------------------------------

    public function testScenario3DuplicatePendingRequestOnSamePropertyIsRejected(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        // First submission succeeds.
        $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/inquilino/solicitudes');
        $this->assertSame(1, $this->db->table('rental_requests')->countAllResults());

        // Opening the form warns that a pending request already exists.
        $form = $this->withSession($session)->get("inquilino/inmuebles/{$propertyId}/solicitar");
        $form->assertOK();
        $form->assertSee('Ya tenés una solicitud pendiente para este inmueble.');

        // Second submission is rejected by the controller.
        $second = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload([
            'desired_months' => '36',
        ]));
        $second->assertRedirectTo('/inquilino/solicitudes');
        $this->assertSame(RentalRequestController::DUPLICATE_PENDING_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(1, $this->db->table('rental_requests')->countAllResults());

        // And the model itself rejects a duplicate pending request under its transaction lock.
        $modelResult = model(RentalRequestModel::class)->createRequest($tenantId, $propertyId, $this->validRequestPayload());
        $this->assertFalse($modelResult['ok']);
        $this->assertSame(RentalRequestModel::ERROR_DUPLICATE_PENDING, $modelResult['error']);
        $this->assertSame(1, $this->db->table('rental_requests')->countAllResults());
    }

    public function testScenario3AnotherTenantCanRequestTheSamePropertyAndTenantCanReapplyAfterCancelledOrRejected(): void
    {
        $ownerId       = $this->makeUser('owner');
        $adminId       = $this->makeUser('admin');
        $firstTenant   = $this->makeUser('tenant');
        $secondTenant  = $this->makeUser('tenant');
        $propertyId    = $this->makeProperty($ownerId);

        // First tenant submits a request; second tenant can also submit one on the same property.
        $this->postWithSession($this->sessionOf($firstTenant, 'tenant'), "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/inquilino/solicitudes');
        $this->postWithSession($this->sessionOf($secondTenant, 'tenant'), "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/inquilino/solicitudes');

        $this->assertSame(2, model(RentalRequestModel::class)->countPending());

        // Once the first tenant cancels their pending request, they can submit a new one.
        $firstRequestId = (int) $this->db->table('rental_requests')
            ->where('tenant_id', $firstTenant)
            ->get()
            ->getRowArray()['id'];

        $this->postWithSession(
            $this->sessionOf($firstTenant, 'tenant'),
            "inquilino/solicitudes/{$firstRequestId}/cancelar",
            ['confirmed' => '1']
        )->assertRedirectTo('/inquilino/solicitudes');

        $this->postWithSession($this->sessionOf($firstTenant, 'tenant'), "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload([
            'desired_months' => '36',
        ]))->assertRedirectTo('/inquilino/solicitudes');

        // And if that request is rejected by the administration, the tenant can also apply again.
        $newRequestId = (int) $this->db->table('rental_requests')
            ->where('tenant_id', $firstTenant)
            ->where('status', RentalRequestModel::STATUS_PENDING)
            ->get()
            ->getRowArray()['id'];

        $this->db->table('rental_requests')->where('id', $newRequestId)->update([
            'status'           => RentalRequestModel::STATUS_REJECTED,
            'rejection_reason' => 'Faltaba confirmar fecha exacta.',
            'resolved_by'      => $adminId,
            'resolved_at'      => '2026-11-17 11:00:00',
        ]);

        $this->postWithSession($this->sessionOf($firstTenant, 'tenant'), "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/inquilino/solicitudes');

        $this->assertSame(3, $this->db->table('rental_requests')->where('tenant_id', $firstTenant)->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 4: Unavailable, deactivated or unapproved properties cannot be requested
    // ---------------------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function unavailablePropertyProvider(): iterable
    {
        yield 'rented property' => [
            ['status' => PropertyModel::STATUS_RENTED],
            false,
        ];

        yield 'property in maintenance' => [
            ['status' => PropertyModel::STATUS_MAINTENANCE],
            false,
        ];

        yield 'deactivated property' => [
            ['deleted_at' => '2026-11-10 10:00:00'],
            false,
        ];

        yield 'owner property pending approval (HU-32)' => [
            ['approval_status' => PropertyModel::APPROVAL_PENDING],
            false,
        ];

        yield 'owner property rejected by administration (HU-33)' => [
            [
                'approval_status'  => PropertyModel::APPROVAL_REJECTED,
                'rejection_reason' => 'Faltan datos del inmueble.',
            ],
            false,
        ];

        yield 'property marked available but with a contract in force' => [
            ['status' => PropertyModel::STATUS_AVAILABLE],
            true,
        ];
    }

    /**
     * @param array<string, mixed> $propertyOverrides
     */
    #[DataProvider('unavailablePropertyProvider')]
    public function testScenario4UnavailablePropertyIsRejectedOnGetPostAndModel(
        array $propertyOverrides,
        bool $withContractInForce
    ): void {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $otherId    = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId, $propertyOverrides);

        if ($withContractInForce) {
            $this->insertContract($propertyId, $otherId, $ownerId);
        }

        // GET redirects to catalog with the unavailability error.
        $getResponse = $this->withSession($session)->get("inquilino/inmuebles/{$propertyId}/solicitar");
        $getResponse->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        // POST redirects to catalog with the unavailability error and stores nothing.
        $postResponse = $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload());
        $postResponse->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('rental_requests')->countAllResults());

        // Model also refuses it under the transaction lock.
        $modelResult = model(RentalRequestModel::class)->createRequest($tenantId, $propertyId, $this->validRequestPayload());
        $this->assertFalse($modelResult['ok']);
        $this->assertSame(RentalRequestModel::ERROR_PROPERTY_UNAVAILABLE, $modelResult['error']);
    }

    public function testScenario4NonExistentPropertyIsRejected(): void
    {
        $tenantId = $this->makeUser('tenant');
        $session  = $this->sessionOf($tenantId, 'tenant');

        $this->withSession($session)
            ->get('inquilino/inmuebles/999999/solicitar')
            ->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, 'inquilino/inmuebles/999999/solicitar', $this->validRequestPayload())
            ->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
    }

    public function testScenario4PropertyBelongingToADeactivatedOwnerCannotBeRequested(): void
    {
        $inactiveOwner = $this->makeUser('owner', '2026-11-10 10:00:00');
        $tenantId      = $this->makeUser('tenant');
        $session       = $this->sessionOf($tenantId, 'tenant');
        $propertyId    = $this->makeProperty($inactiveOwner, ['address' => 'Casa De Propietario Inactivo']);

        $this->withSession($session)
            ->get("inquilino/inmuebles/{$propertyId}/solicitar")
            ->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/catalogo-inmuebles');
        $this->assertSame(RentalRequestController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('rental_requests')->countAllResults());

        $modelResult = model(RentalRequestModel::class)->createRequest($tenantId, $propertyId, $this->validRequestPayload());
        $this->assertFalse($modelResult['ok']);
        $this->assertSame(RentalRequestModel::ERROR_PROPERTY_UNAVAILABLE, $modelResult['error']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: Tracking requests in "Mis solicitudes"
    // ---------------------------------------------------------------------------------------------

    public function testScenario5MyRequestsShowsPropertyDateStatusRejectionReasonAndApprovedContractLink(): void
    {
        $ownerId     = $this->makeUser('owner', null, 'Dueño', 'Oculto');
        $adminId     = $this->makeUser('admin');
        $tenantId    = $this->makeUser('tenant');
        $otherTenant = $this->makeUser('tenant');
        $session     = $this->sessionOf($tenantId, 'tenant');

        $propPending   = $this->makeProperty($ownerId, ['address' => 'Belgrano 101']);
        $propApproved  = $this->makeProperty($ownerId, ['address' => 'San Martín 202', 'status' => 'rented']);
        $propRejected  = $this->makeProperty($ownerId, ['address' => 'Rivadavia 303']);
        $propCancelled = $this->makeProperty($ownerId, ['address' => 'Moreno 404']);
        $propOther     = $this->makeProperty($ownerId, ['address' => 'Inmueble Ajeno 999']);

        $contractId = $this->insertContract($propApproved, $tenantId, $ownerId);

        $pendingReqId = $this->insertRequest($propPending, $tenantId, [
            'desired_start_date' => '2026-12-01',
            'desired_months'     => 24,
            'status'             => RentalRequestModel::STATUS_PENDING,
            'created_at'         => '2026-11-14 09:00:00',
        ]);
        $this->insertRequest($propApproved, $tenantId, [
            'desired_start_date' => '2026-12-15',
            'desired_months'     => 36,
            'status'             => RentalRequestModel::STATUS_APPROVED,
            'resolved_by'        => $adminId,
            'resolved_at'        => '2026-11-15 11:00:00',
            'contract_id'        => $contractId,
            'created_at'         => '2026-11-15 10:00:00',
        ]);
        $this->insertRequest($propRejected, $tenantId, [
            'desired_start_date' => '2027-01-01',
            'desired_months'     => 12,
            'status'             => RentalRequestModel::STATUS_REJECTED,
            'rejection_reason'   => 'Se priorizó una propuesta con garantías ya presentadas.',
            'resolved_by'        => $adminId,
            'resolved_at'        => '2026-11-16 14:00:00',
            'created_at'         => '2026-11-16 12:00:00',
        ]);
        $cancelledReqId = $this->insertRequest($propCancelled, $tenantId, [
            'desired_start_date' => '2027-02-01',
            'desired_months'     => 24,
            'status'             => RentalRequestModel::STATUS_CANCELLED,
            'created_at'         => '2026-11-17 08:00:00',
        ]);
        $this->insertRequest($propOther, $otherTenant, [
            'status' => RentalRequestModel::STATUS_PENDING,
        ]);

        $response = $this->withSession($session)->get('inquilino/solicitudes');

        $response->assertOK();
        $response->assertSee('4 solicitudes');
        $response->assertSee('Belgrano 101');
        $response->assertSee('San Martín 202');
        $response->assertSee('Rivadavia 303');
        $response->assertSee('Moreno 404');
        $response->assertSee('14/11/2026');
        $response->assertSee('15/11/2026');
        $response->assertSee('16/11/2026');
        $response->assertSee('17/11/2026');
        $response->assertSee('Pendiente');
        $response->assertSee('Aprobada');
        $response->assertSee('Rechazada');
        $response->assertSee('Cancelada');
        $response->assertSee('Se priorizó una propuesta con garantías ya presentadas.');
        $response->assertSee(site_url("inquilino/contratos/{$contractId}"));
        $response->assertSee('Ver contrato');
        $response->assertSee(site_url("inquilino/solicitudes/{$pendingReqId}/cancelar"));
        $response->assertDontSee(site_url("inquilino/solicitudes/{$cancelledReqId}/cancelar"));

        // Never leaks another tenant's requests or the owner's personal identity.
        $response->assertDontSee('Inmueble Ajeno 999');
        $response->assertDontSee('Dueño Oculto');
    }

    public function testScenario5EmptyStateInMyRequestsOffersLinkToCatalog(): void
    {
        $tenantId = $this->makeUser('tenant');
        $session  = $this->sessionOf($tenantId, 'tenant');

        $response = $this->withSession($session)->get('inquilino/solicitudes');

        $response->assertOK();
        $response->assertSee('Todavía no enviaste solicitudes de alquiler');
        $response->assertSee('Buscar inmuebles disponibles');
        $response->assertSee(site_url('catalogo-inmuebles'));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 6: Cancelling a pending request
    // ---------------------------------------------------------------------------------------------

    public function testScenario6TenantCancelsPendingRequestWithConfirmationAndItLeavesAdminQueue(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId, ['address' => 'Sarmiento 780']);
        $requestId  = $this->insertRequest($propertyId, $tenantId, [
            'status' => RentalRequestModel::STATUS_PENDING,
        ]);

        $this->assertSame(1, model(RentalRequestModel::class)->countPending());

        // Step 1: unconfirmed POST renders the server-side confirmation page without changing the status.
        foreach ([[], ['confirmed' => '0'], ['confirmed' => 'no']] as $unconfirmed) {
            $confirm = $this->postWithSession($session, "inquilino/solicitudes/{$requestId}/cancelar", $unconfirmed);
            $confirm->assertOK();
            $confirm->assertSee('¿Cancelás tu solicitud de alquiler?');
            $confirm->assertSee('Sarmiento 780');
            $confirm->assertSee('Sí, cancelar solicitud');

            $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
            $this->assertSame(RentalRequestModel::STATUS_PENDING, $row['status']);
        }

        // Step 2: confirmed POST cancels the request and removes it from the administration tray.
        $cancel = $this->postWithSession($session, "inquilino/solicitudes/{$requestId}/cancelar", ['confirmed' => '1']);
        $cancel->assertRedirectTo('/inquilino/solicitudes');
        $this->assertSame(RentalRequestController::REQUEST_CANCELLED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_CANCELLED, $row['status']);
        $this->assertSame(0, model(RentalRequestModel::class)->countPending());
        $this->assertSame([], model(RentalRequestModel::class)->findPendingQueueForAdmin());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonPendingStatusProvider(): iterable
    {
        yield 'approved'  => [RentalRequestModel::STATUS_APPROVED];
        yield 'rejected'  => [RentalRequestModel::STATUS_REJECTED];
        yield 'cancelled' => [RentalRequestModel::STATUS_CANCELLED];
    }

    #[DataProvider('nonPendingStatusProvider')]
    public function testScenario6NonPendingRequestCannotBeCancelled(string $status): void
    {
        $ownerId    = $this->makeUser('owner');
        $adminId    = $this->makeUser('admin');
        $tenantId   = $this->makeUser('tenant');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        $requestId = $this->insertRequest($propertyId, $tenantId, [
            'status'           => $status,
            'rejection_reason' => $status === RentalRequestModel::STATUS_REJECTED ? 'Motivo previo.' : null,
            'resolved_by'      => $status !== RentalRequestModel::STATUS_CANCELLED ? $adminId : null,
            'resolved_at'      => $status !== RentalRequestModel::STATUS_CANCELLED ? '2026-11-17 09:00:00' : null,
        ]);

        foreach (['0', '1'] as $confirmed) {
            $response = $this->postWithSession($session, "inquilino/solicitudes/{$requestId}/cancelar", ['confirmed' => $confirmed]);
            $response->assertRedirectTo('/inquilino/solicitudes');
            $this->assertSame(RentalRequestController::CANCEL_NOT_PENDING_MESSAGE, session()->getFlashdata('error'));
        }

        $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
        $this->assertSame($status, $row['status']);
    }

    public function testScenario6AnotherTenantsOrMissingRequestAnswers404(): void
    {
        $ownerId     = $this->makeUser('owner');
        $tenantId    = $this->makeUser('tenant');
        $otherTenant = $this->makeUser('tenant');
        $session     = $this->sessionOf($tenantId, 'tenant');
        $propertyId  = $this->makeProperty($ownerId);
        $otherReqId  = $this->insertRequest($propertyId, $otherTenant, [
            'status' => RentalRequestModel::STATUS_PENDING,
        ]);

        foreach (['0', '1'] as $confirmed) {
            $this->assertAnswers404(
                fn () => $this->postWithSession($session, "inquilino/solicitudes/{$otherReqId}/cancelar", ['confirmed' => $confirmed]),
                "other tenant request confirmed={$confirmed}"
            );
            $this->assertAnswers404(
                fn () => $this->postWithSession($session, 'inquilino/solicitudes/999999/cancelar', ['confirmed' => $confirmed]),
                "missing request confirmed={$confirmed}"
            );
        }

        $row = $this->db->table('rental_requests')->where('id', $otherReqId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_PENDING, $row['status']);
    }

    public function testCancelRouteAcceptsPostOnly(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);
        $requestId  = $this->insertRequest($propertyId, $tenantId);

        $this->assertAnswers404(
            fn () => $this->withSession($this->sessionOf($tenantId, 'tenant'))->get("inquilino/solicitudes/{$requestId}/cancelar")
        );
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 7: Empty account statement offers links to catalog and "Mis solicitudes"
    // ---------------------------------------------------------------------------------------------

    public function testScenario7EmptyAccountStatementOffersSearchPropertiesAndMyRequestsLinks(): void
    {
        $tenantId = $this->makeUser('tenant');
        $session  = $this->sessionOf($tenantId, 'tenant');

        $response = $this->withSession($session)->get('inquilino/estado-cuenta');

        $response->assertOK();
        $response->assertSee('Todavía no tenés cuotas emitidas');
        $response->assertSee('Buscar inmuebles');
        $response->assertSee('Mis solicitudes');
        $response->assertSee(site_url('catalogo-inmuebles'));
        $response->assertSee(site_url('inquilino/solicitudes'));
    }

    // ---------------------------------------------------------------------------------------------
    // Role, authentication and active-account guards
    // ---------------------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonTenantRoleProvider(): iterable
    {
        yield 'admin'    => ['admin', '/admin/dashboard'];
        yield 'employee' => ['employee', '/admin/dashboard'];
        yield 'owner'    => ['owner', '/propietario/dashboard'];
    }

    #[DataProvider('nonTenantRoleProvider')]
    public function testOnlyTenantsCanAccessRentalRequestRoutes(string $roleName, string $ownPanel): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makeProperty($ownerId);
        $userId     = $this->makeUser($roleName);
        $session    = $this->sessionOf($userId, $roleName);

        $this->withSession($session)
            ->get('inquilino/solicitudes')
            ->assertRedirectTo($ownPanel);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $this->withSession($session)
            ->get("inquilino/inmuebles/{$propertyId}/solicitar")
            ->assertRedirectTo($ownPanel);

        $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo($ownPanel);

        $this->assertSame(0, $this->db->table('rental_requests')->countAllResults());
    }

    public function testVisitorsAreRedirectedToLoginOnAllRentalRequestRoutes(): void
    {
        $ownerId    = $this->makeUser('owner');
        $propertyId = $this->makeProperty($ownerId);

        $this->get('inquilino/solicitudes')->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));

        $this->get("inquilino/inmuebles/{$propertyId}/solicitar")->assertRedirectTo('/auth/login');
        $this->postWithSession([], "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/auth/login');
        $this->postWithSession([], 'inquilino/solicitudes/1/cancelar', ['confirmed' => '1'])
            ->assertRedirectTo('/auth/login');
    }

    public function testDeactivatedTenantIsRejectedEvenWithAnOpenSession(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant', '2026-11-15 10:00:00');
        $session    = $this->sessionOf($tenantId, 'tenant');
        $propertyId = $this->makeProperty($ownerId);

        $this->postWithSession($session, "inquilino/inmuebles/{$propertyId}/solicitar", $this->validRequestPayload())
            ->assertRedirectTo('/auth/login');

        $modelResult = model(RentalRequestModel::class)->createRequest($tenantId, $propertyId, $this->validRequestPayload());
        $this->assertFalse($modelResult['ok']);
        $this->assertSame(RentalRequestModel::ERROR_INACTIVE_TENANT, $modelResult['error']);
        $this->assertSame(0, $this->db->table('rental_requests')->countAllResults());
    }

    // ---------------------------------------------------------------------------------------------
    // Fixtures
    // ---------------------------------------------------------------------------------------------

    private function makeUser(
        string $roleName,
        ?string $deletedAt = null,
        string $firstName = 'Nombre',
        string $lastName = 'Apellido',
        string $phone = '2994000000'
    ): int {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->sequence++;
        $now = '2026-11-01 10:00:00';

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (35000000 + $this->sequence),
            'email'           => "{$roleName}.{$this->sequence}.hu30@example.test",
            'phone'           => $phone,
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @return array{is_logged_in: bool, user_id: int, user_email: string, user_name: string, user_role: string}
     */
    private function sessionOf(int $userId, string $roleName): array
    {
        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_email'   => "user{$userId}@example.test",
            'user_name'    => 'Usuario ' . ucfirst($roleName),
            'user_role'    => $roleName,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert(array_merge([
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => 3,
            'surface'           => 110.00,
            'included_services' => 'Agua',
            'base_rent'         => 550000.00,
            'description'       => 'Casa luminosa con patio',
            'status'            => PropertyModel::STATUS_AVAILABLE,
            'approval_status'   => PropertyModel::APPROVAL_APPROVED,
            'created_at'        => '2026-11-01 10:00:00',
            'updated_at'        => '2026-11-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertRequest(int $propertyId, int $tenantId, array $overrides = []): int
    {
        $this->db->table('rental_requests')->insert(array_merge([
            'property_id'        => $propertyId,
            'tenant_id'          => $tenantId,
            'desired_start_date' => '2026-12-01',
            'desired_months'     => 24,
            'contact_phone'      => '+54 9 299 412-3456',
            'message'            => 'Interesado en visitar el inmueble.',
            'status'             => RentalRequestModel::STATUS_PENDING,
            'rejection_reason'   => null,
            'resolved_by'        => null,
            'resolved_at'        => null,
            'contract_id'        => null,
            'created_at'         => '2026-11-17 09:00:00',
            'updated_at'         => '2026-11-17 09:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    private function insertContract(int $propertyId, int $tenantId, int $ownerId): int
    {
        $this->db->table('contracts')->insert([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'owner_id'                  => $ownerId,
            'start_date'                => '2026-01-01',
            'end_date'                  => '2027-12-31',
            'monthly_rent'              => 550000.00,
            'update_percentage'         => 10.00,
            'due_day'                   => 10,
            'commission_percentage'     => 8.00,
            'late_fee_daily_percentage' => 0.50,
            'status'                    => 'in_force',
            'created_at'                => '2026-01-01 10:00:00',
            'updated_at'                => '2026-01-01 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function validRequestPayload(array $overrides = []): array
    {
        return array_merge([
            'desired_start_date' => '2026-12-01',
            'desired_months'     => '24',
            'contact_phone'      => '+54 9 299 412-3456',
            'message'            => 'Quisiera alquilar a partir de diciembre.',
        ], $overrides);
    }

    /**
     * @param array<string, mixed>  $sessionData
     * @param array<string, string> $postData
     */
    private function postWithSession(array $sessionData, string $uri, array $postData = [])
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();

        return $this->withSession(array_merge($sessionData, [$tokenName => $tokenHash]))
            ->post($uri, array_merge($postData, [$tokenName => $tokenHash]));
    }

    /**
     * @param callable(): mixed $request
     */
    private function assertAnswers404(callable $request, string $message = ''): void
    {
        try {
            $result = $request();
            $this->assertSame(404, $result->response()->getStatusCode(), $message);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }
}
