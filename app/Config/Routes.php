<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Authentication routes (HU-01)
$routes->group('auth', static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::attemptLogin');
});

// Administration & Employee operational routes (HU-01 / R-7)
$routes->group('admin', ['filter' => ['auth', 'role:admin,employee']], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::adminDashboard');
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
});

// Tenant portal routes (HU-01)
$routes->group('inquilino', ['filter' => ['auth', 'role:tenant']], static function (RouteCollection $routes): void {
    $routes->get('estado-cuenta', 'DashboardController::tenantAccountStatus');
});
