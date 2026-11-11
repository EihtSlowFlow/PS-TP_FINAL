# HU-09 — Dar de baja lógicamente un inmueble

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 2 · **Riesgo:** Medio · **Dependencias:** HU-07

## Historia de usuario

Como administrador,

quiero dar de baja lógicamente un inmueble,

para retirarlo de la operatoria activa sin perder su registro histórico ni las referencias asociadas.

## Criterios de aceptación

### Escenario 1: Solicitar la baja de un inmueble activo

**Dado** que existe un inmueble activo sin contrato vigente y el administrador tiene permiso para gestionarlo,

**cuando** solicita la baja del inmueble,

**entonces** el sistema solicita una confirmación explícita indicando el inmueble afectado y las consecuencias de la baja.

Además, mientras no se confirme, el inmueble permanece activo y sin modificaciones.

### Escenario 2: Confirmar la baja

**Dado** que el administrador solicitó la baja de un inmueble activo,

**cuando** confirma la operación,

**entonces** el estado del inmueble pasa de "activo" a "inactivo" y el sistema registra la fecha y el usuario que realizó la baja.

Además, se conservan todos los datos del inmueble (propietario, dirección, tipo, precio y descripción) y sus contratos, cuotas y demás registros asociados.

### Escenario 3: Cancelar la baja

**Dado** que el administrador solicitó la baja de un inmueble activo,

**cuando** cancela la confirmación,

**entonces** el inmueble permanece activo y no se registra ningún cambio.

### Escenario 4: Listado de inmuebles activos

**Dado** que existe un inmueble dado de baja,

**cuando** el administrador consulta el listado de inmuebles activos,

**entonces** el inmueble inactivo no aparece en los resultados, sin necesidad de aplicar ningún filtro.

### Escenario 5: Consulta de inmuebles inactivos

**Dado** que existe un inmueble dado de baja,

**cuando** el administrador filtra el listado por estado "inactivo",

**entonces** el sistema muestra únicamente los inmuebles inactivos, indicando su fecha de baja.

Además, el filtro por estado "activo" muestra únicamente los inmuebles activos, y sin filtro el listado muestra solo los activos.

### Escenario 6: Asociar un inmueble inactivo a un nuevo contrato

**Dado** que un inmueble se encuentra inactivo,

**cuando** se intenta asociarlo a un nuevo contrato, ya sea desde la interfaz o mediante una solicitud directa al servidor,

**entonces** el sistema rechaza la operación, no crea el contrato e informa que el inmueble no está disponible.

Además, el inmueble inactivo no se ofrece como opción al crear un contrato.

### Escenario 7: Conservar el historial

**Dado** que un inmueble tiene contratos u otros registros históricos asociados,

**cuando** se confirma su baja lógica,

**entonces** esos registros y sus referencias al inmueble se conservan y continúan siendo consultables según los permisos correspondientes.

### Escenario 8: Inmueble con contrato vigente

**Dado** que un inmueble posee un contrato de locación vigente,

**cuando** el administrador intenta darlo de baja,

**entonces** el sistema rechaza la operación e informa que no es posible dar de baja un inmueble con contrato vigente.

## Reglas de negocio

- La baja lógica no debe eliminar físicamente el registro del inmueble.
- La baja requiere una confirmación explícita; sin ella no se modifica el inmueble.
- Al confirmar la baja se registran la fecha y el usuario responsable.
- El listado de inmuebles excluye por defecto los inactivos; estos solo se muestran al filtrar por estado "inactivo".
- Un inmueble inactivo no puede utilizarse para nuevas operaciones que requieran un inmueble activo, y esta regla se valida en el servidor.
- La baja no debe eliminar ni desvincular automáticamente el historial existente.
- No se puede dar de baja un inmueble con contrato de locación vigente.

## Definition of Done

- [x] El administrador puede solicitar la baja, y confirmarla o cancelarla.
- [x] Sin confirmación no se modifica el inmueble.
- [x] El estado inactivo, la fecha y el usuario responsable se persisten correctamente.
- [x] El listado excluye los inmuebles inactivos por defecto y el filtro por estado funciona según lo definido.
- [x] El servidor impide asociar inmuebles inactivos a nuevos contratos, incluso ante solicitudes directas.
- [x] El sistema impide la baja de inmuebles con contrato vigente.
- [x] Se conserva el historial y se verifican los permisos de acceso.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes.

## Notas de integración (implementado en HU-09)

- `PropertyModel::deactivate(int $id, int $deletedByUserId)` ejecuta en una transacción con bloqueo (`SELECT ... FOR UPDATE`) la verificación de existencia, baja previa y contrato vigente, persiste `deleted_by` fuera de `allowedFields` y completa `deleted_at` mediante `delete($id)`.
- `PropertyController::deactivate(int $id)` (`POST /admin/inmuebles/baja/(:num)`, protegido por `auth`, `role:admin,employee` y CSRF) exige `confirmed=1` y, si falta, renderiza la confirmación explícita en `app/Views/admin/properties/deactivate_confirm.php` (además del modal accesible en cliente en `app/Views/admin/properties/index.php` y `form.php`).
- El listado administrativo (`/admin/inmuebles`) excluye por defecto los inmuebles inactivos y permite filtrar por estado `active`/`activo`, los estados operativos (`available`, `rented`, `maintenance`) e `inactive`/`inactivo` (mostrando la fecha de baja y el responsable).
