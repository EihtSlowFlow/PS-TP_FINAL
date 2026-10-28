---
name: ci4-routing
description: Use this skill when defining CodeIgniter 4 routes, HTTP verbs, route groups, filters, or route-level access control.
---
# Name: CodeIgniter 4 Routing and Filters

## Description
Use this skill when changing `app/Config/Routes.php`, route groups, filters, auto-routing behavior, or HTTP-method handling.

## Universal Rules (Mandatory)
- Inspect existing route definitions and filters before changing routing behavior.
- Prefer explicit routes tied to the intended HTTP verb; do not enable auto-routing merely for convenience.
- Enforce authentication and authorization on the server, not by hiding UI controls.
- Protect state-changing requests with the configured CSRF mechanism.
- Do not use catch-all routes that shadow existing routes or accidentally broaden access.

## Guidelines & Patterns
- Keep route declarations small and aligned with controller responsibilities.
- Apply filters to the narrowest appropriate route/group; verify inheritance and exclusions.
- When using a generic route method for state changes, verify the request verb in the handler and confirm CSRF behavior for the installed CI4 version.
- After route changes, test allowed methods, rejected methods, unauthorized access, and 404 behavior.

## Code Example (Minimal and Atomic)
```php
// Show a user by ID
$routes->get('users/(:num)', 'Users::show/$1');

// Create a user
$routes->post('users', 'Users::create');
```
