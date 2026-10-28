# HU-26 — Consultar el panel resumen del propietario

> **Rol:** Propietario · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-08, HU-21

## Historia de usuario

Como propietario,

quiero ver un resumen consolidado de mis propiedades,

para conocer cuántas están alquiladas y el total de ingresos que recibí.

## Criterios de aceptación

### Escenario 1: Resumen de propiedades

**Dado** que soy un propietario con inmuebles registrados,

**cuando** accedo a mi panel,

**entonces** el sistema muestra el total de mis inmuebles y cuántos están alquilados y cuántos disponibles.

### Escenario 2: Ingresos acreditados

**Dado** que tengo liquidaciones transferidas en el último año,

**cuando** accedo a mi panel,

**entonces** el sistema muestra el total acreditado en ese período.

### Escenario 3: Contratos por vencer

**Dado** que tengo contratos que vencen en los próximos 60 días,

**cuando** accedo a mi panel,

**entonces** el sistema muestra las alertas correspondientes.

### Escenario 4: Sin inmuebles

**Dado** que no tengo inmuebles registrados,

**cuando** accedo a mi panel,

**entonces** el sistema muestra los indicadores en cero y un mensaje informativo.

## Reglas de negocio

- El propietario solo ve datos de sus propios inmuebles y liquidaciones.
- El total acreditado incluye únicamente liquidaciones en estado "Transferido".
- Los inmuebles dados de baja no se cuentan como disponibles.

## Definition of Done

- [ ] El panel muestra inmuebles totales, alquilados y disponibles.
- [ ] Se muestra el total acreditado en el último año.
- [ ] Se muestran las alertas de contratos por vencer.
- [ ] El propietario solo accede a sus propios datos.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
