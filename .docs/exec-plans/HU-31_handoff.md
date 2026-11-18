# Informe de Avance y Traspaso (Handoff) — HU-31

> **Propósito:** Documento de trazabilidad del avance de HU-31 y traspaso de contexto entre agentes/sesiones. Comparte la tabla `rental_requests` y `RentalRequestModel` con [`HU-30_handoff.md`](HU-30_handoff.md).

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-31 — Gestionar solicitudes de alquiler y crear el contrato · [#56](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/56) (P1, 3 pts) |
| **Sprint / Hito** | Sprint 2 (ampliación detectada el 17/11/2026) |
| **Rama** | `feature/hu-30-31-solicitudes-alquiler` (base `origin/Sprint-2` en `9106a3c`, conjunta con HU-30) |
| **Último commit** | Pendiente de commit |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-17 21:49 |
| **Inicio / Fin** | 2026-11-17 21:34 → 2026-11-17 21:49 |
| **Tiempo invertido** | 15 min efectivos · 15 min de reloj · Medido · sin pausas > 10 min. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Antigravity |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó HU-31 completa sobre la base de HU-30: bandeja administrativa `/admin/solicitudes` con solicitudes pendientes primero, indicadores KPI por estado, filtro por estado y buscador por inquilino o inmueble; contador de solicitudes pendientes en el menú lateral para Administrador y Empleado; ficha `/admin/solicitudes/{id}` con datos completos del inquilino, inmueble, propietario, mensaje y otras solicitudes pendientes sobre el mismo inmueble; rechazo con motivo obligatorio (`POST /admin/solicitudes/{id}/rechazo`, hasta 255 caracteres) visible para el inquilino; y aprobación (`GET /admin/contratos/nuevo?request_id={id}`) que preselecciona inmueble, inquilino, canon y fechas sugeridas en el formulario de HU-10 y al guardar vincula la solicitud aprobada al contrato (`contract_id`, `resolved_by`, `resolved_at`) y rechaza automáticamente las demás solicitudes pendientes del inmueble con el motivo «El inmueble fue alquilado a otro interesado» dentro de una única transacción atómica.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`../historias_de_usuario/HU-31_gestionar_solicitudes_contrato.md`](../historias_de_usuario/HU-31_gestionar_solicitudes_contrato.md) e issue #56.

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: bandeja `/admin/solicitudes` muestra pendientes primero, contador en el menú, inquilino, inmueble, fechas deseadas, fecha de envío y estado, con filtro por estado y buscador | ✅ | `RentalRequestAdminTest::testScenario1AdminAndEmployeeSeeTrayWithPendingFirstSidebarCounterFilterAndSearch` |
| CA-2 | Escenario 2: ficha `/admin/solicitudes/{id}` muestra datos del inquilino, inmueble, propietario, precio base, mensaje y otras solicitudes pendientes sobre el mismo inmueble | ✅ | `RentalRequestAdminTest::testScenario2RequestFileShowsTenantPropertyOwnerPriceMessageAndCompetingPendingRequests`, `testScenario2MissingRequestRedirectsToTrayWithError` |
| CA-3 | Escenario 3: aprobar abre el formulario de contrato de HU-10 preseleccionando inmueble, inquilino y plazo sugerido, y al guardar aprueba la solicitud y la vincula al contrato de forma atómica | ✅ | `RentalRequestAdminTest::testScenario3And4ApprovingOpensPreselectedContractFormLinksContractAtomicallyAndAutoRejectsCompetingRequests`, `testScenario3AtomicityRollsBackContractAndPropertyWhenRequestUpdateFails` |
| CA-4 | Escenario 4: al crear el contrato, las otras solicitudes pendientes sobre el inmueble pasan automáticamente a «Rechazada» con el motivo «El inmueble fue alquilado a otro interesado» | ✅ | `RentalRequestAdminTest::testScenario3And4ApprovingOpensPreselectedContractFormLinksContractAtomicallyAndAutoRejectsCompetingRequests` |
| CA-5 | Escenario 5: rechazar una solicitud pendiente exige motivo obligatorio (hasta 255 caracteres), guarda `resolved_by` y `resolved_at`, y el inquilino ve el motivo en «Mis solicitudes» | ✅ | `RentalRequestAdminTest::testScenario5RejectingPendingRequestStoresReasonAndTenantSeesItInMyRequests`, `testScenario5InvalidRejectionReasonIsRefusedAndRequestStaysPending`, `testScenario5AlreadyResolvedOrCancelledRequestCannotBeRejectedAgain` |
| CA-6 | Escenario 6: si el inmueble dejó de estar disponible, el propietario/inquilino fue dado de baja, la solicitud ya no está pendiente o se altera `property_id`/`tenant_id`, se rechaza la operación con las reglas de HU-10 | ✅ | `RentalRequestAdminTest::testScenario6CannotApproveWhenPropertyBecameUnavailableOrOwnerDeactivatedOrTenantDeactivated`, `testScenario6NonPendingOrMissingRequestCannotBeApprovedIntoAContract` |
| CA-7 | Escenario 7: propietarios, inquilinos y visitantes tienen acceso denegado a `/admin/solicitudes` y sus subrutas | ✅ | `RentalRequestAdminTest::testScenario7OwnersAndTenantsAreDeniedAccessToAdminRequestRoutes`, `testScenario7VisitorsAreRedirectedToLoginAndRejectRouteAcceptsPostOnly` |

**Fuera de alcance / diferido:**
- **Notificaciones en base de datos (HU-22, #47):** aún no implementada en el proyecto; tanto el inquilino aprobado como los rechazados ven el resultado y el motivo actualizado inmediatamente en `/inquilino/solicitudes`.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/RentalRequestModel.php` | Modificado | Agrega `paginateForAdmin()`, `getStatusCounts()`, `findForAdmin()`, `findOtherPendingForProperty()`, `rejectByAdmin()` con `FOR UPDATE` y `AUTO_REJECTION_REASON_PROPERTY_RENTED` |
| Model | `app/Models/ContractModel.php` | Modificado | Extiende `createAndRentProperty()` con `?int $requestId` y `?int $resolvedByUserId` para aprobar y vincular la solicitud (`contract_id`) y rechazar las demás pendientes del inmueble en la misma transacción |
| Controller | `app/Controllers/RentalRequestController.php` | Modificado | Agrega `adminIndex()`, `adminShow()` y `adminReject()` con filtros, paginación y validación de motivo |
| Controller | `app/Controllers/ContractController.php` | Modificado | Extiende `create()` y `store()` para soportar `request_id`, preseleccionar partes y plazo sugerido y validar coincidencia de inmueble/inquilino y estado pendiente |
| Controller | `app/Controllers/BaseController.php` | Modificado | Agrega `pendingRequestCount` en `panelContext()` para `admin` y `employee` |
| Routes | `app/Config/Routes.php` | Modificado | Agrega `GET admin/solicitudes`, `GET admin/solicitudes/(:num)` y `POST admin/solicitudes/(:num)/rechazo` bajo `auth` + `role:admin,employee` |
| View | `app/Views/admin/rental_requests/index.php` | Nuevo | Bandeja administrativa con KPIs, buscador, filtro de estado, tabla ordenada con pendientes primero y paginación |
| View | `app/Views/admin/rental_requests/show.php` | Nuevo | Ficha de la solicitud con datos del inquilino, inmueble, propietario, solicitudes competidoras y panel lateral de resolución |
| View | `app/Views/admin/contracts/form.php` | Modificado | Aviso de aprobación de solicitud, campo oculto `request_id` y preselección de inquilino y fechas sugeridas |
| View | `app/Views/layouts/panel.php` | Modificado | Entrada «Solicitudes» con insignia `#pending-requests-badge` en el menú de `admin` y `employee` |
| Test | `tests/feature/RentalRequestAdminTest.php` | Nuevo | 18 pruebas de integración (305 aserciones) cubriendo CA-1 a CA-7, atomicidad ante fallos de BD y permisos |
| Docs | `.docs/historias_de_usuario/HU-31_gestionar_solicitudes_contrato.md` | Modificado | Definition of Done marcado |

**Cambios de esquema de BD:** Ninguno adicional (reutiliza `rental_requests` creada en HU-30).
**Cambios de rutas / permisos:**
- `GET /admin/solicitudes` (`auth`, `role:admin,employee`)
- `GET /admin/solicitudes/(:num)` (`auth`, `role:admin,employee`)
- `POST /admin/solicitudes/(:num)/rechazo` (`auth`, `role:admin,employee`)

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Aprobación, alta de contrato, cambio de inmueble a `rented` y rechazo de solicitudes competidoras dentro de `ContractModel::createAndRentProperty()` | Actualizar las solicitudes desde el controlador después de crear el contrato | Garantiza atomicidad total: si falla cualquier paso (validado con fallo real de FK en `testScenario3AtomicityRollsBackContractAndPropertyWhenRequestUpdateFails`), se revierte toda la operación |
| Fechas de inicio y fin sugeridas a partir de `desired_start_date` y `desired_months`, editables en el formulario de contrato | Bloquear las fechas en el formulario de contrato | Sigue la propuesta del issue #56: la solicitud sugiere el plazo pero la administración formaliza las condiciones definitivas con las validaciones de HU-10 |
| Verificación de coincidencia de `property_id` y `tenant_id` contra la solicitud tanto en el controlador como bajo bloqueo `FOR UPDATE` en el modelo | Confiar en los selectores preseleccionados | Impide que se manipule el POST para aprobar una solicitud vinculándola a otro inmueble o inquilino |

**Seguridad aplicada:** Rutas restringidas a `admin` y `employee`; POST con filtro CSRF global; bloqueo pesimista (`FOR UPDATE`) sobre inmueble y solicitudes; `resolved_by` tomado de la sesión; validación en servidor del motivo de rechazo (obligatorio, hasta 255 caracteres) y de todas las reglas de HU-10; salida escapada con `esc()`.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de integración HU-31 | `phpunit --no-coverage tests/feature/RentalRequestAdminTest.php` | ✅ 18 pasan (305 aserciones) |
| Tests de integración HU-30 | `phpunit --no-coverage tests/feature/RentalRequestTest.php` | ✅ 40 pasan (429 aserciones) |
| Regresión Contratos (HU-10) | `phpunit --no-coverage tests/feature/ContractCreationTest.php` | ✅ 79 pasan (2 pruebas con `CREATE TRIGGER` requieren privilegio `SUPER` en MySQL con binlog activo, documentado previamente en el repositorio) |
| Estilos | Verificación con `git diff` sin literales hex/rgb ni degradados fuera de `public/css/tokens.css` | ✅ |

**Sin verificar:** Revisión visual interactiva en navegador (el servidor local se detuvo a pedido del usuario antes de iniciar HU-31).

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Dependencia | Las notificaciones automáticas al aprobar/rechazar (HU-22, #47) aún no están implementadas | Baja | Emitirlas desde `RentalRequestModel::rejectByAdmin()` y `ContractModel::createAndRentProperty()` cuando se implemente HU-22 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Implementar HU-29 (#54, detalle público de inmueble) o HU-34 (#59, consultar mis contratos como inquilino) | `app/Controllers/PropertyController.php`, `app/Controllers/ContractController.php` | `ci4-core`, `frontend-html-css` |

**Siguiente HU sugerida:** HU-34 — Consultar mis contratos (inquilino, #59) o HU-29 — Detalle público de un inmueble (#54)
**Prerrequisitos del entorno:** `php spark migrate`

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Antigravity | 15 min | Implementación completa de HU-31: bandeja administrativa, ficha, rechazo con motivo, aprobación prellenando contrato con vinculación y cierre atómico de solicitudes competidoras, y 18 pruebas automatizadas | Pendiente |

