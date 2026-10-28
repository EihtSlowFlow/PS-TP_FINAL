---
name: frontend-javascript
description: Use this skill when implementing browser-side JavaScript, DOM updates, events, Fetch/API calls, dynamic forms, or client-side state without a frontend framework.
---
# Name: Vanilla JavaScript ES6+

## Description
Use this skill for client-side behavior in pages that use native JavaScript or an established lightweight script layer.

## Universal Rules (Mandatory)
- Prefer native browser APIs and existing project dependencies; do not introduce a frontend framework for a localized interaction.
- Use `const` by default, `let` when reassignment is needed, and avoid new `var` declarations.
- Use `addEventListener()` rather than inline HTML event handlers.
- Client-side checks improve usability but never replace server-side validation or authorization.
- Handle loading, HTTP errors, network failures, and user feedback for asynchronous requests.

## Guidelines & Patterns
- Separate event handling, business logic, network access, and DOM rendering when that improves clarity.
- Prefer `textContent` for untrusted text; avoid unsafe HTML injection.
- Use ES modules when they reduce coupling; do not modularize trivial scripts without benefit.
- Preserve keyboard access, focus, and announced state when controls update dynamically.
- Follow `code-conventions` for identifier and comment language.

## Code Example (Minimal and Atomic)
```javascript
// Load users
async function loadUsers() {
    const response = await fetch('/api/users');
    if (!response.ok) throw new Error('Unable to load users');
    return response.json();
}
```
