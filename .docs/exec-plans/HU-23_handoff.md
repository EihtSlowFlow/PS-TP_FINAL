# Informe de Avance y Traspaso (Handoff) — HU-23

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-23 (Alertas de vencimiento de cuotas) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-23 — Alertas de vencimiento de cuotas · [#48](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/48) (P3, 3 pts, riesgo bajo) |
| **Sprint / Hito** | Examen final |
| **Rama** | `feature/hu-23-alertas-vencimiento-cuotas` (base `feature/hu-22-notificaciones-pagos` en `deb071c`) |
| **Fecha de cierre** | 2026-11-27 |
| **Inicio / Fin** | 2026-11-27 16:21 → 2026-11-27 16:29 |
| **Tiempo invertido** | 8 min efectivos · 8 min de reloj · Medido (sin pausas de más de 10 min) |
| **Autor / Agente** | Ramiro (`EihtSlowFlow`), con Antigravity |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** se implementó la detección bajo demanda y visualización de alertas de cuotas pendientes que vencen dentro de los próximos 5 días (`0` a `5` días inclusive) para inquilinos con contratos vigentes. Cada cuota elegible genera una única notificación `installment_due_soon` en la campana y `/notificaciones`, se destaca en el banner `#due-soon-alerts` del estado de cuenta (`/inquilino/estado-cuenta`), y deja de mostrarse como alerta activa apenas se informa el pago.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-23_alertas_vencimiento_cuotas.md`](../historias_de_usuario/HU-23_alertas_vencimiento_cuotas.md).

| # | Criterio | Estado | Evidencia |
|---|---|---|---|
| E-1 + límites (`0`, `1`, `5` días) | Cuotas pendientes que vencen hoy, mañana o en 5 días muestran banner con período, inmueble, vencimiento, cuenta regresiva y enlace «Informar pago», y persisten una notificación `installment_due_soon` cada una | ✅ | `InstallmentDueSoonAlertTest::testPendingInstallmentsWithinFiveDaysShowBannerAndPersistSingleNotifications` |
| E-2 + límite (`6` días) | Cuotas a 6 días, pagadas, en revisión o de contratos cerrados no generan banner ni notificación | ✅ | `InstallmentDueSoonAlertTest::testFarPaidUnderReviewAndClosedContractInstallmentsDoNotTriggerAlerts` |
| E-3 (`-1` día) | Cuotas vencidas se muestran como «Vencida» con su recargo por mora y nunca como próximas a vencer (incluso si se abre `/notificaciones` antes de refrescar mora) | ✅ | `InstallmentDueSoonAlertTest::testOverdueInstallmentIsShownAsOverdueWithLateFeeAndNeverAsDueSoon` |
| RN (una sola vez) | Visitas repetidas o lectura previa no duplican ni recrean la notificación | ✅ | `InstallmentDueSoonAlertTest::testAlertIsPersistedOnlyOnceAndNotRecreatedAfterBeingMarkedRead` |
| RN (descarte al pagar) | Al informar pago la alerta desaparece del banner y pasa a leída atómicamente (si revierte el pago, permanece sin leer) | ✅ | `InstallmentDueSoonAlertTest::testAlertDisappearsOncePaymentIsReportedAndSurvivesRollback` |
| RN (aislamiento + entrada directa) | Cada inquilino solo ve sus propias cuotas y entrar directo a `/notificaciones` sincroniza sus alertas al instante | ✅ | `InstallmentDueSoonAlertTest::testTenantSeesOnlyOwnAlertsAndDirectNotificationsVisitSyncsImmediately` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Model]` | `app/Models/InstallmentModel.php` | Modificado | `DUE_SOON_WINDOW_DAYS = 5`, `isDueSoon()` y `findDueSoonForTenant()` |
| `[Library]` | `app/Libraries/NotificationService.php` | Modificado | `syncDueSoonInstallmentAlertsForTenant()` (con `notifyOnce`) y `markInstallmentDueSoonAsRead()` |
| `[Model]` | `app/Models/PaymentModel.php` | Modificado | Marca como leída la alerta `installment_due_soon` de la cuota dentro de la transacción de `registerTenantPayment()` |
| `[Controller]` | `app/Controllers/BaseController.php` | Modificado | `syncRoleAlerts()` con guardia por solicitud en `panelContext()` (preparado para conectar HU-24) |
| `[Controller]` | `app/Controllers/NotificationController.php` | Modificado | Invoca `syncRoleAlerts()` antes de consultar el listado en `index()` |
| `[Controller]` | `app/Controllers/DashboardController.php` | Modificado | Calcula y ordena `dueSoonInstallments` en `tenantAccountStatus()` |
| `[View]` | `app/Views/dashboard/tenant_account_status.php` | Modificado | Renderiza el banner `#due-soon-alerts` y el badge `Próximo vencimiento` en filas elegibles |
| `[CSS]` | `public/css/panel.css` | Modificado | Estilos `.due-soon-alert-*` usando únicamente tokens de `tokens.css` |
| `[Test]` | `tests/feature/InstallmentDueSoonAlertTest.php` | Nuevo | 6 pruebas de integración (72 aserciones) cubriendo escenarios 1–3, valores límite (`-1`, `0`, `1`, `5`, `6`), idempotencia, descarte al pagar y aislamiento |
| `[Docs]` | `.docs/historias_de_usuario/HU-23_alertas_vencimiento_cuotas.md` | Modificado | DoD tildada y decisiones de implementación |
| `[Docs]` | `.docs/REGISTRO_DE_TIEMPOS.md` | Modificado | Fila de tiempo de HU-23 (#48) y actualización del total |

---

## 4. Verificación

- **Lint PHP (`php -l`):** sin errores en los 7 archivos PHP modificados.
- **Tokens CSS:** verificado sin literales hex/rgb ni degradados nuevos (`CSS_TOKENS_OK`).
- **Pruebas nuevas (`InstallmentDueSoonAlertTest`):** ✅ 6 pruebas, 72 aserciones en verde.
- **Pruebas de regresión (`TenantAccountStatusTest`, `NotificationPaymentTest`, `PaymentRegistrationTest`):** ✅ 31 pruebas, 342 aserciones en verde.
- **Base de datos:** no requiere nuevas migraciones (reutiliza `installment_due_soon` e `installment` ya presentes en `notifications`).
- **Verificación visual en navegador (Chrome, `http://localhost:8081`):**
  - Login con `tenant1.demo@example.com` (Lucas Pérez).
  - `/inquilino/estado-cuenta`: las cuotas vencidas (`10/11/2026` y `10/10/2026`) se muestran bajo **Vencida** con su recargo y días de atraso (nunca como «Próximo vencimiento»), y `#due-soon-alerts` no se renderiza al no haber cuotas pendientes en la ventana de 5 días.
  - Campana superior y `/notificaciones`: popover funcional, filtro por categoría `Vencimientos de cuotas` (`?categoria=cuotas`) y estado vacío en `No leídas` verificados.
  - Responsive móvil (`390×844`): sin desbordamiento horizontal (`scrollWidth: 390`).
  - Consola y red: 0 errores JavaScript y 0 respuestas HTTP 4xx/5xx.

---

## 5. Pendientes y Traspaso

- **HU-24 (#49 — Alertas de vencimiento de contratos):** conectar la sincronización de contratos por vencer (ventana de 60 días) para administración y propietarios directamente dentro de `BaseController::syncRoleAlerts()`.
- **Orden de merge:** esta rama parte de `feature/hu-22-notificaciones-pagos` (PR #73); conviene mergear primero #73 en `Sprint-2` y luego el PR de HU-23.

