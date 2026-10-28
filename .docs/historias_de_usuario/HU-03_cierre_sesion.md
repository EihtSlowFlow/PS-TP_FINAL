# HU-03 — Cerrar sesión

> **Rol:** Usuario autenticado · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 1 · **Riesgo:** Bajo · **Dependencias:** HU-01

## Historia de usuario

Como usuario autenticado,

quiero cerrar mi sesión voluntariamente,

para garantizar que nadie más pueda usar mi cuenta en el dispositivo actual.

## Criterios de aceptación

### Escenario 1: Cierre de sesión

**Dado** que estoy autenticado,

**cuando** solicito cerrar sesión,

**entonces** el sistema destruye mi sesión, me redirige a la pantalla de inicio de sesión e informa que la sesión se cerró correctamente.

### Escenario 2: Acceso posterior al cierre

**Dado** que cerré mi sesión,

**cuando** intento acceder a una página protegida, incluso con el botón "atrás" del navegador,

**entonces** el sistema me exige autenticarme nuevamente.

## Reglas de negocio

- El cierre de sesión invalida completamente la sesión en el servidor.
- Tras el cierre no debe quedar información de la cuenta accesible sin autenticación.

## Definition of Done

- [ ] El usuario puede cerrar sesión desde cualquier panel.
- [ ] La sesión queda invalidada en el servidor.
- [ ] Las páginas protegidas no son accesibles tras el cierre.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
