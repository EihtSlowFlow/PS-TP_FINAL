# HU-13 — Emitir cuotas mensuales de forma masiva

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Alto (núcleo financiero) · **Dependencias:** HU-10

## Historia de usuario

Como administrador,

quiero emitir las cuotas mensuales de los contratos vigentes para un período determinado, de forma masiva o individual,

para habilitar las obligaciones de cobro a los inquilinos.

## Criterios de aceptación

### Escenario 1: Emisión de cuotas del período

**Dado** que existen contratos vigentes que cubren el período seleccionado,

**cuando** el administrador confirma la emisión masiva,

**entonces** el sistema genera una cuota por contrato con estado "Pendiente", vencimiento según el día pactado y monto igual al alquiler mensual del contrato, con su actualización anual ya aplicada (ver «Por definir»).

Además, presenta un informe con la cantidad de cuotas emitidas y el monto total a recaudar.

### Escenario 2: Cuota ya emitida

**Dado** que ya existe una cuota emitida para un contrato en el período seleccionado,

**cuando** se ejecuta la emisión,

**entonces** el sistema omite ese contrato sin duplicar ni modificar la cuota existente.

### Escenario 3: Contratos no incluidos

**Dado** que un contrato está finalizado, rescindido o no cubre el período,

**cuando** se ejecuta la emisión,

**entonces** el sistema no genera cuotas para ese contrato.

### Escenario 4: Período inválido

**Dado** que el administrador no selecciona un período válido,

**cuando** intenta confirmar la emisión,

**entonces** el sistema rechaza la operación e informa el error.

### Escenario 5: Emisión individual

**Dado** que existe un contrato vigente que cubre el período seleccionado,

**cuando** el administrador limita la emisión a ese contrato en la vista previa y confirma,

**entonces** el sistema genera solo la cuota de ese contrato, con las mismas reglas que la emisión masiva.

## Reglas de negocio

- Solo se emiten cuotas para contratos vigentes que cubran el período.
- La emisión puede ser masiva (todos los contratos vigentes del período) o individual (un solo contrato).
- Existe como máximo una cuota por contrato y período.
- La numeración de cuotas es correlativa por contrato.
- Toda cuota nueva inicia con recargo por mora en cero y estado "Pendiente".

## Por definir (resuelto al implementar, a confirmar con el equipo)

- **Períodos futuros o ya vencidos:** se permiten ambos. El único límite es que el período sea un mes entero de un año
  entre 2000 y 2100 y que haya contratos vigentes que lo cubran. Un período pasado sirve para completar cuotas que
  faltan (un contrato puede registrarse con inicio en el pasado, HU-10) y uno futuro, para adelantar el cierre del mes.
- **Qué significa que un contrato «cubre» el período:** el contrato está vigente y el **día de vencimiento de ese mes
  cae dentro de su plazo** (`inicio ≤ vencimiento ≤ fin`). Así nunca se emite una cuota que vence antes de que empiece
  el contrato o después de que termine, y un contrato de N meses completos queda cubierto por exactamente N períodos
  aunque no empiece el día 1. El análisis no lo define; si el equipo prefiere otra regla (por ejemplo, «el contrato está
  vigente algún día del mes»), se cambia en `ContractModel::coverageCondition()`.
- **Numeración correlativa:** el número de la cuota es el siguiente al más alto que ya tenga el contrato (1 si no tiene
  ninguna), en el orden en que se emiten. Si se emite febrero antes que enero, febrero lleva el 1.
- **Monto:** es el alquiler mensual del contrato (`monthly_rent`) con el «incremento por actualización»
  (`update_percentage`) aplicado cada 12 meses sobre el alquiler del año anterior (interés compuesto). El contrato no
  guarda cada cuánto se actualiza, así que la regla es esa (decisión del 18/11/2026): las cuotas de los primeros 12
  meses valen el alquiler del contrato; las de los meses 13 a 24, el alquiler más una actualización; y así. Los meses se
  cuentan desde el mes en que empieza el contrato hasta el mes del período, así que el monto no depende del orden de
  emisión. El cálculo está en `App\Libraries\RentUpdateCalculator` (enteros y centavos, redondeo hacia arriba en cada
  actualización); la vista previa muestra el mismo monto que se emite y cuántas actualizaciones tiene.
- **Quién emite:** administrador y empleado, igual que contratos e inmuebles (decisión de HU-01, R-7).

## Definition of Done

- [x] El administrador puede emitir cuotas para un período, de forma masiva o para un solo contrato.
- [x] No se generan cuotas duplicadas.
- [x] Se informa la cantidad de cuotas emitidas y el monto total.
- [x] El monto de cada cuota incluye la actualización anual del contrato.
- [x] Se verifican los permisos de acceso.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes.

## Notas de integración (desde HU-13)

- Las cuotas se crean con `InstallmentModel::issueForPeriod()`: una transacción que bloquea los contratos
  (`SELECT … FOR UPDATE`, siempre en orden de `id`), omite los que ya tienen cuota del período y revierte todo si algo
  falla. Para otras historias que creen cuotas, pasar por ahí: ahí vive la numeración correlativa.
- Toda cuota nueva queda `pending`, con `late_fee` en 0 y `due_date` = el día de vencimiento del contrato dentro del mes
  del período (`InstallmentModel::dueDateFor()`).
- **HU-14 (mora):** debe pasar las cuotas `pending` con `due_date` anterior a hoy a `overdue` y calcular `late_fee`; la
  emisión nunca modifica una cuota que ya existe, así que no pisa lo que calcule HU-14.
- **HU-15 (estado de cuenta) y HU-16 (pagos):** el estado de una cuota se muestra con
  `InstallmentModel::labelForStatus()`; las clases CSS de las etiquetas son `badge-installment-{estado}`.
- **HU-12 (cierre de contratos):** al pasar a `finished` o `rescinded` el contrato deja de cubrir cualquier período, así
  que no se le emiten más cuotas; las ya emitidas se conservan.
- La pantalla vive en `/admin/cuotas/emitir` (vista previa y confirmación) y `/admin/cuotas/emitir/resultado` (informe).
  Hay un enlace «Cuotas» en el menú de administrador y empleado; el listado de cobranzas (`Cobranzas` en el prototipo) es
  de otra historia y el informe todavía no enlaza a él.
