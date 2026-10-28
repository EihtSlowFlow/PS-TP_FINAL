---
name: frontend-html-css
description: Use this skill when creating or modifying HTML markup, CSS styles, responsive layouts, page structure, forms, or frontend accessibility.
---
# Name: HTML5 and CSS3 Frontend

## Description
Use this skill for semantic markup, layout, responsive behavior, visual styles, and static accessibility concerns.

## Universal Rules (Mandatory)
- Prefer semantic HTML5; use `<button>` for actions and `<a>` for navigation.
- Associate visible form controls with labels and preserve keyboard focus and operation.
- Build responsive layouts from the start; do not assume a single viewport size.
- Default to CSS3 without a new UI framework; preserve an existing framework or add one only for an explicit requirement.
- Keep assets in the project's existing public asset structure; in standard CI4 layouts, static assets belong under `public/`.

## Guidelines & Patterns
- Prefer CSS custom properties, Grid/Flexbox, relative units, logical properties, and focused reusable classes.
- Avoid duplicated declarations, excessive specificity, unnecessary `!important`, and large monolithic stylesheets.
- Use ARIA only when native HTML semantics cannot express the required behavior.
- Do not communicate errors or state through color alone; provide appropriate contrast and informative alternative text.
- For dynamic behavior, load `frontend-javascript` instead of duplicating its rules.

## Code Example (Minimal and Atomic)
```html
<form action="/search" method="get">
    <label for="query">Search</label>
    <input id="query" name="q" type="search">
    <button type="submit">Search</button>
</form>
```
