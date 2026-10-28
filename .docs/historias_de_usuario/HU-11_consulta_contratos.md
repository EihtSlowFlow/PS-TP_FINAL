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
