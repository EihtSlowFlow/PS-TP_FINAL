# Project Architecture and Constraints

## Target stack
- **PHP:** 8.2 or later, subject to the minimum version declared by `composer.json` and the deployment environment.
- **Framework:** `codeigniter4/framework`, major version 4.x.
- **Persistence:** MySQL or MariaDB; verify engine version, driver, schema, and environment before relying on specific capabilities.
- **HTTP server:** Apache.
- **Client:** semantic HTML5, CSS3, and vanilla JavaScript by default; preserve any technology the project intentionally already uses.

## CodeIgniter 4 conventions
Follow the actual project structure. A standard installation commonly assigns responsibilities as follows:

- `app/Controllers/`: coordinate requests and HTTP responses.
- `app/Models/`: queries and persistence when using a Model is appropriate.
- `app/Views/`: server-rendered presentation.
- `app/Config/Routes.php`: explicit route definitions.
- `app/Filters/`: cross-cutting filters, including access controls where appropriate.
- `app/Database/Migrations/` and `app/Database/Seeds/`: schema evolution and reference data.
- `public/`: public web root; do not expose the rest of the project through Apache.
- `tests/`: automated tests; preserve the layout already established by the project.
- `writable/`: application-generated files that require write access.

This list describes common conventions; it does not require creating every directory. Do not add `Repository`, `Service`, entity, or view layers to satisfy a template. Introduce them only when the logic and problem size justify them.

## Source of truth
1. `composer.json` / `composer.lock` for dependencies and compatibility.
2. The repository's active configuration and structure.
3. The actual MySQL/MariaDB migrations and schema.
4. This document, followed by the applicable skills.

If these sources disagree, describe the discrepancy. Do not assume older documentation takes precedence.

## Environments and data
- Keep development, testing, and production separate.
- Database tests must use a dedicated testing connection group and disposable data.
- Never put real credentials in source code, version-controlled documentation, or examples.
- Apache configuration should expose `public/` whenever the CI4 project structure permits it.
