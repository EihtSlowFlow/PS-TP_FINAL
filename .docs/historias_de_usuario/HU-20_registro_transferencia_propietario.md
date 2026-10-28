# HU-20 — Registrar la transferencia al propietario

> **Rol:** Administrador · **Alcance:** Final · **Prioridad:** P2 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-19

## Historia de usuario

Como administrador,

quiero registrar la transferencia bancaria realizada al propietario,

para asentar la acreditación efectiva y cerrar la liquidación.

## Criterios de aceptación

### Escenario 1: Registrar una transferencia

**Dado** que existe una liquidación "Pendiente de transferencia",

**cuando** el administrador registra la fecha de transferencia y el número de operación,

**entonces** la liquidación pasa a "Transferido".

Además, el monto deja de contarse en el saldo pendiente de liquidación.

### Escenario 2: Datos incompletos o inválidos

**Dado** que el administrador omite la fecha o el número de operación, o informa una fecha futura,

**cuando** intenta registrar la transferencia,

**entonces** el sistema rechaza la operación e indica los errores.

### Escenario 3: Liquidación ya transferida

**Dado** que una liquidación ya está "Transferida",

**cuando** el administrador intenta registrar otra transferencia,

**entonces** el sistema rechaza la operación.

## Reglas de negocio

- Solo se pueden transferir liquidaciones en estado "Pendiente de transferencia".
- La fecha y el número de operación son obligatorios.
- Una liquidación transferida no puede volver a modificarse.

## Definition of Done

- [ ] El administrador puede registrar la transferencia de una liquidación.
- [ ] El estado y el saldo pendiente se actualizan correctamente.
- [ ] Se evita registrar transferencias repetidas.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
