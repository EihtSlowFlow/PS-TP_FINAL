# Módulo 6: Módulo de Registro y Validación de Pagos

---

### HU-16: Registro de Pago y Subida de Comprobante por el Inquilino
* **Rol:** Inquilino.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5 (Núcleo de ingreso financiero).
* **Riesgo:** Alto (Manejo de archivos multimedia, seguridad y validaciones de carga).
* **Estimación:** 8 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-15.
* **Descripción:** 
  > **Como** Inquilino,  
  > **quiero** informar a la inmobiliaria un pago realizado adjuntando los datos de la transferencia y el archivo digital del comprobante,  
  > **para** que el personal administrativo pueda conciliar la operación y registrar la cuota como pagada.

#### Criterios de Aceptación
* **Escenario 1: Carga de pago con comprobante digital válido**
  * **Dado** que el Inquilino presiona "Informar Pago" sobre una cuota pendiente o vencida (`/inquilino/pagos/registrar/:cuota_id`),
  * **Cuando** completa el formulario interactivo:
    * Cuota a abonar (seleccionada y no modificable),
    * Monto transferido (validando que sea un número positivo),
    * Fecha de la operación (no futura),
    * Medio de pago (Transferencia bancaria, Depósito, Pasarela digital),
    * Número de transacción o referencia bancaria,
    * Archivo adjunto del comprobante (formato PDF, JPG o PNG; tamaño máx. 5 MB),
  * **Y** presiona "Enviar Pago",
  * **Entonces** el sistema valida los campos en vivo (Vanilla JS) y con el validador de CodeIgniter (`uploaded[comprobante]|max_size[comprobante,5120]|ext_in[comprobante,pdf,png,jpg,jpeg]`),
  * **Y** almacena el archivo con nombre hasheado aleatorio dentro de `writable/uploads/comprobantes/` (directorio fuera de la raíz web pública para evitar descargas no autorizadas directas),
  * **Y** crea el registro en la tabla `pagos` con estado "Pendiente de Validación",
  * **Y** la cuota pasa a estado "En Revisión".

* **Escenario 2: Restricción de formatos inválidos o archivos maliciosos**
  * **Dado** que un usuario intenta subir un archivo que no sea PDF o imagen permitida (ej. `.php`, `.exe`, `.js`),
  * **Entonces** el sistema lo bloquea tanto en el frontend como en el backend retornando el error *"El archivo debe ser un comprobante válido (PDF, JPG, PNG)"*,
  * **Y** no guarda ningún archivo en el disco.

---

### HU-17: Verificación y Conciliación de Pagos por el Administrador
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5.
* **Riesgo:** Alto (Transición de estados contables).
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-16.
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** revisar la bandeja de pagos informados por los inquilinos para aprobarlos o rechazarlos tras cotejar la acreditación en la cuenta bancaria,  
  > **para** mantener la contabilidad al día y emitir el recibo correspondiente.

#### Criterios de Aceptación
* **Escenario 1: Aprobación de pago**
  * **Dado** que el Administrador accede a `/admin/pagos/pendientes` y visualiza un pago,
  * **Cuando** abre y examina el comprobante adjunto y presiona "Aprobar Pago",
  * **Entonces** el sistema actualiza el registro en `pagos` con estado "Aprobado",
  * **Y** la cuota asociada pasa definitivamente a estado "Pagada",
  * **Y** queda registrada la fecha y hora de aprobación y el identificador del empleado que autorizó.

* **Escenario 2: Rechazo de pago con motivo obligatorio**
  * **Dado** que la transferencia no impactó o el comprobante es ilegible,
  * **Cuando** el Administrador presiona "Rechazar Pago" e ingresa el motivo obligatorio (ej. *"Comprobante ilegible / Fondos no acreditados"*),
  * **Entonces** el estado del pago pasa a "Rechazado",
  * **Y** la cuota vuelve a su estado anterior ("Pendiente" o "Vencida"),
  * **Y** se preserva el registro del intento de pago rechazado para auditoría.

---

### HU-18: Historial y Consulta de Comprobantes de Pagos
* **Rol:** Inquilino / Administrador.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 2 Story Points.
* **Prioridad:** P2 (Media).
* **Dependencias:** HU-17.
* **Descripción:** 
  > **Como** Inquilino o Administrador,  
  > **quiero** acceder al histórico de pagos registrados con sus comprobantes y estados de aprobación,  
  > **para** tener constancia documental de las transacciones efectuadas.

#### Criterios de Aceptación
* **Escenario 1: Historial personal del Inquilino**
  * **Dado** que el Inquilino entra a `/inquilino/mis-pagos`,
  * **Entonces** visualiza todas sus operaciones enviadas ordenadas cronológicamente, indicando período abonado, monto, medio de pago, estado (`Aprobado`, `Pendiente`, `Rechazado`) y botón para descargar/ver el comprobante presentado servido a través de un controlador protegido de CodeIgniter.
