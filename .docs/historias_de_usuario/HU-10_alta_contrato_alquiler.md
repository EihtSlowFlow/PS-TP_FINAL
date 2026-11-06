# HU-10 — Dar de alta un contrato de alquiler

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 8 · **Riesgo:** Alto (núcleo transaccional) · **Dependencias:** HU-04, HU-07

## Historia de usuario

Como administrador,

quiero dar de alta un contrato de locación vinculando un inmueble disponible con un inquilino,

para establecer los plazos, el monto mensual, la comisión y las condiciones de mora.

## Criterios de aceptación

### Escenario 1: Alta de un contrato válido

**Dado** que existe un inmueble activo y disponible y un inquilino activo,

**cuando** el administrador elige el inmueble y el inquilino, e informa fecha de inicio, fecha de finalización, monto mensual, porcentaje de incremento por actualización, porcentaje de comisión, día de vencimiento mensual y porcentaje de recargo diario por mora,

**entonces** el sistema registra el contrato con estado "Vigente" y cambia el estado del inmueble a "Alquilado".

Además, el contrato queda vinculado al propietario del inmueble, que el formulario completa y muestra al elegirlo, y el sistema muestra la ficha del contrato con un mensaje de confirmación.

### Escenario 2: Inmueble no disponible

**Dado** que un inmueble ya está afectado a otro contrato vigente o se encuentra inactivo,

**cuando** el administrador intenta asociarlo a un nuevo contrato,

**entonces** el sistema rechaza la operación e informa que el inmueble no está disponible.

### Escenario 3: Fechas inválidas

**Dado** que la fecha de finalización no es posterior a la fecha de inicio,

**cuando** el administrador intenta guardar el contrato,

**entonces** el sistema rechaza la operación e informa el error.

### Escenario 4: Consistencia ante fallos

**Dado** que ocurre un error al guardar el contrato o al actualizar el inmueble,

**cuando** se procesa la operación,

**entonces** no se persiste ninguno de los cambios, evitando estados inconsistentes.

## Reglas de negocio

- Un inmueble solo puede tener un contrato vigente a la vez.
- Solo pueden asociarse inmuebles activos y disponibles, e inquilinos y propietarios activos.
- El propietario del contrato es el propietario del inmueble elegido.
- El monto mensual debe ser mayor a cero y los porcentajes (incremento por actualización, comisión y recargo diario) deben estar entre 0 y 100.
- El día de vencimiento mensual debe estar entre 1 y 28.
- La creación del contrato y el cambio de estado del inmueble se realizan de forma atómica.

## Definition of Done

- [ ] El administrador puede crear contratos con todos los datos requeridos.
- [ ] El contrato queda "Vigente" y el inmueble "Alquilado".
- [ ] El sistema impide contratos duplicados sobre un mismo inmueble.
- [ ] La operación es atómica y se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
