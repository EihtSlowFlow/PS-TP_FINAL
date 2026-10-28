---
name: ci4-data-layer
description: Use this skill when implementing CodeIgniter 4 Models, Query Builder queries, transactions, migrations, seeds, or database-backed application behavior.
---
# Name: CodeIgniter 4 Data Layer

## Description
Use this skill for CI4 persistence work, including Model configuration, query construction, schema changes, and transactional operations.

## Universal Rules (Mandatory)
- Inspect current Models, migrations, schema, and database driver before writing queries.
- Use CI4 Models/Query Builder or parameterized database APIs; never concatenate untrusted input into SQL.
- Set `allowedFields` deliberately on Models that support mass assignment.
- Keep credentials and environment-specific connection settings out of source code.
- Never point automated tests or destructive migration experiments at production data.

## Guidelines & Patterns
- Keep schema changes in migrations when the repository uses CI4 migrations; update seeds/fixtures only when required.
- Use constraints and transactions for integrity that must hold across multiple writes.
- Add indexes based on query predicates, joins, uniqueness, and measured plans—not on every column by default.
- Use raw SQL/`RawSql` only when needed and validate/parameterize every dynamic value; identifiers generally cannot be parameter-bound.
- Avoid adding a repository layer around a CI4 Model unless it creates a clear boundary or testability benefit.

## Code Example (Minimal and Atomic)
```php
use CodeIgniter\Model;

// Configure user persistence
class UserModel extends Model
{
    protected $table = 'users';
    protected $allowedFields = ['name', 'email'];
    protected $useTimestamps = true;
}
```
