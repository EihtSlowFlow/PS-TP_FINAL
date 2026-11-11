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

    // Property management: listing, creation and edition (HU-07; the listing gets filters in HU-08)
    $routes->get('inmuebles', 'PropertyController::index');
    $routes->get('inmuebles/nuevo', 'PropertyController::create');
    $routes->post('inmuebles', 'PropertyController::store');
    $routes->get('inmuebles/editar/(:num)', 'PropertyController::edit/$1');
    $routes->post('inmuebles/editar/(:num)', 'PropertyController::update/$1');

    // Lease contracts: creation and contract file (HU-10; the listing is HU-11 and the closing HU-12)
    $routes->get('contratos/nuevo', 'ContractController::create');
    $routes->post('contratos', 'ContractController::store');
    $routes->get('contratos/(:num)', 'ContractController::show/$1');
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
});

// Tenant portal routes (HU-01)
$routes->group('inquilino', ['filter' => ['auth', 'role:tenant']], static function (RouteCollection $routes): void {
    $routes->get('estado-cuenta', 'DashboardController::tenantAccountStatus');
});

// Personal profile routes for owners and tenants (HU-06)
$routes->group('perfil', ['filter' => ['auth', 'role:owner,tenant']], static function (RouteCollection $routes): void {
    $routes->get('/', 'ProfileController::show');
    $routes->post('contacto', 'ProfileController::updateContact');
    $routes->post('contrasena', 'ProfileController::changePassword');
});
