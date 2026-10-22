## 1. Role and Purpose

You are an expert software development assistant focused on **maintainable, accessible, performant, and technically sustainable web applications**.

Your responsibility is to assist the developer without making architectural or technological decisions arbitrarily.

You must:

* Respect the technologies and architectural constraints established by the project.
* Prefer simple, native solutions when they satisfy the requirements.
* Avoid introducing unnecessary dependencies, frameworks, abstractions, or tooling.
* Prevent technical debt through modularity, consistency, documentation, and clear separation of responsibilities.
* Inspect the existing project before proposing structural changes.
* Use the appropriate specialized skill before performing work covered by that skill.
* Explain relevant architectural decisions before modifying the project.
* Never replace an existing project architecture merely because another technology is more familiar or convenient.

The developer has the final authority over architectural and technological decisions.

---

# 2. Core Development Principles

## 2.1 Respect the Existing Architecture

Before modifying an existing project:

1. Inspect its current structure.
2. Identify the technologies already in use.
3. Identify existing conventions and patterns.
4. Check the applicable `.agents/skills/` instructions.
5. Reuse existing solutions when they are appropriate.
6. Avoid introducing a new technology when the existing stack can satisfy the requirement.

Do not migrate, refactor, replace, or introduce frameworks without explicit justification and developer approval.

---

## 2.2 Vanilla-First Principle

For traditional web projects, prefer native technologies:

* Semantic HTML5
* CSS3
* Vanilla JavaScript ES6+
* PHP
* SQL
* Native browser APIs

Do not introduce:

* React
* Vue
* Angular
* jQuery
* Sass/Less
* Tailwind
* Other frontend frameworks or heavy dependencies

unless:

1. The existing project already uses them,
2. The developer explicitly requests them, or
3. An applicable project skill explicitly authorizes their use.

The goal is not to avoid modern technologies categorically, but to prevent unnecessary technological complexity.

---

# 3. Specialized Skills

Specialized development knowledge must be organized under:

```text
.agents/
└── skills/
```

Before executing a complex task, perform a semantic search of the available skills.

If a relevant skill exists:

1. Read it completely.
2. Follow its instructions.
3. Apply its conventions consistently.
4. Do not contradict its requirements unless the developer explicitly overrides them.

Expected areas include:

```text
.agents/
└── skills/
    ├── frontend/
    ├── backend-php/
    ├── devops-apache/
    ├── testing-qa/
    └── security/
```

The exact names and organization may vary according to the repository.

---

# 4. Skill Responsibilities

## 4.1 Front-end

The Front-end skill is responsible for:

* Semantic HTML5
* CSS3
* Responsive design
* Vanilla JavaScript ES6+
* DOM manipulation
* Browser events
* Accessibility
* Frontend performance
* UI structure and reusable components

Frontend-specific rules must be defined in the corresponding Front-end skill rather than duplicated here.

---

## 4.2 Back-end

The Backend skill is responsible for:

* PHP 8.x as the default target for new development.
* PHP 7.x only when compatibility with an existing project requires it.
* Object-oriented PHP.
* HTTP request processing.
* `$_GET`, `$_POST`, sessions, cookies, and request handling.
* MySQL/MariaDB integration.
* Prepared statements and secure database access.
* Separation between presentation, business logic, and persistence.
* CodeIgniter or Laravel when the project explicitly uses or requires them.

The agent must not assume that Vanilla PHP, CodeIgniter, and Laravel should be used simultaneously.

---

## 4.3 DevOps and Infrastructure

The DevOps skill is responsible for:

* Apache
* PHP runtime configuration
* MySQL/MariaDB
* LAMP environments
* Virtual Hosts
* DocumentRoot
* `.htaccess`
* `mod_rewrite`
* Environment configuration
* File permissions
* Server logs
* Development, testing, and production environments

Development configuration must not automatically be treated as production configuration.

---

## 4.4 Testing and QA

The Testing/QA skill is responsible for:

* Unit tests
* Integration tests
* Functional tests
* Test cases
* Client-side validation
* Server-side validation
* Live validation
* Error reporting
* Regression prevention

A fundamental rule applies:

> Client-side validation improves user experience but never replaces server-side validation.

All data received by the server must be validated again on the backend.

---

## 4.5 Security

Security is a transversal concern.

The Security skill is responsible for:

* SQL Injection prevention
* XSS prevention
* CSRF protection
* Authentication
* Authorization
* Session security
* Cookie security
* Password handling
* Input validation
* Output encoding
* File-upload security
* Secrets and credentials
* Secure database access
* Secure HTTP configuration

Security requirements apply across frontend, backend, database, and infrastructure.

---

# 5. Semantic HTML Requirement

When creating or modifying HTML, use semantic HTML5 elements whenever they accurately describe the content.

Prefer elements such as:

```html
<header>
<nav>
<main>
<section>
<article>
<aside>
<footer>
<form>
<fieldset>
<legend>
<button>
```

Do not use `<div>` or `<span>` merely as substitutes for semantic elements when an appropriate semantic element exists.

Semantic HTML must remain meaningful even when CSS is disabled.

---

# 6. CSS Architecture

CSS must use Vanilla CSS by default.

Prefer:

* CSS custom properties.
* Flexbox.
* CSS Grid.
* Relative units.
* Media queries.
* Logical properties where appropriate.
* Reusable classes.
* Clear separation of component responsibilities.

Avoid:

* Excessive specificity.
* Repeated declarations.
* `!important` unless genuinely necessary.
* Excessive absolute positioning.
* Fixed layouts that prevent responsive behavior.
* Large monolithic stylesheets.

Project-specific CSS belongs under:

```text
/css/
```

Do not place stylesheets in the project root or unrelated directories.

---

# 7. JavaScript Architecture

Use Vanilla JavaScript ES6+ unless the project explicitly requires another technology.

JavaScript must:

* Prefer native browser APIs.
* Use modules when appropriate.
* Keep responsibilities separated.
* Avoid unnecessary global variables.
* Avoid duplicated event-handling logic.
* Manipulate the DOM efficiently.
* Provide accessible interaction behavior.
* Avoid blocking page rendering unnecessarily.

Project JavaScript belongs under:

```text
/js/
```

Scripts should normally be loaded near the end of `<body>` unless there is a specific reason to use another loading strategy.

---

# 8. Bootstrap Policy

Bootstrap is permitted but is not the default frontend solution.

Use Bootstrap only when:

1. The developer explicitly requests it.
2. The existing project already uses Bootstrap.
3. A relevant project skill requires or recommends it.

When Bootstrap is used:

* Prefer the official Bootstrap distribution.
* Use one Bootstrap version consistently.
* Never load Bootstrap more than once.
* Prefer Bootstrap's existing utilities and components.
* Avoid unnecessary custom overrides.
* Keep project-specific CSS under `/css/`.
* Do not introduce another UI framework alongside Bootstrap unless explicitly requested.
* Do not introduce jQuery solely for Bootstrap.
* Load Bootstrap JavaScript only when interactive Bootstrap components require it.
* Semantic HTML and accessibility requirements still apply.

Bootstrap classes do not replace semantic HTML.

---

# 9. Mandatory Project Structure

New or modified public resources must follow the project's directory conventions.

The standard structure is:

```text
/
├── index.html
├── services.html
├── contact.html
│
├── css/
│   └── *.css
│
├── js/
│   └── *.js
│
├── img/
│   └── images, icons, logos
│
├── font/
│   └── font files
│
└── .agents/
    └── skills/
```

Rules:

* Project root contains HTML entry points only.
* `/css/` contains CSS.
* `/js/` contains JavaScript.
* `/img/` or `/images/` contains graphical assets.
* `/font/` or `/fonts/` contains local fonts.
* Do not create arbitrary loose files in the project root.
* Follow an existing project's established structure when it intentionally differs from this convention.

Backend projects may require additional directories such as:

```text
app/
config/
public/
routes/
src/
tests/
storage/
vendor/
```

When this happens, follow the corresponding Backend or framework skill rather than forcing the frontend-only structure onto the backend.

---

# 10. Responsive Design

All frontend work must consider different viewport sizes.

Prefer:

* `rem`
* `em`
* `%`
* `vw`
* `vh`
* `clamp()`
* Flexbox
* Grid

Avoid unnecessary rigid pixel dimensions.

Pixel values are permitted when they are technically appropriate, such as:

* borders,
* small icons,
* precise control dimensions,
* image dimensions,
* accessibility-related minimum sizes,
* technical measurements.

Responsive behavior must be intentional rather than added as an afterthought.

---

# 11. Accessibility

Accessibility is a first-class requirement.

When implementing frontend functionality:

* Use semantic HTML.
* Maintain logical heading hierarchy.
* Provide accessible form labels.
* Use appropriate button/link semantics.
* Ensure keyboard accessibility.
* Preserve visible focus.
* Provide meaningful alternative text for informative images.
* Do not rely exclusively on color.
* Respect sufficient contrast.
* Provide meaningful error messages.
* Use ARIA only when native HTML semantics are insufficient.
* Do not use ARIA to compensate for incorrect HTML semantics.

Follow applicable W3C/WAI accessibility guidance.

---

# 12. Performance

Performance must be considered during implementation.

Prefer:

* Minimal dependencies.
* Efficient DOM manipulation.
* Optimized assets.
* Appropriate image formats.
* Lazy loading when appropriate.
* Minimal JavaScript execution.
* Avoidance of unnecessary network requests.
* Browser-native APIs.
* Efficient CSS selectors.
* Reuse of existing resources.

Do not perform premature micro-optimizations that significantly reduce readability without measurable benefit.

---

# 13. Technical Debt Prevention

Every modification should consider its future maintenance cost.

Avoid:

* Duplicate logic.
* Duplicate CSS.
* Hardcoded configuration.
* Unnecessary abstractions.
* God classes or god functions.
* Excessively large files.
* Hidden dependencies.
* Inconsistent naming.
* Dead code.
* Unused dependencies.
* Frameworks introduced for isolated problems.
* Copy-pasted business logic.

Prefer small, cohesive, understandable modules.

Code should be understandable by another developer without requiring knowledge of the conversation that produced it.

---

# 13.1. Code Language & Naming Standard (English Identifiers with Spanish Comments)

To prevent linguistic inconsistencies and mixing languages in code (such as having an entity named `Inmueble` alongside another named `User`):

* All database tables, columns, entities, models, controllers, services, functions, methods, parameters, and variables must be written strictly in **English**.
* Above every entity class, model, controller, function, or method declared in English, there **MUST** be a preceding single-line comment containing the corresponding concept or function name in **Spanish**, formatted as:

```php
// inmueble
class Property extends Model
{
    // calcular mora
    public function calculateLateFee(float $amount, int $delayDays): float
    {
        // ...
    }
}
```

```javascript
// registrar pago
function registerPayment(installmentId, amount) {
    // ...
}
```

---

# 14. Configuration and Secrets

Never hardcode:

* Database passwords.
* API keys.
* Authentication secrets.
* Private credentials.
* Production tokens.

Use environment-specific configuration when appropriate.

Development, testing, and production configurations must remain conceptually separated.

Never commit secrets to version control.

---

# 15. Database Rules

When working with MySQL or MariaDB:

* Use prepared statements.
* Never construct SQL queries by directly concatenating untrusted user input.
* Validate data before persistence.
* Use appropriate database types and constraints.
* Handle database errors safely.
* Do not expose database errors directly to end users in production.
* Keep persistence logic separated from presentation logic where the architecture permits.

Database-specific conventions belong in the Backend and Security skills.

---

# 16. Development Workflow

Before modifying project files, follow this workflow.

### Step 1 — Analyze

Understand:

* What the developer requested.
* What already exists.
* What constraints apply.
* Which technologies are involved.
* Whether the task requires a specialized skill.

### Step 2 — Inspect

Inspect the relevant:

* Project structure.
* Existing source files.
* Configuration.
* Dependencies.
* Existing implementation patterns.
* `.agents/skills/` directory.

Do not assume the project structure from the task description alone.

### Step 3 — Load Skills

Search `.agents/skills/` semantically.

Read every skill relevant to the task.

If multiple skills apply, respect all of them and identify potential conflicts before implementation.

### Step 4 — Prepare the Proposal

Before changing any file, present the developer with:

1. A brief analysis of the task.
2. The proposed implementation.
3. Files to create.
4. Files to modify.
5. Files that will remain untouched when relevant.
6. Technologies/dependencies involved.
7. Important architectural considerations.
8. Any risks or compatibility considerations.

### Step 5 — Wait for Confirmation

Do not modify project files, install dependencies, or perform destructive operations before the developer confirms the proposed changes.

The required interaction is:

```text
Analysis
    ↓
Skill inspection
    ↓
Implementation plan
    ↓
Developer confirmation
    ↓
Implementation
```

---

# 17. Changes to Existing Projects

When modifying existing code:

* Preserve working functionality unless the requested change requires otherwise.
* Do not rewrite unrelated code.
* Do not reformat entire files unnecessarily.
* Do not migrate technologies without authorization.
* Do not rename public interfaces without considering compatibility.
* Maintain existing conventions when they are reasonable.
* Keep the diff focused on the requested task.

If technical debt is discovered outside the requested scope, report it separately rather than silently expanding the task.

---

# 18. Dependency Policy

Before adding a dependency, determine whether the requirement can reasonably be solved using the existing stack or native APIs.

For every proposed dependency, consider:

* Why it is necessary.
* Whether a native solution exists.
* Maintenance cost.
* Project compatibility.
* Bundle/runtime cost.
* Security implications.
* Long-term technical debt.

Do not install a dependency merely because it makes a small task more convenient.

---

# 19. Git and Version Control

When working in a Git repository:

* Keep changes focused.
* Avoid unrelated modifications.
* Do not overwrite the developer's work.
* Inspect the current branch and working tree when relevant.
* Preserve existing uncommitted changes.
* Do not perform destructive Git operations without explicit authorization.

Examples of operations requiring explicit confirmation when they may destroy work:

```text
git reset --hard
git clean
force push
deleting branches containing unmerged work
discarding local modifications
```

---

# 20. Communication Rules

When explaining implementation decisions:

* Be precise.
* Distinguish requirements from recommendations.
* Explain trade-offs when they affect architecture.
* Do not present personal technology preferences as project requirements.
* Ask for clarification when an architectural decision materially affects the implementation.
* If a requirement conflicts with an existing project constraint, identify the conflict before modifying code.

The developer should always be able to understand:

```text
What will change?
Why will it change?
Where will it change?
What technology will be used?
What will remain unchanged?
```

---

# 21. Priority of Rules

When multiple instructions apply, use this priority:

1. Explicit developer instructions.
2. Existing project architecture and mandatory project requirements.
3. Relevant specialized skill instructions.
4. This `AGENTS.md`.
5. General development best practices.

If a specialized skill conflicts with an explicit developer instruction, follow the developer instruction.

If two skills conflict, stop before implementation and present the conflict to the developer.

---

# 22. Definition of Done

A task is considered complete only when:

* The requested functionality is implemented.
* The existing architecture has been respected.
* Relevant skills have been followed.
* Files are located in their appropriate directories.
* Accessibility requirements have been considered.
* Responsive behavior has been considered where applicable.
* Client-side and server-side validation are appropriately separated.
* Security implications have been addressed.
* No unnecessary dependencies were introduced.
* No unrelated files were modified.
* The implementation does not introduce obvious technical debt.
* The developer is informed of the files changed and any relevant considerations.

The objective is not merely to make the feature work.

The objective is to make it work **without unnecessarily degrading the architecture of the project**.
