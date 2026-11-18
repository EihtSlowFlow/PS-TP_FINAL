<?php

use App\Controllers\ContractController;
use App\Controllers\RentalRequestController;
use App\Database\Seeds\RoleSeeder;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;
use App\Models\ContractModel;
use App\Models\PropertyModel;
use App\Models\RentalRequestModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Acceptance, atomicity and authorization tests for HU-31: administrative management of rental requests and atomic
 * conversion into lease contracts (issue #56).
 *
 * @internal
 */
final class RentalRequestAdminTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TODAY            = '2026-11-17';
    private const BLOCKING_TRIGGER = 'trg_hu31_block_request_resolution';

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
        $this->db->query('DROP TRIGGER IF EXISTS ' . self::BLOCKING_TRIGGER);
        Time::setTestNow();
        $_SESSION = [];

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 1: Administrative tray, pending-first ordering, menu badge, filters and search
    // ---------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function operatorRoleProvider(): array
    {
        return [
            'admin'    => ['admin'],
            'employee' => ['employee'],
        ];
    }

    #[DataProvider('operatorRoleProvider')]
    public function testScenario1AdminAndEmployeeSeeTrayWithPendingFirstSidebarCounterFilterAndSearch(string $roleName): void
    {
        $operatorId = $this->makeUser($roleName);
        $session    = $this->sessionOf($operatorId, $roleName);
        $ownerId    = $this->makeUser('owner');

        $tApproved  = $this->makeUser('tenant', null, 'Andrés', 'Aprobado');
        $tRejected  = $this->makeUser('tenant', null, 'Rocío', 'Rechazada');
        $tCancelled = $this->makeUser('tenant', null, 'Carlos', 'Cancelado');
        $tPending   = $this->makeUser('tenant', null, 'Paula', 'Pendiente');

        $prop1 = $this->makeProperty($ownerId, ['address' => 'Inmueble Uno 111', 'city' => 'Neuquén']);
        $prop2 = $this->makeProperty($ownerId, ['address' => 'Inmueble Dos 222', 'city' => 'Cipolletti']);

        $this->insertRequest($prop1, $tApproved, [
            'status'      => RentalRequestModel::STATUS_APPROVED,
            'resolved_by' => $operatorId,
            'resolved_at' => '2026-11-15 10:00:00',
        ]);
        $this->insertRequest($prop1, $tRejected, [
            'status'           => RentalRequestModel::STATUS_REJECTED,
            'rejection_reason' => 'Ingresos insuficientes.',
            'resolved_by'      => $operatorId,
            'resolved_at'      => '2026-11-16 10:00:00',
        ]);
        $this->insertRequest($prop1, $tCancelled, [
            'status' => RentalRequestModel::STATUS_CANCELLED,
        ]);
        $pendingId = $this->insertRequest($prop2, $tPending, [
            'desired_start_date' => '2026-12-01',
            'desired_months'     => 24,
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);

        $response = $this->withSession($session)->get('admin/solicitudes');

        $response->assertOK();
        $response->assertSee('Solicitudes de alquiler');
        $response->assertSeeElement('#pending-requests-badge');
        $response->assertSee('Pendiente, Paula');
        $response->assertSee('Aprobado, Andrés');
        $response->assertSee('Rechazada, Rocío');
        $response->assertSee('Cancelado, Carlos');

        // Pending requests appear before resolved or cancelled ones.
        $body = (string) $response->getBody();
        $this->assertLessThan(
            strpos($body, 'Aprobado, Andrés'),
            strpos($body, 'Pendiente, Paula'),
            'Pending requests must be listed first.'
        );
        $this->assertLessThan(
            strpos($body, 'Cancelado, Carlos'),
            strpos($body, 'Pendiente, Paula')
        );
        $response->assertSee(site_url("admin/contratos/nuevo?request_id={$pendingId}"));

        // Filtering by status=pending shows only the pending request.
        $filtered = $this->withSession($session)->get('admin/solicitudes?status=pending');
        $filtered->assertOK();
        $filtered->assertSee('Pendiente, Paula');
        $filtered->assertDontSee('Aprobado, Andrés');
        $filtered->assertDontSee('Rechazada, Rocío');
        $filtered->assertDontSee('Cancelado, Carlos');

        // Searching by property or tenant narrows the results.
        $searched = $this->withSession($session)->get('admin/solicitudes?q=Inmueble+Dos');
        $searched->assertOK();
        $searched->assertSee('Pendiente, Paula');
        $searched->assertDontSee('Aprobado, Andrés');

        // Invalid status filter reports an error.
        $invalid = $this->withSession($session)->get('admin/solicitudes?status=inexistente');
        $invalid->assertOK();
        $invalid->assertSee('Seleccioná un estado válido.');
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 2: Request file screen
    // ---------------------------------------------------------------------------------------------

    public function testScenario2RequestFileShowsTenantPropertyOwnerPriceMessageAndCompetingPendingRequests(): void
    {
        $adminId      = $this->makeUser('admin');
        $session      = $this->sessionOf($adminId, 'admin');
        $ownerId      = $this->makeUser('owner', null, 'Héctor', 'Dueño');
        $tenant1      = $this->makeUser('tenant', null, 'Sofía', 'Interesada', '2994111111');
        $tenant2      = $this->makeUser('tenant', null, 'Martín', 'Competidor', '2994222222');
        $tenant3      = $this->makeUser('tenant', null, 'Laura', 'Cancelada', '2994333333');
        $propertyId   = $this->makeProperty($ownerId, [
            'address'   => 'Roca 550',
            'city'      => 'Cipolletti',
            'type'      => 'house',
            'rooms'     => 4,
            'base_rent' => 720000.00,
        ]);

        $req1 = $this->insertRequest($propertyId, $tenant1, [
            'desired_start_date' => '2026-12-01',
            'desired_months'     => 24,
            'contact_phone'      => '+54 9 299 411-1111',
            'message'            => 'Cuento con dos garantes con recibo de sueldo.',
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);
        $req2 = $this->insertRequest($propertyId, $tenant2, [
            'desired_start_date' => '2026-12-15',
            'desired_months'     => 36,
            'contact_phone'      => '+54 9 299 422-2222',
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);
        $this->insertRequest($propertyId, $tenant3, [
            'status' => RentalRequestModel::STATUS_CANCELLED,
        ]);

        $file = $this->withSession($session)->get("admin/solicitudes/{$req1}");

        $file->assertOK();
        $file->assertSee("Solicitud #{$req1}");
        $file->assertSee('Interesada, Sofía');
        $file->assertSee('+54 9 299 411-1111');
        $file->assertSee('Cuento con dos garantes con recibo de sueldo.');
        $file->assertSee('Roca 550');
        $file->assertSee('Cipolletti');
        $file->assertSee('$ 720.000');
        $file->assertSee('Dueño, Héctor');
        $file->assertSee('Competidor, Martín');
        $file->assertSee(site_url("admin/solicitudes/{$req2}"));
        $file->assertDontSee('Cancelada, Laura');
        $file->assertSee(site_url("admin/contratos/nuevo?request_id={$req1}"));
        $file->assertSee(site_url("admin/solicitudes/{$req1}/rechazo"));
    }

    public function testScenario2MissingRequestRedirectsToTrayWithError(): void
    {
        $session = $this->sessionOf($this->makeUser('admin'), 'admin');

        $this->withSession($session)
            ->get('admin/solicitudes/999999')
            ->assertRedirectTo('/admin/solicitudes');
        $this->assertSame(RentalRequestController::REQUEST_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 3 & Scenario 4: Approving, prefilling HU-10 form, atomic creation & auto-rejecting others
    // ---------------------------------------------------------------------------------------------

    #[DataProvider('operatorRoleProvider')]
    public function testScenario3And4ApprovingOpensPreselectedContractFormLinksContractAtomicallyAndAutoRejectsCompetingRequests(
        string $roleName
    ): void {
        $operatorId  = $this->makeUser($roleName, null, 'Operador', 'Revisor');
        $session     = $this->sessionOf($operatorId, $roleName);
        $ownerId     = $this->makeUser('owner', null, 'Marta', 'Propietaria');
        $winnerId    = $this->makeUser('tenant', null, 'Camila', 'Elegida');
        $otherId1    = $this->makeUser('tenant', null, 'Bruno', 'Segundo');
        $otherId2    = $this->makeUser('tenant', null, 'Elena', 'Tercera');
        $cancelledId = $this->makeUser('tenant', null, 'Diego', 'Retirado');
        $otherPropTenant = $this->makeUser('tenant', null, 'Franco', 'OtroInmueble');

        $propertyId      = $this->makeProperty($ownerId, ['address' => 'Belgrano 800', 'base_rent' => 600000.00]);
        $otherPropertyId = $this->makeProperty($ownerId, ['address' => 'Alem 900', 'base_rent' => 500000.00]);

        $winnerReqId = $this->insertRequest($propertyId, $winnerId, [
            'desired_start_date' => '2026-12-01',
            'desired_months'     => 24,
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);
        $competingReq1 = $this->insertRequest($propertyId, $otherId1, [
            'desired_start_date' => '2026-12-10',
            'desired_months'     => 12,
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);
        $competingReq2 = $this->insertRequest($propertyId, $otherId2, [
            'desired_start_date' => '2027-01-01',
            'desired_months'     => 36,
            'status'             => RentalRequestModel::STATUS_PENDING,
        ]);
        $cancelledReq = $this->insertRequest($propertyId, $cancelledId, [
            'status' => RentalRequestModel::STATUS_CANCELLED,
        ]);
        $otherPropReq = $this->insertRequest($otherPropertyId, $otherPropTenant, [
            'status' => RentalRequestModel::STATUS_PENDING,
        ]);

        // Step 1: Opening /admin/contratos/nuevo?request_id={winnerReqId} preselects property, tenant, rent and dates.
        $form = $this->withSession($session)->get("admin/contratos/nuevo?request_id={$winnerReqId}");
        $form->assertOK();
        $body = (string) $form->getBody();

        $this->assertMatchesRegularExpression('/<option\s+value="' . $propertyId . '"[^>]*\sselected/s', $body);
        $this->assertMatchesRegularExpression('/<option\s+value="' . $winnerId . '"[^>]*\sselected/s', $body);
        $this->assertStringContainsString('value="2026-12-01"', $body);
        $this->assertStringContainsString('value="2028-11-30"', $body);
        $this->assertStringContainsString('value="600000"', $body);
        $this->assertStringContainsString('name="request_id" value="' . $winnerReqId . '"', $body);
        $form->assertSee("Solicitud #{$winnerReqId}");

        // Step 2: Submitting the contract form saves the contract, marks the property rented, approves winnerReqId,
        // and rejects competingReq1 and competingReq2 with the mandatory automatic reason.
        $storeResponse = $this->postWithSession($session, 'admin/contratos', [
            'request_id'                => (string) $winnerReqId,
            'property_id'               => (string) $propertyId,
            'tenant_id'                 => (string) $winnerId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '600000',
            'update_percentage'         => '10',
            'commission_percentage'     => '8',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ]);

        $contract = $this->db->table('contracts')->where('property_id', $propertyId)->get()->getRowArray();
        $this->assertNotNull($contract);
        $contractId = (int) $contract['id'];

        $storeResponse->assertRedirectTo("/admin/contratos/{$contractId}");
        $this->assertSame(ContractController::CONTRACT_CREATED_MESSAGE, session()->getFlashdata('success'));

        // Property is now rented.
        $property = $this->db->table('properties')->where('id', $propertyId)->get()->getRowArray();
        $this->assertSame(PropertyModel::STATUS_RENTED, $property['status']);

        // Winner request is approved and linked to the contract with resolver and timestamp.
        $approvedRow = $this->db->table('rental_requests')->where('id', $winnerReqId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_APPROVED, $approvedRow['status']);
        $this->assertSame($contractId, (int) $approvedRow['contract_id']);
        $this->assertSame($operatorId, (int) $approvedRow['resolved_by']);
        $this->assertNotNull($approvedRow['resolved_at']);
        $this->assertNull($approvedRow['rejection_reason']);

        // Competing pending requests on the same property are automatically rejected with the standard reason.
        foreach ([$competingReq1, $competingReq2] as $competingId) {
            $competingRow = $this->db->table('rental_requests')->where('id', $competingId)->get()->getRowArray();
            $this->assertSame(RentalRequestModel::STATUS_REJECTED, $competingRow['status']);
            $this->assertSame(RentalRequestModel::AUTO_REJECTION_REASON_PROPERTY_RENTED, $competingRow['rejection_reason']);
            $this->assertSame($operatorId, (int) $competingRow['resolved_by']);
            $this->assertNotNull($competingRow['resolved_at']);
            $this->assertNull($competingRow['contract_id']);
        }

        // Already cancelled requests on the same property and pending requests on other properties are untouched.
        $cancelledRow = $this->db->table('rental_requests')->where('id', $cancelledReq)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_CANCELLED, $cancelledRow['status']);

        $otherPropRow = $this->db->table('rental_requests')->where('id', $otherPropReq)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_PENDING, $otherPropRow['status']);

        // Both the winning tenant and the competing tenants see the updated statuses in "Mis solicitudes".
        $winnerPortal = $this->withSession($this->sessionOf($winnerId, 'tenant'))->get('inquilino/solicitudes');
        $winnerPortal->assertOK();
        $winnerPortal->assertSee('Aprobada');
        $winnerPortal->assertSee(site_url("inquilino/contratos/{$contractId}"));

        $competingPortal = $this->withSession($this->sessionOf($otherId1, 'tenant'))->get('inquilino/solicitudes');
        $competingPortal->assertOK();
        $competingPortal->assertSee('Rechazada');
        $competingPortal->assertSee(RentalRequestModel::AUTO_REJECTION_REASON_PROPERTY_RENTED);
    }

    public function testScenario3AtomicityRollsBackContractAndPropertyWhenRequestUpdateFails(): void
    {
        $ownerId     = $this->makeUser('owner');
        $tenantId    = $this->makeUser('tenant');
        $otherId     = $this->makeUser('tenant');
        $propertyId  = $this->makeProperty($ownerId);
        $requestId   = $this->insertRequest($propertyId, $tenantId);
        $competingId = $this->insertRequest($propertyId, $otherId);

        // Passing a non-existent resolver ID (999999) lets the contract insert and property update succeed first,
        // and then triggers a real FK constraint error (`fk_rental_requests_resolved_by`) when updating `rental_requests`.
        $result = model(ContractModel::class)->createAndRentProperty([
            'property_id'               => $propertyId,
            'tenant_id'                 => $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '550000.00',
            'update_percentage'         => '10.00',
            'commission_percentage'     => '8.00',
            'due_day'                   => 10,
            'late_fee_daily_percentage' => '0.50',
        ], $requestId, 999999);

        $this->assertNull($result['id']);
        $this->assertSame(ContractModel::ERROR_PERSISTENCE, $result['error']);

        // Nothing was persisted: no contract, property still available, both requests still pending.
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
        $property = $this->db->table('properties')->where('id', $propertyId)->get()->getRowArray();
        $this->assertSame(PropertyModel::STATUS_AVAILABLE, $property['status']);

        foreach ([$requestId, $competingId] as $id) {
            $row = $this->db->table('rental_requests')->where('id', $id)->get()->getRowArray();
            $this->assertSame(RentalRequestModel::STATUS_PENDING, $row['status']);
            $this->assertNull($row['contract_id']);
            $this->assertNull($row['resolved_by']);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 5: Rejecting a pending request with a mandatory reason
    // ---------------------------------------------------------------------------------------------

    #[DataProvider('operatorRoleProvider')]
    public function testScenario5RejectingPendingRequestStoresReasonAndTenantSeesItInMyRequests(string $roleName): void
    {
        $operatorId = $this->makeUser($roleName, null, 'Carla', 'Revisora');
        $session    = $this->sessionOf($operatorId, $roleName);
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId, ['address' => 'San Juan 320']);
        $requestId  = $this->insertRequest($propertyId, $tenantId);

        $reason   = 'No se pudo verificar la antigüedad laboral requerida para el contrato.';
        $response = $this->postWithSession($session, "admin/solicitudes/{$requestId}/rechazo", [
            'rejection_reason' => '  ' . $reason . '  ',
        ]);

        $response->assertRedirectTo("/admin/solicitudes/{$requestId}");
        $this->assertSame(RentalRequestController::REQUEST_REJECTED_MESSAGE, session()->getFlashdata('success'));

        $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_REJECTED, $row['status']);
        $this->assertSame($reason, $row['rejection_reason']);
        $this->assertSame($operatorId, (int) $row['resolved_by']);
        $this->assertNotNull($row['resolved_at']);

        // Admin file shows the resolution details and no longer offers the action buttons.
        $file = $this->withSession($session)->get("admin/solicitudes/{$requestId}");
        $file->assertOK();
        $file->assertSee('Rechazada');
        $file->assertSee($reason);
        $file->assertSee('Revisora, Carla');
        $file->assertDontSee('id="btn-approve-create-contract"');

        // Tenant sees the rejection and reason in "Mis solicitudes".
        $tenantView = $this->withSession($this->sessionOf($tenantId, 'tenant'))->get('inquilino/solicitudes');
        $tenantView->assertOK();
        $tenantView->assertSee('Rechazada');
        $tenantView->assertSee($reason);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidRejectionReasonProvider(): iterable
    {
        yield 'empty' => ['', RentalRequestController::REJECTION_REASON_REQUIRED_MESSAGE];
        yield 'whitespace only' => ['    ', RentalRequestController::REJECTION_REASON_REQUIRED_MESSAGE];
        yield '256 characters' => [str_repeat('x', 256), RentalRequestController::REJECTION_REASON_TOO_LONG_MESSAGE];
    }

    #[DataProvider('invalidRejectionReasonProvider')]
    public function testScenario5InvalidRejectionReasonIsRefusedAndRequestStaysPending(
        string $reason,
        string $expectedMessage
    ): void {
        $adminId    = $this->makeUser('admin');
        $session    = $this->sessionOf($adminId, 'admin');
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);
        $requestId  = $this->insertRequest($propertyId, $tenantId);

        $response = $this->postWithSession($session, "admin/solicitudes/{$requestId}/rechazo", [
            'rejection_reason' => $reason,
        ]);

        $response->assertRedirectTo("/admin/solicitudes/{$requestId}");
        $this->assertSame($expectedMessage, session()->getFlashdata('error'));

        $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_PENDING, $row['status']);
        $this->assertNull($row['rejection_reason']);
        $this->assertNull($row['resolved_by']);
    }

    public function testScenario5AlreadyResolvedOrCancelledRequestCannotBeRejectedAgain(): void
    {
        $adminId    = $this->makeUser('admin');
        $otherAdmin = $this->makeUser('admin');
        $session    = $this->sessionOf($otherAdmin, 'admin');
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);

        $rejectedId = $this->insertRequest($propertyId, $tenantId, [
            'status'           => RentalRequestModel::STATUS_REJECTED,
            'rejection_reason' => 'Motivo original.',
            'resolved_by'      => $adminId,
            'resolved_at'      => '2026-11-16 09:00:00',
        ]);

        $this->postWithSession($session, "admin/solicitudes/{$rejectedId}/rechazo", [
            'rejection_reason' => 'Intento de pisar el motivo.',
        ])->assertRedirectTo('/admin/solicitudes');
        $this->assertSame(RentalRequestController::ALREADY_RESOLVED_MESSAGE, session()->getFlashdata('error'));

        $row = $this->db->table('rental_requests')->where('id', $rejectedId)->get()->getRowArray();
        $this->assertSame('Motivo original.', $row['rejection_reason']);
        $this->assertSame($adminId, (int) $row['resolved_by']);
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 6: Request that can no longer be approved
    // ---------------------------------------------------------------------------------------------

    public function testScenario6CannotApproveWhenPropertyBecameUnavailableOrOwnerDeactivatedOrTenantDeactivated(): void
    {
        $adminId  = $this->makeUser('admin');
        $session  = $this->sessionOf($adminId, 'admin');
        $ownerId  = $this->makeUser('owner');
        $tenantId = $this->makeUser('tenant');

        // 1. Property switched to maintenance after the request was sent.
        $propMaintenance = $this->makeProperty($ownerId, ['status' => PropertyModel::STATUS_MAINTENANCE]);
        $reqMaintenance  = $this->insertRequest($propMaintenance, $tenantId);

        $this->withSession($session)
            ->get("admin/contratos/nuevo?request_id={$reqMaintenance}")
            ->assertRedirectTo("/admin/solicitudes/{$reqMaintenance}");
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        $this->postWithSession($session, 'admin/contratos', [
            'request_id'                => (string) $reqMaintenance,
            'property_id'               => (string) $propMaintenance,
            'tenant_id'                 => (string) $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '550000',
            'update_percentage'         => '10',
            'commission_percentage'     => '8',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ])->assertRedirectTo("/admin/contratos/nuevo?request_id={$reqMaintenance}");
        $this->assertSame(ContractController::PROPERTY_UNAVAILABLE_MESSAGE, session()->getFlashdata('error'));

        // 2. Tenant was deactivated after sending the request.
        $deactivatedTenant = $this->makeUser('tenant', '2026-11-17 08:00:00');
        $propAvailable     = $this->makeProperty($ownerId);
        $reqDeactivated    = $this->insertRequest($propAvailable, $deactivatedTenant);

        $this->withSession($session)
            ->get("admin/contratos/nuevo?request_id={$reqDeactivated}")
            ->assertRedirectTo("/admin/solicitudes/{$reqDeactivated}");
        $this->assertSame(ContractController::INVALID_TENANT_MESSAGE, session()->getFlashdata('error'));

        // 3. Tampering with property_id or tenant_id in POST when request_id is present is rejected.
        $otherProp   = $this->makeProperty($ownerId);
        $validReqId  = $this->insertRequest($propAvailable, $tenantId);

        $this->postWithSession($session, 'admin/contratos', [
            'request_id'                => (string) $validReqId,
            'property_id'               => (string) $otherProp,
            'tenant_id'                 => (string) $tenantId,
            'start_date'                => '2026-12-01',
            'end_date'                  => '2028-11-30',
            'monthly_rent'              => '550000',
            'update_percentage'         => '10',
            'commission_percentage'     => '8',
            'due_day'                   => '10',
            'late_fee_daily_percentage' => '0.5',
        ])->assertRedirectTo("/admin/contratos/nuevo?request_id={$validReqId}");
        $this->assertSame(ContractController::REQUEST_MISMATCH_MESSAGE, session()->getFlashdata('error'));
        $this->assertSame(0, $this->db->table('contracts')->countAllResults());
    }

    public function testScenario6NonPendingOrMissingRequestCannotBeApprovedIntoAContract(): void
    {
        $adminId    = $this->makeUser('admin');
        $session    = $this->sessionOf($adminId, 'admin');
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);
        $cancelled  = $this->insertRequest($propertyId, $tenantId, ['status' => RentalRequestModel::STATUS_CANCELLED]);

        $this->withSession($session)
            ->get("admin/contratos/nuevo?request_id={$cancelled}")
            ->assertRedirectTo('/admin/solicitudes');
        $this->assertSame(ContractController::REQUEST_NOT_PENDING_MESSAGE, session()->getFlashdata('error'));

        $this->withSession($session)
            ->get('admin/contratos/nuevo?request_id=999999')
            ->assertRedirectTo('/admin/solicitudes');
        $this->assertSame(ContractController::REQUEST_NOT_FOUND_MESSAGE, session()->getFlashdata('error'));
    }

    // ---------------------------------------------------------------------------------------------
    // Scenario 7: Restricted access for owners, tenants and visitors
    // ---------------------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonOperatorRoleProvider(): iterable
    {
        yield 'owner'  => ['owner', '/propietario/dashboard'];
        yield 'tenant' => ['tenant', '/inquilino/estado-cuenta'];
    }

    #[DataProvider('nonOperatorRoleProvider')]
    public function testScenario7OwnersAndTenantsAreDeniedAccessToAdminRequestRoutes(string $roleName, string $ownPanel): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);
        $requestId  = $this->insertRequest($propertyId, $tenantId);
        $userId     = $this->makeUser($roleName);
        $session    = $this->sessionOf($userId, $roleName);

        $this->withSession($session)->get('admin/solicitudes')->assertRedirectTo($ownPanel);
        $this->assertSame(RoleFilter::FORBIDDEN_ROLE_MESSAGE, session()->getFlashdata('error'));

        $this->withSession($session)->get("admin/solicitudes/{$requestId}")->assertRedirectTo($ownPanel);
        $this->postWithSession($session, "admin/solicitudes/{$requestId}/rechazo", ['rejection_reason' => 'No permitido'])
            ->assertRedirectTo($ownPanel);

        $row = $this->db->table('rental_requests')->where('id', $requestId)->get()->getRowArray();
        $this->assertSame(RentalRequestModel::STATUS_PENDING, $row['status']);
    }

    public function testScenario7VisitorsAreRedirectedToLoginAndRejectRouteAcceptsPostOnly(): void
    {
        $ownerId    = $this->makeUser('owner');
        $tenantId   = $this->makeUser('tenant');
        $propertyId = $this->makeProperty($ownerId);
        $requestId  = $this->insertRequest($propertyId, $tenantId);

        $this->get('admin/solicitudes')->assertRedirectTo('/auth/login');
        $this->assertSame(AuthFilter::AUTH_REQUIRED_MESSAGE, session()->getFlashdata('error'));

        $this->get("admin/solicitudes/{$requestId}")->assertRedirectTo('/auth/login');
        $this->postWithSession([], "admin/solicitudes/{$requestId}/rechazo", ['rejection_reason' => 'Motivo'])
            ->assertRedirectTo('/auth/login');

        // GET on the rejection route is not registered (404).
        $adminSession = $this->sessionOf($this->makeUser('admin'), 'admin');
        try {
            $res = $this->withSession($adminSession)->get("admin/solicitudes/{$requestId}/rechazo");
            $this->assertSame(404, $res->response()->getStatusCode());
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
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
            'document_number' => (string) (40000000 + $this->sequence),
            'email'           => "{$roleName}.{$this->sequence}.hu31@example.test",
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
}

