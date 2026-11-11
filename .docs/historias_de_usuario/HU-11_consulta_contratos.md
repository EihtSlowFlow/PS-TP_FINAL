# HU-11 — Consultar contratos vigentes e históricos

> **Rol:** Administrador / Propietario · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-10

## Historia de usuario

Como administrador o propietario,

quiero consultar los contratos celebrados,

para auditar los plazos, los datos de contacto del inquilino y los montos acordados.

## Criterios de aceptación

### Escenario 1: Consulta del administrador

**Dado** que existen contratos registrados,

**cuando** el administrador consulta el listado,

**entonces** el sistema muestra todos los contratos con inmueble, inquilino, propietario, vigencia y estado.

Además, puede buscar por inquilino, propietario o dirección y filtrar por estado (vigente, finalizado o rescindido).

### Escenario 2: Consulta del propietario

**Dado** que un propietario está autenticado,

**cuando** consulta sus contratos,

**entonces** el sistema muestra únicamente los contratos de sus inmuebles, con datos de contacto del inquilino, monto pactado, vigencia y comisión acordada.

### Escenario 3: Acceso a contratos ajenos

**Dado** que un propietario intenta acceder al detalle de un contrato de otro propietario,

**cuando** realiza la solicitud,

**entonces** el sistema deniega el acceso.

## Reglas de negocio

- Un propietario solo puede consultar contratos de sus propios inmuebles.
- Los contratos finalizados y rescindidos permanecen consultables.
- La autorización se verifica en el servidor.

## Definition of Done

- [ ] El administrador puede listar, buscar y filtrar todos los contratos.
- [ ] El propietario solo ve los contratos de sus inmuebles.
- [ ] Se deniega el acceso a contratos ajenos.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.

## Notas de integración (desde HU-10)

- Ya existen `ContractModel` (`app/Models/ContractModel.php`) y `ContractController` con la ficha del contrato
  (`GET /admin/contratos/(:num)`, vista `admin/contracts/show.php`, pantalla ADM-08). La ficha es mínima (partes,
  plazo y condiciones); el listado, la búsqueda y los filtros son de esta historia.
- `ContractModel::findWithDetails()` une el inmueble y los dos usuarios **sin filtrar por `deleted_at`**, para que un
  inquilino, un propietario o un inmueble dado de baja siga apareciendo en el historial. Reutilizar ese criterio en el
  listado.
- Las rutas de contratos están en el grupo `auth` + `role:admin,employee`. Para el propietario hay que agregar un grupo
  propio (`propietario/contratos`) que filtre por `contracts.owner_id` igual al usuario de la sesión.
- El enlace «Contratos» del menú lateral (`layouts/panel.php`) apunta hoy a `admin/contratos/nuevo`; cuando exista el
  listado hay que apuntarlo a `admin/contratos`. Los enlaces «Volver al listado de inmuebles» de la ficha y del
  formulario también deberían volver al listado de contratos.
- `ContractController::show()` redirige al panel si el contrato no existe; con el listado puede redirigir a él.
