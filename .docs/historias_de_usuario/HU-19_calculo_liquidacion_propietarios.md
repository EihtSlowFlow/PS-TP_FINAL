# HU-19 — Calcular la liquidación a propietarios

> **Rol:** Administrador · **Alcance:** Final · **Prioridad:** P2 · **Story Points:** 8 · **Riesgo:** Alto (cálculos de dinero y comisiones) · **Dependencias:** HU-17

## Historia de usuario

Como administrador,

quiero generar las liquidaciones de los alquileres cobrados descontando automáticamente la comisión pactada,

para conocer el monto neto exacto a transferir a cada propietario sin errores contables.

## Criterios de aceptación

### Escenario 1: Generar la liquidación de una cuota cobrada

**Dado** que una cuota está "Pagada" con pago aprobado y aún no fue liquidada,

**cuando** el administrador genera la liquidación,

**entonces** el sistema calcula el monto bruto, la comisión según el porcentaje del contrato y el monto neto, y registra la liquidación como "Pendiente de transferencia".

### Escenario 2: Cuota no cobrada

**Dado** que una cuota no está pagada o su pago fue rechazado,

**cuando** el administrador intenta liquidarla,

**entonces** el sistema rechaza la operación.

### Escenario 3: Cuota ya liquidada

**Dado** que una cuota ya tiene una liquidación generada,

**cuando** el administrador intenta liquidarla nuevamente,

**entonces** el sistema rechaza la operación para evitar duplicados.

## Reglas de negocio

- Comisión = monto bruto × (porcentaje de comisión ÷ 100); neto = bruto − comisión.
- El porcentaje aplicado es el vigente en el contrato al momento de la liquidación.
- Cada cuota pagada se liquida una única vez.
- Los importes se calculan con precisión decimal y redondeo definido.

## Decisiones tomadas al implementar (24/11/2026)

Resuelven los puntos «Por definir» del issue [#44](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/44):

- **Granularidad:** una liquidación por contrato y período (análisis, sección 9.4). Como un contrato tiene una sola cuota por período, cada liquidación cubre una cuota, registrada en `settlement_installments`. El período es el de la cuota.
- **Monto bruto:** lo efectivamente cobrado, es decir, la suma de los pagos **aprobados** de la cuota. No se usa el canon del contrato.
- **Redondeo:** la comisión se redondea al centavo, con la mitad hacia arriba. El neto es `bruto − comisión`, así que siempre suman. Las cuentas se hacen en centavos enteros, sin punto flotante.
- **Porcentaje:** se toma el del contrato en el momento de generar y se guarda en la liquidación. Si después cambia el contrato, las liquidaciones anteriores no cambian.
- **Todo o nada:** si alguna de las cuotas elegidas no está cobrada (escenario 2) o ya está liquidada (escenario 3), no se genera ninguna y el mensaje dice cuál fue. Las comprobaciones se repiten sobre las filas bloqueadas (`FOR UPDATE`), y las claves únicas de la base rechazan un duplicado que llegue a la vez.
- **Permisos:** solo el Administrador. El Empleado, el Propietario y el Inquilino son redirigidos a su panel.
- **Pantalla:** ADM-13 en `/admin/liquidaciones/generar`. Muestra las cuotas cobradas sin liquidar, con filtro por propietario, selección y totales de lo elegido. La entrada «Liquidaciones» del menú es solo del administrador. El listado, el detalle y el registro de la transferencia corresponden a HU-20 y HU-21.

## Definition of Done

- [x] El administrador puede generar liquidaciones de cuotas cobradas.
- [x] Los importes de comisión y neto se calculan correctamente.
- [x] No se generan liquidaciones duplicadas ni de cuotas no cobradas.
- [x] Se verifican los permisos de acceso.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.
