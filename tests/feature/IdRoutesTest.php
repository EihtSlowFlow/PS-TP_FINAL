<?php

use App\Database\Seeds\RoleSeeder;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * General review: the routes whose action types the id as `int` must answer 404 when the id does not fit a 64-bit
 * integer. Before the "(:id)" placeholder they reached the action and ended in a TypeError (HTTP 500).
 *
 * @internal
 */
final class IdRoutesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = RoleSeeder::class;

    private const TWENTY_DIGITS = '12345678901234567890';

    /** The smallest number PHP cannot hold as an integer (PHP_INT_MAX + 1). */
    private const NINETEEN_DIGITS_OVERFLOW = '9223372036854775808';

    private const EIGHTEEN_DIGITS = '999999999999999999';

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: list<array{0: string, 1: string}>}> role and its {method, URI with an {id} marker} pairs
     */
    public static function intIdRoutesByRole(): array
    {
        return [
            'administrator' => ['admin', [
                ['GET', 'admin/inmuebles/editar/{id}'],
                ['POST', 'admin/inmuebles/editar/{id}'],
                ['POST', 'admin/inmuebles/baja/{id}'],
                ['POST', 'admin/inmuebles/{id}/aprobacion'],
                ['POST', 'admin/inmuebles/{id}/rechazo'],
                ['GET', 'admin/solicitudes/{id}'],
                ['POST', 'admin/solicitudes/{id}/rechazo'],
                ['GET', 'admin/contratos/{id}'],
                ['POST', 'admin/contratos/{id}/finalizar'],
                ['POST', 'admin/contratos/{id}/rescindir'],
                ['POST', 'admin/contratos/{id}/cerrar'],
                ['GET', 'admin/usuarios/editar/{id}'],
                ['POST', 'admin/usuarios/editar/{id}'],
                ['POST', 'admin/usuarios/baja/{id}'],
            ]],
            'owner' => ['owner', [
                ['GET', 'propietario/inmuebles/editar/{id}'],
                ['POST', 'propietario/inmuebles/editar/{id}'],
                ['POST', 'propietario/inmuebles/baja/{id}'],
                ['GET', 'propietario/contratos/{id}'],
            ]],
            'tenant' => ['tenant', [
                ['GET', 'inquilino/inmuebles/{id}/solicitar'],
                ['POST', 'inquilino/inmuebles/{id}/solicitar'],
                ['POST', 'inquilino/solicitudes/{id}/cancelar'],
            ]],
        ];
    }

    /**
     * @param list<array{0: string, 1: string}> $routes
     */
    #[DataProvider('intIdRoutesByRole')]
    public function testAnIdThatDoesNotFitAnIntegerAnswersNotFound(string $role, array $routes): void
    {
        $session = $this->createSession($role);

        foreach ($routes as [$method, $uriTemplate]) {
            foreach ([self::TWENTY_DIGITS, self::NINETEEN_DIGITS_OVERFLOW] as $id) {
                $uri = str_replace('{id}', $id, $uriTemplate);

                $this->assertSame(404, $this->statusOf($session, $method, $uri), "{$method} {$uri} must answer 404");
            }
        }
    }

    /**
     * @param list<array{0: string, 1: string}> $routes
     */
    #[DataProvider('intIdRoutesByRole')]
    public function testAnEighteenDigitIdStillReachesTheAction(string $role, array $routes): void
    {
        $session = $this->createSession($role);

        foreach ($routes as [$method, $uriTemplate]) {
            $uri    = str_replace('{id}', self::EIGHTEEN_DIGITS, $uriTemplate);
            $status = $this->statusOf($session, $method, $uri);

            // The action runs and answers about a record that does not exist: a redirect with a message, or the
            // 404 that the owner and tenant actions raise themselves. What must never happen is a TypeError (500).
            $this->assertContains($status, [302, 404], "{$method} {$uri} answered {$status}");
        }
    }

    /**
     * @param array<string, mixed> $session
     */
    private function statusOf(array $session, string $method, string $uri): int
    {
        $tokenName = csrf_token();
        $tokenHash = csrf_hash();
        $session   = array_merge($session, [$tokenName => $tokenHash]);

        try {
            $request  = $this->withSession($session);
            $response = $method === 'POST'
                ? $request->post($uri, [$tokenName => $tokenHash])
                : $request->get($uri);
        } catch (PageNotFoundException) {
            return 404;
        }

        return $response->response()->getStatusCode();
    }

    /**
     * @return array<string, mixed>
     */
    private function createSession(string $roleName): array
    {
        $roleId = (int) $this->db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'];
        $email  = $roleName . '@idroutes.example.test';

        $this->db->table('users')->insert([
            'role_id'         => $roleId,
            'first_name'      => ucfirst($roleName),
            'last_name'       => 'Rutas',
            'document_number' => '38000001',
            'email'           => $email,
            'phone'           => '3794000001',
            'address'         => 'Dirección de prueba 123',
            'password_hash'   => password_hash('Secret123!', PASSWORD_BCRYPT),
            'created_at'      => '2026-11-01 10:00:00',
            'updated_at'      => '2026-11-01 10:00:00',
        ]);
        $userId = (int) $this->db->insertID();

        return [
            'is_logged_in' => true,
            'user_id'      => $userId,
            'user_role'    => $roleName,
            'user_name'    => ucfirst($roleName) . ' Rutas',
            'user_email'   => $email,
        ];
    }
}
