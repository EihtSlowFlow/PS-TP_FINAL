# HU-14 — Calcular automáticamente el recargo por mora

> **Rol:** Sistema / Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-13

## Historia de usuario

Como administrador,

quiero que el sistema calcule automáticamente el recargo diario por mora en las cuotas vencidas,

para aplicar los intereses punitorios pactados en cada contrato.

## Criterios de aceptación

### Escenario 1: Cuota vencida sin abonar

**Dado** que una cuota está "Pendiente" y la fecha actual supera su fecha de vencimiento,

**cuando** se consulta el estado de cuenta o el listado de cobranzas,

**entonces** la cuota pasa a "Vencida" y se calcula su recargo según los días de atraso y el porcentaje diario pactado en el contrato.

Además, el monto total exigible se muestra desglosado en monto base y recargo.

### Escenario 2: Cuota abonada a término

**Dado** que una cuota fue pagada hasta su fecha de vencimiento,

**cuando** se consulta su detalle,

**entonces** el recargo por mora permanece en cero.

### Escenario 3: Cuota en revisión

**Dado** que el inquilino informó un pago y este se encuentra pendiente de validación,

**cuando** se recalcula la mora,

**entonces** el recargo de la cuota no se modifica mientras dure la revisión.

## Reglas de negocio

- Recargo = monto base × (porcentaje diario de mora ÷ 100) × días de atraso.
- Los días de atraso se cuentan desde el día posterior al vencimiento.
- Las cuotas pagadas no generan ni modifican recargos.
- El cálculo se realiza en el servidor y es reproducible.

## Definition of Done

- [ ] El sistema marca como vencidas las cuotas que superan su fecha límite.
- [ ] El recargo se calcula correctamente según el contrato.
- [ ] El desglose de base, recargo y total es visible.
- [ ] Las cuotas pagadas conservan recargo en cero.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.
