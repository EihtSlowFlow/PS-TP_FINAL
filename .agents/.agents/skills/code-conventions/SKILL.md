---
name: code-conventions
description: Use this skill when establishing or refactoring naming rules, comments, formatting, file organization, or cross-language conventions in this repository.
---
# Name: Code Conventions

## Description
Use this skill when code style or naming consistency is the task, or when a substantive refactor adds declarations that must match repository conventions.

## Universal Rules (Mandatory)
- Inspect nearby files and existing tooling before applying or changing conventions.
- Write identifiers (classes, methods, variables, functions, database tables, and columns) consistently in English.
- Write code comments and test-intent comments in English; keep them concise and focused on intent or non-obvious decisions.
- Do not narrate each line of straightforward code.
- Do not run formatters across unrelated files or introduce tools that are not already approved/configured.

## Guidelines & Patterns
- Follow language/framework naming conventions, including CI4 namespaces and class/file alignment.
- Prefer formatting already enforced by project configuration; inspect `.editorconfig`, PHP-CS-Fixer/Pint configuration, ESLint configuration, and Composer scripts before choosing commands.
- Keep domain vocabulary in `.docs/domain-glossary.md` only if applicable to this project; do not copy domain-specific terms into general skills.
- If a naming rule conflicts with a framework convention or existing public interface, report the conflict before broad renaming.

## Code Example (Minimal and Atomic)
```php
// Calculate a late fee
public function calculateLateFee(float $amount, int $delayDays): float
{
    return $amount * 0.01 * $delayDays;
}
```
