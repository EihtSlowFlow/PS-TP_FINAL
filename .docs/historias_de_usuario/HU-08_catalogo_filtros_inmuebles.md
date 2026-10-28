# HU-08 — Consultar y filtrar el catálogo de inmuebles

> **Rol:** Administrador / Propietario / Público · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-07

## Historia de usuario

Como usuario del sistema o visitante,

quiero consultar y filtrar el catálogo de inmuebles por estado, tipo y precio,

para identificar rápidamente las propiedades disponibles o alquiladas.

## Criterios de aceptación

### Escenario 1: Consulta del administrador

**Dado** que existen inmuebles activos en distintos estados,

**cuando** el administrador consulta el catálogo,

**entonces** el sistema muestra todos los inmuebles activos con su estado (disponible, alquilado o en mantenimiento) claramente identificado.

Además, puede filtrar por propietario, rango de precio o estado.

### Escenario 2: Consulta del propietario

**Dado** que un propietario está autenticado,

**cuando** consulta sus inmuebles,

**entonces** el sistema muestra únicamente los inmuebles vinculados a su cuenta, indicando si están alquilados o disponibles.

### Escenario 3: Consulta pública

**Dado** que un visitante accede al catálogo público,

**cuando** consulta las propiedades,

**entonces** el sistema muestra solo los inmuebles activos y disponibles, sin exponer datos internos.

### Escenario 4: Sin resultados

**Dado** que ningún inmueble cumple los filtros aplicados,

**cuando** se realiza la búsqueda,

**entonces** el sistema informa que no se encontraron resultados.

## Reglas de negocio

- Los inmuebles dados de baja no aparecen en los listados activos.
- Un propietario solo puede ver sus propios inmuebles.
- El visitante solo accede a información pública de inmuebles disponibles.

## Definition of Done

- [ ] El catálogo se puede consultar según el rol del usuario.
- [ ] Los filtros por estado, tipo, propietario y precio funcionan correctamente.
- [ ] Se respetan las restricciones de visibilidad por rol.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
