# Módulo 3: ABMC de Inmuebles y Catálogo de Propiedades

---

### HU-07: Alta y Edición de Inmuebles con Asignación de Propietario
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5.
* **Riesgo:** Medio.
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-04 (Requiere propietarios dados de alta).
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** dar de alta y editar propiedades vinculándolas formalmente a su propietario,  
  > **para** ampliar el catálogo comercial y gestionar el inventario de bienes en alquiler.

#### Criterios de Aceptación
* **Escenario 1: Alta exitosa de una propiedad**
  * **Dado** que el Administrador accede al formulario de alta de inmueble (`/admin/inmuebles/nuevo`),
  * **Cuando** completa:
    * Propietario (seleccionado de un menú desplegable filtrando usuarios con rol `Propietario`),
    * Dirección exacta,
    * Tipo de inmueble (Departamento, Casa, Local comercial, Terreno, Oficina),
    * Cantidad de ambientes (número entero positivo),
    * Precio base de alquiler sugerido (valor decimal mayor a cero),
    * Descripción detallada del estado y comodidades,
  * **Y** presiona "Guardar Inmueble",
  * **Entonces** el sistema valida los tipos de datos en cliente (JS) y servidor (CI4),
  * **Y** almacena el registro en la tabla `inmuebles` con estado inicial "Disponible",
  * **Y** redirige al listado con notificación de éxito.

* **Escenario 2: Modificación de características del inmueble**
  * **Dado** que se edita un inmueble existente,
  * **Cuando** se modifican datos como el precio base o la descripción,
  * **Entonces** el sistema actualiza el registro registrando el cambio en `updated_at`.

---

### HU-08: Listado y Filtros del Catálogo de Inmuebles
* **Rol:** Administrador / Empleado / Propietario / Público.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 3 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-07.
* **Descripción:** 
  > **Como** usuario del sistema o visitante,  
  > **quiero** consultar y filtrar el catálogo de inmuebles por estado, tipo y precio,  
  > **para** identificar rápidamente propiedades disponibles o en alquiler.

#### Criterios de Aceptación
* **Escenario 1: Consulta administrativa completa con badges de estado**
  * **Dado** que el Administrador ingresa a `/admin/inmuebles`,
  * **Entonces** puede visualizar todas las propiedades con indicación visual de su estado:
    * `Disponible` (insignia verde),
    * `Alquilado` (insignia azul),
    * `En Mantenimiento` (insignia amarilla).
  * **Y** puede filtrar por propietario, rango de precio o estado de ocupación.

* **Escenario 2: Consulta del Propietario (Filtro por propietario)**
  * **Dado** que un Propietario accede a su portal (`/propietario/mis-inmuebles`),
  * **Entonces** el sistema lista únicamente los inmuebles vinculados a su identificador de usuario (`propietario_id = session()->get('user_id')`),
  * **Y** muestra si se encuentra alquilado o disponible.

---

### HU-09: Baja Lógica y Restricción de Eliminación de Inmuebles
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 2 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-07.
* **Descripción:** 
  > **Como** Administrador,  
  > **quiero** dar de baja un inmueble que no se ofrecerá más en alquiler,  
  > **para** retirarlo de las búsquedas sin comprometer contratos previos o cuotas pasadas.

#### Criterios de Aceptación
* **Escenario 1: Bloqueo de baja si el inmueble posee contrato vigente**
  * **Dado** que un inmueble posee un contrato de alquiler activo en estado "Vigente",
  * **Cuando** el Administrador intenta darlo de baja,
  * **Entonces** el sistema bloquea la acción y emite una advertencia: *"No es posible dar de baja un inmueble con contrato de locación vigente"*.

* **Escenario 2: Baja lógica exitosa**
  * **Dado** que un inmueble se encuentra en estado "Disponible" o sin contratos vigentes,
  * **Cuando** el Administrador confirma la baja,
  * **Entonces** el modelo aplica *Soft Delete* marcando `deleted_at = NOW()`,
  * **Y** la propiedad deja de estar visible en el catálogo de ofertas pero se preserva en la base de datos.
