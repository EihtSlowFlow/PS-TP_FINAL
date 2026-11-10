# Informe de Avance y Traspaso (Handoff) — Review de HU-01, HU-04 y HU-05

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | Review posterior a HU-01 (#21), HU-04 (#17) y HU-05 (#10). No tiene issue propio |
| **Sprint / Hito** | Sprint 1 (Seguridad y usuarios) |
| **Rama** | `fix/review-hu-01-04-05` (creada desde `main` en `ed6cf02`) |
| **Último commit** | El commit que incorpora este documento (`git log -1` en la rama) |
| **Pull Request** | Sin PR (la rama no se pusheó) |
| **Fecha de cierre** | 2026-11-09 |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se revisó lo implementado de `0e678a7` a `ed6cf02` (HU-01, HU-04 y HU-05, mergeadas en los pull requests #27, #28 y #29) contra los criterios de aceptación de cada historia y contra el enunciado. Los 13 escenarios de aceptación estaban cubiertos; el review encontró 3 hallazgos medios y 6 menores, y **los 9 se corrigieron**. Se instaló PHPUnit para poder ejecutar la suite: pasó de 70 tests / 388 aserciones a **85 tests / 512 aserciones, todo en verde**.

---

## 2. Alcance y Criterios de Aceptación

Referencias: [HU-01](../historias_de_usuario/HU-01_iniciar_sesion_control_acceso.md), [HU-04](../historias_de_usuario/HU-04_gestion_usuarios.md), [HU-05](../historias_de_usuario/HU-05_baja_logica_usuario.md) y el [enunciado](../2026%20-%20inmogestion.md).

Resultado del review: HU-01 (5 escenarios), HU-04 (5) y HU-05 (3) cumplen sus escenarios y reglas de negocio. Los hallazgos fueron de robustez, seguridad fina, HTML válido y mantenibilidad.

| # | Hallazgo | Sev. | Estado | Evidencia |
|---|---|---|---|---|
| 1 | Se podía dejar al sistema sin administradores: el único admin podía quitarse el rol al editar (la única protección era el modal del cliente) | Media | ✅ | `UserManagementTest::testSoleActiveAdministratorCannotLoseTheAdministratorRoleOnEdit` y `testAdministratorRoleCanBeRemovedWhileAnotherActiveAdministratorRemains` |
| 2 | El login revelaba "cuenta deshabilitada" a quien escribiera el email de un usuario dado de baja con cualquier contraseña (enumeración de cuentas, Escenario 2 de HU-01) | Media | ✅ | `AuthAccessControlTest::testScenario5DisabledStateIsNotRevealedWithoutTheCorrectPassword` |
| 3 | HTML inválido en el selector de rol: `<div>` y `<p>` dentro de `<label>` (el enunciado exige HTML5 válido) | Media | ✅ | `UserManagementTest::testRoleSelectorMarkupKeepsLabelContentValidForHtml5` |
| 4 | La baja no pedía confirmación en el servidor: sin JavaScript el botón daba de baja directo (Escenario 1 de HU-05: "solicita la baja y confirma") | Baja | ✅ | `UserDeactivationTest::testScenario1DeactivationNeedsServerSideConfirmationEvenWithoutJavaScript` |
| 5 | Código muerto: `DashboardController::adminUsers()` sin ruta y la comprobación de "último admin" en la baja, inalcanzable y sin test | Baja | ✅ | Eliminados; `grep` sin referencias residuales |
| 6 | El login enlazaba a `auth/register` y `auth/recuperar-password`, que dan 404 hasta HU-02 y HU-03b | Baja | ✅ | `AuthAccessControlTest::testLoginScreenOnlyLinksToRegisteredAuthRoutes` y `testLoginScreenShowsRegistrationAndRecoveryLinksOnceTheirRoutesExist` |
| 7 | `novalidate` en el login y en el formulario de usuarios: los atributos `required`/`minlength` nunca actuaban | Baja | ✅ | `testLoginFormKeepsNativeBrowserValidationEnabled` y `testUserFormKeepsNativeBrowserValidationEnabled` |
| 8 | El DoD de HU-05 marcaba "no ser seleccionado en nuevos contratos" sin que exista el formulario de contratos | Baja | ✅ | DoD aclarado (nivel de modelo hasta HU-10) y notas de integración ya presentes en HU-07 y HU-10 |
| 9 | Duplicación: sidebar y topbar copiados en 3 vistas, contexto de layout calculado en 2 controladores y rol por defecto `admin` | Baja | ✅ | `layouts/panel.php`, `BaseController::panelContext()` y `AuthAccessControlTest::testSharedPanelLayoutRendersRoleAwareNavigation` |
| — | Faltaban tests de casos de error: validación del servidor al crear usuarios y POST sin token CSRF | Baja | ✅ | `UserManagementTest::testCreateRejectsInvalidFieldsOnTheServerAndKeepsTheInput` y `AuthAccessControlTest::testLoginPostWithoutCsrfTokenIsRejected` |
| — | Handoffs con el estado del PR desactualizado ("Sin PR", "lista para PR") | Baja | ✅ | HU-01, HU-04 y HU-05 apuntan a los PR #27, #28 y #29 |

**Fuera de alcance / diferido:**
- **Cierre de sesión** (HU-03): sigue sin haber ruta ni botón para salir. Es una historia aparte con sus propios criterios y se evitó pisar su rama.
- **Pantallas de registro y recuperación** (HU-02 y HU-03b): solo se ocultan los enlaces hasta que existan.
- **Listar contratos, cuotas y pagos de un usuario dado de baja** (HU-04, Escenario 5): hoy se muestran contadores y el último contrato; el detalle llega con HU-11, HU-15 y HU-18.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Controller | `app/Controllers/BaseController.php` | Modificado | `panelContext()` (datos comunes del layout: título, breadcrumb, usuario, rol, iniciales) y `routeExists()` (consulta la colección de rutas GET) |
| Controller | `app/Controllers/AuthController.php` | Modificado | El mensaje de cuenta deshabilitada solo sale si `password_verify()` contra el hash del usuario inactivo es correcto; si no, mensaje genérico. `login()` pasa a la vista si existen las rutas de registro y recuperación |
| Controller | `app/Controllers/UserController.php` | Modificado | `checkLastAdministratorRoleRetention()` en `update()`; `deactivate()` exige `confirmed=1` y, si falta, devuelve la página de confirmación; se quitó la comprobación inalcanzable de último admin, su constante y `buildLayoutContext()` |
| Controller | `app/Controllers/DashboardController.php` | Modificado | Usa `panelContext()`; se eliminó `adminUsers()` |
| View | `app/Views/layouts/panel.php` | Nuevo | Layout único (CI4 `extend`/`section`): `<head>`, sidebar por rol, topbar, mensajes flash y secciones `content`, `modals` y `scripts` |
| View | `app/Views/admin/users/deactivate_confirm.php` | Nuevo | Paso de confirmación de la baja renderizado por el servidor |
| View | `app/Views/admin/users/index.php`, `form.php` | Modificado | Extienden el layout; campo oculto `confirmed=0` en los formularios de baja; el selector de rol usa `span`; sin `novalidate`; `minlength` en nombre, apellido, DNI y teléfono y `pattern` en el DNI |
| View | `app/Views/dashboard/panel_placeholder.php` | Modificado | Solo el contenido; extiende el layout |
| View | `app/Views/auth/login.php` | Modificado | Enlaces condicionados a las rutas existentes; sin `novalidate` |
| Assets | `public/js/users.js` | Modificado | Al aceptar el modal pone `confirmed=1` antes de enviar el formulario |
| Test | `tests/feature/UserManagementTest.php` | Modificado | 5 tests nuevos (último admin ×2, markup del `<label>`, validación al crear, validación nativa) |
| Test | `tests/feature/AuthAccessControlTest.php` | Modificado | 6 tests nuevos (estado de cuenta sin contraseña, enlaces ×2, validación nativa, CSRF, navegación por rol ×4 casos) |
| Test | `tests/feature/UserDeactivationTest.php` | Modificado | 1 test nuevo (confirmación en el servidor); los existentes envían `confirmed=1` |
| Docs | `.docs/exec-plans/HU-01_handoff.md`, `HU-04_handoff.md`, `HU-05_handoff.md` | Modificado | Estado de los PR, nuevas decisiones y filas del historial |
| Docs | `.docs/historias_de_usuario/HU-05_baja_logica_usuario.md` | Modificado | DoD aclarado (ver hallazgo 8) |
| Docs | `.docs/exec-plans/REVIEW-HU-01-04-05_handoff.md` | Nuevo | Este documento |
| Docs | `.docs/ORGANIZACION_DEL_PROYECTO.md` | Modificado | Registro de lo organizado y dudas resueltas de HU-04 y HU-05 |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** Ninguna ruta nueva. `POST /admin/usuarios/baja/(:num)` ahora responde con una página de confirmación (HTTP 200) cuando no recibe `confirmed=1`, y con la baja y una redirección cuando lo recibe. Los permisos no cambian (`auth` + `role:admin`).

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Rechazar en el servidor solo quitar el rol de Administrador al **único administrador activo**; con otro administrador activo el cambio de rol propio sigue permitido | Prohibir siempre el cambio de rol propio | El modal de confirmación del cliente ya asume que se puede cambiar el propio rol y HU-04 no lo prohíbe. La regla evita el bloqueo total sin quitar esa posibilidad. Un administrador dado de baja no cuenta como disponible |
| Mostrar "cuenta deshabilitada" solo tras verificar la contraseña | Mantener el mensaje para cualquier intento | Evita distinguir un email dado de baja de uno inexistente (Escenario 2 de HU-01). El Escenario 5 sigue cumplido con credenciales correctas. Contrapartida: un usuario dado de baja que olvidó su contraseña ve el mensaje genérico |
| Confirmar con el campo `confirmed=1` en el mismo endpoint (el formulario envía `0`; el JS lo cambia a `1`; sin JS el servidor muestra la confirmación) | Ruta GET de confirmación aparte; eliminar el modal | Mantiene el modal accesible ya construido y una sola ruta. La confirmación deja de depender del cliente |
| Eliminar la comprobación de "último admin" en la baja | Dejarla como defensa adicional | Era inalcanzable: quien da de baja es un administrador activo distinto del objetivo (la autobaja está bloqueada), así que siempre queda otro. Queda documentado en un comentario en `deactivate()` |
| Layout con `extend`/`section` de CI4 y contexto en `BaseController` | Parcial `include` o dejar la duplicación | Es el mecanismo estándar de CI4 para vistas y reduce a un solo lugar el sidebar, la topbar y los mensajes flash |
| Ocultar los enlaces a rutas inexistentes consultando la colección de rutas (`routeExists()`), no con una bandera fija | Quitar los enlaces del login | Los enlaces reaparecen solos cuando HU-02 y HU-03b registren sus rutas, sin tocar la vista |
| Quitar `novalidate` y usar atributos HTML5 (`required`, `minlength`, `pattern`) | Validación propia con JavaScript | Aprovecha la validación nativa del navegador, en su idioma, sin código nuevo. El servidor sigue validando todo |
| El breadcrumb de las pantallas de formulario pasa a ser "InmoGestión / Usuarios / Editar usuario" (sin enlace) | Agregar al layout un padre con enlace | El enlace "Volver al listado de usuarios" ya está en la página. Es un cambio visual menor |

**Seguridad aplicada:** autorización en el servidor (`AuthFilter` + `RoleFilter`), regla de último administrador en el servidor, confirmación de operación destructiva en el servidor, sin enumeración del estado de la cuenta en el login, CSRF en todos los POST (con test de rechazo sin token), validación del servidor intacta y escape de salida con `esc()` en las vistas nuevas.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| PHPUnit | `phpunit-11.phar` 11.5.57 de https://phar.phpunit.de/phpunit-11.phar (5,8 MB). Se guardó fuera del repo, en la carpeta temporal de la sesión | ✅ Instalado |
| Suite base (antes de tocar código) | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage` | ✅ 70 tests / 388 aserciones |
| Tests nuevos antes de corregir | Se corrieron solos antes de aplicar cada fix | ✅ Fallaron por el motivo esperado (rojo → verde) |
| Suite completa final | Igual que la base | ✅ **85 tests / 512 aserciones**, sin riesgosos |
| Sintaxis | `php -l` sobre todos los PHP modificados | ✅ Sin errores |
| Comparación del HTML renderizado | Volcado de 10 pantallas (4 dashboards, login, listado, alta, 3 ediciones) antes y después del refactor del layout | ✅ Dashboards idénticos; en el resto solo difieren los cambios intencionales |
| Prueba visual | Página de confirmación y selector de rol abiertos en el navegador integrado | ✅ Se ven bien |
| Regresión | Los 70 tests previos siguen en verde | ✅ |

Las pruebas usan la base `inmogestion2026_test`, distinta de la de desarrollo.

Para repetirlo en Windows con WAMP, desde la raíz del proyecto y con el `.phar` descargado en la misma carpeta o en otra:

```bash
C:\wamp64\bin\php\php8.2.29\php.exe phpunit-11.phar -c phpunit.dist.xml --no-coverage
```

**Error evitado durante el trabajo:** el primer chequeo de rutas usaba `getRoutes('get')` y devolvía siempre vacío, porque la colección indexa por verbo en **mayúsculas** (`'GET'`). Lo detectó un test que fallaba y se agregó un control positivo para que la guardia no pase en vacío.

**Sin verificar:**
- **Validador W3C** de HTML5 y CSS3. El hallazgo 3 está cubierto por un test del markup, no por una validación formal. El `pattern` del DNI usa solo escapes válidos, pero conviene pasarlo por el validador.
- **JavaScript del navegador**: el modal de baja, el modal de cambio de rol propio y el seteo de `confirmed=1` no tienen prueba automatizada.
- **Prueba manual de punta a punta** con la base de desarrollo y los distintos roles.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Dependencia | Sigue sin existir el cierre de sesión (HU-03) | Media | Implementarlo en HU-03 y agregar el botón al sidebar de `layouts/panel.php` |
| R-2 | Deuda | W3C sin validar y JavaScript sin tests | Baja | Pasar el validador antes de la entrega; probar los modales a mano |
| R-3 | Riesgo | Si dos administradores se quitan el rol a la vez, ambas comprobaciones pueden pasar (no hay transacción con bloqueo) | Baja | Aceptable para el alcance; si importa, envolver la comprobación y la actualización en una transacción |
| R-4 | Accesibilidad | Los modales no atrapan el foco del teclado | Baja | Mejora opcional |
| R-5 | Coordinación | Los selectores de usuarios activos (`findActiveTenantsForContract()`, `findActiveOwnersForProperty()`) aún no los usa ningún formulario | Baja | Conectarlos en HU-07 y HU-10 con las notas de integración de cada HU |
| R-6 | Limpieza | La clase CSS `.breadcrumb-link` de `panel.css` quedó sin uso | Baja | Borrarla cuando convenga |
| R-7 | Pendiente de definir | Si se puede dar de baja a quien tiene contratos vigentes (hoy se permite) | Baja | Decidirlo al implementar HU-10/HU-12 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | HU-03 Cierre de sesión: ruta `POST /auth/logout` con CSRF, destruir la sesión y botón en el sidebar | `app/Controllers/AuthController.php`, `app/Config/Routes.php`, `app/Views/layouts/panel.php` | `ci4-core`, `ci4-routing`, `php-security` |
| 2 | HU-07 y HU-10: usar los selectores de usuarios activos y validar en el servidor que sigan activos | `app/Controllers/`, `app/Views/` | `ci4-core`, `ci4-http-validation`, `ci4-data-layer` |
| 3 | Pasar el validador W3C por login, listado, formulario y confirmación | — | `frontend-html-css` |

**Siguiente HU sugerida:** HU-03 (Cierre de sesión) o HU-03b (Restablecer contraseña); HU-02 y HU-03b solo necesitan registrar sus rutas para que los enlaces del login aparezcan.
**Prerrequisitos del entorno:** `.env` con `database.default.*` y `database.tests.*`; la base `inmogestion2026_test` creada (`php spark db:create inmogestion2026_test`); PHPUnit (Composer o el `.phar`).

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-09 | Claude Code con `LauPerisse` | Review de `0e678a7` a `ed6cf02`, instalación de PHPUnit, corrección de los 3 hallazgos medios y de los 6 menores, 15 casos de prueba nuevos y actualización de los handoffs. Suite completa: 85 tests / 512 aserciones OK | Rama `fix/review-hu-01-04-05` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).
