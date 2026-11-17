# HU-31 — Gestionar solicitudes de alquiler y crear el contrato

> **Rol:** Administrador / Empleado · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-30 (#55)

## Historia de usuario

Como administrador o empleado,

quiero revisar las solicitudes de alquiler y convertir una aprobada en contrato,

para atender interesados sin cargar manualmente sus datos ni perder el origen de la contratación.

## Criterios de aceptación

### Escenario 1: Bandeja de solicitudes

**Dado** que existen solicitudes,

**cuando** abro `/admin/solicitudes`,

**entonces** veo pendientes primero, con inquilino, inmueble, fechas, fecha de envío y estado, y puedo filtrar y buscar.

### Escenario 2: Ficha de la solicitud

**Dado** que abro una solicitud,

**cuando** se carga la ficha,

**entonces** veo datos del inquilino, inmueble, propietario, precio, mensaje y otras solicitudes pendientes sobre el mismo inmueble.

### Escenario 3: Aprobar y crear contrato

**Dado** que la solicitud está pendiente y el inmueble disponible,

**cuando** elijo aprobar y crear contrato,

**entonces** se abre el formulario de HU-10 con inmueble e inquilino preseleccionados y la solicitud se vincula al contrato de forma atómica.

### Escenario 4: Cierre de las demás solicitudes

**Dado** que se creó el contrato,

**cuando** la operación finaliza,

**entonces** las solicitudes pendientes sobre el inmueble pasan a Rechazada con el motivo correspondiente y sus inquilinos reciben aviso.

### Escenario 5: Rechazar

**Dado** que una solicitud está pendiente,

**cuando** la rechazo con un motivo obligatorio,

**entonces** pasa a Rechazada y el motivo se muestra al inquilino.

### Escenario 6: Solicitud imposible de aprobar

**Dado** que el inmueble dejó de estar disponible o el inquilino fue dado de baja,

**cuando** intento aprobar,

**entonces** el sistema rechaza la operación.

### Escenario 7: Acceso restringido

**Dado** que soy propietario o inquilino,

**cuando** intento acceder a `/admin/solicitudes`,

**entonces** se deniega el acceso.

## Reglas de negocio

- Solo se resuelven solicitudes Pendiente.
- El motivo de rechazo es obligatorio y tiene hasta 255 caracteres.
- La aprobación reutiliza todas las validaciones de HU-10.
- `resolved_by` y `resolved_at` deben quedar registrados.
- Las operaciones POST usan CSRF y están limitadas a Administrador y Empleado.

## Por definir

- Plazo y fecha sugeridos pueden modificarse en el contrato: propuesta según el issue.
- Avisar al propietario del inmueble al aprobar: propuesta afirmativa.

## Definition of Done

- [ ] Se pueden listar, filtrar y abrir solicitudes.
- [ ] Aprobar abre el contrato prellenado y vincula la solicitud de forma atómica.
- [ ] Las demás solicitudes pendientes del inmueble se rechazan automáticamente.
- [ ] El rechazo exige motivo y se muestra al inquilino.
- [ ] Se actualizan menú y contador de pendientes.
- [ ] Se verifican permisos y atomicidad ante fallos.
- [ ] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue.

## Dependencias

- HU-30 (#55): tabla y flujo de solicitudes.
- HU-10 (#15) y HU-11 (#16): contrato y ficha.
- HU-22 (#47): avisos.

## Referencias

- Rutas propuestas: `GET /admin/solicitudes`, `GET /admin/solicitudes/{id}`, `POST /admin/solicitudes/{id}/rechazo` y `GET /admin/contratos/nuevo?request_id={id}`.
- Código: `app/Controllers/ContractController.php`, `app/Models/ContractModel.php`, `app/Controllers/PaymentController.php`.
- Issue: [#56](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/56).
