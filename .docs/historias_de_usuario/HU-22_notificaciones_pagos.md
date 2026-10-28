# HU-22 — Recibir notificaciones de pagos y resoluciones

> **Rol:** Inquilino / Administrador · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-17

## Historia de usuario

Como administrador o inquilino,

quiero recibir notificaciones sobre la carga y la validación de pagos,

para estar informado sin revisar manualmente cada estado.

## Criterios de aceptación

### Escenario 1: Notificar al administrador

**Dado** que un inquilino informa un nuevo pago,

**cuando** el sistema lo registra,

**entonces** se genera una notificación para los administradores indicando el inquilino, el período y el inmueble.

Además, se incrementa su contador de notificaciones no leídas.

### Escenario 2: Notificar al inquilino un pago aprobado

**Dado** que el administrador aprueba un pago,

**cuando** se confirma la aprobación,

**entonces** se genera una notificación para el inquilino indicando que su pago del período fue aprobado.

### Escenario 3: Notificar al inquilino un pago rechazado

**Dado** que el administrador rechaza un pago,

**cuando** se confirma el rechazo,

**entonces** se genera una notificación para el inquilino con el motivo e indicando que debe reenviar el comprobante.

### Escenario 4: Marcar como leída

**Dado** que tengo notificaciones sin leer,

**cuando** las abro o las marco como leídas,

**entonces** el contador de no leídas se actualiza.

## Reglas de negocio

- Cada usuario solo ve sus propias notificaciones.
- Las notificaciones se generan al producirse el evento que las origina.
- Las notificaciones leídas se conservan en el historial.

## Definition of Done

- [ ] Se generan notificaciones ante el envío, la aprobación y el rechazo de pagos.
- [ ] El contador de no leídas se actualiza correctamente.
- [ ] Cada usuario solo accede a sus notificaciones.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
