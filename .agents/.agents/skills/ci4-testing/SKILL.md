---
name: ci4-testing
description: Use this skill when writing or changing PHPUnit tests for CodeIgniter 4 controllers, models, validation, database operations, or HTTP features.
---
# Name: CodeIgniter 4 Testing

## Description
Use this skill when creating or refactoring automated tests that exercise CI4 code or its request/database lifecycle.

## Universal Rules (Mandatory)
- Use the project's configured PHPUnit/CI4 test tooling; inspect `composer.json` and `phpunit.xml` first.
- Never run database tests against production or an unverified connection.
- Use `DatabaseTestTrait` when a test reads or changes database state; configure the dedicated testing database group.
- If overriding `setUp()` or `tearDown()`, call parent methods required by the CI4 test traits.
- Make tests deterministic; isolate state and clean up generated data.

## Guidelines & Patterns
- Mirror `app/` under `tests/app/` when that matches repository conventions; CI4 does not require one universal directory layout.
- Use `FeatureTestTrait` for HTTP request/response behavior and controller helpers when appropriate.
- Cover success, validation failure, authorization failure, boundaries, and persistence effects relevant to the behavior.
- Test behavior and public outcomes; avoid coupling assertions to private implementation details.
- Keep class, method, and intent-comment language consistent with `code-conventions`.

## Code Example (Minimal and Atomic)
```php
class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    public function testCreatesUserRecord(): void
    {
        $this->hasInDatabase('users', ['email' => 'a@example.test']);
    }
}
```
