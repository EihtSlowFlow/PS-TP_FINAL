# HU-12 — Finalizar o rescindir un contrato de alquiler

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P2 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-10

## Historia de usuario

Como administrador,

quiero finalizar o rescindir anticipadamente un contrato de locación,

para cerrar el período de cobros y devolver el inmueble a disponibilidad.

## Criterios de aceptación

### Escenario 1: Finalizar o rescindir un contrato vigente

**Dado** que un contrato se encuentra "Vigente" y el administrador tiene permiso para gestionarlo,

**cuando** solicita finalizarlo o rescindirlo y confirma la operación,

**entonces** el contrato pasa a "Finalizado" o "Rescindido" y el inmueble asociado vuelve a "Disponible".

### Escenario 2: Conservar el historial

**Dado** que el contrato tiene cuotas, pagos y comprobantes asociados,

**cuando** se finaliza o rescinde,

**entonces** todos esos registros se conservan íntegramente y siguen siendo consultables.

### Escenario 3: Contrato no vigente

**Dado** que el contrato ya está finalizado o rescindido,

**cuando** el administrador intenta finalizarlo o rescindirlo nuevamente,

**entonces** el sistema rechaza la operación e informa que el contrato no está vigente.

## Reglas de negocio

- Solo los contratos vigentes pueden finalizarse o rescindirse.
- El cierre del contrato no elimina cuotas, pagos ni comprobantes.
- El cambio de estado del contrato y del inmueble se realiza de forma atómica.

## Definition of Done

- [ ] El administrador puede finalizar o rescindir contratos vigentes.
- [ ] El inmueble vuelve a "Disponible" tras el cierre.
- [ ] Se conserva todo el historial financiero.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
