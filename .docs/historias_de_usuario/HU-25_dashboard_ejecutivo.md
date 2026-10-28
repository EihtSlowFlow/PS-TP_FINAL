# HU-25 — Consultar el dashboard ejecutivo

> **Rol:** Administrador · **Alcance:** Final · **Prioridad:** P2 · **Story Points:** 8 · **Riesgo:** Alto (métricas consolidadas) · **Dependencias:** HU-11, HU-14, HU-17, HU-19

## Historia de usuario

Como administrador,

quiero acceder a un panel con las métricas clave del negocio,

para evaluar de un vistazo la ocupación, la cobranza, la morosidad y las obligaciones pendientes con propietarios.

## Criterios de aceptación

### Escenario 1: Indicadores principales

**Dado** que el administrador accede al dashboard,

**cuando** se carga el panel,

**entonces** el sistema muestra el porcentaje de ocupación del catálogo, el monto recaudado en el mes, el índice y monto de morosidad, y el saldo pendiente de liquidación a propietarios.

### Escenario 2: Sin datos

**Dado** que no existen inmuebles, pagos o cuotas vencidas,

**cuando** se carga el panel,

**entonces** los indicadores se muestran en cero sin producir errores ni divisiones por cero.

### Escenario 3: Acceso restringido

**Dado** que un usuario que no es administrador intenta acceder al dashboard,

**cuando** realiza la solicitud,

**entonces** el sistema deniega el acceso.

## Reglas de negocio

- Ocupación = inmuebles alquilados ÷ inmuebles activos totales × 100.
- El monto recaudado suma únicamente los pagos aprobados del mes en curso.
- La morosidad considera las cuotas en estado "Vencida".
- El saldo pendiente suma las liquidaciones aún no transferidas.
- Los inmuebles dados de baja no se incluyen en la ocupación.

## Definition of Done

- [ ] El dashboard muestra los cuatro indicadores con valores correctos.
- [ ] Los casos sin datos se manejan sin errores.
- [ ] Solo el administrador puede acceder.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
