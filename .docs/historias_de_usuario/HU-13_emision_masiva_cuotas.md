# HU-13 — Emitir cuotas mensuales de forma masiva

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Alto (núcleo financiero) · **Dependencias:** HU-10

## Historia de usuario

Como administrador,

quiero emitir masivamente las cuotas mensuales de los contratos vigentes para un período determinado,

para habilitar las obligaciones de cobro a los inquilinos.

## Criterios de aceptación

### Escenario 1: Emisión de cuotas del período

**Dado** que existen contratos vigentes que cubren el período seleccionado,

**cuando** el administrador confirma la emisión masiva,

**entonces** el sistema genera una cuota por contrato con estado "Pendiente", vencimiento según el día pactado y monto igual al alquiler mensual del contrato.

Además, presenta un informe con la cantidad de cuotas emitidas y el monto total a recaudar.

### Escenario 2: Cuota ya emitida

**Dado** que ya existe una cuota emitida para un contrato en el período seleccionado,

**cuando** se ejecuta la emisión,

**entonces** el sistema omite ese contrato sin duplicar ni modificar la cuota existente.

### Escenario 3: Contratos no incluidos

**Dado** que un contrato está finalizado, rescindido o no cubre el período,

**cuando** se ejecuta la emisión,

**entonces** el sistema no genera cuotas para ese contrato.

### Escenario 4: Período inválido

**Dado** que el administrador no selecciona un período válido,

**cuando** intenta confirmar la emisión,

**entonces** el sistema rechaza la operación e informa el error.

## Reglas de negocio

- Solo se emiten cuotas para contratos vigentes que cubran el período.
- Existe como máximo una cuota por contrato y período.
- La numeración de cuotas es correlativa por contrato.
- Toda cuota nueva inicia con recargo por mora en cero y estado "Pendiente".

## Definition of Done

- [ ] El administrador puede emitir cuotas masivamente para un período.
- [ ] No se generan cuotas duplicadas.
- [ ] Se informa la cantidad de cuotas emitidas y el monto total.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
