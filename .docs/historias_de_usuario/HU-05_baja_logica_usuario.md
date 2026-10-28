# HU-05 — Dar de baja lógicamente un usuario

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 2 · **Riesgo:** Medio · **Dependencias:** HU-04

## Historia de usuario

Como administrador,

quiero dar de baja lógicamente a un usuario,

para desactivarlo sin perder su registro histórico ni las referencias contables asociadas.

## Criterios de aceptación

### Escenario 1: Dar de baja un usuario activo

**Dado** que existe un usuario activo y el administrador tiene permiso para gestionarlo,

**cuando** solicita la baja y confirma la operación,

**entonces** el usuario pasa a estado inactivo y ya no puede iniciar sesión.

Además, se conserva su registro.

### Escenario 2: Selección en nuevas operaciones

**Dado** que existe un usuario dado de baja,

**cuando** el administrador crea un nuevo contrato,

**entonces** el usuario inactivo no aparece como opción seleccionable.

### Escenario 3: Conservar el historial

**Dado** que el usuario tiene contratos, cuotas o pagos asociados,

**cuando** se confirma su baja lógica,

**entonces** esos registros y sus referencias se conservan y siguen siendo consultables para auditoría.

## Reglas de negocio

- La baja lógica no debe eliminar físicamente el registro del usuario.
- Un usuario inactivo no puede iniciar sesión ni utilizarse en nuevas operaciones.
- La baja no debe eliminar ni desvincular automáticamente el historial existente.

## Definition of Done

- [ ] El administrador puede solicitar y confirmar la baja de un usuario.
- [ ] El estado inactivo se persiste correctamente.
- [ ] El usuario inactivo no puede iniciar sesión ni ser seleccionado en nuevos contratos.
- [ ] Se conserva el historial y se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
