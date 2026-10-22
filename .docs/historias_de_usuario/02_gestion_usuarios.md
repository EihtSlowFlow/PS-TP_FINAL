# Módulo 2: ABMC de Personas y Usuarios

---

### HU-04: Gestión Administrativa de Usuarios
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada / Final.
* **Valor para el PO:** 5 / 5.
* **Riesgo:** Medio.
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-01 (Autenticación previa).
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** dar de alta, consultar, listar, buscar y modificar los datos de los usuarios (Inquilinos, Propietarios y Empleados),  
  > **para** mantener actualizado el padrón de clientes y operadores del sistema.

#### Criterios de Aceptación
* **Escenario 1: Alta manual de usuario por el Administrador**
  * **Dado** que el Administrador accede a `/admin/usuarios/nuevo`,
  * **Cuando** ingresa los datos personales del usuario (Nombre, Apellido, DNI, Email, Teléfono, Dirección) y selecciona el rol (`Administrador`, `Propietario` o `Inquilino`),
  * **Entonces** el sistema valida que no exista otro usuario activo con el mismo email o DNI,
  * **Y** genera la clave cifrada con BCRYPT (`password_hash`),
  * **Y** guarda el usuario mostrando un mensaje de éxito en el listado general con diseño en tarjeta / tabla.

* **Escenario 2: Listado, filtrado y búsqueda reactiva**
  * **Dado** que el Administrador se encuentra en el índice de usuarios (`/admin/usuarios`),
  * **Cuando** visualiza la tabla paginada o ingresa criterios de búsqueda (por nombre, DNI, email o filtro por rol),
  * **Entonces** la interfaz muestra los registros coincidentes sin perder el estado de la paginación.

* **Escenario 3: Modificación de datos existentes**
  * **Dado** que el Administrador edita un usuario existente (`/admin/usuarios/editar/:id`),
  * **Cuando** modifica campos de contacto (teléfono, dirección) o cambia su rol y deja el campo de contraseña en blanco,
  * **Entonces** el sistema actualiza la información sin alterar el hash de la contraseña actual,
  * **Y** actualiza automáticamente el campo `updated_at`.

---

### HU-05: Borrado Lógico (*Soft Delete*) de Usuarios
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 2 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-04.
* **Descripción:** 
  > **Como** Administrador,  
  > **quiero** desactivar a un usuario del sistema sin destruir su registro físico en la base de datos,  
  > **para** preservar la integridad referencial y el histórico contable de contratos y cobros pasados.

#### Criterios de Aceptación
* **Escenario 1: Desactivación mediante Soft Delete**
  * **Dado** que el Administrador solicita la baja de un usuario desde el listado,
  * **Cuando** confirma el diálogo modal de confirmación,
  * **Entonces** el modelo `UsuarioModel` de CodeIgniter ejecuta el borrado lógico (`$useSoftDeletes = true`),
  * **Y** actualiza la columna `deleted_at` con la fecha y hora actual del servidor sin ejecutar una sentencia destructiva `DELETE FROM`,
  * **Y** el usuario deja de figurar en los selectores de nuevos contratos, pero sus contratos y pagos históricos permanecen intactos para auditoría contable.

---

### HU-06: Gestión de Perfil Propio (Propietarios e Inquilinos)
* **Rol:** Propietario / Inquilino.
* **Alcance:** Cursada.
* **Valor para el PO:** 3 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 2 Story Points.
* **Prioridad:** P2 (Media).
* **Dependencias:** HU-02.
* **Descripción:** 
  > **Como** usuario externo autenticado (Propietario o Inquilino),  
  > **quiero** consultar y actualizar mi información de contacto y mi contraseña,  
  > **para** mantener mis datos al día y garantizar la seguridad de mi cuenta.

#### Criterios de Aceptación
* **Escenario 1: Actualización de datos de contacto**
  * **Dado** que el usuario accede a su sección "Mi Perfil",
  * **Cuando** actualiza su número de teléfono o dirección de correspondencia,
  * **Entonces** el sistema valida los datos y guarda los cambios con mensaje de confirmación flash.

* **Escenario 2: Cambio de contraseña seguro**
  * **Dado** que el usuario desea cambiar su clave,
  * **Cuando** ingresa su contraseña actual correcta y define una nueva que cumple con los requisitos mínimos de longitud (mínimo 8 caracteres),
  * **Entonces** el sistema valida la contraseña anterior mediante `password_verify()`,
  * **Y** cifra la nueva clave con BCRYPT antes de persistir los cambios.
