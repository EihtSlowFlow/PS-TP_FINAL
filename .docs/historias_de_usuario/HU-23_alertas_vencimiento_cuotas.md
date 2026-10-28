# HU-23 — Recibir alertas de próximos vencimientos de cuotas

> **Rol:** Inquilino · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-13

## Historia de usuario

Como inquilino,

quiero recibir alertas sobre cuotas próximas a vencer,

para pagar a término y evitar recargos por mora.

## Criterios de aceptación

### Escenario 1: Cuota próxima a vencer

**Dado** que tengo una cuota pendiente que vence dentro de los próximos 5 días,

**cuando** ingreso al sistema o reviso mis alertas,

**entonces** el sistema me muestra un aviso con el período y la fecha de vencimiento.

### Escenario 2: Cuota lejana, pagada o en revisión

**Dado** que mi cuota vence en más de 5 días, ya está pagada o está en revisión,

**cuando** reviso mis alertas,

**entonces** el sistema no muestra aviso para esa cuota.

### Escenario 3: Cuota vencida

**Dado** que mi cuota ya venció,

**cuando** reviso mis alertas,

**entonces** el sistema no la muestra como próxima a vencer, sino como cuota vencida con su recargo.

## Reglas de negocio

- Se alerta únicamente sobre cuotas pendientes dentro de la ventana de anticipación definida (5 días).
- Cada inquilino solo recibe alertas sobre sus propias cuotas.
- Una alerta deja de mostrarse cuando la cuota se informa como pagada.

## Definition of Done

- [ ] El inquilino ve alertas de cuotas que vencen en 5 días o menos.
- [ ] No se muestran alertas de cuotas pagadas, en revisión o vencidas.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.
