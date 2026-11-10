# Informe de Avance y Traspaso (Handoff) — HU-04

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-04 — Gestionar usuarios del sistema · [#17](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/17) |
| **Sprint / Hito** | Sprint 1 (Etapa 2 — Seguridad y usuarios) |
| **Rama** | `Historia-de-usuario-04` (`historia_usuario_cuatro`) |
| **Último commit** | `feat: implementar módulo de gestión de usuarios con ABMC, filtros por rol y pruebas para HU-04` |
| **Pull Request** | [#28](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/28), mergeado en `main` |
| **Fecha de cierre** | 2026-11-09 17:15 |
| **Autor / Agente** | Antigravity con `EihtSlowFlow` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó el módulo administrativo de gestión de usuarios (HU-04): listado paginado con KPIs por rol, búsqueda por nombre/DNI/correo, filtro por rol (`admin`, `employee`, `owner`, `tenant`) y casilla para incluir usuarios dados de baja; formularios de alta (`ADM-03a`) y edición (`ADM-03b`) con validación en el servidor, verificación de unicidad de correo y DNI entre usuarios activos, cifrado de contraseña con BCRYPT, preservación de contraseña en edición cuando se deja en blanco, trazabilidad y consulta de registros históricos asociados (contratos, cuotas y pagos), y suite de pruebas automatizadas (`UserManagementTest`, 12 pruebas / 154 aserciones).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-04_gestion_usuarios.md`](../historias_de_usuario/HU-04_gestion_usuarios.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: Alta de un usuario con datos personales y cualquiera de los cuatro roles (`admin`, `employee`, `owner`, `tenant`), contraseña cifrada con BCRYPT y confirmación en listado | ✅ | `UserManagementTest::testScenario1AdminCreatesUserWithAnyRoleAndEncryptedPassword` (DataProvider con 4 roles) |
| CA-2 | Escenario 2: Rechazo de alta con correo electrónico o DNI duplicado entre usuarios activos informando el campo en conflicto, permitiendo reutilizar datos si solo pertenecían a un usuario dado de baja | ✅ | `UserManagementTest::testScenario2RejectsDuplicateEmailOrDniAmongActiveUsersAndAllowsReuseFromDeactivatedUser` |
| CA-3 | Escenario 3: Listado paginado con búsqueda por nombre, DNI o correo y filtro por rol | ✅ | `UserManagementTest::testScenario3ListsSearchesFiltersByRoleAndPaginatesUsers` |
| CA-4 | Escenario 4: Modificación de datos de contacto y rol conservando la contraseña vigente cuando el campo se deja en blanco y actualizándola cifrada cuando se ingresa una nueva | ✅ | `UserManagementTest::testScenario4UpdatesUserContactAndRolePreservingPasswordWhenBlankAndUpdatingWhenProvided` |
| CA-5 | Escenario 5 (Final): Opción "Incluir usuarios dados de baja" en el listado identificándolos como dados de baja y conservando consultables sus datos e historial (contratos, cuotas y pagos) | ✅ | `UserManagementTest::testScenario5IncludesSoftDeletedUsersOnlyWhenRequestedAndPreservesConsultableHistory` |

**Fuera de alcance / diferido:**
- Acción de dar de baja lógicamente a un usuario (corresponde a **HU-05 — Issue #10**).
- Registro público de propietarios e inquilinos (`/auth/register`, corresponde a **HU-02 — Issue #20**, asignado a `joaquincorbalan`).
- Edición del perfil propio (`/perfil`, corresponde a **HU-06 — Issue #11**, asignado a `joaquincorbalan`).

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Controller | `app/Controllers/UserController.php` | Nuevo | Acciones `index`, `create`, `store`, `edit`, `update` con validaciones server-side, unicidad activa, hashing BCRYPT y sincronización de sesión si el admin edita su propia cuenta |
| Model | `app/Models/UserModel.php` | Modificado | Métodos `findByIdWithRole`, `findRoleIdByName`, `existsActiveEmail`, `existsActiveDocumentNumber`, `getRoleCounts`, `searchAndPaginate`, `getUserOperationalSummary` y `ROLE_DESCRIPTIONS` |
| Routes | `app/Config/Routes.php` | Modificado | Rutas `GET /admin/usuarios`, `GET /admin/usuarios/nuevo`, `POST /admin/usuarios`, `GET /admin/usuarios/editar/(:num)` y `POST /admin/usuarios/editar/(:num)` bajo `auth` + `role:admin` |
| View | `app/Views/admin/users/index.php` | Nuevo | Pantalla `ADM-02`: tarjetas KPI por rol, buscador, selector de rol, checkbox de dados de baja, tabla responsiva y paginación |
| View | `app/Views/admin/users/form.php` | Nuevo | Pantallas `ADM-03a` (alta) y `ADM-03b` (edición) con selector de 4 roles, campo de contraseña y barra lateral de trazabilidad e historial operativo |
| Assets | `public/css/panel.css`, `public/js/users.js` | Modificado / Nuevo | Estilos responsivos para directorio/formularios y JS vanilla (`addEventListener`) para mostrar/ocultar y sugerir contraseñas |
| Test | `tests/feature/UserManagementTest.php` | Nuevo | 12 pruebas automatizadas (154 aserciones) cubriendo los 5 escenarios de HU-04 y el bloqueo a visitantes y roles no administradores |
| Docs | `.docs/historias_de_usuario/HU-04_gestion_usuarios.md` | Modificado | Marcado del Definition of Done |

**Cambios de esquema de BD:** Ninguno (reutiliza `users`, `roles`, `contracts`, `installments` y `payments` del issue #22).
**Cambios de rutas / permisos:**
- `GET /admin/usuarios` → `UserController::index` (`auth`, `role:admin`)
- `GET /admin/usuarios/nuevo` → `UserController::create` (`auth`, `role:admin`)
- `POST /admin/usuarios` → `UserController::store` (`auth`, `role:admin`, CSRF)
- `GET /admin/usuarios/editar/(:num)` → `UserController::edit/$1` (`auth`, `role:admin`)
- `POST /admin/usuarios/editar/(:num)` → `UserController::update/$1` (`auth`, `role:admin`, CSRF)

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Aislamiento en `UserController` y `app/Views/admin/users/*` | Extender `DashboardController` o `AuthController` | Evita conflictos de merge con HU-02 (`joaquincorbalan`), HU-03 (`LauPerisse`) y HU-06 (`joaquincorbalan`) |
| Normalización de DNI quitando puntos, espacios y guiones antes de validar y buscar | Exigir formato estricto sin puntos y rechazar `32.148.902` | Permite ingresar o buscar DNIs con o sin puntos manteniendo almacenamiento canónico en `users.document_number` |
| Verificación de unicidad filtrando `deleted_at IS NULL` | `is_unique` estándar de CI4 sobre toda la tabla | Respeta la regla de negocio de HU-04 y el índice único sobre columnas virtuales `active_email` y `active_document_number` de la BD |
| Resumen de historial (`contracts`, `installments`, `payments`) en la vista de edición | Mostrar solo datos de `users` | Cumple el Escenario 5 (conservar consultables los registros asociados de usuarios dados de baja) y el bloque "Contexto operativo" del prototipo `ADM-03b` |

**Seguridad aplicada:** Autorización server-side mediante `AuthFilter` y `RoleFilter` (`role:admin`); protección CSRF global en `POST`; remoción de `password` de `superglobals` antes de `withInput()`; hash de contraseñas con `password_hash(..., PASSWORD_BCRYPT)`; consultas parametrizadas con Query Builder; escape contextual con `esc()` en todas las vistas.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Sintaxis PHP | `php -l` sobre todos los archivos creados/modificados | ✅ Sin errores de sintaxis |
| Tests de HU-04 | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/UserManagementTest.php` | ✅ 12 pruebas, 154 aserciones en verde |
| Suite completa (Regresión + BD + HU-01 + HU-04) | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage` | ✅ 66 pruebas, 304 aserciones en verde |

**Sin verificar:**
- Acción interactiva de baja lógica desde el listado/edición (diferida a HU-05 — Issue #10).

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Continuidad | Falta el endpoint y confirmación de baja lógica de usuario | Baja | Implementar en **HU-05 — Dar de baja lógicamente un usuario (#10)** sobre `UserController` |
| R-2 | Seguridad / UX | Un admin que edita su propio rol pierde acceso de inmediato; no hay bloqueo ni confirmación | Baja | Mitigado: modal de confirmación con cooldown de 3 s en `form.php`/`users.js` (solo cliente) y, en el servidor, `UserController::update` rechaza quitar el rol de Administrador al único administrador activo (`LAST_ADMIN_ROLE_CHANGE_MESSAGE`, cubierto en `UserManagementTest`). Con otro administrador activo el cambio de rol propio sigue permitido |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Iniciar **HU-05 — Dar de baja lógicamente un usuario (#10)** o **HU-03b — Restablecer contraseña (#18)** | `app/Controllers/UserController.php`, `app/Models/UserModel.php`, `app/Views/admin/users/` | `ci4-core`, `ci4-http-validation`, `ci4-data-layer`, `php-security`, `ci4-testing` |

**Siguiente HU sugerida:** HU-05 — Dar de baja lógicamente un usuario (#10), que extiende directamente el listado y edición de HU-04.
**Prerrequisitos del entorno:** `.env` configurado con `database.default.*` y `database.tests.*`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-09 | Antigravity con `EihtSlowFlow` | Implementación completa de HU-04 (`UserModel`, `UserController`, rutas `/admin/usuarios*`, vistas `ADM-02`/`ADM-03a`/`ADM-03b`, CSS/JS y 12 pruebas en `UserManagementTest`) | `f252d47` en rama `Historia-de-usuario-04` |
| 2026-11-09 | Antigravity con `EihtSlowFlow` | Revisión previa al PR: contraseña sugerida con `crypto.getRandomValues`, modal de confirmación con cooldown de 3 s al cambiar el propio rol (R-2), 66 pruebas / 304 aserciones en verde | `7c4747b` en rama `Historia-de-usuario-04` |
| 2026-11-09 | Claude Code (review posterior a HU-01/04/05) | Guardia en el servidor para no dejar la plataforma sin administradores al editar roles; el selector de rol de `form.php` usa `span` en lugar de `div`/`p` dentro de `<label>` (HTML5 válido) con prueba que lo verifica. Suite completa con PHPUnit 11.5.57: 74 tests / 432 aserciones OK | Cambios sin commitear en `main` |
| 2026-11-09 | Claude Code (review posterior a HU-01/04/05) | Hallazgos menores: formularios sin `novalidate` (validación nativa) con `minlength` y `pattern` en nombre, apellido, DNI y teléfono; las vistas `index.php`/`form.php` usan el layout compartido `layouts/panel.php`; tests nuevos de validación en el servidor al crear usuarios. Suite completa: 85 tests / 512 aserciones OK | Cambios sin commitear en `main` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).

