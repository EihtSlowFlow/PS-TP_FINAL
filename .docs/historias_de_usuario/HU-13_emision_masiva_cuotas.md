# HU-13 — Emitir cuotas mensuales de forma masiva

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Alto (núcleo financiero) · **Dependencias:** HU-10

## Historia de usuario

Como administrador,

quiero emitir las cuotas mensuales de los contratos vigentes para un período determinado, de forma masiva o individual,

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

### Escenario 5: Emisión individual

**Dado** que existe un contrato vigente que cubre el período seleccionado,

**cuando** el administrador limita la emisión a ese contrato en la vista previa y confirma,

**entonces** el sistema genera solo la cuota de ese contrato, con las mismas reglas que la emisión masiva.

## Reglas de negocio

- Solo se emiten cuotas para contratos vigentes que cubran el período.
- La emisión puede ser masiva (todos los contratos vigentes del período) o individual (un solo contrato).
- Existe como máximo una cuota por contrato y período.
- La numeración de cuotas es correlativa por contrato.
- Toda cuota nueva inicia con recargo por mora en cero y estado "Pendiente".

## Definition of Done

- [ ] El administrador puede emitir cuotas para un período, de forma masiva o para un solo contrato.
- [ ] No se generan cuotas duplicadas.
- [ ] Se informa la cantidad de cuotas emitidas y el monto total.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
