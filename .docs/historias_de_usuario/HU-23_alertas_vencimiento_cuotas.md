# HU-23 — Recibir alertas de próximos vencimientos de cuotas

> **Rol:** Inquilino · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-13

## Historia de usuario

Como inquilino,

quiero recibir alertas sobre cuotas próximas a vencer,

para pagar a término y evitar recargos por mora.

## Criterios de aceptación

### Escenario 1: Cuota próxima a vencer

**Dado** que tengo una cuota pendiente que vence dentro de los próximos 5 días,

**cuando** ingreso al sistema o reviso mis alertas,

**entonces** el sistema me muestra un aviso con el período y la fecha de vencimiento.

### Escenario 2: Cuota lejana, pagada o en revisión

**Dado** que mi cuota vence en más de 5 días, ya está pagada o está en revisión,

**cuando** reviso mis alertas,

**entonces** el sistema no muestra aviso para esa cuota.

### Escenario 3: Cuota vencida

**Dado** que mi cuota ya venció,

**cuando** reviso mis alertas,

**entonces** el sistema no la muestra como próxima a vencer, sino como cuota vencida con su recargo.

## Reglas de negocio

- Se alerta únicamente sobre cuotas pendientes dentro de la ventana de anticipación definida (5 días).
- Cada inquilino solo recibe alertas sobre sus propias cuotas.
- Una alerta deja de mostrarse cuando la cuota se informa como pagada.
- La alerta se detecta consultando las cuotas y se registra una sola vez como notificación del inquilino, para poder marcarla como leída (HU-22).

## Definition of Done

- [x] El inquilino ve alertas de cuotas que vencen en 5 días o menos.
- [x] No se muestran alertas de cuotas pagadas, en revisión o vencidas.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.

## Decisiones tomadas al implementar

- **Ventana inclusiva (`0` a `5` días):** una cuota pendiente de un contrato vigente con `due_date` entre hoy (`0` días) y `hoy + 5 días` inclusive activa la alerta. A los `6` días no alerta, y con `due_date < hoy` pasa a mora (`Vencida` con recargo, nunca como próxima a vencer).
- **Detección bajo demanda sin duplicados:** `BaseController::syncRoleAlerts()` (invocado una sola vez por solicitud en `panelContext()` y al abrir `/notificaciones`) ejecuta `NotificationService::syncDueSoonInstallmentAlertsForTenant()` para usuarios con rol `tenant`, persistiendo la notificación una única vez mediante `notifyOnce()` (si el inquilino ya la leyó, no vuelve a crearse).
- **Aviso visual y notificación:** en `/inquilino/estado-cuenta` se muestra el bloque `#due-soon-alerts` con período, inmueble, fecha de vencimiento, cuenta regresiva en vivo y acceso directo a «Informar pago», además de un badge `Próximo vencimiento` en la fila pendiente.
- **Descarte al informar pago:** al registrar un pago en `PaymentModel::registerTenantPayment()` (dentro de la misma transacción), se marca como leída la notificación `installment_due_soon` pendiente de esa cuota, por lo que deja de mostrarse como alerta activa y se conserva en el historial. Si la transacción revierte, la alerta permanece sin leer.
