# HU-17 — Verificar y aprobar pagos informados

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Alto (transición de estados contables) · **Dependencias:** HU-16

## Historia de usuario

Como administrador,

quiero revisar los pagos informados por los inquilinos y aprobarlos o rechazarlos,

para mantener la contabilidad al día y confirmar las cuotas efectivamente cobradas.

## Criterios de aceptación

### Escenario 1: Aprobar un pago

**Dado** que existe un pago "Pendiente de validación",

**cuando** el administrador revisa el comprobante y lo aprueba,

**entonces** el pago pasa a "Aprobado" y la cuota asociada pasa a "Pagada".

Además, se registran la fecha y hora de aprobación y el empleado que la realizó.

### Escenario 2: Rechazar un pago

**Dado** que existe un pago "Pendiente de validación" con un comprobante ilegible o una transferencia no acreditada,

**cuando** el administrador lo rechaza e indica un motivo,

**entonces** el pago pasa a "Rechazado" y la cuota vuelve a su estado anterior ("Pendiente" o "Vencida").

Además, se conserva el registro del intento rechazado para auditoría.

### Escenario 3: Rechazo sin motivo

**Dado** que el administrador intenta rechazar un pago,

**cuando** no informa el motivo,

**entonces** el sistema rechaza la operación y exige indicarlo.

### Escenario 4: Pago ya resuelto

**Dado** que un pago ya fue aprobado o rechazado,

**cuando** el administrador intenta resolverlo nuevamente,

**entonces** el sistema rechaza la operación.

## Reglas de negocio

- Solo se pueden resolver pagos en estado "Pendiente de validación".
- El rechazo exige un motivo obligatorio.
- Se registran el responsable y la fecha de cada resolución.
- Los pagos rechazados no se eliminan.

## Definition of Done

- [ ] El administrador puede listar, aprobar y rechazar pagos pendientes.
- [ ] La cuota refleja el estado correcto tras cada resolución.
- [ ] Se registran responsable y fecha de la resolución.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
