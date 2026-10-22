# Módulo 7: Liquidación Financiera a Propietarios

---

### HU-19: Cálculo Automático de Liquidación a Propietarios
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 5 / 5 (Cierre contable con propietarios).
* **Riesgo:** Alto (Cálculos de dinero, comisiones y saldos netos).
* **Estimación:** 8 Story Points.
* **Prioridad:** P2 (Media - Alcance Final).
* **Dependencias:** HU-17 (Requiere cuotas con pagos aprobados).
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** generar las liquidaciones financieras de los alquileres cobrados deduciendo de forma automática la comisión pactada con la inmobiliaria,  
  > **para** disponer de los montos netos exactos a transferir a cada propietario sin inconsistencias contables manuales.

#### Criterios de Aceptación
* **Escenario 1: Generación de liquidación por cuota cobrada**
  * **Dado** que una cuota de alquiler se encuentra en estado "Pagada" (con pago aprobado),
  * **Cuando** el Administrador accede a `/admin/liquidaciones/generar`,
  * **Entonces** el sistema identifica al propietario del inmueble y el porcentaje de comisión fijado en el contrato correspondiente (ej. 10%),
  * **Y** calcula automáticamente:
    * `monto_bruto` = Monto total cobrado al inquilino,
    * `porcentaje_comision` = Comisión fijada en el contrato,
    * `monto_comision` = `monto_bruto * (porcentaje_comision / 100)`,
    * `monto_neto` = `monto_bruto - monto_comision`.
  * **Y** genera un registro en la tabla `liquidaciones` en estado "Pendiente de Transferencia".

* **Escenario 2: Restricción sobre cuotas no cobradas**
  * **Dado** que una cuota no ha sido pagada o su pago fue rechazado,
  * **Entonces** el sistema no permite generar liquidación para esa cuota.

---

### HU-20: Registro de Transferencia Bancaria al Propietario
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P2 (Media - Alcance Final).
* **Dependencias:** HU-19.
* **Descripción:** 
  > **Como** Administrador,  
  > **quiero** registrar la ejecución de la transferencia bancaria realizada a la cuenta del propietario,  
  > **para** asentar la acreditación efectiva y cerrar la liquidación financiera.

#### Criterios de Aceptación
* **Escenario 1: Marcado de liquidación como transferida**
  * **Dado** que el Administrador realizó la transferencia bancaria al propietario,
  * **Cuando** accede al detalle de la liquidación y presiona "Registrar Transferencia",
  * **Y** completa la fecha de transferencia y el número de transacción/referencia bancaria,
  * **Entonces** la liquidación cambia a estado "Transferido",
  * **Y** se descuenta del indicador de "Saldo pendiente de liquidación" del panel de control.

---

### HU-21: Consulta de Liquidaciones y Recibos por el Propietario
* **Rol:** Propietario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 3 Story Points.
* **Prioridad:** P2 (Media - Alcance Final).
* **Dependencias:** HU-20.
* **Descripción:** 
  > **Como** Propietario de un inmueble,  
  > **quiero** acceder a mi portal privado y consultar el histórico de liquidaciones de mis alquileres,  
  > **para** auditar los montos cobrados, la comisión deducida y los fondos netos acreditados en mi cuenta.

#### Criterios de Aceptación
* **Escenario 1: Listado y desglose de liquidaciones**
  * **Dado** que el Propietario ingresa a `/propietario/liquidaciones`,
  * **Entonces** visualiza una tabla detallada con:
    * Período liquidado y dirección del inmueble,
    * Monto bruto cobrado al inquilino,
    * Desglose claro de la comisión inmobiliaria retenida (porcentaje e importe en $),
    * Monto neto transferido,
    * Fecha de acreditación y número de comprobante/operación,
    * Estado de la liquidación (`Pendiente` o `Transferido`).
  * **Y** dispone de una opción para imprimir o descargar el comprobante de liquidación.
