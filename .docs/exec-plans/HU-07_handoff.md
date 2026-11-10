# Informe de Avance y Traspaso (Handoff) — HU-07

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-07 — Alta y edición de inmuebles · [#12](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/12) |
| **Sprint / Hito** | Sprint 1 (Inmuebles y contratos) |
| **Rama** | `feature/hu-03-cierre-sesion-hu-07-inmuebles` (la misma de HU-03, a pedido; creada desde `fix/review-hu-01-04-05`) |
| **Último commit** | `feat: implementar alta y edición de inmuebles para HU-07` (commit de esta rama; ver `git log`; el anterior es `a24187f`, de HU-03) |
| **Pull Request** | Todavía no se abrió. Depende del [#30](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/30) (layout compartido) |
| **Fecha de cierre** | 2026-11-09 |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó el alta y la edición de inmuebles para administradores y empleados: `PropertyModel`, `PropertyController`, rutas `/admin/inmuebles`, formulario de alta y edición (pantalla `ADM-05`), un listado mínimo para mostrar el resultado y el enlace "Inmuebles" en el menú. El inmueble nace "Disponible", solo se asignan propietarios activos y toda la validación se hace en el servidor. Se agregaron 49 casos de prueba (suite completa: **151 tests / 1200 aserciones OK**).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-07_alta_edicion_inmueble.md`](../historias_de_usuario/HU-07_alta_edicion_inmueble.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: el administrador completa propietario, dirección, ciudad, tipo, ambientes, superficie, servicios, precio y descripción; el inmueble se registra "Disponible" y aparece en el listado con mensaje de éxito | ✅ | `PropertyManagementTest::testScenario1AdminCreatesAPropertyForAnActiveOwnerWithStatusAvailable` (los 5 tipos), `testANewPropertyIsAlwaysCreatedAsAvailableWhateverStatusIsPosted` |
| CA-2 | Escenario 2: con datos inválidos (precio ≤ 0, ambientes no positivos, superficie ≤ 0, ciudad vacía) se rechaza y se indica el error de cada campo | ✅ | `testScenario2InvalidDataIsRejectedAndTheFieldErrorIsReported` (21 casos), `testScenario2EveryInvalidFieldGetsItsOwnErrorAtTheSameTime` |
| CA-3 | Escenario 3: al modificar precio o descripción se actualiza el registro y queda constancia de la fecha de modificación | ✅ | `testScenario3AdminUpdatesPriceAndDescriptionAndTheModificationDateIsRecorded` |
| RN-1 | Todo inmueble está vinculado a un propietario activo | ✅ | `testOnlyActiveOwnersAreOfferedInTheSelectorAndAcceptedOnSave`, `testEditionKeepsTheCurrentOwnerEvenIfDeactivatedButNewOwnersMustBeActive` |
| RN-2 | Tipo dentro del conjunto definido; dirección y ciudad obligatorias; superficie y servicios opcionales; precio > 0; ambientes enteros positivos salvo terrenos | ✅ | Casos de `invalidDataProvider`, `propertyTypeProvider` (terreno sin ambientes) y `testOptionalFieldsCanBeLeftEmptyAndAreStoredAsNull` |
| RN-3 | Validaciones en el servidor | ✅ | Todas las pruebas envían datos directos al servidor |
| DoD | Permisos de acceso | ✅ | `testAdministratorsAndEmployeesCanUseThePropertyScreens`, `testOwnersAndTenantsCannotReachAnyPropertyRoute`, `testVisitorsAreRedirectedToTheLogin` |

**Punto "Por definir" del issue (estado al editar):** resuelto. Al editar se puede pasar entre "Disponible" y "En mantenimiento"; "Alquilado" lo asigna el contrato (HU-10) y no se puede asignar ni quitar a mano. Lo respaldan el análisis de requerimientos y el prototipo de edición.

**Fuera de alcance / diferido:**
- Filtros del listado (HU-08) y baja lógica de inmuebles (HU-09): el listado es mínimo (paginado, con resumen por estado y la acción de editar).
- Fotos, casillas de comodidades y bloques informativos del prototipo `ADM-05`: no figuran en los criterios y no hay tablas que los guarden.
- Vincular el estado "Alquilado" con los contratos: es de HU-10.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/PropertyModel.php` | Nuevo | Tipos y estados con sus etiquetas, borrado lógico, `findWithOwner()`, `paginateWithOwner()` y `getStatusCounts()`; el propietario se une siempre sin filtrar por baja |
| Controller | `app/Controllers/PropertyController.php` | Nuevo | `index`, `create`, `store`, `edit` y `update` con validación en el servidor, regla de propietario activo y regla de estados |
| Routes | `app/Config/Routes.php` | Modificado | `GET/POST /admin/inmuebles`, `GET /admin/inmuebles/nuevo`, `GET/POST /admin/inmuebles/editar/(:num)` dentro del grupo `auth` + `role:admin,employee` |
| View | `app/Views/admin/properties/index.php` | Nuevo | Listado con resumen por estado, tabla y paginación |
| View | `app/Views/admin/properties/form.php` | Nuevo | Formulario de alta y edición, tarjeta de estado y de registro (fechas) |
| View | `app/Views/layouts/panel.php` | Modificado | Enlace "Inmuebles" para administrador y empleado |
| Assets | `public/js/properties.js`, `public/css/panel.css` | Nuevo / Modificado | Contador de caracteres, ambientes no obligatorios en terrenos; estilos de badges de estado, `textarea`, prefijo de moneda y opciones de estado |
| Test | `tests/feature/PropertyManagementTest.php` | Nuevo | 49 casos de prueba |
| Test | `tests/feature/AuthAccessControlTest.php` | Modificado | La prueba del menú por rol incluye "Inmuebles" |
| Docs | `.docs/historias_de_usuario/HU-07_alta_edicion_inmueble.md` y otros | Modificado | DoD, notas de integración y este informe |

**Cambios de esquema de BD:** Ninguno (usa `properties` y `users`, creadas en el issue #22).
**Cambios de rutas / permisos:**
- `GET /admin/inmuebles` → `PropertyController::index`
- `GET /admin/inmuebles/nuevo` → `PropertyController::create`
- `POST /admin/inmuebles` → `PropertyController::store` (CSRF)
- `GET /admin/inmuebles/editar/(:num)` → `PropertyController::edit/$1`
- `POST /admin/inmuebles/editar/(:num)` → `PropertyController::update/$1` (CSRF)
- Todas con `auth` y `role:admin,employee`.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Rutas para administrador **y empleado** | Solo administrador | HU-01 dejó al empleado con las rutas operativas (todo menos `/admin/usuarios`) y su descripción en el sistema es "operación diaria de inmuebles, contratos…". El análisis aún tiene la función del empleado "por definir"; si se decide otra cosa, alcanza con cambiar el filtro de rol de esas rutas |
| Cambiar el estado al editar solo entre Disponible y En mantenimiento; "Alquilado" bloqueado | Permitir cualquier estado; no permitir cambiar el estado | Resuelve el "Por definir" del issue con el análisis y el prototipo. Un estado enviado a mano que no corresponde se rechaza con mensaje (no se ignora en silencio) |
| Al alta se ignora cualquier estado enviado: siempre "Disponible" | Aceptar el estado del formulario | Lo exige el Escenario 1 y evita manipular el formulario |
| Propietario: solo activos al asignar; conservar el actual al editar aunque haya sido dado de baja | Exigir siempre un propietario activo | Es la nota de integración de HU-05: un inmueble no pierde su propietario por una baja. El selector muestra al propietario actual marcado "(dado de baja)" para poder seguir guardando |
| Ambientes obligatorios salvo en terrenos; si en un terreno se informan deben ser positivos | Ignorar los ambientes de los terrenos | Cumple la regla y evita guardar valores sin sentido. El tope de 255 es el del tipo de columna |
| Precio y superficie: números con hasta dos decimales; se acepta coma o punto y el prefijo `$` | Solo punto | El usuario argentino escribe con coma. El navegador envía punto desde un campo numérico; el servidor normaliza igual |
| Listado mínimo en `PropertyController::index` | Dejar el listado completo a HU-08 | El Escenario 1 pide mostrar el alta "en el listado". HU-08 agrega filtros sobre esta base |
| Reutilizar clases CSS de usuarios (`users-table`, `kpi-card`…) | Duplicar estilos con nombres nuevos | Evita copiar cientos de líneas de CSS; el nombre es engañoso pero el estilo es el mismo |
| Descripción de hasta 1.000 caracteres | Dejar el `TEXT` sin tope | El prototipo muestra el contador sobre 1.000 |

**Seguridad aplicada:** autorización en el servidor (`AuthFilter` + `RoleFilter`), CSRF en los `POST`, validación completa en el servidor, consultas con el Query Builder, escape de salida con `esc()` (con prueba de que un `<script>` en la dirección se muestra escapado) y los estados y propietarios no se confían al cliente.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de HU-07 | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/PropertyManagementTest.php` | ✅ 49 casos / 560 aserciones |
| Suite completa | Igual, sin archivo | ✅ **151 tests / 1200 aserciones** (antes 102 / 636) |
| Los tests detectan una regresión | Se desactivaron temporalmente la regla de propietario activo y la del estado "Alquilado" | ✅ Fallaron 3 tests; se restauró el código |
| Sintaxis | `php -l` en los PHP modificados | ✅ |
| Revisión visual | Listado, alta, alta con errores y edición (disponible, en mantenimiento y alquilado) en el navegador | ✅ Coinciden con el prototipo; los errores salen por campo y se conservan los datos cargados |
| JavaScript | Se probó en el navegador | ✅ El contador cuenta bien; "ambientes" deja de ser obligatorio con "Terreno"; el formulario vacío no es válido (validación nativa) |
| Recorrido en vivo | Servidor local con los datos de demo y las cuentas de administrador, propietario, inquilino y visitante (solo lecturas e inicios de sesión) | ✅ Listado, alta y las 7 ediciones responden 200 sin errores de PHP; los permisos por rol redirigen a donde corresponde; el cierre de sesión invalida la cookie anterior |
| Estructura HTML | Revisión automática de las 12 páginas renderizadas (ids duplicados, etiquetas, `form` asociado, elementos de bloque dentro de `label`) | ✅ 0 problemas |
| Controles de estado fuera del `<form>` | Se comprobó en el navegador con `FormData` | ✅ Se envían con el formulario y cambian al elegir otra opción |

**Sin verificar:**
- Escrituras sobre la base de desarrollo con el servidor local: el recorrido en vivo fue solo de lectura (para no modificar los datos de demo); crear y editar se probaron con la base de pruebas.
- Validador W3C de HTML5/CSS3.
- Cómo interpretan cada navegador y configuración regional un número con coma dentro de un campo numérico.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Coordinación | HU-08 (joaquincorbalan) va a extender `PropertyController::index`, `PropertyModel::paginateWithOwner()` y la vista del listado: puede haber conflictos de fusión | Media | Mergear HU-07 antes; las notas de integración están en HU-08 y HU-09 |
| R-2 | Pendiente de definir | Permisos del empleado sobre inmuebles (análisis: "por definir") | Baja | Confirmarlo con el equipo; es un cambio de filtro en las rutas |
| R-3 | Dependencia | El estado "Alquilado" y la oferta de inmuebles disponibles dependen de HU-10 | Media | Ver las notas de integración agregadas en HU-10 |
| R-4 | Deuda | Los estilos reutilizan nombres como `users-table`, `users-kpi-grid` | Baja | Renombrarlos a genéricos cuando se toque el CSS |
| R-5 | Caso borde | Si a un propietario con inmuebles se le cambia el rol, sus inmuebles lo siguen teniendo como propietario y la edición lo acepta sin cambios | Baja | Decidir la política cuando se defina qué pasa con los inmuebles de un usuario que deja de ser propietario |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Mergear el PR #30 y abrir el PR de HU-03 y HU-07 | — | — |
| 2 | HU-08 — Catálogo y filtros de inmuebles (extiende el listado) | `app/Controllers/PropertyController.php`, `app/Models/PropertyModel.php`, `app/Views/admin/properties/index.php` | `ci4-core`, `ci4-data-layer`, `ci4-http-validation` |
| 3 | HU-09 — Baja lógica de inmuebles (`deleted_at` y `deleted_by`) | `app/Controllers/PropertyController.php` | `ci4-core`, `ci4-data-layer`, `php-security` |
| 4 | HU-10 — Alta de contratos: usar `PropertyModel::STATUS_RENTED` y ofrecer solo inmuebles disponibles | `app/Controllers/`, `app/Views/` | `ci4-core`, `ci4-data-layer` |

**Siguiente HU sugerida:** HU-09 (baja lógica de inmuebles) o HU-10 (alta de contratos).
**Prerrequisitos del entorno:** `.env` con `database.default.*` y `database.tests.*`; base `inmogestion2026_test` creada; propietarios cargados (`php spark db:seed DemoSeeder` para datos de prueba).

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-09 | Claude Code con `LauPerisse` | Lectura del issue #12, modelo, controlador, rutas, vistas y JavaScript de inmuebles, 49 casos de prueba y revisión visual y en vivo. Suite completa: 151 tests / 1200 aserciones OK | Rama `feature/hu-03-cierre-sesion-hu-07-inmuebles` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).
