# Informe de Avance y Traspaso (Handoff) — HU-09

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-09 — Dar de baja lógicamente un inmueble · [#13](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/13) |
| **Sprint / Hito** | Sprint 1 (Etapa 4 — Inmuebles y contratos) |
| **Rama** | `feature/hu-09-baja-logica-inmueble` (creada desde `main` en `484ce4c`) |
| **Último commit** | Pendiente de commit tras validación final |
| **Pull Request** | Pendiente de abrir |
| **Fecha de cierre** | 2026-11-10 23:42 |
| **Inicio / Fin** | 2026-11-10 23:10 → 2026-11-10 23:42 |
| **Tiempo invertido** | 32 min efectivos · 32 min de reloj · Medido · sin pausas de más de 10 minutos. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Antigravity con `EihtSlowFlow` |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó la baja lógica (*soft delete*) de inmuebles (HU-09, issue #13): ruta `POST /admin/inmuebles/baja/(:num)` protegida con `auth`, `role:admin,employee` y CSRF; método transaccional `PropertyModel::deactivate(int $id, int $deletedByUserId)` con bloqueo `SELECT ... FOR UPDATE` que persiste `deleted_by` (fuera de `$allowedFields`) y `deleted_at` conservando todos los datos físicos y referencias históricas; confirmación explícita tanto en cliente (`#deactivate-property-modal` en `public/js/properties.js`) como en servidor (`confirmed=1` y vista `app/Views/admin/properties/deactivate_confirm.php`); bloqueo de baja ante contratos vigentes (`status = 'rented'` o contrato en `in_force`); filtro por estado `Activo` / `Inactivo` en `/admin/inmuebles` mostrando la fecha de baja y el responsable; y suite de pruebas automatizadas (`PropertyDeactivationTest`, 8 pruebas / 159 aserciones).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-09_baja_logica_inmueble.md`](../historias_de_usuario/HU-09_baja_logica_inmueble.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: Solicitar la baja de un inmueble activo sin contrato vigente pide confirmación explícita indicando el inmueble afectado y las consecuencias; mientras no se confirme permanece activo y sin cambios | ✅ | `PropertyDeactivationTest::testScenario1And3RequestingDeactivationRequiresConfirmationAndLeavesPropertyActiveUntilConfirmed` |
| CA-2 | Escenario 2: Al confirmar la baja, el inmueble pasa a inactivo, se registran `deleted_at` y `deleted_by`, y se conservan todos sus datos y referencias | ✅ | `PropertyDeactivationTest::testScenario2ConfirmingDeactivationPersistsDeletedAtAndDeletedByAndPreservesPropertyData` (roles `admin` y `employee`) |
| CA-3 | Escenario 3: Al cancelar la confirmación, el inmueble permanece activo y no se registra ningún cambio | ✅ | `PropertyDeactivationTest::testScenario1And3RequestingDeactivationRequiresConfirmationAndLeavesPropertyActiveUntilConfirmed` |
| CA-4 | Escenario 4: El listado de inmuebles excluye por defecto los inactivos sin necesidad de aplicar ningún filtro | ✅ | `PropertyDeactivationTest::testScenario4And5ActiveListingExcludesInactivePropertiesAndStatusFilterShowsInactiveWithDeactivationDate` |
| CA-5 | Escenario 5: Al filtrar el listado por estado «inactivo» se muestran únicamente los inmuebles inactivos indicando su fecha de baja; el filtro «activo» y la vista sin filtro muestran únicamente los activos | ✅ | `PropertyDeactivationTest::testScenario4And5ActiveListingExcludesInactivePropertiesAndStatusFilterShowsInactiveWithDeactivationDate` |
| CA-6 | Escenario 6: Un inmueble inactivo no se ofrece al crear un contrato y el servidor rechaza cualquier intento directo de asociarlo a un nuevo contrato | ✅ | `PropertyDeactivationTest::testScenario6DeactivatedPropertyIsExcludedFromContractSelectorAndRejectedOnDirectServerPost` |
| CA-7 | Escenario 7: Al dar de baja un inmueble con contratos, cuotas y pagos históricos, dichos registros y sus referencias se conservan y siguen siendo consultables | ✅ | `PropertyDeactivationTest::testScenario7DeactivatingPropertyWithHistoricalContractsPreservesHistoryAndReferences` |
| CA-8 | Escenario 8: Si el inmueble posee un contrato de locación vigente, el sistema rechaza la baja e informa el motivo | ✅ | `PropertyDeactivationTest::testScenario8PropertyWithContractInForceCannotBeDeactivated` |

**Fuera de alcance / diferido:**
- Reactivación de inmuebles dados de baja (queda documentada como decisión abierta en `ORGANIZACION_DEL_PROYECTO.md`, no forma parte de los criterios de aceptación de HU-09).

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Config / Routes | `app/Config/Routes.php` | Modificado | Agrega `POST /admin/inmuebles/baja/(:num)` (`PropertyController::deactivate/$1`) en el grupo `auth` + `role:admin,employee` |
| Model | `app/Models/PropertyModel.php` | Modificado | Agrega constantes `FILTER_STATUS_ACTIVE`, `FILTER_STATUS_INACTIVE` y `ADMIN_FILTER_STATUS_LABELS`; `findWithOwner($id, $withDeleted)`; `hasContractInForce($propertyId)`; `deactivate($id, $deletedByUserId)` transaccional con `FOR UPDATE`; soporte de filtro de inactivos en `paginateProperties()`, `getOwnerFilterOptions()` y `getStatusCounts()` |
| Controller | `app/Controllers/PropertyController.php` | Modificado | Agrega `deactivate(int $id)` con validación de existencia, baja previa, contrato vigente y confirmación `confirmed=1`; extiende `catalogFilters()` para aceptar `active`/`activo` e `inactive`/`inactivo` en el listado administrativo |
| View | `app/Views/admin/properties/deactivate_confirm.php` | Nuevo | Pantalla de confirmación explícita renderizada por el servidor cuando no se recibe `confirmed=1` |
| View | `app/Views/admin/properties/index.php` | Modificado | Selector de estado con opciones `Activo` e `Inactivo`, visualización de fecha de baja y responsable en filas inactivas, botón de baja o botón deshabilitado por contrato vigente, y modal `#deactivate-property-modal` |
| View | `app/Views/admin/properties/form.php` | Modificado | Botón «Dar de baja inmueble» y modal de confirmación en la edición de inmuebles activos sin contrato vigente |
| View | `app/Views/admin/contracts/show.php` | Modificado | Muestra badge «Dado de baja» junto al inmueble cuando `property_deleted_at` no es nulo |
| Assets | `public/js/properties.js` | Modificado | Manejo del modal accesible `#deactivate-property-modal` para `.js-deactivate-property-form` (foco, `Escape`, `confirmed=1`) |
| Test | `tests/feature/PropertyDeactivationTest.php` | Nuevo | 8 pruebas automatizadas (159 aserciones) cubriendo los 8 escenarios de aceptación, atomicidad transaccional, CSRF y control de acceso por rol |
| Docs | `.docs/historias_de_usuario/HU-09_baja_logica_inmueble.md` | Modificado | Marcado del Definition of Done y notas de integración |

**Cambios de esquema de BD:** Ninguno (usa `properties.deleted_at` y `properties.deleted_by` creados en la migración `2026-11-06-100400_CreatePropertiesTable.php`).
**Cambios de rutas / permisos:**
- `POST /admin/inmuebles/baja/(:num)` → `PropertyController::deactivate/$1` (`auth`, `role:admin,employee`, CSRF).

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Baja transaccional con `SELECT ... FOR UPDATE` en `PropertyModel::deactivate()` | Llamar a `update()` y `delete()` por separado desde el controlador | Evita condiciones de carrera si se firma un contrato en simultáneo, mantiene `deleted_by` fuera de `$allowedFields` y garantiza que si falla `deleted_by` o `deleted_at` se revierta toda la operación |
| Confirmación en dos capas: modal en cliente (`properties.js`) + página `deactivate_confirm.php` en servidor cuando `confirmed !== '1'` | Solo `confirm()` o modal en JavaScript | Cumple los Escenarios 1, 2 y 3 incluso con JavaScript deshabilitado o ante envíos directos al servidor, siguiendo el patrón de HU-05 |
| Soportar `active`/`activo` e `inactive`/`inactivo` además de `available`, `rented` y `maintenance` en el filtro `status` de `/admin/inmuebles` | Reemplazar los estados operativos o agregar otro parámetro GET | Cumple el Escenario 5 de HU-09 («filtra el listado por estado "inactivo"» / «"activo"») manteniendo el 100 % de compatibilidad con los filtros operativos de HU-08 |

**Seguridad aplicada:** Autorización server-side mediante `AuthFilter` y `RoleFilter` (`role:admin,employee`), protección CSRF obligatoria en `POST /admin/inmuebles/baja/(:num)`, `deleted_by` tomado exclusivamente de la sesión autenticada y mantenido fuera de `$allowedFields`, consultas parametrizadas y escape contextual con `esc()` en todas las vistas.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Sintaxis PHP | `php -l` sobre los 8 archivos PHP nuevos o modificados | ✅ Sin errores de sintaxis |
| Tests de HU-09 + HU-08 | `./vendor/bin/phpunit -c phpunit.dist.xml --no-coverage tests/feature/PropertyDeactivationTest.php tests/feature/PropertyCatalogTest.php` | ✅ 15 pruebas, 285 aserciones en verde |
| Tests de HU-07 | `./vendor/bin/phpunit -c phpunit.dist.xml --no-coverage tests/feature/PropertyManagementTest.php` | ✅ 49 pruebas, 560 aserciones en verde |
| Regresión Inmuebles y Contratos (HU-07, HU-08, HU-09, HU-10) | `./vendor/bin/phpunit -c phpunit.dist.xml --no-coverage tests/feature/PropertyDeactivationTest.php tests/feature/PropertyCatalogTest.php tests/feature/PropertyManagementTest.php tests/feature/ContractCreationTest.php` | ✅ 137 de 139 pruebas en verde (1560 aserciones); los únicos 2 errores corresponden a `CREATE TRIGGER` en `ContractCreationTest` porque el usuario MySQL local carece de privilegio `SUPER` con `log_bin` activo |

**Sin verificar:**
- Navegación manual interactiva en navegador con servidor Apache local.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Decisiones abiertas | Reactivación de inmuebles dados de baja | Baja | Si el equipo decide habilitar la reactivación, agregar una ruta `POST /admin/inmuebles/reactivar/(:num)` que limpie `deleted_at` y `deleted_by` |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Hacer commit en `feature/hu-09-baja-logica-inmueble`, pushear y abrir PR hacia `main` cerrando #13 | — | — |
| 2 | Continuar con las historias pendientes de la etapa 5 (HU-11, HU-12, HU-13) o HU-16 | `app/Controllers/`, `app/Models/`, `app/Views/` | `ci4-core`, `ci4-data-layer`, `ci4-http-validation`, `php-security` |

**Siguiente HU sugerida:** HU-11 / HU-12 (Contratos) o HU-16 (Registro de pago y comprobante).
**Prerrequisitos del entorno:** `.env` configurado con `database.default.*` y `database.tests.*`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-10 | Antigravity con `EihtSlowFlow` | 32 min | Actualización de `main`, creación de `feature/hu-09-baja-logica-inmueble`, implementación completa de HU-09 (modelo, controlador, vistas, JS, filtros y 8 tests automatizados) | Pendiente de commit |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio del issue (`Get-Date -Format "yyyy-MM-dd HH:mm"` o `date '+%Y-%m-%d %H:%M'`); si se retoma el issue, anotar la hora de inicio de la nueva sesión.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8), completar la fila del issue en [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) y dejar el tiempo como comentario en el issue de GitHub.

