# HU-18 — Consultar el historial de pagos y comprobantes

> **Rol:** Inquilino / Administrador · **Alcance:** Cursada · **Prioridad:** P2 · **Story Points:** 2 · **Riesgo:** Bajo · **Dependencias:** HU-17

## Historia de usuario

Como inquilino o administrador,

quiero consultar el historial de pagos con sus comprobantes y estados,

para contar con constancia documental de las operaciones realizadas.

## Criterios de aceptación

### Escenario 1: Historial del inquilino

**Dado** que soy un inquilino con pagos informados,

**cuando** consulto mi historial,

**entonces** el sistema muestra mis pagos en orden cronológico con período, monto, medio de pago y estado (aprobado, pendiente o rechazado).

Además, puedo ver o descargar el comprobante que presenté.

### Escenario 2: Historial del administrador

**Dado** que soy administrador,

**cuando** consulto el historial de pagos,

**entonces** el sistema muestra los pagos de todos los inquilinos con la posibilidad de filtrarlos por estado y período.

### Escenario 3: Acceso a comprobantes ajenos

**Dado** que un inquilino intenta acceder al comprobante de otro inquilino,

**cuando** realiza la solicitud,

**entonces** el sistema deniega el acceso.

## Reglas de negocio

- Los comprobantes solo se entregan a través de una vía protegida que verifica permisos.
- Cada inquilino solo accede a sus propios pagos y comprobantes.
- Los pagos rechazados permanecen visibles en el historial con su motivo.

## Definition of Done

- [ ] El inquilino ve su historial y puede acceder a sus comprobantes.
- [ ] El administrador ve y filtra el historial completo.
- [ ] Los comprobantes solo son accesibles para quienes tienen permiso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
