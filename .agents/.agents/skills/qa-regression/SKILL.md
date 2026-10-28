---
name: qa-regression
description: Use this skill when diagnosing a bug, planning quality checks, assessing regression risk, or verifying a feature beyond writing an individual CI4 test.
---
# Name: Quality Assurance and Regression

## Description
Use this skill to define and run a proportionate verification plan for a change, bug fix, form, authorization rule, or database behavior.

## Universal Rules (Mandatory)
- Reproduce or document the reported failure before changing behavior when feasible.
- Test the expected path, invalid input, relevant boundaries, and failure modes.
- Verify the original defect is gone and related behavior still works.
- Never use production data or production credentials for tests.
- Report checks actually run separately from checks proposed or blocked by the environment.

## Guidelines & Patterns
- Match the test level to the risk: unit, integration, CI4 feature/HTTP, or manual smoke test.
- For forms, distinguish browser-side feedback, server validation, business rules, and database constraints.
- For access-control changes, test both allowed and denied identities/actions.
- For database changes, verify integrity, rollback/migration behavior, and query impact when relevant.
- Prefer existing repository scripts; do not add linters or test dependencies solely because a skill mentions them.

## Code Example (Minimal and Atomic)
```php
// Verify that all items contribute to the total
public function testTotalIncludesAllItems(): void
{
    $calculator = new TotalCalculator();
    $this->assertSame(30.0, $calculator->sum([10.0, 20.0]));
}
```
