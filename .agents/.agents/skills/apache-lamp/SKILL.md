---
name: apache-lamp
description: Use this skill when changing Apache VirtualHosts, DocumentRoot, rewrite rules, PHP integration, filesystem permissions, web-server logs, HTTPS, or LAMP deployment.
---
# Name: Apache and LAMP Operations

## Description
Use this skill for Apache and runtime/infrastructure changes. Application behavior and CI4 routing belong to the corresponding application skills.

## Universal Rules (Mandatory)
- Inspect active VirtualHosts, modules, PHP handler, filesystem ownership, and logs before making changes.
- For CI4, expose `public/` as DocumentRoot where deployment permits; never publish `.env`, `app/`, `vendor/`, tests, backups, or logs.
- Follow least privilege; never use `chmod -R 777` as a general fix or make the entire project writable by Apache.
- Keep development and production configuration separate and secrets out of version control.
- Test configuration before reloading/restarting; minimize service disruption.

## Guidelines & Patterns
- Keep server-wide rules in the VirtualHost when you control Apache; use `.htaccess` only when deployment or existing conventions require it.
- Enable only required modules and PHP extensions.
- On Debian/Ubuntu, inspect with `apache2ctl -S`, validate with `apache2ctl configtest`, and review relevant access/error logs.
- Diagnose the failing layer before changing rewrite rules, permissions, PHP integration, or database settings.

## Code Example (Minimal and Atomic)
```apache
<VirtualHost *:80>
    ServerName app.local
    DocumentRoot /path/to/project/public
    <Directory /path/to/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
