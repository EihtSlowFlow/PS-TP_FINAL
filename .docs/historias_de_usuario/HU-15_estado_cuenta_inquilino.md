# HU-15 — Consultar el estado de cuenta y las cuotas

> **Rol:** Inquilino · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-13, HU-14

## Historia de usuario

Como inquilino,

quiero consultar mi estado de cuenta con mis cuotas pagadas, pendientes y vencidas, y el detalle de recargos,

para conocer mis compromisos de pago y poder informar un pago rápidamente.

## Criterios de aceptación

### Escenario 1: Cuotas pendientes

**Dado** que tengo cuotas pendientes,

**cuando** consulto mi estado de cuenta,

**entonces** el sistema muestra cada cuota con su período, monto, fecha de vencimiento y la opción de informar el pago.

### Escenario 2: Cuotas vencidas

**Dado** que tengo cuotas vencidas,

**cuando** consulto mi estado de cuenta,

**entonces** el sistema las destaca e indica los días de atraso, el recargo acumulado y el monto total exigible.

### Escenario 3: Cuotas pagadas

**Dado** que tengo cuotas pagadas,

**cuando** consulto mi historial,

**entonces** el sistema muestra la fecha de pago, el medio utilizado y el acceso al comprobante aprobado.

### Escenario 4: Acceso a cuotas ajenas

**Dado** que intento consultar cuotas de otro inquilino,

**cuando** realizo la solicitud,

**entonces** el sistema deniega el acceso.

## Reglas de negocio

- Cada inquilino solo ve las cuotas de sus propios contratos.
- Las cuotas en revisión se identifican como tales y no admiten un nuevo pago simultáneo.
- Los montos y recargos mostrados provienen del cálculo del servidor.

## Definition of Done

- [ ] El inquilino ve sus cuotas agrupadas por estado.
- [ ] Se muestra el desglose de mora en las cuotas vencidas.
- [ ] Se restringe el acceso a datos de otros inquilinos.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
