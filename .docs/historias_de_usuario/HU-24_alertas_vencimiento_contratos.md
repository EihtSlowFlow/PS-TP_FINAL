# HU-24 — Recibir alertas de vencimiento de contratos

> **Rol:** Administrador / Propietario · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-10

## Historia de usuario

Como administrador o propietario,

quiero recibir alertas de los contratos que vencerán en los próximos 60 días,

para iniciar a tiempo la renovación o coordinar la restitución del inmueble.

## Criterios de aceptación

### Escenario 1: Contrato próximo a vencer

**Dado** que un contrato vigente vence dentro de los próximos 60 días,

**cuando** el administrador o el propietario del inmueble ingresa al sistema,

**entonces** el sistema muestra una alerta con el inmueble, el inquilino y la fecha de vencimiento.

Además, permite acceder directamente al contrato.

### Escenario 2: Contrato fuera de la ventana

**Dado** que un contrato vence en más de 60 días o ya no está vigente,

**cuando** se consultan las alertas,

**entonces** el sistema no muestra alerta para ese contrato.

### Escenario 3: Alcance por propietario

**Dado** que soy propietario,

**cuando** consulto mis alertas,

**entonces** solo veo las de contratos de mis inmuebles.

## Reglas de negocio

- La ventana de anticipación es de 60 días corridos antes de la fecha de finalización.
- Solo se alerta sobre contratos vigentes.
- El propietario solo recibe alertas de sus propios inmuebles.
- La alerta se detecta consultando los contratos y se registra una sola vez como notificación de cada destinatario, para poder marcarla como leída (HU-22).

## Definition of Done

- [x] El administrador y el propietario ven alertas de contratos que vencen en 60 días o menos.
- [x] No se muestran alertas de contratos fuera de la ventana o no vigentes.
- [x] Cada propietario solo ve las alertas de sus contratos.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.

## Decisiones tomadas al implementar

- **Ventana inclusiva (`0` a `60` días):** `ContractModel::findExpiringSoon()` consulta contratos en estado `in_force` con `end_date` entre hoy (`0` días) y `hoy + 60 días` inclusive, aprovechando el índice `idx_contracts_status_end_date`. A los `61` días, con fecha ya pasada o en estado `finished`/`rescinded` no genera alerta.
- **Destinatarios y persistencia «una sola vez»:** `BaseController::syncRoleAlerts()` sincroniza una vez por solicitud las notificaciones `contract_expiring` para usuarios activos `admin`/`employee` (todos los contratos por vencer) y `owner` (estrictamente filtrados por `contracts.owner_id`). `NotificationService::notifyOnce()` evita duplicados incluso después de marcar la notificación como leída.
- **Descarte al cerrar contrato:** `ContractModel::closeContract()` marca automáticamente como leídas las alertas `contract_expiring` sin leer de ese contrato dentro de la misma transacción.
- **Bloque reutilizable para HU-25 y HU-26:** se creó el parcial `app/Views/partials/expiring_contracts_alert.php` (incluido en `/admin/dashboard`, `/propietario/dashboard`, `/admin/contratos` y `/propietario/contratos`), con acceso directo a la ficha del contrato y sin acción de renovación (fuera de alcance según el issue #49).
