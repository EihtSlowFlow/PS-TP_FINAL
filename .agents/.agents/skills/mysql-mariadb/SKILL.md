---
name: mysql-mariadb
description: Use this skill when designing MySQL/MariaDB schemas, keys, indexes, constraints, transactions, or analyzing SQL performance independently of a specific CI4 implementation.
---
# Name: MySQL and MariaDB

## Description
Use this skill for schema design and SQL-level decisions involving MySQL or MariaDB. For application-level integration in CI4, also load `ci4-data-layer`.

## Universal Rules (Mandatory)
- Confirm the actual engine/version, schema, storage engine, and SQL mode before relying on version-specific behavior.
- Model integrity with suitable data types, `NOT NULL`, unique keys, foreign keys, and check constraints where supported and enforced.
- Parameterize values in application SQL; never concatenate untrusted input.
- Do not change production schema or data without an approved migration/backup plan.
- Do not add indexes blindly; account for write cost, selectivity, and existing indexes.

## Guidelines & Patterns
- Design indexes around real filters, joins, sort order, uniqueness, and measured query plans (`EXPLAIN`).
- Consider composite indexes in left-to-right column order based on actual query predicates; verify with plans instead of assuming an index will be used.
- Use transactions for logically atomic multi-statement updates, and verify isolation/locking needs.
- Prefer migrations checked into version control over undocumented manual schema edits.
- Keep MySQL- and MariaDB-specific differences explicit when they affect compatibility.

## Code Example (Minimal and Atomic)
```sql
CREATE INDEX idx_orders_status_created
    ON orders (status, created_at);
EXPLAIN SELECT id, created_at
FROM orders WHERE status = 'pending' ORDER BY created_at;
```
