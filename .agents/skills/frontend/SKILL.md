## Purpose

This skill governs the development and maintenance of traditional web front-ends using semantic HTML5, Vanilla CSS3, and Vanilla JavaScript ES6+.

The primary goals are:

* Semantic and accessible markup.
* Maintainable CSS architecture.
* Progressive enhancement.
* Responsive layouts.
* Efficient DOM manipulation.
* Minimal dependencies.
* Good browser performance.
* Prevention of frontend technical debt.

---

## When to Use This Skill

Use this skill when a task involves:

* HTML structure.
* Page layout.
* Navigation.
* Forms.
* Buttons and interactive controls.
* CSS styling.
* Responsive design.
* DOM manipulation.
* Browser events.
* Client-side validation.
* Frontend accessibility.
* Frontend performance.

---

# 1. HTML

Use semantic HTML5 as the default.

Prefer meaningful elements such as:

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

Use `<div>` only when no appropriate semantic element exists.

Use `<span>` only for inline content that has no more appropriate semantic meaning.

### Rules

* Maintain a logical heading hierarchy.
* Use headings to represent document structure, not visual size.
* Use `<button>` for actions.
* Use `<a>` for navigation.
* Associate form controls with `<label>`.
* Use appropriate input types.
* Use native HTML validation where appropriate.
* Do not use ARIA when native HTML semantics already provide the required behavior.

---

# 2. CSS

Use Vanilla CSS3 by default.

Prefer:

* CSS custom properties.
* Flexbox.
* CSS Grid.
* Media queries.
* Relative units.
* `clamp()`.
* Logical properties.
* Reusable classes.

Example:

```css
:root {
    --primary-color: #2563eb;
    --surface-color: #ffffff;
    --text-color: #1f2937;
    --spacing-unit: 0.5rem;
}
```

Avoid:

* Excessive specificity.
* Deep selector nesting.
* Repeated declarations.
* `!important` unless necessary.
* Large monolithic stylesheets.
* Excessive absolute positioning.
* Layouts dependent on fixed pixel dimensions.

Project-specific styles belong in:

```text
/css/
```

Do not use inline styles unless explicitly required.

---

# 3. Responsive Design

Design for different viewport sizes from the beginning.

Prefer:

```css
rem
em
%
vw
vh
clamp()
```

Use Flexbox and Grid for layout.

Avoid designing for a single fixed screen size and adding responsiveness afterward.

Use media queries when the layout genuinely requires a breakpoint.

---

# 4. JavaScript

Use Vanilla JavaScript ES6+.

Prefer native browser APIs over external libraries.

Use:

* `const`
* `let`
* modules where appropriate
* `addEventListener()`
* `querySelector()`
* `querySelectorAll()`
* `fetch()`
* `FormData`
* modern Promise-based APIs

Avoid:

* jQuery.
* Global variables.
* Inline event handlers.
* Duplicated event logic.
* Unnecessary DOM queries.
* Heavy dependencies for simple interactions.

Project JavaScript belongs in:

```text
/js/
```

Scripts should normally be loaded at the end of `<body>` unless another loading strategy is justified.

### 4.1. Naming Conventions & Spanish Annotation Rule
All JavaScript functions, classes, variables, constants, and event handlers must be written in **English** (avoiding mixing Spanish and English terms).

Every function, method, or class declaration must be preceded by a single-line comment stating the corresponding concept or function in **Spanish**, formatted as:

```javascript
// inmueble
class PropertyCard {
    // ...
}

// calcular mora
function calculateLateFee(baseAmount, delayDays) {
    // ...
}

// registrar pago
function registerPayment(installmentId, amount) {
    // ...
}

// validar comprobante
function validateReceiptFile(fileInput) {
    // ...
}
```

---

# 5. Accessibility

Accessibility is mandatory.

Ensure:

* Keyboard accessibility.
* Visible focus
