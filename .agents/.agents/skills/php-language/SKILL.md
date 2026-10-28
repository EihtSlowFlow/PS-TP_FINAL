---
name: php-language
description: Use this skill when writing standalone PHP 8.2+ code or CLI scripts outside CodeIgniter 4, or when a task is specifically about PHP language features and error handling.
---
# Name: PHP 8.2+ Language

## Description
Use this skill for PHP language-level changes that are not primarily governed by CI4 conventions. For normal CI4 application code, load `ci4-core` instead.

## Universal Rules (Mandatory)
- Check the actual PHP requirement in `composer.json` and deployment configuration before using version-specific syntax.
- Prefer strict, explicit types where they improve correctness; handle nullable values and return types intentionally.
- Use exceptions for exceptional failures and return values for expected outcomes; do not suppress errors silently.
- Avoid global state, hidden side effects, and large functions that mix input, business logic, persistence, and output.
- Reuse Composer autoloading and project dependencies; do not reinvent framework or library behavior.

## Guidelines & Patterns
- Use `declare(strict_types=1);` when consistent with project and call-site expectations; do not introduce it blindly into a mixed codebase.
- Prefer `DateTimeImmutable` for immutable date/time handling and explicit timezone assumptions.
- Use PHP password APIs for passwords and parameterized database APIs for SQL.
- Keep CLI scripts explicit about exit codes, output channels, and configuration; never assume CLI and web-server environments share the same variables.
- For framework-managed requests, validation, responses, and persistence, follow the framework skill instead of direct PHP primitives.

## Code Example (Minimal and Atomic)
```php
// Normalize an identifier
function normalizeId(string $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id === false ? null : $id;
}
```
