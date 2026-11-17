# HU-32 — Publicar un inmueble propio

> **Rol:** Propietario · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-07 (#12), HU-08 (#3), HU-09 (#13)

## Historia de usuario

Como propietario,

quiero registrar mis propios inmuebles desde mi portal,

para ofrecerlos en alquiler, quedando sujetos a la aprobación de la inmobiliaria.

## Criterios de aceptación

### Escenario 1: Alta del inmueble

**Dado** que soy un propietario activo,

**cuando** completo los datos del inmueble,

**entonces** se registra a mi nombre con estado de revisión Pendiente de aprobación.

### Escenario 2: Datos inválidos

**Dado** que informo precio, ambientes, superficie o ciudad inválidos,

**cuando** intento guardar,

**entonces** el sistema muestra los errores y no registra el inmueble.

### Escenario 3: Visibilidad hasta la aprobación

**Dado** que mi inmueble está pendiente o rechazado,

**cuando** se consulta el catálogo, el selector de contratos o los indicadores,

**entonces** no aparece como disponible y yo lo veo en «Mis inmuebles» con su estado y motivo.

### Escenario 4: Corregir y reenviar

**Dado** que mi inmueble está pendiente o rechazado,

**cuando** modifico sus datos y guardo,

**entonces** se actualiza y vuelve a quedar Pendiente de aprobación.

### Escenario 5: Inmueble aprobado

**Dado** que el inmueble fue aprobado,

**cuando** intento editarlo,

**entonces** el sistema impide la edición directa y indica que debe solicitarlo a la administración.

### Escenario 6: Inmueble ajeno

**Dado** que intento ver o editar un inmueble de otro propietario,

**cuando** realizo la solicitud,

**entonces** el sistema responde 404.

### Escenario 7: Sin inmuebles

**Dado** que no tengo inmuebles,

**cuando** abro «Mis inmuebles»,

**entonces** veo el llamado «Registrar mi primer inmueble».

## Reglas de negocio

- `owner_id` sale de la sesión y no del formulario.
- Los campos y validaciones se reutilizan de HU-07.
- Los inmuebles cargados por Administración o Empleado quedan aprobados automáticamente.
- `approval_status` es independiente del estado comercial y de la baja lógica.
- Un inmueble aprobado nace Disponible.

## Modelo de datos

Agregar a `properties`: `approval_status`, `submitted_by`, `rejection_reason`, `reviewed_by` y `reviewed_at`. Los inmuebles existentes quedan aprobados por defecto.

Filtrar por aprobación en catálogo público, selector de contratos, indicadores y detalle público.

## Por definir

- Edición posterior a la aprobación: propuesta, solo por administración.
- Baja de inmueble aprobado o pendiente: propuesta, el propietario puede dar de baja los pendientes y la administración los aprobados.

## Definition of Done

- [ ] El propietario registra inmuebles pendientes de aprobación.
- [ ] Los inmuebles pendientes o rechazados no aparecen en consultas públicas ni operativas.
- [ ] Se puede corregir y reenviar un inmueble rechazado.
- [ ] Los inmuebles administrativos siguen aprobados automáticamente.
- [ ] No se pueden ver ni editar inmuebles ajenos.
- [ ] Se prueban visibilidad, validaciones y casos de error.
- [ ] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue.

## Dependencias

- HU-07 (#12), HU-08 (#3), HU-09 (#13) y HU-02 (#20).
- Habilita HU-33 (#58).

## Referencias

- Rutas propuestas: `GET /propietario/inmuebles/nuevo`, `POST /propietario/inmuebles`, `GET/POST /propietario/inmuebles/editar/{id}`.
- Código: `app/Controllers/PropertyController.php`, `app/Models/PropertyModel.php`, `app/Views/admin/properties/form.php`, `app/Views/properties/owner_catalog.php`.
- Issue: [#57](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/57).
