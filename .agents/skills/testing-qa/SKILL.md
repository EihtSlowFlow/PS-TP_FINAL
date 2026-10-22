# Testing & QA Skill

## Purpose

This skill governs validation, testing, regression prevention, and quality assurance across the application.

---

## When to Use This Skill

Use this skill when:

* Adding new functionality.
* Modifying existing behavior.
* Implementing forms.
* Changing validation.
* Fixing bugs.
* Modifying database behavior.
* Changing authentication or authorization.
* Refactoring existing code.

---

# 1. Validation Layers

Applications should validate data at the appropriate layers:

```text
HTML
  ↓
Client-side JavaScript
  ↓
HTTP request
  ↓
Server-side PHP
  ↓
Business rules
  ↓
Database constraints
```

Each layer has a different purpose.

Client-side validation improves user experience.

Server-side validation protects application integrity.

Database constraints protect persistent data integrity.

No layer should be assumed to replace another when the protection belongs to that layer.

---

# 2. Test Cases

For every non-trivial feature, consider:

### Valid input

Expected input should produce the expected result.

### Invalid input

Malformed or unacceptable input should be rejected safely.

### Boundary input

Test values at relevant limits.

Examples:

```text
minimum
maximum
empty
zero
negative
maximum length
one character beyond maximum
```

### Unexpected input

Consider:

* Missing fields.
* Additional fields.
* Incorrect data types.
* Invalid identifiers.
* Malformed requests.
* Repeated requests.

---

# 3. Regression Testing

When fixing a bug:

1. Identify the original failure.
2. Reproduce it when possible.
3. Implement the smallest appropriate fix.
4. Verify the original failure no longer occurs.
5. Verify related functionality still works.

Do not assume that a successful manual test proves that unrelated behavior was not affected.

---

# 4. Error Reporting

Errors should be:

* Reproducible when possible.
* Descriptive.
* Actionable.
* Appropriate for their audience.

Separate technical diagnostics from user-facing messages.

Do not expose sensitive internal information to users.

---

# 5. Quality Checklist

Before considering a feature complete, verify:

* Normal behavior.
* Invalid input.
* Boundary conditions.
* Error handling.
* Accessibility.
* Responsive behavior where applicable.
* Authentication/authorization where applicable.
* Database behavior where applicable.
* Regression risks.

---

# Security Skill

## Purpose

Security is a cross-cutting concern affecting frontend, backend, database, HTTP, sessions, and infrastructure.

Security must be considered during design and implementation rather than added only after development.

---

# 1. Trust Boundary

Treat all externally supplied data as untrusted.

This includes:

```text
URL parameters
Form fields
HTTP headers
Cookies
Uploaded files
JSON requests
Browser-controlled values
Database content
External API responses
```

Validate data before using it in security-sensitive operations.

---

# 2. SQL Injection

Never construct SQL queries using direct concatenation of untrusted input.

Use prepared statements or the framework's safe query mechanisms.

---

# 3. Cross-Site Scripting

Prevent XSS by:

* Validating input where appropriate.
* Encoding output according to context.
* Avoiding unsafe DOM APIs when unnecessary.
* Treating database content as untrusted.

For HTML output in PHP, use appropriate escaping such as:

```php
htmlspecialchars()
```

---

# 4. Cross-Site Request Forgery

State-changing authenticated operations should consider CSRF protection.

Use framework-provided CSRF protection when available.

For custom implementations, use:

* Server-generated tokens.
* Session-bound validation.
* Appropriate token verification.

Do not rely solely on obscurity or frontend checks.

---

# 5. Authentication

Use secure password hashing:

```php
password_hash()
password_verify()
```

Never store plaintext passwords.

After successful authentication, regenerate the session identifier.

---

# 6. Authorization

Every protected server-side operation must verify authorization.

Never rely solely on:

* Hidden buttons.
* Disabled inputs.
* Frontend routes.
* JavaScript checks.
* Client-side roles.

The server is responsible for enforcing permissions.

---

# 7. Sessions and Cookies

When appropriate, configure cookies with:

```text
HttpOnly
Secure
SameSite
```

Use secure session practices.

Do not place sensitive credentials or unnecessary personal information in client-accessible storage.

---

# 8. File Uploads

File uploads must be treated as untrusted.

Consider:

* File size.
* MIME type.
* Extension.
* Generated filenames.
* Storage location.
* Executable file prevention.
* Path traversal.
* Access permissions.

Never trust the filename or MIME type supplied by the client alone.

---

# 9. Secrets

Never commit:

* Passwords.
* API keys.
* Private tokens.
* Database credentials.
* Application secrets.

Use environment configuration or an appropriate secret-management mechanism.

---

# 10. Error Disclosure

Production responses must not expose:

* Stack traces.
* SQL queries.
* Absolute filesystem paths.
* Credentials.
* Internal server configuration.
* Debugging information.

Log diagnostic information securely instead.

---

# 11. Security Definition of Done

Before completing a security-sensitive feature, verify:

* Inputs are treated as untrusted.
* Authentication is enforced where required.
* Authorization is enforced server-side.
* Database queries are parameterized.
* Output is safely encoded.
* CSRF protection is considered.
* Sessions are protected.
* Secrets are not hardcoded.
* File uploads are safely handled when applicable.
* Production errors do not expose internal details.

---

# 12. Naming Conventions for Tests & Fixtures

All test classes, test methods, assertions, and test fixtures must be written in **English**.

Each test method must be preceded by a single-line comment stating the test's intent in **Spanish**, formatted as:

```php
// verificar cálculo de mora con 5 días de retraso
public function testCalculateLateFeeWithFiveDaysDelay(): void
{
    // ...
}

// verificar rechazo de comprobante con formato inválido
public function testRejectInvalidReceiptMimeType(): void
{
    // ...
}
```

