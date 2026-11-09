# Informe de Avance y Traspaso (Handoff) — HU-05

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-05 — Dar de baja lógicamente un usuario · [#10](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/10) |
| **Sprint / Hito** | Sprint 1 (Etapa 3 — Seguridad y usuarios) |
| **Rama** | `historia_usuario_cinco` (`Historia-de-usuario-05`) |
| **Último commit** | `f4e7b67` — feat: implementar baja lógica de usuarios (HU-05) |
| **Pull Request** | [#29](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/29), mergeado en `main` |
| **Fecha de cierre** | 2026-11-09 18:20 |
| **Autor / Agente** | Antigravity con `EihtSlowFlow` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó la baja lógica (*soft delete*) de usuarios para el rol Administrador (HU-05): ruta `POST /admin/usuarios/baja/(:num)` protegida con `auth`, `role:admin` y CSRF; método `UserController::deactivate` que persiste `deleted_at` conservando el registro físico y el historial de contratos, cuotas y pagos, exige confirmación en el servidor (`confirmed=1`; sin ese campo devuelve una página de confirmación, de modo que también funciona sin JavaScript) e impide la autobaja y la baja repetida (la autobaja bloqueada ya garantiza que siempre quede un administrador activo, porque quien da de baja es un administrador activo distinto del objetivo); métodos de selección de usuarios activos por rol en `UserModel` (`findActiveSelectableByRole`, `findActiveTenantsForContract`, `findActiveOwnersForProperty`) para excluir usuarios inactivos en nuevas operaciones; botones de baja y modal de confirmación accesible en `ADM-02` y `ADM-03b`; y suite de pruebas automatizadas (`UserDeactivationTest`, 4 pruebas / 84 aserciones).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-05_baja_logica_usuario.md`](../historias_de_usuario/HU-05_baja_logica_usuario.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: Dar de baja un usuario activo confirmando la operación; pasa a estado inactivo (`deleted_at IS NOT NULL`), ya no puede iniciar sesión (ni continuar con una sesión abierta) y se conserva su registro | ✅ | `UserDeactivationTest::testScenario1AdminDeactivatesActiveUserPersistsSoftDeleteAndBlocksLoginAndSession` y `testScenario1DeactivationNeedsServerSideConfirmationEvenWithoutJavaScript` |
| CA-2 | Escenario 2: Un usuario dado de baja no aparece como opción seleccionable al crear un nuevo contrato u operación | ✅ | `UserDeactivationTest::testScenario2DeactivatedUserIsExcludedFromSelectableOptionsInNewOperations` |
| CA-3 | Escenario 3: Al confirmar la baja lógica de un usuario con contratos, cuotas o pagos asociados, los registros y sus referencias se conservan y siguen siendo consultables para auditoría | ✅ | `UserDeactivationTest::testScenario3DeactivationPreservesAssociatedContractsInstallmentsAndPaymentsForAudit` |
| CA-4 | Reglas de negocio y casos de error: bloqueo de autobaja, rechazo de baja repetida o inexistente y control de acceso por rol/autenticación | ✅ | `UserDeactivationTest::testRejectsSelfDeactivationAlreadyDeactivatedNonExistentAndUnauthorizedRequests` |

**Fuera de alcance / diferido:**
- Restablecimiento de contraseña (`/auth/forgot-password` y `/auth/reset-password`, corresponde a **HU-03b — Issue #18**).
- Formulario de alta de contratos de alquiler (`/admin/contratos/nuevo`, corresponde a **HU-10 — Issue #15**, asignado a `LauPerisse`, que consumirá `UserModel::findActiveTenantsForContract()`).

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Controller | `app/Controllers/UserController.php` | Modificado | Acción `deactivate(int $id)` con validación de existencia, bloqueo de autobaja, control de baja previa, confirmación en el servidor (`confirmed=1`, si falta devuelve `admin/users/deactivate_confirm`) y `$userModel->delete($id)` |
| View | `app/Views/admin/users/deactivate_confirm.php` | Nuevo | Página de confirmación de la baja (paso de confirmación del servidor, usado sin JavaScript) |
| Model | `app/Models/UserModel.php` | Modificado | Métodos `countActiveByRole`, `findActiveSelectableByRole`, `findActiveTenantsForContract` y `findActiveOwnersForProperty` filtrando `users.deleted_at IS NULL` |
| Routes | `app/Config/Routes.php` | Modificado | Ruta `POST /admin/usuarios/baja/(:num)` → `UserController::deactivate/$1` bajo `auth` + `role:admin` |
| View | `app/Views/admin/users/index.php` | Modificado | Botón de baja lógica por fila con protección CSRF, candado deshabilitado para el usuario propio y modal de confirmación `#deactivate-user-modal` |
| View | `app/Views/admin/users/form.php` | Modificado | Botón "Dar de baja usuario" en el encabezado de edición (`ADM-03b`) para usuarios activos distintos del propio admin y modal de confirmación |
| Assets | `public/js/users.js`, `public/css/panel.css` | Modificado | Manejador accesible para `.js-deactivate-form` + `#deactivate-user-modal` (con soporte de `Escape` y restitución de foco) y estilos `.btn-danger`, `.btn-danger-outline`, `.action-icon-btn-danger` |
| Test | `tests/feature/UserDeactivationTest.php` | Nuevo | 4 pruebas automatizadas (84 aserciones) cubriendo los 3 escenarios de HU-05 y los casos de error y permisos |
| Docs | `.docs/historias_de_usuario/HU-05_baja_logica_usuario.md` | Modificado | Marcado del Definition of Done |

**Cambios de esquema de BD:** Ninguno (utiliza `users.deleted_at` y `$useSoftDeletes = true` ya definidos en el issue #22).
**Cambios de rutas / permisos:**
- `POST /admin/usuarios/baja/(:num)` → `UserController::deactivate/$1` (`auth`, `role:admin`, CSRF)

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Baja mediante `POST /admin/usuarios/baja/(:num)` con formulario HTML y CSRF | Enlace `GET` o llamada `fetch` exclusivamente por JS | Cumple las reglas de `ci4-routing` y `php-security` (las operaciones que cambian estado usan `POST` con token CSRF y degradan limpiamente si JS no está disponible) |
| Bloqueo de autobaja (`session('user_id') === $id`); sin comprobación adicional de "último admin" | Permitir que un administrador se dé de baja a sí mismo; comprobar además el último admin activo | Coincide con el prototipo `ADM-02` (`"No se puede dar de baja (Usuario propio)"`). Quien da de baja es siempre un administrador activo distinto del objetivo, así que la comprobación del último admin era inalcanzable y se eliminó. La protección equivalente al editar roles vive en `UserController::update` |
| Confirmación en el servidor (`confirmed=1`) además del modal de JavaScript | Confirmar solo con el modal del cliente | Con JavaScript deshabilitado el botón ya no baja al usuario sin confirmar: el servidor devuelve la página de confirmación |
| Métodos explícitos `findActiveSelectableByRole`, `findActiveTenantsForContract` y `findActiveOwnersForProperty` en `UserModel` | Filtrar `deleted_at IS NULL` ad-hoc en cada controlador futuro | Centraliza la regla del Escenario 2 en la capa de datos y deja lista la interfaz para HU-07 y HU-10 |

**Seguridad aplicada:** Autorización server-side mediante `AuthFilter` y `RoleFilter` (`role:admin`); revocación inmediata de sesiones abiertas de usuarios dados de baja en `AuthFilter`; bloqueo de inicio de sesión en `AuthController::attemptLogin`; protección CSRF en `POST /admin/usuarios/baja/(:num)`; escape contextual con `esc()` en vistas y uso de `textContent` en `public/js/users.js`.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Sintaxis PHP | `php -l` sobre todos los archivos creados/modificados | ✅ Sin errores de sintaxis |
| Tests de HU-05 + HU-04 | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/UserDeactivationTest.php tests/feature/UserManagementTest.php` | ✅ 16 pruebas, 238 aserciones en verde |
| Suite completa (Regresión + BD + HU-01 + HU-04 + HU-05) | `php /tmp/phpunit-11.phar -c phpunit.dist.xml --no-coverage` | ✅ 70 pruebas, 388 aserciones en verde |

**Sin verificar:**
- Integración visual del selector de inquilinos en el formulario de alta de contratos (`/admin/contratos/nuevo`), que corresponde a **HU-10 — Issue #15** (`LauPerisse`). La exclusión a nivel modelo (`UserModel::findActiveTenantsForContract()`) quedó verificada por prueba automatizada.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Coordinación | Cuando `LauPerisse` implemente HU-07 y HU-10, debe utilizar `UserModel::findActiveOwnersForProperty()` y `UserModel::findActiveTenantsForContract()` (o `findActiveById`) | Baja | Documentado en este handoff y cubierto en `UserModel` |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Iniciar **HU-03b — Restablecer contraseña (#18)** o **HU-09 — Baja lógica de inmuebles (#13)** | `app/Controllers/AuthController.php`, `app/Models/`, `app/Views/auth/` | `ci4-core`, `ci4-http-validation`, `ci4-data-layer`, `php-security`, `ci4-testing` |

**Siguiente HU sugerida:** HU-03b — Restablecer contraseña (#18), siguiente historia asignada a `EihtSlowFlow` en la Etapa 3 del Sprint 1.
**Prerrequisitos del entorno:** `.env` configurado con `database.default.*` y `database.tests.*`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-09 | Antigravity con `EihtSlowFlow` | Implementación completa de HU-05 (`UserController::deactivate`, selectores activos en `UserModel`, ruta `POST /admin/usuarios/baja/(:num)`, UI/modal en `ADM-02`/`ADM-03b` y 4 pruebas en `UserDeactivationTest`) | Rama `historia_usuario_cinco` |
| 2026-11-09 | Claude Code (review posterior a HU-01/04/05) | Hallazgos menores del review: confirmación de baja también en el servidor (`confirmed=1` + página `deactivate_confirm`), eliminación del guard inalcanzable de "último admin" y de `DashboardController::adminUsers()`, y DoD de HU-05 aclarado (la exclusión de inactivos en nuevos contratos está a nivel de modelo hasta HU-10). Suite completa con PHPUnit 11.5.57: 85 tests / 512 aserciones OK | Cambios sin commitear en `main` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).

