<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('catalogo-inmuebles', 'PropertyController::publicIndex');

// Authentication routes (HU-01, HU-02)
$routes->group('auth', static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::attemptLogin');
    $routes->get('register', 'AuthController::register');
    $routes->post('register', 'AuthController::storeRegistration');
    // HU-03b: public recovery flow. State changes remain POST-only and use the global CSRF filter.
    $routes->get('recuperar-password', 'PasswordResetController::requestForm');
    $routes->post('recuperar-password', 'PasswordResetController::sendLink');
    $routes->get('restablecer-password/(:segment)', 'PasswordResetController::resetForm/$1');
    $routes->post('restablecer-password/(:segment)', 'PasswordResetController::updatePassword/$1');
    // HU-03: POST only (with the global CSRF filter) so a link or an image cannot log a user out.
    $routes->post('logout', 'AuthController::logout');
});

// Administration & Employee operational routes (HU-01 / R-7)
$routes->group('admin', ['filter' => ['auth', 'role:admin,employee']], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::adminDashboard');

    // Property management: listing, creation, edition and logical deactivation (HU-07, HU-08, HU-09)
    $routes->get('inmuebles', 'PropertyController::index');
    $routes->get('inmuebles/nuevo', 'PropertyController::create');
    $routes->post('inmuebles', 'PropertyController::store');
    $routes->get('inmuebles/editar/(:num)', 'PropertyController::edit/$1');
    $routes->post('inmuebles/editar/(:num)', 'PropertyController::update/$1');
    $routes->post('inmuebles/baja/(:num)', 'PropertyController::deactivate/$1');

    // Lease contracts: creation, listing and contract file (HU-10/11; closing is HU-12)
    $routes->get('contratos', 'ContractController::index');
    $routes->get('contratos/nuevo', 'ContractController::create');
    $routes->post('contratos', 'ContractController::store');
    $routes->post('contratos/(:num)/finalizar', 'ContractController::finalize/$1');
    $routes->post('contratos/(:num)/rescindir', 'ContractController::rescind/$1');
    $routes->post('contratos/(:num)/cerrar', 'ContractController::close/$1');
    $routes->get('contratos/(:num)', 'ContractController::show/$1');

    // Monthly installment issuing: period and preview, confirmation and report (HU-13)
    $routes->get('cuotas/emitir', 'InstallmentController::emit');
    $routes->post('cuotas/emitir', 'InstallmentController::issue');
    $routes->get('cuotas/emitir/resultado', 'InstallmentController::result');

    // Collections: installments with their automatic late fee, and the detail of one (HU-14)
    $routes->get('cuotas', 'InstallmentController::index');
    $routes->get('cuotas/(:num)', 'InstallmentController::show/$1');

    // Payment review queue and resolution (HU-17)
    $routes->get('pagos', 'PaymentController::index');
    $routes->get('pagos/pendientes', 'PaymentController::index');
    $routes->get('pagos/historial', 'PaymentController::history');
    $routes->get('pagos/(:num)', 'PaymentController::show/$1');
    $routes->post('pagos/(:num)/aprobacion', 'PaymentController::resolve/$1');
    $routes->post('pagos/(:num)/rechazo', 'PaymentController::reject/$1');
});

// Exclusive Administrator routes: User management and logical deactivation (HU-04, HU-05)
$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function (RouteCollection $routes): void {
    $routes->get('usuarios', 'UserController::index');
    $routes->get('usuarios/nuevo', 'UserController::create');
    $routes->post('usuarios', 'UserController::store');
    $routes->get('usuarios/editar/(:num)', 'UserController::edit/$1');
    $routes->post('usuarios/editar/(:num)', 'UserController::update/$1');
    $routes->post('usuarios/baja/(:num)', 'UserController::deactivate/$1');
});

// Owner portal routes (HU-01)
$routes->group('propietario', ['filter' => ['auth', 'role:owner']], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::ownerDashboard');
    $routes->get('inmuebles', 'PropertyController::ownerIndex');
    $routes->get('contratos', 'ContractController::ownerIndex');
    $routes->get('contratos/(:num)', 'ContractController::ownerShow/$1');
});

// Tenant portal routes (HU-01, HU-16, HU-18)
$routes->group('inquilino', ['filter' => ['auth', 'role:tenant']], static function (RouteCollection $routes): void {
    $routes->get('estado-cuenta', 'DashboardController::tenantAccountStatus');
    $routes->get('mis-pagos', 'PaymentController::tenantHistory');
    $routes->get('cuotas/(:num)/informar-pago', 'PaymentController::create/$1');
    $routes->post('cuotas/(:num)/informar-pago', 'PaymentController::store/$1');
});

// Protected receipt download/preview for owning tenant or admin/employee (HU-16)
$routes->get('comprobantes/(:num)', 'PaymentController::receipt/$1', ['filter' => 'auth']);

// Personal profile routes for owners and tenants (HU-06)
$routes->group('perfil', ['filter' => ['auth', 'role:owner,tenant']], static function (RouteCollection $routes): void {
    $routes->get('/', 'ProfileController::show');
    $routes->post('contacto', 'ProfileController::updateContact');
    $routes->post('contrasena', 'ProfileController::changePassword');
});
