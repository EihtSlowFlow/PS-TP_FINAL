# HU-14 — Calcular automáticamente el recargo por mora

> **Rol:** Sistema / Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-13

## Historia de usuario

Como administrador,

quiero que el sistema calcule automáticamente el recargo diario por mora en las cuotas vencidas,

para aplicar los intereses punitorios pactados en cada contrato.

## Criterios de aceptación

### Escenario 1: Cuota vencida sin abonar

**Dado** que una cuota está "Pendiente" y la fecha actual supera su fecha de vencimiento,

**cuando** se consulta el estado de cuenta o el listado de cobranzas,

**entonces** la cuota pasa a "Vencida" y se calcula su recargo según los días de atraso y el porcentaje diario pactado en el contrato.

Además, el monto total exigible se muestra desglosado en monto base y recargo.

### Escenario 2: Cuota abonada a término

**Dado** que una cuota fue pagada hasta su fecha de vencimiento,

**cuando** se consulta su detalle,

**entonces** el recargo por mora permanece en cero.

### Escenario 3: Cuota en revisión

**Dado** que el inquilino informó un pago y este se encuentra pendiente de validación,

**cuando** se recalcula la mora,

**entonces** el recargo de la cuota no se modifica mientras dure la revisión.

## Reglas de negocio

- Recargo = monto base × (porcentaje diario de mora ÷ 100) × días de atraso.
- Los días de atraso se cuentan desde el día posterior al vencimiento.
- Las cuotas pagadas no generan ni modifican recargos.
- El cálculo se realiza en el servidor y es reproducible.

## Por definir (resuelto al implementar, a confirmar con el equipo)

- **Redondeo:** el recargo se redondea **al centavo, hacia arriba en la mitad** (0,005 pasa a 0,01), como el `ROUND` de
  MySQL y como calcula la carga de datos de demostración. Se redondea una sola vez, sobre el resultado final (no por
  día), con aritmética entera en centavos y centésimas de porcentaje: nunca con decimales binarios, así el mismo dato da
  siempre el mismo importe.
- **Tope del recargo acumulado:** no hay tope de negocio (el issue no lo define). El único límite es técnico: lo que
  entra en `installments.late_fee` (`DECIMAL(12,2)`, hasta 9.999.999.999,99); si el cálculo lo superara se guarda ese
  máximo en lugar de fallar. Si el equipo define un tope, se agrega en `LateFeeCalculator::lateFee()`.
- **Cuándo se recalcula:** **en cada consulta**, no con una tarea programada. `InstallmentModel::refreshLateFees()` se
  ejecuta al abrir el listado de cobranzas y el detalle de una cuota; deja guardados el estado `overdue` y el recargo
  de `installments.late_fee` al día de hoy. Es reproducible: el recargo depende solo del monto base, del porcentaje del
  contrato, del vencimiento y del día para el que se calcula (se puede pedir cualquier día), y calcular dos veces el
  mismo día no cambia nada. Si más adelante se quiere una tarea programada, alcanza con llamar al mismo método.
- **Qué cuotas se recalculan:** las `pending` y `overdue` con vencimiento **anterior a hoy**, también las de contratos
  ya finalizados o rescindidos (la deuda sigue existiendo). Las `under_review` y `paid` nunca se tocan.
- **Cuota en revisión que se rechaza:** al volver a `overdue` (HU-17) el recargo se calcula de nuevo desde el
  vencimiento hasta ese día, como cualquier cuota vencida. Si HU-17 prefiere descontar los días de revisión, es una
  decisión de esa historia.
- **Cuota pagada con atraso:** HU-16/HU-17 deciden qué recargo queda al pagar; HU-14 solo garantiza que, una vez
  `paid`, no se vuelve a modificar.
- **Quién la consulta:** administrador y empleado, como el resto de las cuotas (decisión de HU-01, R-7).

## Definition of Done

- [x] El sistema marca como vencidas las cuotas que superan su fecha límite.
- [x] El recargo se calcula correctamente según el contrato.
- [x] El desglose de base, recargo y total es visible.
- [x] Las cuotas pagadas conservan recargo en cero.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes, incluidos los valores límite.

## Notas de integración (desde HU-14)

- El cálculo vive en `App\Libraries\LateFeeCalculator` (funciones puras: `daysLate()`, `lateFee()`, `forInstallment()`)
  y la actualización guardada en `InstallmentModel::refreshLateFees(?string $hoy = null, ?int $cuota = null)`.
- **HU-15 (estado de cuenta del inquilino):** tiene que llamar a `InstallmentModel::refreshLateFees()` antes de leer las
  cuotas (es lo que exige el escenario 1: «cuando se consulta el estado de cuenta»). Muestra base, recargo
  (`late_fee`) y total (`base_amount + late_fee`) igual que `admin/installments/show.php`; el total lo da
  `InstallmentModel::sumAmounts()`.
- **HU-16/HU-17 (pagos):** al informar un pago la cuota pasa a `under_review` y deja de recalcularse; al aprobarlo pasa
  a `paid` y queda como está. El recalculo usa `WHERE status IN ('pending','overdue')` en cada `UPDATE`, así que no
  pisa un estado que otra operación cambió en el mismo instante.
- **HU-19 (liquidación) y HU-27 (morosidad):** pueden leer `installments.late_fee` y `status = 'overdue'` sabiendo que
  están al día de la última consulta de cobranzas; para un dato al día hay que llamar antes a `refreshLateFees()`.
- **Zona horaria:** «hoy» es la fecha de la zona de la aplicación (`appTimezone`, hoy `UTC`); de 21:00 a 24:00 en
  Argentina ya es el día siguiente en UTC y la cuota del día venció. Se resuelve cambiando `appTimezone` o
  `InstallmentModel::today()`.
- Las pantallas: listado de cobranzas `GET /admin/cuotas` (`ADM-10`, menú «Cobranzas») y detalle `GET /admin/cuotas/(:num)`.
