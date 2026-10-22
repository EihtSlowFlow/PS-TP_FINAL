# Módulo 4: ABMC de Contratos de Alquiler

---

### HU-10: Alta y Vinculación de Contratos de Alquiler
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5 (Núcleo del negocio).
* **Riesgo:** Alto (Core transaccional: une inmueble, propietario e inquilino).
* **Estimación:** 8 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-04 (Usuarios) y HU-07 (Inmuebles).
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** confeccionar y dar de alta un contrato de locación formal vinculando un inmueble disponible con un inquilino,  
  > **para** establecer los plazos, montos del alquiler mensual, comisiones y condiciones de mora.

#### Criterios de Aceptación
* **Escenario 1: Creación de contrato válida con actualización de estado**
  * **Dado** que el Administrador accede a `/admin/contratos/nuevo`,
  * **Cuando** selecciona:
    * Un inmueble (filtrado exclusivamente entre los que están con estado "Disponible"),
    * Un inquilino (seleccionado entre los usuarios con rol `Inquilino`),
    * Fecha de inicio y fecha de finalización del contrato (validando que fecha fin sea posterior a fecha inicio),
    * Monto mensual pactado del alquiler ($),
    * Porcentaje de comisión inmobiliaria (ej. 10%),
    * Día límite de vencimiento mensual (ej. día 10 de cada mes),
    * Porcentaje de recargo diario por mora (ej. 0.5% diario),
  * **Y** presiona "Generar Contrato",
  * **Entonces** el sistema guarda el registro en la tabla `contratos` con estado "Vigente",
  * **Y** actualiza atómicamente el estado del inmueble a "Alquilado",
  * **Y** redirige a la ficha del contrato recién confeccionado con mensaje de confirmación.

* **Escenario 2: Restricción de inmueble ya alquilado**
  * **Dado** que un inmueble ya está afectado a otro contrato activo,
  * **Entonces** el sistema no lo lista en el selector y rechaza en el backend cualquier intento de asignación duplicada.

---

### HU-11: Consulta de Contratos Vigentes e Históricos
* **Rol:** Administrador / Propietario.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5 (Requerimiento de máximo valor para la inmobiliaria).
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-10.
* **Descripción:** 
  > **Como** Administrador o Propietario de un inmueble,  
  > **quiero** consultar los términos y condiciones de los contratos celebrados,  
  > **para** auditar los plazos locativos, datos de contacto del inquilino y montos acordados.

#### Criterios de Aceptación
* **Escenario 1: Consulta del Administrador con Query Builder y Alias Claros**
  * **Dado** que el Administrador accede a `/admin/contratos`,
  * **Entonces** la consulta SQL une las tablas `contratos`, `inmuebles`, `usuarios` (inquilino) y `usuarios` (propietario) usando alias explícitos (evitando colisión de nombres de columnas como `name` o `id`),
  * **Y** visualiza todos los contratos con buscador por inquilino, propietario, dirección y filtro por estado (`Vigente`, `Finalizado`, `Rescindido`).

* **Escenario 2: Consulta del Propietario sobre sus inmuebles**
  * **Dado** que un Propietario autenticado accede a `/propietario/mis-contratos`,
  * **Entonces** visualiza únicamente los contratos que involucran a inmuebles de su propiedad,
  * **Y** puede ver el nombre y datos de contacto del inquilino, monto pactado, vigencia y comisión acordada.

---

### HU-12: Rescisión o Finalización de Contratos de Alquiler
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P2 (Media).
* **Dependencias:** HU-10.
* **Descripción:** 
  > **Como** Administrador,  
  > **quiero** dar por finalizado o rescindir anticipadamente un contrato de locación,  
  > **para** cerrar el período de cobros y retornar el inmueble al estado disponible en el catálogo.

#### Criterios de Aceptación
* **Escenario 1: Conclusión normal o anticipada**
  * **Dado** que un contrato se encuentra en estado "Vigente",
  * **Cuando** el Administrador ejecuta la acción "Finalizar Contrato" o "Rescindir",
  * **Entonces** el sistema actualiza el estado del contrato a "Finalizado" (o "Rescindido"),
  * **Y** modifica el estado del inmueble asociado nuevamente a "Disponible",
  * **Y** preserva íntegramente todas las cuotas generadas, pagos registrados y comprobantes para auditoría contable.
