## Purpose

This skill governs backend development using PHP, MySQL/MariaDB, HTTP, sessions, and object-oriented programming.

The primary goals are:

* Clear separation of responsibilities.
* Secure request processing.
* Maintainable PHP code.
* Safe database access.
* Correct session handling.
* Server-side validation.
* Compatibility with traditional LAMP architectures.

---

## When to Use This Skill

Use this skill when working with:

* PHP files.
* HTTP requests.
* `$_GET` and `$_POST`.
* Sessions.
* Cookies.
* Authentication.
* Authorization.
* MySQL/MariaDB.
* CRUD operations.
* Server-side validation.
* PHP APIs.
* CodeIgniter or Laravel projects.

---

# 1. PHP Version

For new development:

> Target PHP 8.x.

PHP 7.x should only be used when an existing project explicitly requires PHP 7 compatibility.

Do not introduce syntax unavailable in the project's declared PHP version.

Before changing version-dependent code, inspect:

* Existing project configuration.
* `composer.json`.
* Server configuration.
* Deployment requirements.

---

# 2. Object-Oriented Programming

Prefer object-oriented PHP for application-level backend code.

Separate responsibilities between components such as:

```text
Controller
    ↓
Service / Business Logic
    ↓
Repository / Data Access
    ↓
Database
```

Do not force this architecture into a very small project when it would create unnecessary complexity.

Avoid:

* God classes.
* God methods.
* Global state.
* Business logic embedded directly in HTML.
* Database queries scattered throughout unrelated files.

---

# 2.1. Naming Conventions & Domain Language Standard (English with Spanish Comments)

All code identifiers must be written strictly and consistently in **English**. This prevents bilingual inconsistencies (e.g. having an entity named `Inmueble` alongside another named `User`).

This applies to:
* Database tables and columns (e.g., `properties`, `users`, `contracts`, `installments`, `payments`, `settlements`).
* Class and Model names (e.g., `Property`, `PropertyModel`, `UserController`).
* Functions and Methods (e.g., `calculateLateFee()`, `registerPayment()`, `approveSettlement()`).
* Variables, properties, and parameters (e.g., `$propertyId`, `$delayDays`, `$amount`).

### Mandatory Spanish Annotation Rule
Above every entity class, model, controller, function, or method declared in English, there **MUST** be a preceding comment containing the corresponding concept or function name in **Spanish**, formatted as follows:

```php
// inmueble
class Property extends Model
{
    // calcular mora
    public function calculateLateFee(float $amount, int $delayDays): float
    {
        // ...
    }
}
```

```php
// registrar pago
function registerPayment(int $installmentId, float $amount): bool
{
    // ...
}
```

### Domain Terminology Mapping (InmoGestión)
| Concepto en Español | English Identifier in Code | Database Table / Entity |
| :--- | :--- | :--- |
| Usuario / Persona | User | `users` / `UserModel` |
| Inmueble / Propiedad | Property | `properties` / `PropertyModel` |
| Contrato de Alquiler | Contract / Lease | `contracts` / `ContractModel` |
| Cuota de Alquiler | Installment | `installments` / `InstallmentModel` |
| Pago / Comprobante | Payment / Receipt | `payments` / `PaymentModel` |
| Liquidación a Propietario | Settlement | `settlements` / `SettlementModel` |
| Notificación / Alerta | Notification | `notifications` / `NotificationModel` |
| Roles (Admin / Propietario / Inquilino) | admin / owner / tenant | `roles` (`name` column) |

---

# 3. HTTP Request Handling

Treat all incoming request data as untrusted.

Potential input sources include:

```php
$_GET
$_POST
$_COOKIE
$_FILES
$_SERVER
```

Validate and normalize data before using it.

Do not assume that browser-side validation was performed.

---

# 4. Server-side Validation

Every important input must be validated on the server.

Validation should consider:

* Required fields.
* Data types.
* Length.
* Format.
* Allowed values.
* Business rules.
* Authorization.
* Relationships between fields.

Example:

```php
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if ($email === false || $email === null) {
    // Handle validation error.
}
```

Client-side validation is not sufficient.

---

# 5. Database Access

Use MySQL or MariaDB through a safe database abstraction such as PDO or the project's established database layer.

Use prepared statements.

Never construct SQL by directly concatenating untrusted input.

Unsafe:

```php
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

Preferred:

```php
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE id = :id'
);

$stmt->execute([
    'id' => $id
]);
```

Database errors must not expose sensitive implementation details to users in production.

---

# 6. Sessions

When using sessions:

* Start sessions consistently.
* Store only necessary information.
* Do not store passwords in sessions.
* Regenerate session identifiers after authentication.
* Validate authorization server-side.
* Configure secure cookie attributes when appropriate.

Consider:

```text
HttpOnly
Secure
SameSite
```

according to the deployment environment.

---

# 7. Authentication and Authorization

Authentication answers:

> Who is the user?

Authorization answers:

> What is the user allowed to do?

Do not treat them as the same mechanism.

Passwords must never be stored in plaintext.

Use PHP's password APIs:

```php
password_hash()
password_verify()
```

Authorization checks must occur on the server.

Never rely solely on hidden buttons or frontend restrictions to protect privileged operations.

---

# 8. Output Handling

Escape data according to its output context.

For HTML output, use appropriate encoding such as:

```php
htmlspecialchars()
```

Do not assume that data retrieved from a database is safe merely because it came from the database.

Database data can contain previously stored malicious content.

---

# 9. Application Structure

When appropriate, separate:

```text
Presentation
Business Logic
Persistence
Configuration
```

Do not mix large amounts of SQL, HTML, validation, authentication, and business logic inside a single PHP file.

For small scripts, avoid unnecessary architectural abstraction.

The architecture must match the project's actual complexity.

---

# 10. Frameworks

CodeIgniter and Laravel are allowed when:

* The existing project already uses them.
* The developer explicitly requests them.
* The project requirements justify them.

Do not introduce a framework into a small Vanilla PHP project merely to solve a localized problem.

When working inside a framework project, follow the framework's established conventions rather than imposing a generic Vanilla PHP architecture.

---

# 11. Error Handling

Separate:

```text
Developer diagnostics
User-facing errors
```

Development environments may provide detailed diagnostics.

Production environments must avoid exposing:

* Stack traces.
* SQL queries.
* File paths.
* Credentials.
* Internal configuration.
* Sensitive debugging information.

Log useful diagnostic information securely instead.

---

# 12. Environment Configuration

Do not hardcode:

* Database passwords.
* API keys.
* Application secrets.
* Production credentials.

Use environment-specific configuration.

Development, testing, and production must be treated as different environments.

---

# 13. Definition of Done

Backend work is complete when:

* All external input is treated as untrusted.
* Server-side validation is implemented.
* Database access uses safe parameterization.
* Authentication and authorization are separated.
* Sensitive information is protected.
* Business logic is appropriately separated.
* Errors do not expose sensitive information.
* The implementation follows the project's PHP version.
* No unnecessary framework or dependency was introduced.
