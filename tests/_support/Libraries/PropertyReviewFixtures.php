<?php

namespace Tests\Support\Libraries;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Fixtures shared by the tests of the owner's publication (HU-32) and of the administration's review (HU-33):
 * users with a role, properties in each review state, sessions and POSTs with a valid CSRF token.
 *
 * It is meant for a test class that uses `DatabaseTestTrait` and `FeatureTestTrait` and seeds the roles.
 */
trait PropertyReviewFixtures
{
    private int $fixtureSequence = 0;

    /**
     * Creates a user with the given role (unique e-mail and document number) and returns its id.
     */
    private function makeUser(string $roleName, ?string $deletedAt = null, string $firstName = 'Nombre', string $lastName = 'Apellido'): int
    {
        $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();
        $this->assertNotNull($role, "Role {$roleName} must exist");

        $this->fixtureSequence++;
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'role_id'         => $role->id,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'document_number' => (string) (20000000 + $this->fixtureSequence),
            'email'           => "{$roleName}.{$this->fixtureSequence}.review@example.test",
            'phone'           => '2994000000',
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'created_at'      => $now,
            'updated_at'      => $now,
            'deleted_at'      => $deletedAt,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * The session data the login of that user would store.
     *
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
     * Inserts a property straight into the table. Without overrides it is approved (the column default), "available"
     * and active.
     *
     * @param array<string, mixed> $overrides
     */
    private function makeProperty(int $ownerId, array $overrides = []): int
    {
        $this->db->table('properties')->insert(array_merge([
            'owner_id'          => $ownerId,
            'address'           => 'Mitre 845',
            'city'              => 'Cipolletti',
            'type'              => 'house',
            'rooms'             => 4,
            'surface'           => 120.50,
            'included_services' => 'Agua',
            'base_rent'         => 650000.00,
            'description'       => 'Casa con jardín',
            'status'            => 'available',
            'created_at'        => '2026-01-01 10:00:00',
            'updated_at'        => '2026-01-01 10:00:00',
        ], $overrides));

        return (int) $this->db->insertID();
    }

    /**
     * A property the owner published and that waits for the administration.
     *
     * @param array<string, mixed> $overrides
     */
    private function makePendingProperty(int $ownerId, array $overrides = []): int
    {
        return $this->makeProperty($ownerId, array_merge([
            'approval_status' => 'pending',
            'submitted_by'    => $ownerId,
        ], $overrides));
    }

    /**
     * A property the administration rejected with a reason.
     *
     * @param array<string, mixed> $overrides
     */
    private function makeRejectedProperty(int $ownerId, int $reviewerId, string $reason = 'Faltan las fotos y la dirección exacta.', array $overrides = []): int
    {
        return $this->makeProperty($ownerId, array_merge([
            'approval_status'  => 'rejected',
            'submitted_by'     => $ownerId,
            'rejection_reason' => $reason,
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => '2026-02-01 09:30:00',
        ], $overrides));
    }

    /**
     * Makes a property in the given review state ('approved', 'pending' or 'rejected').
     *
     * @param array<string, mixed> $overrides
     */
    private function makePropertyIn(string $approval, int $ownerId, int $reviewerId, array $overrides = []): int
    {
        return match ($approval) {
            'pending'  => $this->makePendingProperty($ownerId, $overrides),
            'rejected' => $this->makeRejectedProperty($ownerId, $reviewerId, 'Faltan las fotos y la dirección exacta.', $overrides),
            default    => $this->makeProperty($ownerId, $overrides),
        };
    }

    /**
     * A complete, valid property form submission (the owner's form has no owner field).
     *
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function validPropertyPayload(array $overrides = []): array
    {
        return array_merge([
            'address'           => 'Belgrano 1250, 4°B',
            'city'              => 'Cipolletti',
            'type'              => 'apartment',
            'rooms'             => '3',
            'surface'           => '54.5',
            'included_services' => ['Agua', 'Luz'],
            'base_rent'         => '420000',
            'description'       => 'Luminoso, con balcón',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyRow(int $propertyId): array
    {
        $row = $this->db->table('properties')->where('id', $propertyId)->get()->getRowArray();
        $this->assertNotNull($row, "Property {$propertyId} must exist");

        return $row;
    }

    /**
     * Sends a POST with the given session data plus a valid session CSRF token.
     *
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
     * Asserts that the request answers 404, whether it is the response or the exception the framework throws.
     *
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
