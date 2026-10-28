# Skills Refactor for PHP 8.2+ / CodeIgniter 4 / MySQL-MariaDB / Apache

This is a modular proposal ready for review and integration. It does not modify any live repository; it reorganizes the material from the supplied consolidated skills file.

## How to use it
1. Compare `AGENTS.md` with the project's current instructions and merge any still-valid project-specific rules.
2. Copy `.agents/skills/` and `.docs/` into the repository, preserving documentation that is not included in this package.
3. Load only the skills selected by the matrix in `AGENTS.md`; do not concatenate every `SKILL.md` into one instruction.
4. Review `.docs/domain-glossary.md` and remove it if the project does not use the InmoGestión domain concepts.

## Contents
- `AGENTS.md`: concise global index and skill-activation matrix.
- `.docs/architecture.md`: summarized source of truth for the stack and typical project structure.
- `.docs/domain-glossary.md`: optional domain vocabulary moved out of the generic PHP skill.
- `.docs/skill-refactor-audit.md`: pruning decisions, overlaps, and detected limitations.
- `.agents/skills/*/SKILL.md`: 13 focused skills, each with an activation description, mandatory rules, guidelines, and an atomic example.
- `SKILL-LINE-COUNTS.txt`: auditable line counts for each skill.

## Design principle
The global file answers **what to load**; the focused skill answers **how to act on that task**; `.docs/` holds project facts that should not be repeated in every instruction. A skill may link to another skill, but must not duplicate it.
