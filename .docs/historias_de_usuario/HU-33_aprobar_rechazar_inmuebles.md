# HU-33 — Aprobar o rechazar inmuebles publicados por propietarios

> **Rol:** Administrador / Empleado · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-32 (#57)

## Historia de usuario

Como administrador o empleado,

quiero revisar los inmuebles cargados por propietarios y aprobarlos o rechazarlos,

para controlar qué se publica en el catálogo y mantener la información correcta.

## Criterios de aceptación

### Escenario 1: Pendientes de aprobación

**Dado** que existen inmuebles enviados por propietarios,

**cuando** abro `/admin/inmuebles`,

**entonces** puedo filtrarlos por Pendientes de aprobación y veo el propietario y la cantidad pendiente.

### Escenario 2: Aprobar

**Dado** que un inmueble está pendiente,

**cuando** lo apruebo,

**entonces** pasa a Aprobado y queda visible en el catálogo y disponible para contratos.

### Escenario 3: Rechazar

**Dado** que un inmueble está pendiente,

**cuando** lo rechazo informando un motivo,

**entonces** pasa a Rechazado y el propietario puede verlo y corregirlo en «Mis inmuebles».

### Escenario 4: Corregir antes de aprobar

**Dado** que el inmueble tiene datos incorrectos,

**cuando** lo edito desde el formulario de HU-07,

**entonces** los cambios se guardan y el estado sigue pendiente.

### Escenario 5: Ya resuelto

**Dado** que otro usuario ya resolvió el inmueble,

**cuando** intento resolverlo otra vez,

**entonces** se informa que ya fue resuelto y no cambia nada.

### Escenario 6: Acceso restringido

**Dado** que soy propietario o inquilino,

**cuando** intento aprobar o rechazar,

**entonces** se deniega el acceso.

## Reglas de negocio

- Solo se resuelven inmuebles Pendientes y no dados de baja.
- El motivo de rechazo es obligatorio y tiene hasta 255 caracteres.
- Aprobar no cambia el estado comercial; el inmueble queda Disponible.
- Se registran `reviewed_by` y `reviewed_at`.
- Las operaciones son POST con CSRF y acceso de Administrador o Empleado.

## Por definir

- Propuesta: no permitir aprobar «en mantenimiento» directamente; se aprueba y luego se edita el estado comercial mediante HU-07. **Decidido (17/11/2026): se implementó así.**
- Los avisos al propietario (escenarios 2 y 3) dependen de HU-22 (#47), que todavía no existe: quedan pendientes (ver el [handoff](../exec-plans/HU-33_handoff.md)).

## Definition of Done

- [x] El listado administrativo filtra pendientes y muestra el contador.
- [x] Aprobar publica el inmueble donde corresponde.
- [x] Rechazar exige motivo y lo muestra el propietario.
- [x] Una segunda resolución no cambia el inmueble.
- [x] Se verifican los permisos.
- [x] Se prueban los escenarios y casos de error.
- [x] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue (el comentario se publica al confirmar el commit).

## Dependencias

- HU-32 (#57): datos y flujo del propietario.
- HU-07 (#12), HU-08 (#3), HU-22 (#47).

## Referencias

- Rutas propuestas: `POST /admin/inmuebles/{id}/aprobacion`, `POST /admin/inmuebles/{id}/rechazo`.
- Código: `app/Controllers/PropertyController.php`, `app/Models/PropertyModel.php`, `app/Views/admin/properties/index.php`.
- Issue: [#58](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/58).
