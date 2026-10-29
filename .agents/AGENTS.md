# AGENTS.md

## Purpose
Assist with a maintainable web application using **PHP 8.2+, CodeIgniter 4.x, MySQL/MariaDB, and Apache**. Always respect the existing repository; `composer.json`, `composer.lock`, active configuration, and source code are the source of truth for implementation details.

## Global rules
- Inspect the project structure, versions, conventions, local changes, and documentation before editing.
- Do not add frameworks, dependencies, architectural layers, or tools without a demonstrated need.
- In CI4 projects, follow the framework's conventions and request lifecycle. **Do not build a parallel MVC implementation or require every task to touch a Controller + Model + View.**
- Keep code identifiers and comments in English. See `.agents/skills/code-conventions/SKILL.md` for the shared convention.
- Treat external input as untrusted; validate it server-side, authorize every protected operation, and encode output for its context.
- Never use production databases for tests or run destructive commands without explicit authorization.
- Preserve existing work; do not discard local changes or silently expand scope.
- Keep detailed rules in skills and persistent architecture information in `.docs/`; do not duplicate them here.
- Before ending a session, update the active handoff document in `.docs/exec-plans/` using the project template (`HANDOFF_TEMPLATE.md`). Record only verified facts: status, scope, changes, tests, risks, and next steps.
- When resuming work in a new session or on another machine, read the current handoff and the related user story/issue before editing code.

## Progressive skill disclosure
1. Identify the task type.
2. Load **only** the skills triggered by that task; add another only when a real secondary responsibility is involved.
3. Read the primary skill and open linked references only when needed.
4. If applicable rules conflict, report the conflict before implementation.

| Task | Primary skill | Load additionally when needed |
|---|---|---|
| Controllers, CI4 components, or application structure | `ci4-core` | `ci4-routing`, `ci4-http-validation`, `ci4-data-layer` |
| Routes, filters, HTTP methods, or route access | `ci4-routing` | `php-security` |
| Forms, validation, JSON, or HTTP responses | `ci4-http-validation` | `php-security`, `frontend-javascript` for client interaction |
| CI4 models, queries, migrations, or transactions | `ci4-data-layer` | `mysql-mariadb`, `php-security` for sensitive data |
| PHPUnit / framework tests | `ci4-testing` | `qa-regression` for bugs or cross-cutting impact |
| Authentication, authorization, CSRF, XSS, secrets, or uploads | `php-security` | Skill for the affected layer |
| Schema, indexes, or SQL outside CI4 | `mysql-mariadb` | `ci4-data-layer` when implemented through CI4 |
| VirtualHost, DocumentRoot, Apache, permissions, or deployment | `apache-lamp` | `php-security` if application exposure changes |
| HTML, CSS, semantics, or responsive layout | `frontend-html-css` | `frontend-javascript` only for dynamic behavior |
| DOM, events, Fetch, or client-side state | `frontend-javascript` | `frontend-html-css` only if markup/styles change |
| Standalone PHP or CLI scripts outside CI4 | `php-language` | `php-security` when processing external input |
| Naming, comments, or conventions | `code-conventions` | Skills for the affected technology |
| Test planning, bug diagnosis, or regression | `qa-regression` | Technical skills for the affected area |

## Workflow
- For explicitly requested, routine, bounded changes, inspect, implement, and verify without asking redundant confirmation questions.
- Before broad architectural changes, adding dependencies, destructive operations, modifying real data, or potentially incompatible migrations: explain the impact and wait for approval.
- Run checks already configured in the project (for example, `composer.json` scripts); do not invent lint commands or install tools solely to satisfy a skill.
- At the end, report what changed, which tests ran, and what remains unverified.
- Keep the handoff updated at the end of each work session to maintain continuity across agents and machines.
    
## Definition of done
The change satisfies the request, follows CI4 conventions and security boundaries, avoids unrelated modifications, and reports relevant tests and limitations.
