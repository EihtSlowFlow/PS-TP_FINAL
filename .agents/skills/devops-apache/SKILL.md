## Purpose

This skill governs the configuration, development, and maintenance of traditional web applications running on a **LAMP stack**:

> **Linux + Apache + MySQL/MariaDB + PHP**

Its primary goals are:

* Reliable local development environments.
* Correct Apache configuration.
* Clear separation between application code and server configuration.
* Reproducible development, testing, and production environments.
* Secure server configuration.
* Maintainable virtual hosts.
* Correct PHP integration.
* Appropriate filesystem permissions.
* Useful and secure logging.
* Prevention of environment-related technical debt.

This skill focuses on Apache and the surrounding LAMP infrastructure. Application-level PHP architecture belongs to the Backend PHP skill.

---

# 1. When to Use This Skill

Use this skill when a task involves:

* Apache configuration.
* Virtual Hosts.
* DocumentRoot.
* PHP integration with Apache.
* MySQL/MariaDB server configuration.
* `.htaccess`.
* `mod_rewrite`.
* Apache modules.
* PHP extensions.
* File permissions.
* Web-server logs.
* Local LAMP environments.
* Development/staging/production environments.
* Environment variables.
* Deployment configuration.
* Apache troubleshooting.
* HTTP/HTTPS configuration.

Do not use this skill to redesign PHP application architecture unless the infrastructure change directly requires it.

---

# 2. LAMP Architecture

A traditional LAMP application can be represented as:

```text
Browser
   │
   │ HTTP / HTTPS
   ▼
Apache
   │
   ├── Static resources
   │
   └── PHP
        │
        ▼
      PHP Application
        │
        ▼
    MySQL / MariaDB
```

Each layer has a distinct responsibility.

### Linux

Provides:

* Operating system.
* Filesystem.
* Processes.
* Users and groups.
* Permissions.
* Networking.
* Services.

### Apache

Provides:

* HTTP/HTTPS serving.
* Virtual Hosts.
* Static file serving.
* PHP integration.
* URL rewriting.
* Access control.
* Request logging.

### PHP

Provides:

* Server-side application execution.
* Request processing.
* Business logic.
* Database interaction.

### MySQL/MariaDB

Provides:

* Persistent relational data storage.
* Queries.
* Transactions.
* Constraints.
* Database-level integrity.

Do not unnecessarily move responsibilities between these layers.

---

# 3. Inspect Before Changing

Before modifying the server environment, inspect the current configuration.

Determine, when relevant:

* Operating system.
* Apache version.
* PHP version.
* Database engine and version.
* Enabled Apache modules.
* Enabled PHP extensions.
* Active Virtual Hosts.
* Current DocumentRoot.
* Current application directory.
* Current permissions.
* Current logs.
* Existing `.htaccess`.
* Existing environment configuration.

Do not assume that a machine uses the default Apache configuration.

---

# 4. Apache Configuration

Apache configuration should be kept organized and predictable.

Prefer established Apache configuration locations and conventions rather than creating arbitrary configuration files.

Typical Debian/Ubuntu-style locations include:

```text
/etc/apache2/
├── apache2.conf
├── ports.conf
├── sites-available/
├── sites-enabled/
├── mods-available/
└── mods-enabled/
```

When working on systems that use a different Apache layout, follow the operating system's established conventions.

Do not modify system configuration unnecessarily.

---

# 5. Virtual Hosts

Use Virtual Hosts to separate web applications when multiple applications are hosted on the same development machine.

A Virtual Host should clearly define:

* Server name.
* DocumentRoot.
* Directory permissions/options.
* Logging.
* Access behavior.

Example conceptual structure:

```apache
<VirtualHost *:80>
    ServerName example.local

    DocumentRoot /var/www/example/public

    <Directory /var/www/example/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/example_error.log
    CustomLog ${APACHE_LOG_DIR}/example_access.log combined
</VirtualHost>
```

Do not copy this configuration blindly.

Adapt it to:

* The actual project structure.
* The operating system.
* Apache version.
* Application requirements.
* Security requirements.

---

# 6. DocumentRoot

The `DocumentRoot` defines the directory Apache exposes as the website's public root.

Prefer exposing only the directory that actually needs to be publicly accessible.

For applications with a dedicated public directory:

```text
project/
├── app/
├── config/
├── public/
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── img/
└── ...
```

prefer:

```text
DocumentRoot /path/to/project/public
```

rather than exposing the entire project directory.

This prevents internal files such as configuration, source code, logs, or environment files from becoming directly accessible through HTTP.

---

# 7. Public vs Private Files

Never intentionally expose sensitive application files through Apache.

Examples of files that should generally remain outside the public web root:

```text
.env
.env.*
database credentials
private configuration
logs
backups
source-only files
development scripts
test fixtures
```

The exact structure depends on the application architecture.

If a file does not need to be downloaded by a browser, it should not be placed in a publicly accessible directory.

---

# 8. Apache Modules

Enable only the Apache modules actually required by the project.

Common examples include:

```text
rewrite
headers
ssl
expires
```

Do not enable arbitrary modules "just in case."

Before enabling a module:

1. Determine why it is required.
2. Check whether it is already enabled.
3. Verify compatibility.
4. Enable it using the operating system's standard Apache tooling.
5. Validate the configuration.
6. Reload or restart Apache only when necessary.

---

# 9. mod_rewrite

Use `mod_rewrite` when the application requires URL rewriting or front-controller routing.

Typical use cases:

```text
/example
/example/
example.php?id=10
```

being mapped to an application entry point.

Keep rewrite rules:

* Minimal.
* Understandable.
* Ordered intentionally.
* Documented when non-obvious.

Avoid large collections of duplicated or conflicting rewrite rules.

When a framework provides its own routing mechanism, follow the framework's documented Apache integration instead of inventing a parallel routing system.

---

# 10. `.htaccess`

Use `.htaccess` only when appropriate.

Prefer Virtual Host/server configuration when the developer controls the Apache configuration and the rule does not need to be distributed with the application.

`.htaccess` can be appropriate when:

* The project is deployed into environments where server configuration cannot be changed.
* The existing application already depends on it.
* Per-directory configuration is intentionally required.

Avoid using `.htaccess` as a dumping ground for unrelated configuration.

Keep rules focused and documented.

---

# 11. PHP Integration

Before troubleshooting PHP execution through Apache, determine how PHP is integrated.

Possible configurations include:

* Apache module.
* PHP-FPM.
* Other supported server integrations.

Do not assume that installing PHP automatically means Apache is configured correctly.

Verify:

* PHP version.
* PHP handler.
* Required extensions.
* Apache integration.
* Application requirements.

For modern deployments, prefer a maintained PHP integration method appropriate to the operating system and PHP version.

---

# 12. PHP Extensions

Before installing a PHP extension:

1. Check whether it is already installed.
2. Determine whether the application actually requires it.
3. Check compatibility with the installed PHP version.
4. Install only the necessary extension.
5. Verify the resulting PHP configuration.

Common extensions may include:

```text
mysqli
pdo
pdo_mysql
mbstring
xml
curl
zip
intl
gd
```

Do not install every available PHP extension by default.

---

# 13. MySQL / MariaDB

The database layer must be treated as a separate service.

Before modifying database configuration:

* Determine whether the project uses MySQL or MariaDB.
* Determine the server version.
* Check whether the database service is running.
* Verify the database and user required by the application.
* Avoid modifying unrelated databases.

Application credentials must not be hardcoded into publicly accessible source files.

---

# 14. Database Credentials

Development credentials should be environment-specific.

Avoid:

```php
$password = 'mypassword';
```

inside application source code.

Prefer environment configuration or another project-approved configuration mechanism.

Production credentials must never be committed to version control.

---

# 15. Environment Separation

Treat these as separate environments:

```text
Development
     ↓
Testing / Staging
     ↓
Production
```

Do not assume that configuration suitable for development is suitable for production.

Examples of environment-specific settings include:

* Database credentials.
* Database host.
* Debugging.
* Error display.
* Logging.
* Application URL.
* API endpoints.
* Cookies.
* HTTPS configuration.

Production should not expose development debugging information.

---

# 16. Environment Variables

When environment variables are used:

* Keep secrets outside version-controlled source code.
* Provide safe example configuration when appropriate.
* Do not commit real credentials.
* Document required variables.
* Validate required variables during application startup when appropriate.

Example:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
APP_ENV
APP_DEBUG
```

A `.env.example` file may document required variables without containing real secrets.

---

# 17. Filesystem Permissions

Use the principle of least privilege.

The Apache/PHP process should have only the filesystem access required by the application.

Avoid making the entire project writable by Apache.

Do not use:

```bash
chmod -R 777
```

as a general troubleshooting solution.

If a writable directory is required, identify exactly:

* Which directory needs writing.
* Which process needs access.
* Why it needs access.
* Which owner/group should provide that access.

---

# 18. Apache User and Group

Determine which operating-system user and group Apache/PHP processes use before modifying permissions.

Do not assume the service account name across all Linux distributions.

Permissions should be designed around:

```text
Owner
Group
Other
```

and the actual application's requirements.

---

# 19. Logs

Use Apache logs to diagnose infrastructure problems.

Common logs include:

```text
access.log
error.log
```

Application-specific Virtual Hosts should use appropriately separated logs when practical.

Logs should help answer:

* What request occurred?
* Which response was returned?
* Did Apache encounter an error?
* Did PHP fail?
* Was a rewrite applied?
* Did permissions prevent access?

Do not expose server logs through the public web root.

---

# 20. Configuration Validation

Before reloading or restarting Apache after configuration changes, validate the configuration.

On systems using the standard Debian/Ubuntu Apache tooling, this commonly includes:

```bash
apache2ctl configtest
```

A configuration change should not be considered complete until Apache accepts the resulting configuration.

Prefer reloading configuration when a full restart is unnecessary.

---

# 21. Service Management

Use the operating system's service-management tools rather than manually launching duplicate Apache processes.

For systemd-based systems, typical operations include:

```bash
systemctl status apache2
systemctl reload apache2
systemctl restart apache2
```

Do not restart services unnecessarily.

Before restarting a production service, consider:

* Active connections.
* Downtime.
* Configuration validity.
* Deployment impact.

---

# 22. HTTPS

Production applications should use HTTPS.

When configuring HTTPS:

* Use a valid certificate.
* Redirect HTTP to HTTPS when appropriate.
* Avoid mixed content.
* Configure secure cookies where applicable.
* Do not disable certificate validation as a general solution to HTTPS problems.

Local development may use HTTP or locally trusted certificates depending on the project requirements.

---

# 23. Server Security

Avoid exposing unnecessary information.

Consider:

* Directory listing.
* Server version disclosure.
* Unnecessary HTTP methods.
* Unused modules.
* Sensitive files.
* Incorrect permissions.
* Debug output.
* Public configuration files.
* Insecure default settings.

Security-specific requirements belong to the Security skill, but infrastructure changes must not knowingly violate them.

---

# 24. Troubleshooting Workflow

When a LAMP application does not work, diagnose from the bottom upward.

Use:

```text
1. Operating system
        ↓
2. Service status
        ↓
3. Apache configuration
        ↓
4. Virtual Host
        ↓
5. DocumentRoot / filesystem permissions
        ↓
6. PHP integration
        ↓
7. PHP extensions
        ↓
8. Application configuration
        ↓
9. Database connectivity
        ↓
10. Application logic
```

Do not immediately modify application code when the actual problem is infrastructure configuration.

---

# 25. Safe Change Procedure

Before changing server configuration:

1. Inspect the current configuration.
2. Identify the relevant service and files.
3. Determine the minimum required change.
4. Check for existing project conventions.
5. Apply the smallest appropriate modification.
6. Validate configuration.
7. Reload or restart only when required.
8. Test the affected application.
9. Check relevant logs.
10. Report what changed.

Do not perform broad configuration changes to solve a narrow problem.

---

# 26. Development Environment

For local development, prioritize:

* Reproducibility.
* Simple configuration.
* Clear project separation.
* Useful error reporting.
* Safe test credentials.
* Easy log inspection.
* Minimal global system changes.

When possible, avoid modifying global Apache behavior merely to support one project.

Prefer project-specific Virtual Host configuration.

---

# 27. Production Environment

Production requires stricter controls.

At minimum, consider:

* HTTPS.
* Restricted filesystem permissions.
* Disabled development debugging.
* Protected secrets.
* Secure database credentials.
* Appropriate logging.
* Restricted public filesystem access.
* Updated supported software.
* Correct PHP configuration.
* Appropriate Apache configuration.

Never assume that a configuration tested locally is automatically production-ready.

---

# 28. Dependency and Package Management

Use the operating system's package manager for system-level packages.

For PHP application dependencies, use the project's established dependency manager, such as Composer, when applicable.

Do not manually download and install random server packages when an official package-management mechanism is available.

Avoid modifying system libraries solely to solve an application-level problem without understanding the compatibility consequences.

---

# 29. Do Not Hide Infrastructure Problems

Do not "fix" infrastructure issues by adding unrelated application workarounds.

Examples:

* Do not change PHP code to compensate for an incorrect Apache DocumentRoot.
* Do not add duplicate rewrite rules because the existing rule was not inspected.
* Do not make the entire project writable because of one permission error.
* Do not disable security controls simply because they cause development friction.
* Do not suppress errors without identifying their cause.

Fix the problem at the layer where it actually occurs.

---

# 30. Definition of Done

An Apache/LAMP task is complete when:

* The relevant environment was inspected first.
* Apache configuration is valid.
* The correct Virtual Host is configured when applicable.
* The DocumentRoot exposes only what should be public.
* PHP is correctly integrated.
* Required PHP extensions are available.
* Database connectivity works when applicable.
* Filesystem permissions follow least privilege.
* Logs can be used to diagnose failures.
* Development and production configuration are appropriately separated.
* Secrets are not exposed.
* No unnecessary global configuration was introduced.
* The smallest appropriate infrastructure change was made.
* The application was tested after the infrastructure change.
* Relevant logs were checked when troubleshooting was involved.
