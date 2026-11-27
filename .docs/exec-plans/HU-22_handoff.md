 # Informe de Avance y Traspaso (Handoff) — HU-22

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-22 (Notificaciones de pagos y validaciones) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-22 — Notificaciones de pagos y validaciones · [#47](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/47) (P3 en el tablero, base de HU-23/HU-24, 5 pts, riesgo medio) |
| **Sprint / Hito** | Examen final |
| **Rama** | `feature/hu-22-notificaciones-pagos` (base `origin/Sprint-2` en `3ad80a1`) |
| **Fecha de cierre** | 2026-11-27 |
| **Inicio / Fin** | 2026-11-27 15:41 → 2026-11-27 15:52 |
| **Tiempo invertido** | 11 min efectivos · 11 min de reloj · Medido (sin pausas de más de 10 min) |
| **Autor / Agente** | Ramiro (`EihtSlowFlow`), con Antigravity |
| **Estado global** | 🟢 Completada (lista para commit y pull request a `Sprint-2`) |
| **Avance estimado** | 100 % |

**Resumen:** se implementó la infraestructura central de notificaciones in-app (`NotificationModel`, `NotificationService`, `NotificationController`), la campana con contador y popover en el encabezado de todos los paneles (`layouts/panel.php`), el centro `/notificaciones` con filtros e historial, y la emisión atómica de notificaciones ante los eventos de pago (HU-16 y HU-17).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-22_notificaciones_pagos.md`](../historias_de_usuario/HU-22_notificaciones_pagos.md).

| # | Criterio | Estado | Evidencia |
|---|---|---|---|
| E-1 | Al informar un nuevo pago, se notifica a todos los usuarios activos con rol Administrador/Empleado (indicando inquilino, período e inmueble) y se incrementa el contador de no leídas | ✅ | `NotificationPaymentTest::testReportingPaymentNotifiesAllActiveStaffAndIncrementsUnreadCounter` |
| E-1 (atomicidad) | Si el registro del pago revierte, no se persiste ninguna notificación | ✅ | `NotificationPaymentTest::testRolledBackPaymentCreatesNoNotifications` |
| E-2 | Al aprobar un pago, se notifica al inquilino dueño de la cuota con el período y el inmueble | ✅ | `NotificationPaymentTest::testApprovingPaymentNotifiesOwningTenant` |
| E-3 | Al rechazar un pago, se notifica al inquilino con el motivo y la indicación de reenviar el comprobante (y no se notifica si falta el motivo) | ✅ | `NotificationPaymentTest::testRejectingPaymentNotifiesTenantWithReasonAndResubmitHint` |
| E-4 | Al abrir una notificación o marcarla como leída (individual o masivamente), se actualiza el contador y se conserva en el historial | ✅ | `NotificationPaymentTest::testMarkAsReadOpenTargetAndMarkAllAsReadUpdateCounterAndPreserveHistory` |
| RN | Cada usuario solo ve y modifica sus propias notificaciones; los visitantes son redirigidos al login | ✅ | `NotificationPaymentTest::testUserIsolationAndGuestProtection` |
| Base HU-23/24/30-33 | ENUMs ampliados y `NotificationService::notifyOnce()` listo para alertas sin duplicados | ✅ | `NotificationPaymentTest::testExpandedNotificationTypesAndNotifyOnceWorkCleanly` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Migration]` | `app/Database/Migrations/2026-11-27-160000_ExpandNotificationTypes.php` | Nuevo | Amplía `type` y `reference_type` de `notifications` para contemplar solicitudes de alquiler e inmuebles |
| `[Model]` | `app/Models/NotificationModel.php` | Nuevo | Constantes de tipos/categorías/tonos, resolución de rutas destino por rol, conteo de no leídas, filtros y marcado como leída |
| `[Library]` | `app/Libraries/NotificationService.php` | Nuevo | Servicio central `notifyUser`, `notifyStaff`, `notifyOnce` y helpers de negocio con soporte de conexión transaccional |
| `[Config]` | `app/Config/Services.php` | Modificado | Registra `service('notifications')` |
| `[Model]` | `app/Models/PaymentModel.php` | Modificado | Dispara `notifyPaymentSubmitted`, `notifyPaymentApproved` y `notifyPaymentRejected` dentro de las transacciones existentes |
| `[Controller]` | `app/Controllers/BaseController.php` | Modificado | Expone `unreadNotificationCount` y `recentNotifications` en `panelContext()` para todos los roles autenticados |
| `[Controller]` | `app/Controllers/NotificationController.php` | Nuevo | `index()`, `markRead()` (con soporte `open_target=1`) y `markAllRead()` con control de propiedad y CSRF |
| `[Config]` | `app/Config/Routes.php` | Modificado | Grupo `notificaciones` detrás del filtro `auth` (`GET /`, `POST leer-todas`, `POST (:id)/leer`) |
| `[View]` | `app/Views/layouts/panel.php` | Modificado | Campana superior con badge `#unread-notifications-badge` y popover `#notification-popover` |
| `[View]` | `app/Views/notifications/index.php` | Nuevo | Centro de notificaciones con tabs (Todas / No leídas / Leídas), filtro por categoría y acciones POST |
| `[JS]` | `public/js/layout.js` | Modificado | Apertura/cierre accesible del popover de la campana (con degradación elegante sin JS) |
| `[CSS]` | `public/css/panel.css` | Modificado | Estilos de campana, popover y listado de notificaciones usando únicamente variables de `tokens.css` |
| `[Test]` | `tests/feature/NotificationPaymentTest.php` | Nuevo | 7 pruebas de integración (94 aserciones) cubriendo escenarios 1–4, atomicidad, aislamiento y tipos ampliados |
| `[Docs]` | `.docs/historias_de_usuario/HU-22_notificaciones_pagos.md` | Modificado | DoD tildada y decisiones tomadas al implementar |
| `[Docs]` | `.docs/REGISTRO_DE_TIEMPOS.md` | Modificado | Fila de tiempo de HU-22 (#47) y actualización del total |

---

## 4. Verificación

- **Sintaxis PHP (`php -l`):** sin errores en los 8 archivos PHP creados/modificados.
- **Tokens CSS:** verificado que el diff en `public/css/panel.css` no introduce ningún literal hex/rgb ni degradado (`CSS_TOKENS_OK`).
- **Pruebas nuevas (`NotificationPaymentTest`):** ✅ 7 pruebas, 94 aserciones en verde.
- **Pruebas de regresión e integridad (`SchemaIntegrityTest`, `PaymentReviewTest`, `PaymentRegistrationTest`):** ✅ 63 pruebas en verde (en la corrida conjunta previa al ajuste del helper del test nuevo).
- **Limitación / pendiente:** no se hizo una revisión visual manual en el navegador ni se corrió la suite completa de más de 900 pruebas.

### Revisión de correctitud (2026-11-27, 15:56)

| Hallazgo | Severidad | Resolución |
|---|---|---|
| El `SELECT … FOR UPDATE` de `registerTenantPayment()` y `resolvePendingPayment()` se había ampliado con `JOIN` a `users`/`properties`, así que InnoDB bloqueaba también esas filas durante la transacción (riesgo de esperas o deadlocks con contratos o bajas de inmuebles) | Media | ✅ El bloqueo volvió a su alcance original. Los datos del mensaje se leen con `PaymentModel::notificationContextForInstallment()`, una lectura sin bloqueo dentro de la misma transacción |
| `inmogestion_schema.sql` no tenía los valores nuevos de los `ENUM` (la cátedra instala desde ese script, issue #71) | Media | ✅ Sincronizado con la migración |
| Las acciones del popover (marcar una o todas) mandaban siempre a `/notificaciones`, en vez de dejar al usuario en la página donde estaba | Baja | ✅ Con `origin=popover` vuelve a `previous_url()`, solo si es una URL del sitio. Prueba: `testPopoverActionsReturnToTheCurrentPageButNeverToAnExternalUrl` |
| La migración `ExpandNotificationTypes` faltaba aplicar en la base de desarrollo | Info | ✅ Aplicada con `php spark migrate` (batch 4) |
| Los contadores de las pestañas no tienen en cuenta el filtro de categoría. El listado muestra como máximo 100 avisos y no tiene paginación | Baja | Aceptado: la historia no pide paginación |

**Pruebas después de la revisión:** 147 de regresión (layout y flujos de pago: `TenantAccountStatus`, `PropertyApproval`, `RentalRequestAdmin`, `PropertyOwnerDeactivation`, `IdRoutes`, `SettlementGeneration`, `AuthAccessControl`, `Logout`, `TenantContracts`; 1471 aserciones) y 73 de notificaciones, pagos, esquema y estado de cuenta (459 aserciones). Todas en verde.

**Verificación visual en navegador (Chrome, `http://localhost:8081`):**
- Login con `admin.demo@example.com`, campana superior alineada con badge de rol, popover `#notification-popover` con cierre por `Escape` y navegación a `/notificaciones`.
- Centro `/notificaciones`: filtros por pestañas (`Todas`, `No leídas`, `Leídas`), filtro por categoría y estado vacío funcionando correctamente.
- Responsive móvil (`390x844`): sin desbordamiento horizontal (`scrollWidth: 390`), popover encuadrado dentro del viewport.
- Consola y red: 0 errores JavaScript y 0 respuestas HTTP 4xx/5xx.
- Ajustes menores de pulido aplicados tras la inspección visual: padding compacto en la barra de filtros, separador `·` entre fecha de lectura y fecha de creación, y eliminación de la línea divisoria sobrante en la tarjeta de estado vacío.

---

## 5. Pendientes y Traspaso

- **HU-23 (#48 — Alertas de vencimiento de cuotas):** reutilizar `service('notifications')->notifyOnce()` con `NotificationModel::TYPE_INSTALLMENT_DUE_SOON` y `NotificationModel::REF_INSTALLMENT` al consultar el estado de cuenta / notificaciones del inquilino.
- **HU-24 (#49 — Alertas de vencimiento de contratos):** reutilizar `service('notifications')->notifyOnce()` con `NotificationModel::TYPE_CONTRACT_EXPIRING` y `NotificationModel::REF_CONTRACT` para administración y propietarios.
- **HU-30 a HU-33 (opcional):** los helpers `notifyRentalRequest*` y `notifyProperty*` ya están listos en `NotificationService` para conectarse cuando el equipo lo considere oportuno.

