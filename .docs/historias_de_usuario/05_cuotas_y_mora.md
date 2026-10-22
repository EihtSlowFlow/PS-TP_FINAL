# Módulo 5: Emisión de Cuotas y Recargos de Mora

---

### HU-13: Emisión Masiva de Cuotas Mensuales
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5 (Operación crítica mensual).
* **Riesgo:** Alto (Core financiero).
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-10 (Contratos vigentes).
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** emitir masivamente las cuotas mensuales de todos los contratos vigentes para un período determinado (mes/año),  
  > **para** habilitar las obligaciones de cobro a los inquilinos.

#### Criterios de Aceptación
* **Escenario 1: Generación masiva controlada**
  * **Dado** que el Administrador accede a `/admin/cuotas/emitir` y selecciona un período (ej. `2026-11`),
  * **Cuando** confirma la emisión masiva,
  * **Entonces** el sistema consulta todos los contratos con estado "Vigente" que cubran ese mes,
  * **Y** genera por cada contrato una fila en `cuotas`:
    * `numero_cuota`: Correlativo incremental por contrato,
    * `periodo`: Formato `YYYY-MM`,
    * `fecha_vencimiento`: Combinando año, mes y el día pactado en el contrato (ej. `2026-11-10`),
    * `monto_base`: Monto mensual estipulado en el contrato,
    * `monto_mora`: Inicializado en `0.00`,
    * `monto_total`: Igual al `monto_base`,
    * `estado`: `Pendiente`.
  * **Y** presenta un informe con la cantidad de cuotas emitidas y el monto global a recaudar.

* **Escenario 2: Control estricto de duplicados**
  * **Dado** que para un contrato ya existe una cuota emitida para dicho período,
  * **Entonces** el sistema ignora ese contrato sin generar duplicaciones ni alterar las cuotas preexistentes.

---

### HU-14: Cálculo Automático de Recargo por Mora
* **Rol:** Sistema / Administrador.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5.
* **Riesgo:** Medio.
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-13.
* **Descripción:** 
  > **Como** sistema y Administrador,  
  > **quiero** que se calcule y aplique automáticamente el recargo diario por mora en aquellas cuotas cuya fecha de vencimiento haya expirado,  
  > **para** exigir los intereses punitorios correspondientes pactados en el contrato.

#### Criterios de Aceptación
* **Escenario 1: Cuota vencida sin abonar**
  * **Dado** que una cuota se encuentra en estado "Pendiente" y la fecha actual del servidor supera la `fecha_vencimiento`,
  * **Cuando** el usuario consulta su estado de cuenta o el administrador abre el listado de cobranzas,
  * **Entonces** el estado de la cuota pasa automáticamente a "Vencida",
  * **Y** se calculan los días de atraso:  
    `dias_mora = DATEDIFF(fecha_actual, fecha_vencimiento)`
  * **Y** se calcula el punitorio:  
    `monto_mora = monto_base * (porcentaje_mora_diario / 100) * dias_mora`
  * **Y** el `monto_total` exigible se muestra como `monto_base + monto_mora` con el desglose visible.

* **Escenario 2: Cuota abonada a término**
  * **Dado** que una cuota fue cancelada antes o durante el día de vencimiento,
  * **Entonces** `monto_mora` permanece fijado en `0.00`.

---

### HU-15: Estado de Cuenta y Detalle de Cuotas del Inquilino
* **Rol:** Inquilino.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5.
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-13 y HU-14.
* **Descripción:** 
  > **Como** Inquilino,  
  > **quiero** acceder a mi estado de cuenta personal para ver mis cuotas pagadas, pendientes y vencidas con el detalle de moras,  
  > **para** estar informado de mis compromisos de pago y tener acceso rápido al botón de informar pago.

#### Criterios de Aceptación
* **Escenario 1: Visualización del estado de cuenta con layout Admin Template**
  * **Dado** que un Inquilino inicia sesión y entra a `/inquilino/estado-cuenta`,
  * **Entonces** visualiza tarjetas resumen con:
    1. **Cuotas Pendientes del Mes:** Monto base, fecha límite y botón de acción *"Informar Pago"*.
    2. **Cuotas Vencidas en Mora:** Con advertencia en color rojo, especificando días de mora acumulados y recargo liquidado al día de hoy.
    3. **Historial de Cuotas Pagadas:** Con fecha de pago, medio utilizado y enlace al comprobante aprobado.
