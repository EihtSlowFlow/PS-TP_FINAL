# HU-27 — Generar reportes de recaudación y morosidad

> **Rol:** Administrador · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-25

## Historia de usuario

Como administrador,

quiero generar e imprimir listados de cobranzas, morosidad y contratos,

para respaldar auditorías, balances contables y el control operativo.

## Criterios de aceptación

### Escenario 1: Reporte de morosidad

**Dado** que existen cuotas vencidas,

**cuando** el administrador filtra por rango de fechas o días de atraso,

**entonces** el sistema muestra los inquilinos con deuda, el inmueble, las cuotas impagas, los recargos calculados y el teléfono de contacto.

### Escenario 2: Reporte de recaudación

**Dado** que existen pagos aprobados,

**cuando** el administrador filtra por período,

**entonces** el sistema muestra los pagos cobrados con sus totales.

### Escenario 3: Imprimir o exportar

**Dado** que el administrador visualiza un reporte,

**cuando** solicita imprimirlo o exportarlo,

**entonces** el sistema genera una versión limpia, sin elementos de navegación innecesarios.

### Escenario 4: Sin resultados o filtros inválidos

**Dado** que no hay datos para los filtros aplicados o el rango de fechas es inválido,

**cuando** se genera el reporte,

**entonces** el sistema informa que no hay resultados o indica el error del filtro.

## Reglas de negocio

- Solo el administrador puede generar reportes.
- Los reportes reflejan los datos vigentes al momento de la consulta.
- Los importes de recargo coinciden con el cálculo de mora del sistema.

## Definition of Done

- [ ] El administrador puede generar reportes de morosidad y recaudación con filtros.
- [ ] El reporte puede imprimirse o exportarse correctamente.
- [ ] Se manejan los casos sin resultados y filtros inválidos.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
