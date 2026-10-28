---
name: ci4-http-validation
description: Use this skill when processing CodeIgniter 4 form submissions, JSON requests, server-side validation, redirects, or HTTP responses.
---
# Name: CodeIgniter 4 HTTP and Validation

## Description
Use this skill when handling request data, validating fields, returning errors, or implementing form/API responses in CI4.

## Universal Rules (Mandatory)
- Treat every request value as untrusted, including IDs, headers, cookies, JSON, and uploaded files.
- Read request data through CI4's Request API; do not default to direct `$_GET` / `$_POST` access inside controllers.
- Validate on the server regardless of browser-side validation.
- Return only data intended for the current response; do not trust client-supplied ownership, role, or permission fields.
- Escape dynamic output in views with CI4's contextual escaping helpers.

## Guidelines & Patterns
- Define explicit validation rules for required fields, type/format, length, allowed values, and business constraints.
- Reuse the project's validation language/messages and error-display conventions.
- Distinguish validation failures (user-correctable) from unexpected server/database errors.
- For JSON endpoints, validate the decoded structure and return suitable status codes and a consistent response shape.

## Code Example (Minimal and Atomic)
```php
// Validate form input
if (! $this->validate(['email' => 'required|valid_email'])) {
    return redirect()->back()->withInput()
        ->with('errors', $this->validator->getErrors());
}
```
