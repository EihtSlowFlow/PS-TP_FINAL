# HU-19 — Calcular la liquidación a propietarios

> **Rol:** Administrador · **Alcance:** Final · **Prioridad:** P2 · **Story Points:** 8 · **Riesgo:** Alto (cálculos de dinero y comisiones) · **Dependencias:** HU-17

## Historia de usuario

Como administrador,

quiero generar las liquidaciones de los alquileres cobrados descontando automáticamente la comisión pactada,

para conocer el monto neto exacto a transferir a cada propietario sin errores contables.

## Criterios de aceptación

### Escenario 1: Generar la liquidación de una cuota cobrada

**Dado** que una cuota está "Pagada" con pago aprobado y aún no fue liquidada,

**cuando** el administrador genera la liquidación,

**entonces** el sistema calcula el monto bruto, la comisión según el porcentaje del contrato y el monto neto, y registra la liquidación como "Pendiente de transferencia".

### Escenario 2: Cuota no cobrada

**Dado** que una cuota no está pagada o su pago fue rechazado,

**cuando** el administrador intenta liquidarla,

**entonces** el sistema rechaza la operación.

### Escenario 3: Cuota ya liquidada

**Dado** que una cuota ya tiene una liquidación generada,

**cuando** el administrador intenta liquidarla nuevamente,

**entonces** el sistema rechaza la operación para evitar duplicados.

## Reglas de negocio

- Comisión = monto bruto × (porcentaje de comisión ÷ 100); neto = bruto − comisión.
- El porcentaje aplicado es el vigente en el contrato al momento de la liquidación.
- Cada cuota pagada se liquida una única vez.
- Los importes se calculan con precisión decimal y redondeo definido.

## Definition of Done

- [ ] El administrador puede generar liquidaciones de cuotas cobradas.
- [ ] Los importes de comisión y neto se calculan correctamente.
- [ ] No se generan liquidaciones duplicadas ni de cuotas no cobradas.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.
