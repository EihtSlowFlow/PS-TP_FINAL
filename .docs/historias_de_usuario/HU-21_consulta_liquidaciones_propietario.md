# HU-21 — Consultar liquidaciones y recibos

> **Rol:** Propietario · **Alcance:** Final · **Prioridad:** P2 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-20

## Historia de usuario

Como propietario,

quiero consultar el historial de liquidaciones de mis alquileres,

para auditar los montos cobrados, la comisión deducida y los fondos netos acreditados.

## Criterios de aceptación

### Escenario 1: Listado y desglose

**Dado** que soy un propietario con liquidaciones generadas,

**cuando** consulto mis liquidaciones,

**entonces** el sistema muestra período, inmueble, monto bruto, comisión retenida (porcentaje e importe), monto neto, fecha de acreditación, número de operación y estado.

### Escenario 2: Descargar el recibo

**Dado** que una liquidación fue transferida,

**cuando** solicito su comprobante,

**entonces** el sistema permite imprimirlo o descargarlo.

### Escenario 3: Acceso a liquidaciones ajenas

**Dado** que intento consultar una liquidación de otro propietario,

**cuando** realizo la solicitud,

**entonces** el sistema deniega el acceso.

## Reglas de negocio

- Cada propietario solo accede a sus propias liquidaciones.
- Los estados posibles son "Pendiente" y "Transferido".
- El recibo solo se emite para liquidaciones transferidas.

## Definition of Done

- [ ] El propietario puede consultar y filtrar sus liquidaciones.
- [ ] El desglose de bruto, comisión y neto es correcto.
- [ ] Se restringe el acceso a liquidaciones de otros propietarios.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
