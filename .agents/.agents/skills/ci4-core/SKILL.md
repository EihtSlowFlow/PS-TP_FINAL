---
name: ci4-core
description: Use this skill when creating or modifying CodeIgniter 4 application code, controllers, views, models, configuration, or framework integration in a PHP 8.2+ project.
---
# Name: CodeIgniter 4 Core

## Description
Use this skill for application work implemented within `codeigniter4/framework` 4.x. Load a more specific CI4 skill when the task primarily concerns routing, request validation, persistence, or testing.

## Universal Rules (Mandatory)
- Inspect `composer.json`, the installed CI4 minor version, and nearby files before choosing APIs.
- Follow the application's existing CI4 structure and conventions; prefer framework services/helpers where suitable.
- Do not implement a parallel router, framework lifecycle, or custom MVC layer.
- Do not require a task to modify a Controller, Model, and View all at once.
- Keep controllers focused on HTTP orchestration; extract business logic only when complexity or reuse justifies it.
- Preserve existing configuration and compatibility; do not introduce Laravel or raw-PHP patterns into CI4.

## Guidelines & Patterns
- Use native locations such as `app/Controllers`, `app/Models`, `app/Views`, `app/Config`, and `public/` when present.
- Use Services, Entities, Filters, Libraries, or helpers only when their responsibilities fit the task.
- Respect the project's declared PHP 8.2+ baseline and the version locked by Composer.
- Load `ci4-routing`, `ci4-http-validation`, `ci4-data-layer`, or `ci4-testing` only when that task area is involved.

## Code Example (Minimal and Atomic)
```php
public function show(int $id)
{
    $user = model(UserModel::class)->find($id);
    if ($user === null) return $this->response->setStatusCode(404);
    return view('users/show', ['user' => $user]);
}
```
