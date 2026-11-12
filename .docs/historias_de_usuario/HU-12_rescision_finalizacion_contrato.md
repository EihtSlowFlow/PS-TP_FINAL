# HU-12 — Finalizar o rescindir un contrato de alquiler

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P2 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-10

## Historia de usuario

Como administrador,

quiero finalizar o rescindir anticipadamente un contrato de locación,

para cerrar el período de cobros y devolver el inmueble a disponibilidad.

## Criterios de aceptación

### Escenario 1: Finalizar o rescindir un contrato vigente

**Dado** que un contrato se encuentra "Vigente" y el administrador tiene permiso para gestionarlo,

**cuando** solicita finalizarlo o rescindirlo, puede indicar el motivo y confirma la operación,

**entonces** el contrato pasa a "Finalizado" o "Rescindido" y el inmueble asociado vuelve a "Disponible".

Además, el sistema registra la fecha del cierre y, si se informó, el motivo.

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
- Un contrato vigente no tiene fecha ni motivo de cierre; al cerrarse se guarda la fecha.
- El cambio de estado del contrato y del inmueble se realiza de forma atómica.

## Definition of Done

- [x] El administrador puede finalizar o rescindir contratos vigentes.
- [x] El inmueble vuelve a "Disponible" tras el cierre.
- [x] Se conserva todo el historial financiero.
- [x] Se verifican los permisos de acceso.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes.

## Notas de integración (desde HU-10)

- El alta (`ContractModel::createAndRentProperty()`) es el modelo a seguir para el cierre: abre una transacción,
  **bloquea la fila del inmueble** (`SELECT … FOR UPDATE`), cambia el contrato y el inmueble, y revierte todo si algo
  falla. El cierre debe hacer lo mismo en sentido inverso: contrato a `finished` o `rescinded` con `closed_at` y
  `closing_reason` (la tabla exige ambos nulos mientras está `in_force`) e inmueble a `PropertyModel::STATUS_AVAILABLE`.
- Al salir de `in_force`, la columna generada `contracts.active_property_id` pasa a `NULL`: eso libera el índice único y
  permite crear un contrato nuevo sobre el mismo inmueble. No hace falta tocarla.
- Si el inmueble está en `maintenance` o fue dado de baja cuando se cierra el contrato, decidir si vuelve igual a
  «Disponible» (la regla actual dice que sí vuelve) o conserva su estado.
- La ficha (`admin/contracts/show.php`) ya muestra «Cerrado el» y «Motivo» para los contratos no vigentes: el cierre solo
  tiene que guardar esos datos. Falta agregar ahí el botón de cierre.
