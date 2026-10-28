# Skills Consolidation Audit

## Issues found
- The original `Agents.md` was approximately 722 lines and mixed global principles, frontend, backend, security, infrastructure, workflow, and definition-of-done rules.
- Testing/QA and Security appeared in two duplicated blocks. In addition, one Security block was preceded by a Testing/QA heading, making rule ownership unclear.
- The general frontend skill repeated JavaScript guidance already covered by `frontend/javascript/skill.md`; both also repeated accessibility, performance, and naming rules.
- The PHP skill mixed generic request access (`$_GET`, `$_POST`, PDO) with CodeIgniter/Laravel guidance. Applied literally to CI4, this could discourage use of the framework's native abstractions.
- The Apache skill mixed server infrastructure with PHP application details and database configuration. Those responsibilities have been separated.
- The InmoGestión domain name appeared in generic PHP rules even though its tables and concepts are application-specific. It has been moved to an optional glossary.
- The JavaScript skill in the source file ended mid-sentence at `Avoid int...`. Concise rules were reconstructed from the complete content available in the parallel frontend skill; the incomplete section was not treated as whole.
- The literal string `MVC` did not appear in the supplied file. A generic flow did appear: `Controller → Service / Business Logic → Repository / Data Access → Database`. For CI4, this was replaced with contextual guidance: reuse the existing structure and do not require extra layers or force every task to touch every component.

## Changes made
- `AGENTS.md` is now a concise operational index and activation matrix rather than an encyclopedia.
- The material is divided into 13 focused skills. Each `SKILL.md` is intended to be brief at initial load; all skills should not be loaded for every turn.
- Security is one cross-cutting responsibility. General QA and CI4-specific testing are distinct.
- Naming and comment conventions are defined once in `code-conventions`.
- The default stack is PHP 8.2+, CI4 4.x, MySQL/MariaDB, and Apache. If the actual repository differs, report that before forcing a migration.
- No requirement to use `php-cs-fixer` or another linter is imposed unless it is already configured in the project.
- All instruction text, examples, comments, and documentation in this English edition use English. Code identifiers remain English; intent comments must also be written in English.

## Size limits
The `SKILL.md` files are intentionally short. Architecture and domain details live in `.docs/`, and external references should be opened only when they provide details needed for the task. Exact line counts are included in `SKILL-LINE-COUNTS.txt`.

## Design references
- OpenAI, Harness Engineering: https://openai.com/index/harness-engineering/
- OpenAI, Agent Skills: https://developers.openai.com/api/docs/guides/tools-skills
- Microsoft Learn, Agent Skills and progressive disclosure: https://learn.microsoft.com/en-us/agent-framework/agents/skills
- CodeIgniter 4, database testing: https://www.codeigniter.com/user_guide/testing/database.html
- CodeIgniter 4, security/CSRF: https://codeigniter.com/user_guide/libraries/security.html
