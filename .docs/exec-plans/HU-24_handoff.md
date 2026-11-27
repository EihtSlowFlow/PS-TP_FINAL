# Informe de Avance y Traspaso (Handoff) — HU-24

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-24 (Alertas de vencimiento de contratos) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-24 — Alertas de vencimiento de contratos · [#49](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/49) (P3, 3 pts, riesgo bajo) |
| **Sprint / Hito** | Examen final |
| **Rama** | `feature/hu-24-alertas-vencimiento-contratos` (base `origin/Sprint-2` en `6d9eec5`) |
| **Fecha de cierre** | 2026-11-27 |
| **Inicio / Fin** | 2026-11-27 17:33 → 2026-11-27 17:56 |
| **Tiempo invertido** | 9 min efectivos · 23 min de reloj · Medido (pausa de 14 min entre 17:37 y 17:51 descontada) |
| **Autor / Agente** | Ramiro (`EihtSlowFlow`), con Antigravity |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** se implementó la detección bajo demanda y visualización de alertas de contratos vigentes que vencen dentro de los próximos 60 días corridos (`0` a `60` días inclusive) para administración (`admin`/`employee`) y propietarios (`owner`, acotado estrictamente a sus propios inmuebles). Cada contrato elegible genera una única notificación `contract_expiring` por destinatario en la campana y `/notificaciones`, se destaca en el bloque `#expiring-contracts-alerts` de los paneles y listados de contratos con enlace directo a la ficha, y se descarta automáticamente si el contrato se cierra.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-24_alertas_vencimiento_contratos.md`](../historias_de_usuario/HU-24_alertas_vencimiento_contratos.md).

| # | Criterio | Estado | Evidencia |
|---|---|---|---|
| E-1 + límites (`0`, `1`, `60` días) | Contratos vigentes que vencen hoy, mañana o en 60 días muestran alerta con inmueble, inquilino, fecha de vencimiento, cuenta regresiva y enlace directo a la ficha del contrato; persisten una notificación `contract_expiring` por destinatario y abrirla redirige a la ficha | ✅ | `ContractExpiringAlertTest::testInForceContractsWithinSixtyDaysAlertStaffAndOwnerWithDirectLinks` |
| E-2 + límites (`61`, `-1` días, `finished`, `rescinded`) | Contratos a 61 días, con fecha ya pasada o no vigentes no muestran alerta ni generan notificación | ✅ | `ContractExpiringAlertTest::testContractsOutsideSixtyDaysPastEndOrNotInForceTriggerNoAlerts` |
| E-3 (alcance por propietario + exclusión inquilino) | Cada propietario solo ve alertas de sus propios contratos y nunca de terceros; los inquilinos no reciben alertas `contract_expiring` | ✅ | `ContractExpiringAlertTest::testOwnerSeesOnlyOwnExpiringContractsAndTenantReceivesNone` |
| RN (una sola vez) | Visitas repetidas y lectura previa no duplican ni recrean la notificación; entrar directo a `/notificaciones` sincroniza al instante | ✅ | `ContractExpiringAlertTest::testAlertIsPersistedOncePerRecipientAndNotRecreatedAfterRead` |
| RN (cierre de contrato) | Finalizar o rescindir el contrato marca como leídas las alertas `contract_expiring` sin leer y retira el banner | ✅ | `ContractExpiringAlertTest::testClosingContractDismissesUnreadAlertsAndRemovesBanner` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Model]` | `app/Models/ContractModel.php` | Modificado | `EXPIRING_WINDOW_DAYS = 60`, `today()`, `daysUntilEnd()`, `isExpiringSoon()`, `findExpiringSoon(?int $ownerId, ?string $today)` (usa `idx_contracts_status_end_date`) y descarte de alertas sin leer al cerrar contrato en `closeContract()` |
| `[Library]` | `app/Libraries/NotificationService.php` | Modificado | `syncExpiringContractAlertsForStaff()`, `syncExpiringContractAlertsForOwner()` (vía `notifyOnce()`) y `markContractExpiringAsRead()` |
| `[Controller]` | `app/Controllers/BaseController.php` | Modificado | Conecta sincronización de contratos por vencer para `admin`, `employee` y `owner` dentro de `syncRoleAlerts()` |
| `[Controller]` | `app/Controllers/DashboardController.php` | Modificado | Pasa `expiringContracts` (global para administración, acotado al propietario para `ownerDashboard`) a los paneles |
| `[Controller]` | `app/Controllers/ContractController.php` | Modificado | Pasa `expiringContracts` y `contractToday` a `/admin/contratos` y `/propietario/contratos` |
| `[View]` | `app/Views/partials/expiring_contracts_alert.php` | Nuevo | Parcial reutilizable `#expiring-contracts-alerts` (listo para HU-25 y HU-26), sin botones de renovación fuera de alcance |
| `[View]` | `app/Views/dashboard/panel_placeholder.php` | Modificado | Incluye `partials/expiring_contracts_alert` |
| `[View]` | `app/Views/contracts/index.php` | Modificado | Incluye `partials/expiring_contracts_alert` y muestra badge `Por vencer` en filas dentro de la ventana |
| `[Test]` | `tests/feature/ContractExpiringAlertTest.php` | Nuevo | 5 pruebas de integración cubriendo escenarios 1–3, límites (`-1`, `0`, `1`, `60`, `61` días), alcance por propietario, idempotencia y cierre |
| `[Docs]` | `.docs/historias_de_usuario/HU-24_alertas_vencimiento_contratos.md` | Modificado | DoD tildada y decisiones de implementación |
| `[Docs]` | `.docs/REGISTRO_DE_TIEMPOS.md` | Modificado | Fila de tiempo de HU-24 (#49) y actualización del total |

---

## 4. Verificación

- **Lint PHP (`php -l`):** sin errores en los 8 archivos PHP tocados.
- **Pruebas nuevas y de notificaciones (`ContractExpiringAlertTest`, `InstallmentDueSoonAlertTest`, `NotificationPaymentTest`):** ✅ **19 pruebas, 286 aserciones en verde**.
- **Regresión (`AuthAccessControlTest`, `TenantContractsTest`):** ✅ **39 pruebas, 348 aserciones en verde**.
- **Base de datos y CSS:** no requiere nuevas migraciones (reutiliza `contract_expiring`). Se agregó una regla menor en `public/css/panel.css` (usando solo tokens de `tokens.css`) para que el botón `button.link-button` de avisos sin leer en el popover luzca igual que el enlace de avisos leídos.
- **Verificación visual en navegador (Chrome, `http://localhost:8081`):**
  - **Administrador (`admin.demo@example.com`):** `#expiring-contracts-alerts` en `/admin/dashboard` y `/admin/contratos` muestra *Los Pinos 120 · Neuquén*, *Sofía Díaz*, vencimiento `21/12/2026` (*Vence en 24 días*), badge `Por vencer` solo en esa fila, enlace «Ver contrato» funcional a `/admin/contratos/2`, campana y `/notificaciones?categoria=contratos` correctos.
  - **Propietario (`owner1.demo@example.com`):** `#expiring-contracts-alerts` en `/propietario/dashboard` y `/propietario/contratos` con enlace «Ver contrato» a `/propietario/contratos/2`, sin botón «Iniciar renovación», badge `1` en la campana y listado `/notificaciones?categoria=contratos` correcto.
  - **Responsive móvil (`390×844`):** 0 desbordamiento horizontal; banner y botones apilan limpiamente.
  - **Consola y red:** 0 errores JavaScript y 0 respuestas HTTP 4xx/5xx.

---

## 5. Pendientes y Traspaso

- **HU-25 (#50 — Dashboard ejecutivo) y HU-26 (#51 — Panel del propietario):** pueden reutilizar directamente `model(ContractModel::class)->findExpiringSoon()` (administración) / `findExpiringSoon($ownerId)` (propietario) y el parcial `app/Views/partials/expiring_contracts_alert.php`.
- Con HU-22, HU-23 y HU-24 completadas queda cerrado todo el módulo de **Notificaciones** (#47, #48 y #49).

