# HU-24 — Recibir alertas de vencimiento de contratos

> **Rol:** Administrador / Propietario · **Alcance:** Final · **Prioridad:** P3 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-10

## Historia de usuario

Como administrador o propietario,

quiero recibir alertas de los contratos que vencerán en los próximos 60 días,

para iniciar a tiempo la renovación o coordinar la restitución del inmueble.

## Criterios de aceptación

### Escenario 1: Contrato próximo a vencer

**Dado** que un contrato vigente vence dentro de los próximos 60 días,

**cuando** el administrador o el propietario del inmueble ingresa al sistema,

**entonces** el sistema muestra una alerta con el inmueble, el inquilino y la fecha de vencimiento.

Además, permite acceder directamente al contrato.

### Escenario 2: Contrato fuera de la ventana

**Dado** que un contrato vence en más de 60 días o ya no está vigente,

**cuando** se consultan las alertas,

**entonces** el sistema no muestra alerta para ese contrato.

### Escenario 3: Alcance por propietario

**Dado** que soy propietario,

**cuando** consulto mis alertas,

**entonces** solo veo las de contratos de mis inmuebles.

## Reglas de negocio

- La ventana de anticipación es de 60 días corridos antes de la fecha de finalización.
- Solo se alerta sobre contratos vigentes.
- El propietario solo recibe alertas de sus propios inmuebles.

## Definition of Done

- [ ] El administrador y el propietario ven alertas de contratos que vencen en 60 días o menos.
- [ ] No se muestran alertas de contratos fuera de la ventana o no vigentes.
- [ ] Cada propietario solo ve las alertas de sus contratos.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.
