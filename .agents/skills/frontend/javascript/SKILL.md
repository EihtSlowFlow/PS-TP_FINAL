# Vanilla JavaScript Development Skill

## Purpose

This skill governs client-side development using **Vanilla JavaScript (ES6+)** without frontend rendering frameworks or unnecessary third-party libraries.

The primary goals are:

* Maintainable JavaScript.
* Native browser APIs.
* Clear separation of concerns.
* Accessible interactions.
* Efficient DOM manipulation.
* Predictable event handling.
* Progressive enhancement.
* Minimal dependencies.
* Prevention of frontend technical debt.

---

# 1. When to Use This Skill

Use this skill when a task involves:

* JavaScript logic.
* DOM manipulation.
* Event handling.
* Form interaction.
* Client-side validation.
* Dynamic UI behavior.
* Fetching data from APIs.
* AJAX-style requests.
* Browser storage.
* URL manipulation.
* Interactive components.
* Frontend state management.
* Accessibility-related behavior.

---

# 2. Technology Requirements

Use:

> **Vanilla JavaScript ES6+**

Prefer native browser APIs over external libraries.

Allowed technologies include:

* ECMAScript 2015+.
* DOM APIs.
* Fetch API.
* Web APIs.
* ES modules.
* Promises.
* `async` / `await`.
* `FormData`.
* `URL` and `URLSearchParams`.
* `localStorage` when appropriate.
* `sessionStorage` when appropriate.
* `IntersectionObserver`.
* `AbortController`.
* Other standard browser APIs when appropriate.

---

# 3. Framework Restrictions

Do not introduce frontend frameworks such as:

* React.
* Vue.
* Angular.
* Svelte.
* Solid.
* Alpine.js.

Do not introduce them merely to solve problems that can reasonably be handled with native JavaScript.

The same applies to libraries such as:

* jQuery.
* Lodash.
* Moment.js.
* Other utility libraries.

A dependency may be used when:

1. The existing project already depends on it.
2. The developer explicitly requests it.
3. A documented project requirement requires it.
4. The native browser API would be clearly insufficient for the requirement.

Do not replace an existing project dependency without authorization.

---

# 4. JavaScript File Organization

JavaScript files belong under:

```text id="z3f5e8"
/js/
```

When the project grows, organize files by responsibility rather than creating one large JavaScript file.

For example:

```text id="6k5r2x"
/js/
├── main.js
├── navigation.js
├── forms.js
├── validation.js
└── api.js
```

The exact structure should follow the project's existing conventions.

Avoid creating dozens of extremely small files when the resulting structure becomes harder to understand.

---

# 5. ES Modules

Use ES modules when they improve organization.

Example:

```js id="u6h5h8"
import { validateForm } from './validation.js';

const form = document.querySelector('#contact-form');

form.addEventListener('submit', (event) => {
    if (!validateForm(form)) {
        event.preventDefault();
    }
});
```

Use:

```html id="3p9x3w"
<script type="module" src="/js/main.js"></script>
```

when the application uses modules.

Do not introduce modules solely for trivial scripts where they provide no meaningful benefit.

---

# 6. Variables and Scope

Prefer:

```js id="2g6qz7"
const
let
```

Do not use `var` in new code unless compatibility with an existing codebase requires it.

Prefer narrow scopes.

Avoid unnecessary global variables.

Bad:

```js id="7x1p4a"
let currentUser;
let form;
let data;
```

Prefer encapsulated or module-scoped state.

---

# 7. DOM Manipulation

Use native DOM APIs.

Common APIs include:

```js id="q2s5m4"
document.querySelector()
document.querySelectorAll()
element.querySelector()
element.classList
element.setAttribute()
element.removeAttribute()
element.addEventListener()
document.createElement()
```

Avoid repeatedly querying the same DOM element when a reference can safely be reused.

Avoid manipulating large portions of the DOM unnecessarily.

Prefer updating only the part of the interface that actually changed.

---

# 8. Event Handling

Use `addEventListener()`.

Prefer:

```js id="3x6g8y"
button.addEventListener('click', handleClick);
```

over inline HTML handlers:

```html id="c7m2v8"
<button onclick="handleClick()">
```

Keep event handlers focused.

When multiple elements share the same behavior, consider event delegation when appropriate.

Example:

```js id="7d1z9k"
list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-action="delete"]');

    if (!button) {
        return;
    }

    handleDelete(button);
});
```

Do not introduce event delegation when it makes the code less understandable for a small number of elements.

---

# 9. Functions

Functions should have a clear responsibility.

Prefer:

```js id="1y3x5k"
function validateEmail(email) {
    // ...
}
```

over functions that simultaneously:

* Read form data.
* Validate input.
* Send HTTP requests.
* Update multiple UI components.
* Display unrelated messages.

Avoid excessively large functions.

If a function requires extensive explanation to understand what it does, consider whether its responsibilities should be separated.

---

# 10. DOM and Business Logic Separation

Avoid mixing application logic unnecessarily with DOM manipulation.

Prefer:

```text id="6t3k2m"
User interaction
      ↓
Event handler
      ↓
Application logic
      ↓
DOM update
```

rather than placing all logic inside event callbacks.

Example:

```js id="5q8h2f"
function calculateTotal(items) {
    return items.reduce((total, item) => total + item.price, 0);
}

function renderTotal(totalElement, total) {
    totalElement.textContent = total.toFixed(2);
}
```

This makes logic easier to test independently from the DOM.

---

# 11. Forms

Use semantic HTML forms.

JavaScript should enhance the form rather than make the form completely dependent on JavaScript when native HTML behavior can provide a functional fallback.

Use:

```js id="z7k4pm"
form.addEventListener('submit', handleSubmit);
```

instead of relying exclusively on button click events.

Always consider what happens if JavaScript fails or is unavailable.

---

# 12. Client-side Validation

Use JavaScript for validation when dynamic or contextual validation is required.

Use native browser validation when appropriate:

```js id="1t5f9q"
form.checkValidity();
form.reportValidity();
```

Client-side validation must never be considered a security mechanism.

The server must independently validate all submitted data.

---

# 13. Fetch and HTTP Requests

Use the native Fetch API for HTTP requests.

Example:

```js id="w8c4y1"
const response = await fetch('/api/users');

if (!response.ok) {
    throw new Error('Request failed.');
}

const users = await response.json();
```

Always consider:

* HTTP status codes.
* Network failures.
* Invalid responses.
* Timeouts/cancellation where appropriate.
* Loading states.
* Error states.
* User feedback.

Do not assume that a successful network request means the requested operation succeeded.

---

# 14. Error Handling

Handle expected failures explicitly.

Example:

```js id="3n5j7c"
try {
    const response = await fetch('/api/data');

    if (!response.ok) {
        throw new Error('Unable to retrieve data.');
    }

    const data = await response.json();
} catch (error) {
    console.error(error);
    showErrorMessage();
}
```

Do not silently swallow errors.

Avoid displaying raw technical errors to end users.

---

# 15. Accessibility

JavaScript must preserve or improve accessibility.

Interactive JavaScript components should:

* Work with keyboard input.
* Preserve focus appropriately.
* Provide meaningful state changes.
* Update accessible names or states when necessary.
* Avoid trapping focus unintentionally.
* Avoid int
