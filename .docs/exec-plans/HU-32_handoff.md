# Informe de Avance y Traspaso (Handoff) — HU-32

> HU-32 y HU-33 se implementaron **juntas, en una misma rama**, porque comparten la migración, el modelo, las vistas y las pruebas de visibilidad. Este documento cuenta el lado del propietario; el de la administración está en [`HU-33_handoff.md`](HU-33_handoff.md). El tiempo es conjunto (ver la sección 1).

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-32 — Publicar un inmueble propio (propietario) · [#57](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/57) (P1, 5 pts) |
| **Sprint / Hito** | Sprint 2 (ampliación detectada el 17/11/2026) |
| **Rama** | `feature/hu-32-33-propietario-publica-admin-aprueba`, creada desde `origin/Sprint-2` (`a81a942`, la versión más reciente: trae #33 y las historias HU-28 a HU-34 documentadas) |
| **Último commit** | `3fe4ffa` — feat: el propietario publica sus inmuebles y la administración los aprueba o rechaza (HU-32, HU-33) |
| **Pull Request** | [#63](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/63) → `Sprint-2` (lleva `Closes #57` y `Closes #58`, pero GitHub no los cierra solos porque la base no es `main`) |
| **Fecha de cierre** | 2026-11-17 20:12 (commit, push y pull request) |
| **Inicio / Fin** | 2026-11-17 19:34 → 2026-11-17 20:12 (dos bloques: 19:34–20:10 y 20:10–20:12) |
| **Tiempo invertido** | 38 min efectivos · 38 min de reloj · Medido (implementación, 36 min) y reconstruido por el registro de la sesión (commit, push y PR, 2 min); tiempo **conjunto** de HU-32 y HU-33, sin pausas de más de 10 min. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟡 Implementada, verificada y con pull request abierto; falta la revisión del PR y el comentario del tiempo en el issue |
| **Avance estimado** | 95 % (queda la revisión del PR) |

**Resumen:** el propietario registra sus inmuebles desde «Mis inmuebles» con el mismo formulario y las mismas validaciones de HU-07; quedan a su nombre con la revisión «Pendiente de aprobación» y no aparecen en el catálogo público, en el selector de contratos, al crear un contrato ni en los indicadores hasta que la administración los aprueba (HU-33). Puede corregirlos y reenviarlos mientras estén pendientes o rechazados; una vez aprobados, solo los edita la administración. Un inmueble ajeno responde 404.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`../historias_de_usuario/HU-32_publicar_inmueble_propietario.md`](../historias_de_usuario/HU-32_publicar_inmueble_propietario.md) e issue #57.

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: el propietario completa los datos y el inmueble queda a su nombre, «Pendiente de aprobación», con mensaje de éxito en «Mis inmuebles» | ✅ | `PropertyOwnerSubmissionTest::testScenario1OwnerPublishesAPropertyThatWaitsForApproval` |
| CA-2 | Escenario 2: datos inválidos (precio ≤ 0, ambientes no positivos, superficie ≤ 0, ciudad vacía…) se rechazan con el error de cada campo, con las reglas de HU-07 | ✅ | `testScenario2InvalidDataIsRejectedAndEveryFieldErrorIsReported` (15 casos) y `testScenario2SeveralErrorsAreReportedTogetherAndTheTypedValuesAreKept` |
| CA-3 | Escenario 3: pendiente o rechazado no aparece en el catálogo público, el selector de contratos ni los indicadores; el propietario lo ve «En revisión» o «Rechazado» con el motivo | ✅ | `testScenario3*` (cada uno con «pending» y «rejected»): catálogo, selector, indicadores, alta de contrato (controlador y modelo) y vista del propietario |
| CA-4 | Escenario 4: corregir y reenviar deja el inmueble pendiente y borra el motivo anterior | ✅ | `testScenario4CorrectingAPendingOrRejectedPropertyLeavesItPendingAndClearsTheReason` y 3 más |
| CA-5 | Escenario 5: un inmueble aprobado no se edita desde el portal; se indica que los cambios se piden a la administración | ✅ | `testScenario5AnApprovedPropertyCannotBeEditedFromThePortal`; `testScenario5ApprovalThatLandsWhileTheOwnerIsEditingIsNotUndone` (carrera) |
| CA-6 | Escenario 6: un inmueble ajeno (o inexistente o dado de baja) responde 404 | ✅ | `testScenario6*` (3 pruebas) |
| CA-7 | Escenario 7: sin inmuebles, «Registrar mi primer inmueble» | ✅ | `testScenario7AnOwnerWithoutPropertiesIsInvitedToRegisterTheFirstOne` y `testAnOwnerWithPropertiesSeesTheRegisterButtonButNotTheEmptyState` |
| CA-8 | Escenario 8 (**agregado a pedido de `LauPerisse`**, no está en el issue #57): el propietario cancela la solicitud de un inmueble pendiente o rechazado, o da de baja uno aprobado, con confirmación; no con un contrato vigente; uno ajeno responde 404 | ✅ | `PropertyOwnerDeactivationTest` (16 pruebas, 167 aserciones): ambas bajas con su confirmación, contrato vigente (con `status` alquilado y con `status` disponible pero contrato), ajeno / inexistente / ya dado de baja (404), solo POST, roles y visitante, y que desaparece del catálogo, el selector, los indicadores y la cola |
| DoD | Los inmuebles de la administración siguen entrando aprobados; los existentes siguen visibles | ✅ | `testAPropertyLoadedByTheAdministrationIsApprovedAutomaticallyAndVisible`, `testExistingPropertiesStayApprovedByDefaultAndVisible` (y las 49 pruebas de HU-07, sin cambios) |
| DoD | El llamado «Publicar mi inmueble» lleva al formulario | 🟡 | Agregado en el encabezado del catálogo público (`propietario/inmuebles/nuevo`). **La página de inicio es HU-28 (#53) y todavía no existe**: cuando se haga, que enlace a la misma ruta |

**Fuera de alcance / diferido:**
- **Reactivar un inmueble dado de baja** (por el propietario o por la administración): no existe en el proyecto (HU-09 no lo define). Tras la baja, el propietario lo ve desaparecer de «Mis inmuebles» y se le indica que se comunique con la administración.
- **Avisos a la administración** cuando llega un inmueble nuevo (HU-22, #47): todavía no existe; mientras tanto, la cola se ve con el contador del menú y el filtro del listado.
- **Detalle público de un inmueble (HU-29, #54)**: no existe aún; cuando se implemente debe filtrar por `approval_status = approved` (ver R-1).

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Migración | `app/Database/Migrations/2026-11-17-200000_AddApprovalToProperties.php` | Nuevo | Agrega `approval_status` (ENUM `pending`/`approved`/`rejected`, NOT NULL, por defecto `approved`), `submitted_by`, `rejection_reason` (VARCHAR 255), `reviewed_by`, `reviewed_at`, tres índices, dos FK a `users` y un `CHECK` que exige el motivo cuando está rechazado. `down()` lo deshace |
| Modelo | `app/Models/PropertyModel.php` | Modificado | Constantes y etiquetas de la revisión; `submitForApproval()`, `resubmitForApproval()` (con `FOR UPDATE`), `findForOwner()`; `paginatePublic()`, `findAvailableForContract()` y `getStatusCounts()` filtran por aprobados; el listado del propietario trae el estado de revisión y el motivo (el catálogo público nunca los recibe) |
| Modelo | `app/Models/PropertyModel.php` (baja) | Modificado | `deactivate()` recibe `?int $onlyOwnerId`: si se informa, un inmueble de otro dueño se reporta «no encontrado» sobre la fila bloqueada (la regla de HU-09 —no con contrato vigente— no cambia) |
| Modelo | `app/Models/ContractModel.php` | Modificado | `createAndRentProperty()` rechaza un inmueble no aprobado bajo el bloqueo de la fila |
| Controlador | `app/Controllers/PropertyController.php` | Modificado | `ownerCreate/ownerStore/ownerEdit/ownerUpdate`; `collectInput()` recibe el propietario de la sesión y descarta el estado comercial; el alta de la administración fija `approval_status = approved`; las validaciones y el `buildPayload()` de HU-07 se reutilizan sin copiarlos |
| Controlador | `app/Controllers/ContractController.php` | Modificado | `checkProperty()` exige aprobado; el mensaje de «no disponible» suma «o todavía no fue aprobado por la administración» |
| Controlador | `app/Controllers/PropertyController.php` (baja) | Modificado | `ownerDeactivate()`: 404 si es ajeno; rechaza con contrato vigente antes de pedir confirmación; sin `confirmed=1` muestra la página de confirmación; con `confirmed=1` hace la baja lógica de HU-09 con el propietario como responsable; mensaje distinto si era una solicitud o un inmueble aprobado |
| Rutas | `app/Config/Routes.php` | Modificado | Grupo `propietario` (filtros `auth` + `role:owner`): `GET inmuebles/nuevo`, `POST inmuebles`, `GET/POST inmuebles/editar/(:num)`, `POST inmuebles/baja/(:num)` |
| Vista | `app/Views/properties/owner_form.php` | Nuevo | Formulario del propietario (alta y corrección) sin selector de propietario ni estado; muestra el motivo del rechazo |
| Vista | `app/Views/properties/form_fields.php`, `form_description.php` | Nuevo | Los campos de HU-07 extraídos de `admin/properties/form.php` para que la administración y el propietario usen exactamente los mismos |
| Vista | `app/Views/admin/properties/form.php` | Modificado | Incluye los parciales (mismo HTML) y suma el panel de revisión (ver HU-33) |
| Vista | `app/Views/properties/owner_catalog.php` | Modificado | Botón «Registrar inmueble», estado «En revisión»/«Rechazado» con el motivo, «Corregir», «Cancelar solicitud» / «Dar de baja» (con la nota «Con contrato vigente» cuando no se puede), estado vacío «Registrar mi primer inmueble» |
| Vista | `app/Views/properties/owner_deactivate_confirm.php` | Nuevo | Confirmación en el servidor (sin JavaScript) de la cancelación de la solicitud o de la baja |
| Vista | `app/Views/properties/public_catalog.php` | Modificado | Enlace «Publicar mi inmueble» |
| CSS | `public/css/panel.css` | Modificado | Insignias `badge-approval-*` (ícono + texto + color), `sidebar-badge`, `review-reason`, `review-note`; solo con variables de `tokens.css` |
| Test | `tests/feature/PropertyOwnerSubmissionTest.php` | Nuevo | 51 pruebas (436 aserciones) |
| Test | `tests/feature/PropertyOwnerDeactivationTest.php` | Nuevo | Baja del propio inmueble (ver CA-8) |
| Test | `tests/_support/Libraries/PropertyReviewFixtures.php` | Nuevo | Trait de apoyo compartido con las pruebas de HU-33 |
| Docs | `.docs/ANALISIS_DE_REQUERIMIENTOS.md`, `.docs/base_de_datos/MODELO_DE_DATOS.md`, `.docs/historias_de_usuario/HU-32_…`, `HU-33_…` | Modificado | Glosario de estados (9.3) con la revisión, modelo de datos, decisiones y Definition of Done |

**Cambios de esquema de BD:** `properties` +`approval_status`, `submitted_by`, `rejection_reason`, `reviewed_by`, `reviewed_at`; índices `idx_properties_approval_status`, `idx_properties_submitted_by`, `idx_properties_reviewed_by`; FK `fk_properties_submitted_by` y `fk_properties_reviewed_by` (RESTRICT); `CHECK chk_properties_rejection_reason`. Los inmuebles existentes quedan `approved`. `.docs/base_de_datos/inmogestion_schema.sql`: se actualizó el bloque de `properties` con el `mysqldump` de la base migrada (MySQL 8.4.7, sin `AUTO_INCREMENT`); el resto del archivo no se tocó.
**Cambios de rutas / permisos:** rutas del propietario arriba; solo el rol `owner` activo (un administrador, empleado o inquilino es redirigido a su panel con el mensaje de acceso denegado, un visitante al login).

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| `approval_status` como eje independiente de `status` | Un cuarto estado comercial «Pendiente» | El análisis (9.3) fija los estados del inmueble y la baja lógica como independiente; un cuarto estado rompería el glosario. El issue lo pide así |
| Parciales `properties/form_fields` y `form_description` compartidos | Copiar el formulario de HU-07 en una vista del propietario; o una sola vista con condicionales | El propietario y la administración ven y validan los mismos campos con los mismos mensajes, sin duplicar 180 líneas de HTML. Las 49 pruebas de HU-07 y las 81 de contratos siguen en verde |
| `collectInput($ownerId)` con el propietario de la sesión y sin estado | Validar que `owner_id` del formulario coincida con la sesión | El dato ni siquiera se lee: no hay forma de manipularlo. Se prueba posteando `owner_id`, `status`, `approval_status`, `submitted_by` y `reviewed_by` ajenos |
| Reenvío con `FOR UPDATE` y revisión de la fila dentro de la transacción | `UPDATE` directo | Si la administración aprueba mientras el propietario edita, el guardado no puede volver a dejar «pendiente» un inmueble ya publicado. Se prueba con `resubmitForApproval()` |
| Un inmueble ajeno, inexistente o dado de baja responde 404 con la misma excepción | Redirigir con un mensaje | Lo pide el criterio 6 y no revela cuál de los tres casos era |
| `CHECK` en la base: rechazado exige motivo | Solo validarlo en la aplicación | El motivo es obligatorio; la base lo garantiza aunque se cargue por otro camino. Se prueba que la base lo rechaza |
| El propietario puede dar de baja tanto un inmueble pendiente o rechazado (cancelar la solicitud) como uno aprobado | La propuesta del issue: solo los pendientes; la administración los aprobados | Lo pidió `LauPerisse` el 17/11/2026. Reutiliza `PropertyModel::deactivate()` (HU-09), así que vale la misma regla: nunca con un contrato vigente; confirmación en el servidor; el propietario queda como responsable (`deleted_by`) |

**Seguridad aplicada:** `owner_id` de la sesión; autorización por rol y por propiedad (404); validación del lado del servidor con las reglas de HU-07; POST con CSRF (filtro global); salida escapada con `esc()`; el catálogo público no recibe las columnas de revisión; el propietario no puede aprobar su propio inmueble ni elegir estado.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Pruebas de HU-32 | `phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/PropertyOwnerSubmissionTest.php` | ✅ 51 pasan (436 aserciones) |
| Pruebas de HU-33 | `… tests/feature/PropertyApprovalTest.php` | ✅ 27 pasan (297 aserciones) |
| Pruebas de la baja del propietario | `… tests/feature/PropertyOwnerDeactivationTest.php` | ✅ 16 pasan (167 aserciones) |
| Regresión de lo tocado | `PropertyManagementTest` (HU-07), `PropertyCatalogTest` (HU-08), `PropertyDeactivationTest` (HU-09), `ContractCreationTest` (HU-10), `SchemaIntegrityTest` | ✅ 49 + 7 + 8 + 81 + 34, todas en verde (se repitieron con el código final, salvo `SchemaIntegrityTest`, que no depende de lo último) |
| Las pruebas detectan un olvido | Se quitó el filtro de aprobación del catálogo, del selector, de los indicadores y de la creación de contratos (en una copia) | ✅ las pruebas de visibilidad fallan; se restauró |
| Suite completa | Todo `tests/` en una copia con MySQL aislado (13 min), antes de sumar la baja del propietario | 702 pruebas y 5.717 aserciones: **5 errores y 1 falla, todos previos** y ajenos a esta rama (los 3 de `DOMParser` de HU-02 y HU-06 y los 3 de `PaymentReviewTest` de HU-17, que fallan igual en un checkout limpio de `Sprint-2`). Sobre `Sprint-2` eran 624 pruebas: las 78 de más son las de HU-32 y HU-33. Las 16 de la baja del propietario se corrieron aparte, después |
| Migraciones | `php spark migrate` sobre una base vacía | ✅ corre `AddApprovalToProperties`; los inmuebles de demostración quedan `approved` |
| Prueba manual | Servidor local con los datos de demostración: propietario (lista con «En revisión»/«Rechazado», motivo, formulario de corrección) y administrador (cola, filtro, contador del menú, ficha y clic real en «Aprobar inmueble», con el CSRF verdadero) | ✅ |
| Estilos | Sin hex/rgb fuera de `tokens.css`; estados con ícono + texto + color; 1440 px revisado en pantalla y 375 px sin desplazamiento horizontal (medido en el DOM) | ✅ (el propietario a 375 px: solo se midió la ficha del administrador, que comparte las insignias y el motivo) |

**Sin verificar:** la pantalla del propietario a 375 px y la revisión del pull request por el equipo; no hubo comparación con los prototipos (no tienen pantalla propia, según el issue). La suite completa se corrió en una instancia MySQL aislada porque el MySQL de esta máquina estaba apagado.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Riesgo | El **detalle público** (HU-29, #54) y la solicitud de alquiler (HU-30, #55) todavía no existen: si consultan `properties` directo, publicarían un inmueble sin revisar | Alta | Quien los haga debe filtrar `approval_status = 'approved'` (como `paginatePublic()`) y sumar una prueba de visibilidad |
| R-2 | Deuda | La página de inicio (HU-28, #53) debe enlazar «Publicar mi inmueble» a `propietario/inmuebles/nuevo` | Media | Resolverlo en HU-28 |
| R-3 | Deuda | `inmogestion_schema.sql` solo se actualizó en la tabla `properties`; no se volvió a ejecutar en Workbench | Baja | Regenerarlo entero con el `mysqldump` de `MODELO_DE_DATOS.md` si se vuelve a tocar el esquema |
| R-4 | Deuda | El issue #57 no trae el escenario de la baja por el propietario (se agregó a pedido) y no hay forma de reactivar un inmueble dado de baja | Baja | Actualizar el texto del issue; decidir con el equipo si hace falta reactivar |
| R-5 | Dependencia | Avisos de HU-22 sin implementar (nuevo inmueble → administración; resolución → propietario) | Media | Emitirlos desde `PropertyModel::submitForApproval()` y `resolveApproval()` cuando HU-22 esté |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisión del PR #63 y comentario del tiempo en #57 y #58 | — | — |
| 2 | HU-28 (#53): enlazar «Publicar mi inmueble» desde la página de inicio | `app/Views/home/…` | — |
| 3 | HU-29 (#54): detalle público con el filtro de aprobación | `PropertyModel`, vista pública | — |

**Siguiente HU sugerida:** HU-29 — Detalle público de un inmueble (#54) o HU-22 — Notificaciones (#47)
**Prerrequisitos del entorno:** `php spark migrate` (corre la migración nueva); para ver pantallas, `php spark db:seed DemoSeeder`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Claude Code con `LauPerisse` | 38 min | HU-32 y HU-33 en una rama desde `origin/Sprint-2`: migración, modelo, controlador, vistas, 78 pruebas y documentación | `3fe4ffa` / [#63](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/63) |
