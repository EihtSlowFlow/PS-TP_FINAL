---
name: php-security
description: Use this skill when implementing or reviewing authentication, authorization, CSRF, XSS, SQL injection prevention, sessions, secrets, uploads, or security-sensitive PHP behavior.
---
# Name: PHP and Application Security

## Description
Use this skill for security-sensitive changes across CI4, PHP, browser-facing output, database access, and deployment configuration.

## Universal Rules (Mandatory)
- Deny by default: validate inputs, authorize every protected action server-side, and grant only the minimum access required.
- Never concatenate untrusted values into SQL or shell commands.
- Use framework-provided protections (CI4 validation, CSRF filters, session/authentication mechanisms) before designing custom equivalents.
- Encode output for its context; HTML escaping does not make data safe for JavaScript, URLs, or SQL.
- Never commit secrets or reveal stack traces, credentials, SQL, or filesystem paths to production users.

## Guidelines & Patterns
- Use password hashing/verification APIs; regenerate session identifiers after authentication when managing sessions.
- Configure `HttpOnly`, `Secure`, and `SameSite` according to deployment and threat model.
- Validate upload size, detected type, allowed extension, generated storage name, destination, and authorization; store uploads outside executable/public paths where possible.
- Check CSRF protection and HTTP verbs for state-changing requests; do not assume every method is covered.
- Keep diagnostics in protected logs and return safe, actionable messages to users.

## Code Example (Minimal and Atomic)
```php
<?php if ($user !== null): ?>
    <p><?= esc($user['name']) ?></p>
<?php else: ?>
    <p>User unavailable</p>
<?php endif; ?>
```
