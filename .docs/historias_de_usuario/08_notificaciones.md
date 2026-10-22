# Módulo 8: Gestión de Notificaciones y Alertas

---

### HU-22: Notificaciones de Pagos y Aprobaciones
* **Rol:** Inquilino / Administrador.
* **Alcance:** Examen Final.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 5 Story Points.
* **Prioridad:** P3 (Baja - Alcance Final).
* **Dependencias:** HU-17.
* **Descripción:** 
  > **Como** usuario del sistema (Administrador o Inquilino),  
  > **quiero** recibir alertas y notificaciones en mi panel sobre la carga y validación de pagos,  
  > **para** estar informado en tiempo real sin tener que revisar manualmente cada estado.

#### Criterios de Aceptación
* **Escenario 1: Alerta al Administrador al recibir un pago**
  * **Dado** que un Inquilino envía un nuevo comprobante de pago,
  * **Entonces** el sistema genera una notificación interna dirigida al rol Administrador: *"El inquilino [Nombre] ha informado un pago para la cuota [Período] del inmueble [Dirección]"*,
  * **Y** se incrementa el contador de notificaciones no leídas en la barra superior del Admin Template.

* **Escenario 2: Notificación al Inquilino tras la resolución del pago**
  * **Dado** que el Administrador aprueba o rechaza un pago,
  * **Entonces** el sistema genera una notificación en la cuenta del Inquilino:
    * Si es aprobado: *"Tu pago para el período [Período] ha sido aprobado exitosamente"*.
    * Si es rechazado: *"Tu pago fue rechazado. Motivo: [Motivo]. Por favor, reenvía el comprobante"*.

---

### HU-23: Alertas de Próximos Vencimientos de Cuotas
* **Rol:** Inquilino.
* **Alcance:** Examen Final.
* **Valor para el PO:** 3 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 3 Story Points.
* **Prioridad:** P3 (Baja - Alcance Final).
* **Dependencias:** HU-13.
* **Descripción:** 
  > **Como** Inquilino,  
  > **quiero** recibir notificaciones preventivas sobre cuotas próximas a vencer,  
  > **para** efectuar el pago a término y evitar la generación de recargos por mora.

#### Criterios de Aceptación
* **Escenario 1: Aviso de vencimiento inminente**
  * **Dado** que faltan 5 días o menos para el vencimiento de una cuota pendiente,
  * **Cuando** el Inquilino ingresa al sistema o revisa sus alertas,
  * **Entonces** se le muestra un aviso preventivo destacado: *"Tu cuota de alquiler de [Período] vence el día [Fecha Vencimiento]. Evita recargos por mora"*.

---

### HU-24: Alertas de Vencimiento de Contratos
* **Rol:** Administrador / Propietario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 3 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 3 Story Points.
* **Prioridad:** P3 (Baja - Alcance Final).
* **Dependencias:** HU-10.
* **Descripción:** 
  > **Como** Administrador o Propietario,  
  > **quiero** recibir notificaciones de los contratos de alquiler que vencerán en los próximos 30 o 60 días,  
  > **para** iniciar oportunamente las gestiones de renovación o coordinar la restitución del inmueble.

#### Criterios de Aceptación
* **Escenario 1: Detección y notificación de fin de contrato**
  * **Dado** que un contrato vigente se encuentra a 60 días o menos de su `fecha_fin`,
  * **Entonces** el sistema genera una alerta visible en el panel del Administrador y del Propietario asociado: *"El contrato del inmueble [Dirección] con el inquilino [Nombre] vencerá el [Fecha Fin]"*,
  * **Y** permite acceder directamente al contrato para tramitar prórroga, rescisión o renovación.
