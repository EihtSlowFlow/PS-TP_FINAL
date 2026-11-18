# Informe de Avance y Traspaso (Handoff) — HU-30

> **Propósito:** Documento de trazabilidad del avance de HU-30 y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-30 — Solicitar el alquiler de un inmueble (inquilino) · [#55](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/55) (P1, 5 pts) |
| **Sprint / Hito** | Sprint 2 (ampliación detectada el 17/11/2026) |
| **Rama** | `feature/hu-30-31-solicitudes-alquiler` (base `origin/Sprint-2` en `9106a3c`) |
| **Último commit** | Pendiente de commit |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-17 21:02 |
| **Inicio / Fin** | 2026-11-17 20:36 → 2026-11-17 21:02 |
| **Tiempo invertido** | 26 min efectivos · 26 min de reloj · Medido · sin pausas > 10 min. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Antigravity |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó HU-30 completa: migración de la tabla `rental_requests` con claves foráneas e invariantes `CHECK`, `RentalRequestModel` con transacciones y bloqueos `FOR UPDATE`, `RentalRequestController`, rutas protegidas bajo `inquilino` (`auth` + `role:tenant`), vistas del formulario de solicitud, listado «Mis solicitudes» y confirmación de cancelación en el servidor, actualización del estado vacío de «Estado de cuenta» y del menú del inquilino, botón «Solicitar alquiler» en el catálogo público, insignias de estado en `panel.css` usando exclusivamente variables de `tokens.css`, y 42 pruebas automatizadas nuevas (40 de integración en `RentalRequestTest` y 2 de esquema en `SchemaIntegrityTest`).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`../historias_de_usuario/HU-30_solicitar_alquiler_inquilino.md`](../historias_de_usuario/HU-30_solicitar_alquiler_inquilino.md) e issue #55.

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: inquilino activo solicita inmueble disponible y aprobado informando fecha de inicio, plazo, teléfono (precargado del perfil) y mensaje opcional; se registra en estado «Pendiente» sin cambiar el estado comercial del inmueble | ✅ | `RentalRequestTest::testScenario1TenantSubmitsRequestForAvailablePropertyAndPropertyStaysAvailable`, `testScenario1OptionalMessageCanBeOmittedAndIsStoredAsNull` |
| CA-2 | Escenario 2: datos inválidos (fecha anterior a hoy o inválida, plazo no entero positivo, teléfono vacío o inválido, mensaje > 500 caracteres) se rechazan e informan error por campo | ✅ | `RentalRequestTest::testScenario2InvalidDataIsRejectedAndEveryFieldErrorIsReported` (14 casos) y `testScenario2SeveralErrorsAreReportedTogetherAndTypedValuesAreKept` |
| CA-3 | Escenario 3: solicitud repetida mientras existe una pendiente para el mismo inquilino e inmueble se rechaza; permite volver a solicitar tras cancelarla o si fue rechazada | ✅ | `RentalRequestTest::testScenario3DuplicatePendingRequestOnSamePropertyIsRejected`, `testScenario3AnotherTenantCanRequestTheSamePropertyAndTenantCanReapplyAfterCancelledOrRejected` |
| CA-4 | Escenario 4: inmueble alquilado, en mantenimiento, dado de baja, pendiente/rechazado de aprobación (HU-32/33), con contrato vigente o inexistente se rechaza informando no disponibilidad | ✅ | `RentalRequestTest::testScenario4UnavailablePropertyIsRejectedOnGetPostAndModel` (6 estados) y `testScenario4NonExistentPropertyIsRejected` |
| CA-5 | Escenario 5: «Mis solicitudes» (`/inquilino/solicitudes`) muestra inmueble, condiciones, fecha de envío, estado (Pendiente, Aprobada, Rechazada, Cancelada), motivo de rechazo y enlace al contrato si fue aprobada | ✅ | `RentalRequestTest::testScenario5MyRequestsShowsPropertyDateStatusRejectionReasonAndApprovedContractLink`, `testScenario5EmptyStateInMyRequestsOffersLinkToCatalog` |
| CA-6 | Escenario 6: cancelar solicitud pendiente con confirmación en el servidor pasa a «Cancelada» y deja de aparecer en la bandeja administrativa (`findPendingQueueForAdmin()` / `countPending()`); solicitudes ajenas responden 404 | ✅ | `RentalRequestTest::testScenario6TenantCancelsPendingRequestWithConfirmationAndItLeavesAdminQueue`, `testScenario6NonPendingRequestCannotBeCancelled`, `testScenario6AnotherTenantsOrMissingRequestAnswers404` |
| CA-7 | Escenario 7: «Estado de cuenta» sin cuotas ofrece accesos a «Buscar inmuebles» (`/catalogo-inmuebles`) y «Mis solicitudes» (`/inquilino/solicitudes`) | ✅ | `RentalRequestTest::testScenario7EmptyAccountStatementOffersSearchPropertiesAndMyRequestsLinks`, `TenantAccountStatusTest::testAccountShowsAnEmptyStateWhenTenantHasNoInstallments` |

**Fuera de alcance / diferido:**
- **Bandeja administrativa de resolución y conversión en contrato (HU-31, #56):** `RentalRequestModel` ya provee la tabla `rental_requests`, las constantes de estado y `countPending()` / `findPendingQueueForAdmin()` listos para construir HU-31.
- **Notificaciones en base de datos (HU-22, #47):** aún no implementada en el proyecto; mientras tanto las solicitudes pendientes quedan disponibles en la cola de `RentalRequestModel`.
- **Detalle público de un inmueble (HU-29, #54):** aún no implementado; el catálogo público (`/catalogo-inmuebles`) ya ofrece el botón «Solicitar alquiler» apuntando a `inquilino/inmuebles/{id}/solicitar`, ruta que HU-29 reutilizará directamente.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Migration | `app/Database/Migrations/2026-11-17-210000_CreateRentalRequestsTable.php` | Nuevo | Crea `rental_requests` con FK a `properties`, `users` (`tenant_id`, `resolved_by`) y `contracts`, índices `(property_id, status)` y `tenant_id`, y restricciones `CHECK` (`desired_months > 0` y motivo obligatorio si `status = 'rejected'`) |
| Model | `app/Models/RentalRequestModel.php` | Nuevo | Constantes y etiquetas de estado, `findRequestableProperty()`, `hasPendingForTenantAndProperty()`, `createRequest()` y `cancelByTenant()` transaccionales con `FOR UPDATE`, `findForTenant()`, `findOneForTenant()`, `countPending()` y `findPendingQueueForAdmin()` |
| Controller | `app/Controllers/RentalRequestController.php` | Nuevo | Acciones `index`, `create`, `store` y `cancel` con validación en servidor (`AccountRules::valid_phone`, fechas y plazos), control de propiedad (404) y confirmación de cancelación |
| Routes | `app/Config/Routes.php` | Modificado | Rutas `GET inquilino/solicitudes`, `GET/POST inquilino/inmuebles/(:num)/solicitar` y `POST inquilino/solicitudes/(:num)/cancelar` bajo `auth` + `role:tenant` |
| View | `app/Views/tenant/rental_requests/form.php` | Nuevo | Formulario para solicitar alquiler con teléfono precargado del perfil y resumen lateral del inmueble |
| View | `app/Views/tenant/rental_requests/index.php` | Nuevo | Listado «Mis solicitudes» con insignias de estado, motivo de rechazo, acceso al contrato aprobado y acción de cancelación |
| View | `app/Views/tenant/rental_requests/cancel_confirm.php` | Nuevo | Confirmación de cancelación renderizada en servidor (sin depender de JavaScript) |
| View | `app/Views/layouts/panel.php` | Modificado | Agrega «Buscar inmuebles» y «Mis solicitudes» al menú lateral del rol `tenant` |
| View | `app/Views/dashboard/tenant_account_status.php` | Modificado | Agrega enlaces a «Buscar inmuebles» y «Mis solicitudes» en el estado vacío (Escenario 7) |
| View | `app/Views/properties/public_catalog.php` | Modificado | Agrega botón «Solicitar alquiler» en cada inmueble disponible, enlaces para inquilino autenticado y mensajes flash |
| CSS | `public/css/panel.css` | Modificado | Insignias `.badge-rental-request-*` (ícono + texto + color) y `.public-property-footer` usando solo variables de `tokens.css` |
| Test | `tests/feature/RentalRequestTest.php` | Nuevo | 40 pruebas de integración (429 aserciones) cubriendo CA-1 a CA-7, permisos por rol, visitante y concurrencia/modelo |
| Test | `tests/database/SchemaIntegrityTest.php` | Modificado | Verifica existencia de `rental_requests` y restricciones `CHECK` (`desired_months > 0` y motivo de rechazo) |
| Test | `tests/feature/TenantAccountStatusTest.php` | Modificado | Comprueba los accesos de Escenario 7 en el estado de cuenta vacío |
| Docs | `.docs/ANALISIS_DE_REQUERIMIENTOS.md`, `.docs/base_de_datos/MODELO_DE_DATOS.md`, `.docs/base_de_datos/inmogestion_schema.sql`, `.docs/historias_de_usuario/HU-30_solicitar_alquiler_inquilino.md` | Modificado | Glosario de estados (9.3), DER, modelo relacional, copia de esquema SQL y Definition of Done |

**Cambios de esquema de BD:** Nueva tabla `rental_requests` con 4 claves foráneas (`RESTRICT`) y 2 restricciones `CHECK`.
**Cambios de rutas / permisos:**
- `GET /inquilino/solicitudes` (`auth`, `role:tenant`)
- `GET/POST /inquilino/inmuebles/(:num)/solicitar` (`auth`, `role:tenant`)
- `POST /inquilino/solicitudes/(:num)/cancelar` (`auth`, `role:tenant`, titularidad verificada con respuesta 404 ante solicitud ajena)

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Unicidad de solicitud pendiente `(property_id, tenant_id)` verificada en transacción con `SELECT ... FOR UPDATE` | Índice único en BD | MySQL no soporta índices únicos parciales (`WHERE status = 'pending'`) y un inquilino debe poder volver a solicitar si una solicitud anterior fue cancelada o rechazada |
| Confirmación de cancelación renderizada en servidor (`confirmed=1`) | `confirm()` en JavaScript únicamente | Sigue el patrón exacto de HU-09 y HU-32 (`owner_deactivate_confirm.php`), funcionando con o sin JavaScript |
| `findRequestableProperty()` verifica `status = 'available'`, `approval_status = 'approved'`, `deleted_at IS NULL` y ausencia de contrato vigente | Verificar solo `status = 'available'` | Impide solicitar inmuebles publicados por propietarios aún no aprobados por administración (HU-32/33) o con estado inconsistente |

**Seguridad aplicada:** `tenant_id` tomado exclusivamente de la sesión; `property_id` tomado de la ruta y revalidado bajo bloqueo pesimista; autorización por rol (`role:tenant`) y por titularidad (404 para no filtrar existencia de solicitudes ajenas); CSRF global en todos los POST; validación en servidor con `AccountRules::valid_phone`; escape de salida con `esc()`; no exposición de `owner_id` ni datos del propietario al inquilino.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de integración HU-30 | `phpunit --no-coverage tests/feature/RentalRequestTest.php` | ✅ 40 pasan (429 aserciones) |
| Regresión Estado de cuenta (HU-15) | `phpunit --no-coverage tests/feature/TenantAccountStatusTest.php` | ✅ 2 pasan (31 aserciones) |
| Regresión Catálogo (HU-08) | `phpunit --no-coverage tests/feature/PropertyCatalogTest.php` | ✅ 7 pasan (54 aserciones) |
| Integridad de esquema BD (#22 + HU-30) | `phpunit --no-coverage tests/database/SchemaIntegrityTest.php` | ✅ 36 pasan (58 aserciones) |
| Migraciones | `php spark migrate` | ✅ Ejecuta `CreateRentalRequestsTable` sin errores |
| Estilos | Verificación con `git diff` sin literales hex/rgb ni degradados fuera de `public/css/tokens.css`; estados con ícono + texto + color | ✅ |

**Sin verificar:** Revisión visual interactiva en navegador a 1440 px y 390 px (verificada estructuralmente por pruebas de integración y clases compartidas de `panel.css`).

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Dependencia | HU-31 (#56) debe consumir `rental_requests` para que administración apruebe/rechace y cree el contrato vinculado | Media | Usar `RentalRequestModel` en HU-31 para listar, rechazar con motivo o aprobar vinculando `contract_id` atómicamente |
| R-2 | Dependencia | HU-29 (#54, detalle público) y HU-34 (#59, ficha de contrato del inquilino) aún están pendientes | Baja | Enlazar el botón de HU-29 a `inquilino/inmuebles/{id}/solicitar` y la ruta `inquilino/contratos/{id}` en HU-34 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Implementar HU-31 (#56): bandeja administrativa de solicitudes y conversión en contrato | `app/Controllers/RentalRequestController.php`, `app/Controllers/ContractController.php`, `app/Models/RentalRequestModel.php` | `ci4-core`, `ci4-data-layer` |
| 2 | Implementar HU-29 (#54) y HU-34 (#59) | `app/Controllers/PropertyController.php`, `app/Controllers/ContractController.php` | `ci4-core`, `frontend-html-css` |

**Siguiente HU sugerida:** HU-31 — Gestionar solicitudes de alquiler y crear el contrato (#56)
**Prerrequisitos del entorno:** `php spark migrate`

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Antigravity | 26 min | Implementación completa de HU-30: migración `rental_requests`, modelo, controlador, rutas, vistas, estilos, 42 pruebas automatizadas y documentación | Pendiente |

