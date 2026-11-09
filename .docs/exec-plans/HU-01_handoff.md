# Informe de Avance y Traspaso (Handoff) — HU-01

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-01 — Iniciar sesión y acceder según el rol · [#21](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/21) |
| **Sprint / Hito** | Sprint 1 (Etapa 1 — Seguridad y usuarios) |
| **Rama** | `feature/hu-01-iniciar-sesion-control-acceso` (creada desde `main` en `aaf956c`) |
| **Último commit** | `0e678a7` — feat: implementar autenticación de usuarios y control de acceso por roles para HU-01 |
| **Pull Request** | PR abierto hacia `main` (validado: suite completa 54 tests / 150 aserciones OK, merge sin conflictos) |
| **Fecha de cierre** | 2026-11-09 15:32 |
| **Autor / Agente** | Antigravity con `EihtSlowFlow` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó el flujo completo de inicio de sesión y control de acceso por rol (HU-01): `UserModel`, `AuthController`, `DashboardController`, filtros `AuthFilter` y `RoleFilter`, protección CSRF global basada en sesión, vista semántica de login (`AUT-01`) con CSS3 y JS vanilla sin frameworks, vistas base protegidas por rol (`/admin/dashboard`, `/admin/usuarios`, `/propietario/dashboard`, `/inquilino/estado-cuenta`), y suite de pruebas automatizadas (`AuthAccessControlTest`, 15 pruebas / 87 aserciones). Quedó también resuelta la deuda `R-7` de `BD-22_handoff.md` sobre los permisos del rol `employee`.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-01_iniciar_sesion_control_acceso.md`](../historias_de_usuario/HU-01_iniciar_sesion_control_acceso.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: Usuario activo con credenciales válidas inicia sesión, regenera ID de sesión, guarda datos mínimos y redirige al panel de su rol (`admin`/`employee` → `/admin/dashboard`, `owner` → `/propietario/dashboard`, `tenant` → `/inquilino/estado-cuenta`) | ✅ | `AuthAccessControlTest::testScenario1SuccessfulLoginRedirectsToRolePanelAndStoresMinimalSession` (4 roles probados con DataProvider) |
| CA-2 | Escenario 2: Credenciales incorrectas (correo inexistente o clave errónea) se rechazan con mensaje genérico unificado, conservando el correo ingresado y limpiando la contraseña | ✅ | `AuthAccessControlTest::testScenario2InvalidCredentialsReturnUnifiedGenericMessageAndPreserveEmail` |
| CA-3 | Escenario 3: Acceso sin sesión a cualquier ruta protegida redirige a `/auth/login` e informa que debe autenticarse | ✅ | `AuthAccessControlTest::testScenario3UnauthenticatedAccessToProtectedRouteRedirectsToLogin` |
| CA-4 | Escenario 4: Usuario autenticado sin permiso sobre una sección de otro rol recibe denegación, aviso de falta de autorización y redirección a su propio panel (incluyendo `employee` bloqueado en `/admin/usuarios`) | ✅ | `AuthAccessControlTest::testScenario4RoleWithoutPermissionIsDeniedAndRedirectedToOwnPanel` y `testEmployeeSharesAdminDashboardButIsBlockedFromUserManagement` |
| CA-5 | Escenario 5: Usuario dado de baja lógicamente (`deleted_at IS NOT NULL`) tiene el ingreso bloqueado y recibe mensaje de cuenta deshabilitada (también si es dado de baja con sesión activa) | ✅ | `AuthAccessControlTest::testScenario5SoftDeletedUserCannotLoginAndReceivesDisabledMessage` y `testUserDeactivatedWhileLoggedInIsBlockedOnSubsequentProtectedRequest` |

**Fuera de alcance / diferido:**
- Cierre de sesión explícito (`POST /auth/logout`, corresponde a **HU-03 — Issue #19**).
- Registro público de propietarios e inquilinos (`/auth/register`, corresponde a **HU-02 — Issue #20**).
- Recuperación y restablecimiento de contraseña (`/auth/recuperar-password`, corresponde a **HU-03b — Issue #18**).
- Pantallas definitivas de gestión de usuarios (HU-04 / HU-05), dashboard ejecutivo (HU-25), dashboard propietario (HU-26) y estado de cuenta (HU-15): se dejaron placeholders funcionales protegidos por filtro de rol.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/UserModel.php` | Nuevo | Consultas de usuario activo/inactivo por email e ID con join a `roles`, constantes de roles, etiquetas en español y mapa de rutas por rol |
| Filter | `app/Filters/AuthFilter.php` | Nuevo | Verifica sesión activa, revoca sesión si el usuario fue dado de baja en BD, sincroniza `user_role` y aplica encabezados `Cache-Control: no-store` |
| Filter | `app/Filters/RoleFilter.php` | Nuevo | Verifica autorización por rol en el servidor (`role:admin,employee`, `role:admin`, `role:owner`, `role:tenant`) y redirige al panel propio con mensaje flash |
| Controller | `app/Controllers/AuthController.php` | Nuevo | `GET /auth/login` y `POST /auth/login` con validación server-side, `password_verify()`, mitigación de timing, `session()->regenerate()` y limpieza de `password` en old input |
| Controller | `app/Controllers/DashboardController.php` | Nuevo | Vistas base protegidas para `/admin/dashboard`, `/admin/usuarios`, `/propietario/dashboard` e `/inquilino/estado-cuenta` |
| Controller | `app/Controllers/Home.php` | Modificado | Redirige `/` al panel del rol si hay sesión activa o a `/auth/login` si es visitante |
| Controller | `app/Controllers/BaseController.php` | Modificado | Carga los helpers `form` y `url` |
| Config | `app/Config/Filters.php` | Modificado | Registra alias `auth` y `role`; activa `csrf`, `invalidchars` y `secureheaders` globales |
| Config | `app/Config/Security.php` | Modificado | Configura `$csrfProtection = 'session'` |
| Config | `app/Config/Routes.php` | Modificado | Define rutas explícitas de autenticación y grupos protegidos por `auth` y `role` |
| View | `app/Views/auth/login.php` | Nuevo | Pantalla `AUT-01` en HTML5 semántico con `csrf_field()`, mensajes flash, errores por campo y preservación de `old('email')` |
| View | `app/Views/dashboard/panel_placeholder.php` | Nuevo | Layout base con sidebar por rol, barra superior y alertas flash |
| Assets | `public/css/auth.css`, `public/css/panel.css`, `public/js/auth.js` | Nuevo | Estilos CSS3 basados en `DESIGN.md` y toggle de visibilidad de contraseña con `addEventListener` |
| Test | `tests/feature/AuthAccessControlTest.php` | Nuevo | 15 pruebas automatizadas cubriendo los 5 escenarios de HU-01 y la separación `admin` vs `employee` |
| Docs | `.docs/historias_de_usuario/HU-01_iniciar_sesion_control_acceso.md` | Modificado | Marcado del Definition of Done |

**Cambios de esquema de BD:** Ninguno (usa `users` y `roles` creados en el issue #22).
**Cambios de rutas / permisos:**
- `GET /` → redirige a `/auth/login` o al panel según rol.
- `GET /auth/login`, `POST /auth/login` → públicos con protección CSRF.
- `GET /admin/dashboard` → `auth`, `role:admin,employee`.
- `GET /admin/usuarios` → `auth`, `role:admin`.
- `GET /propietario/dashboard` → `auth`, `role:owner`.
- `GET /inquilino/estado-cuenta` → `auth`, `role:tenant`.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Rol `employee` comparte `/admin/dashboard` y rutas operativas con `admin`, excepto `/admin/usuarios*` (`role:admin`) | Mismos permisos totales que `admin` o panel `/empleado/*` separado | Resuelve la deuda `R-7` de `BD-22_handoff.md` respetando los prototipos compartidos y reservando el ABMC/baja de usuarios (HU-04 y HU-05) al Administrador |
| Protección CSRF global con almacenamiento en sesión (`$csrfProtection = 'session'`) | Basada en cookie o por ruta individual | Evita desincronización de cookies en entornos locales y protege todas las peticiones POST/PUT/DELETE/PATCH por defecto |
| Re-verificación de usuario activo (`deleted_at IS NULL`) en `AuthFilter` | Confiar solo en el flag de sesión hasta que expire | Si un administrador da de baja lógicamente a un usuario (HU-05) mientras tiene sesión abierta, su acceso se corta en la siguiente petición |
| Eliminación explícita de `password` de `superglobals` antes de `withInput()` | Limpiar solo el atributo `value=""` en la vista | Cumple el Escenario 2 en la vista y además evita guardar contraseñas en texto plano dentro de `_ci_old_input` en la sesión |

**Seguridad aplicada:** Verificación con `password_verify()` (BCRYPT) y hash dummy ante emails inexistentes para mitigar enumeración por tiempos; `session()->regenerate()` tras autenticar; almacenamiento mínimo en sesión (`is_logged_in`, `user_id`, `user_email`, `user_name`, `user_role`); escape contextual con `esc()` en todas las vistas; encabezados `Cache-Control: no-store` en páginas autenticadas; filtro `CSRF` global activo.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Sintaxis PHP | `php -l` sobre los 13 archivos PHP nuevos o modificados | ✅ Sin errores de sintaxis |
| Tests de HU-01 | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/AuthAccessControlTest.php` | ✅ 15 pruebas, 87 aserciones en verde |
| Suite completa (Regresión + BD + HU-01) | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage` | ✅ 54 pruebas, 150 aserciones en verde |
| Migraciones | `php spark migrate:status` en `default` y `tests` | ✅ 11 migraciones aplicadas y operativas |

**Sin verificar:**
- Navegación manual en navegador con servidor Apache local sobre los enlaces aún no implementados (`/auth/register` de HU-02 y `/auth/recuperar-password` de HU-03b).

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Resuelto (`R-7` de BD-22) | Permisos del rol `employee` frente a `admin` | — | Cerrado: `employee` comparte `/admin/dashboard` y rutas operativas (`role:admin,employee`), mientras que `/admin/usuarios*` exige `role:admin` |
| R-2 | Dependencia | El botón de cierre de sesión definitivo en el sidebar se completa en HU-03 (#19) | Baja | Implementar `POST /auth/logout` en HU-03 reutilizando la sesión de HU-01 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Hacer commit en `feature/hu-01-iniciar-sesion-control-acceso`, pushear y abrir PR hacia `main` cerrando #21 | — | — |
| 2 | Iniciar siguiente issue de `EihtSlowFlow`: **HU-04 — Gestión de usuarios (#17)** o **HU-03b — Restablecer contraseña (#18)** | `app/Controllers/`, `app/Models/UserModel.php`, `app/Views/` | `ci4-core`, `ci4-http-validation`, `ci4-data-layer`, `php-security` |

**Siguiente HU sugerida para `EihtSlowFlow`:** HU-04 — Gestión de usuarios (#17) o HU-03b — Restablecer contraseña (#18), ambas desbloqueadas tras HU-01.
**Prerrequisitos del entorno:** `.env` configurado con `database.default.*`, `database.tests.*`, `seed.adminEmail`, `seed.adminPassword` y `seed.demoPassword`.

### Cuentas de prueba por rol (cargadas por `DatabaseSeeder` + `DemoSeeder`)

| Rol (`roles.name`) | Etiqueta | Usuario | Correo electrónico | Variable de clave en `.env` | Ruta destino |
|---|---|---|---|---|---|
| `admin` | Administrador | Admin InmoGestion / Carla Rossi | `admin@inmogestion.com` / `admin.demo@example.com` | `seed.adminPassword` / `seed.demoPassword` | `/admin/dashboard` |
| `employee` | Empleado | Esteban Martínez | `employee.demo@example.com` | `seed.demoPassword` | `/admin/dashboard` (sin acceso a `/admin/usuarios`) |
| `owner` | Propietario | María Gómez / Jorge Sosa / Inversiones del Sur SA | `owner1.demo@example.com` a `owner3.demo@example.com` | `seed.demoPassword` | `/propietario/dashboard` |
| `tenant` | Inquilino | Lucas Pérez / Sofía Díaz / Martín López / Camila Torres | `tenant1.demo@example.com` a `tenant4.demo@example.com` | `seed.demoPassword` | `/inquilino/estado-cuenta` |
| `tenant` (baja lógica) | Inquilino deshabilitado | Ana Vega | `former.tenant.demo@example.com` | `seed.demoPassword` | Rechaza login (Escenario 5) |

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-09 | Antigravity con `EihtSlowFlow` | Creación de la rama `feature/hu-01-iniciar-sesion-control-acceso`, verificación de entorno MySQL y suite previa, implementación completa de HU-01 (modelo, controlador, filtros, vistas, CSS/JS y 15 tests de integración) y cierre de deuda `R-7` sobre rol `employee` | Commit `0e678a7`; PR abierto hacia `main`. Verificación previa al merge: `composer test` 54 tests OK, sin conflictos con `main` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).

