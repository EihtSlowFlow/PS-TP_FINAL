<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// "(:id)" is a database id that fits PHP's signed 64-bit integer (up to 18 digits). Unlike "(:num)" it does not match
// longer numbers, so a hand-edited URL answers 404 instead of a TypeError (HTTP 500) in the actions that type `$id`
// as `int`. Use it for every action with an `int` parameter; "(:num)" stays where the action casts the value itself.
$routes->addPlaceholder('id', '[0-9]{1,18}');

$routes->get('/', 'Home::index');
$routes->get('catalogo-inmuebles', 'PropertyController::publicIndex');
$routes->get('catalogo-inmuebles/(:num)', 'PropertyController::publicShow/$1');
// Photos of the properties: public once the property is in the catalog, otherwise only for its owner and the staff.
$routes->get('inmuebles/fotos/(:id)', 'PropertyImageController::show/$1');

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
    $routes->get('inmuebles/editar/(:id)', 'PropertyController::edit/$1');
    $routes->post('inmuebles/editar/(:id)', 'PropertyController::update/$1');
    $routes->post('inmuebles/baja/(:id)', 'PropertyController::deactivate/$1');
    // Review of the properties published by owners: POST only, behind the CSRF filter (HU-33)
    $routes->post('inmuebles/(:id)/aprobacion', 'PropertyController::approve/$1');
    $routes->post('inmuebles/(:id)/rechazo', 'PropertyController::reject/$1');

    // Tenant rental requests tray, file and rejection (HU-31; approval goes through contratos/nuevo?request_id=)
    $routes->get('solicitudes', 'RentalRequestController::adminIndex');
    $routes->get('solicitudes/(:id)', 'RentalRequestController::adminShow/$1');
    $routes->post('solicitudes/(:id)/rechazo', 'RentalRequestController::adminReject/$1');

    // Lease contracts: creation, listing and contract file (HU-10/11; closing is HU-12)
    $routes->get('contratos', 'ContractController::index');
    $routes->get('contratos/nuevo', 'ContractController::create');
    $routes->post('contratos', 'ContractController::store');
    $routes->post('contratos/(:id)/finalizar', 'ContractController::finalize/$1');
    $routes->post('contratos/(:id)/rescindir', 'ContractController::rescind/$1');
    $routes->post('contratos/(:id)/cerrar', 'ContractController::close/$1');
    $routes->get('contratos/(:id)', 'ContractController::show/$1');

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
    $routes->get('usuarios/editar/(:id)', 'UserController::edit/$1');
    $routes->post('usuarios/editar/(:id)', 'UserController::update/$1');
    $routes->post('usuarios/baja/(:id)', 'UserController::deactivate/$1');

    // Owner settlements: collected installments minus the agreed commission (HU-19). Administrator only, because they
    // move money and the employee's permissions are still to be defined (analysis, section 3).
    $routes->get('liquidaciones/generar', 'SettlementController::generate');
    $routes->post('liquidaciones/generar', 'SettlementController::store');
});

// Owner portal routes (HU-01, HU-32)
$routes->group('propietario', ['filter' => ['auth', 'role:owner']], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::ownerDashboard');
    $routes->get('inmuebles', 'PropertyController::ownerIndex');
    // The owner publishes a property and corrects it until the administration approves it (HU-32)
    $routes->get('inmuebles/nuevo', 'PropertyController::ownerCreate');
    $routes->post('inmuebles', 'PropertyController::ownerStore');
    $routes->get('inmuebles/editar/(:id)', 'PropertyController::ownerEdit/$1');
    $routes->post('inmuebles/editar/(:id)', 'PropertyController::ownerUpdate/$1');
    // Cancel the request of a pending property or take an approved one down (logical deactivation, HU-09 rules)
    $routes->post('inmuebles/baja/(:id)', 'PropertyController::ownerDeactivate/$1');
    $routes->get('contratos', 'ContractController::ownerIndex');
    $routes->get('contratos/(:id)', 'ContractController::ownerShow/$1');
});

// Tenant portal routes (HU-01, HU-16, HU-18, HU-30, HU-34)
$routes->group('inquilino', ['filter' => ['auth', 'role:tenant']], static function (RouteCollection $routes): void {
    $routes->get('estado-cuenta', 'DashboardController::tenantAccountStatus');
    // The tenant's own contracts: list and file, read only (HU-34)
    $routes->get('contratos', 'ContractController::tenantIndex');
    $routes->get('contratos/(:num)', 'ContractController::tenantShow/$1');
    $routes->get('mis-pagos', 'PaymentController::tenantHistory');
    $routes->get('cuotas/(:num)/informar-pago', 'PaymentController::create/$1');
    $routes->post('cuotas/(:num)/informar-pago', 'PaymentController::store/$1');
    // Rental requests: apply for an available property, track status and cancel pending requests (HU-30)
    $routes->get('solicitudes', 'RentalRequestController::index');
    $routes->get('inmuebles/(:id)/solicitar', 'RentalRequestController::create/$1');
    $routes->post('inmuebles/(:id)/solicitar', 'RentalRequestController::store/$1');
    $routes->post('solicitudes/(:id)/cancelar', 'RentalRequestController::cancel/$1');
});

// Protected receipt download/preview for owning tenant or admin/employee (HU-16)
$routes->get('comprobantes/(:num)', 'PaymentController::receipt/$1', ['filter' => 'auth']);

// Personal profile routes for owners and tenants (HU-06)
$routes->group('perfil', ['filter' => ['auth', 'role:owner,tenant']], static function (RouteCollection $routes): void {
    $routes->get('/', 'ProfileController::show');
    $routes->post('contacto', 'ProfileController::updateContact');
    $routes->post('contrasena', 'ProfileController::changePassword');
});

// In-app notifications center and read-state actions for every authenticated role (HU-22)
$routes->group('notificaciones', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'NotificationController::index');
    $routes->post('leer-todas', 'NotificationController::markAllRead');
    $routes->post('(:id)/leer', 'NotificationController::markRead/$1');
});
