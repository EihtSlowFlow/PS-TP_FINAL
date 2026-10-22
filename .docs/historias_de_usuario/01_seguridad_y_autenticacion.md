# Módulo 1: Seguridad, Autenticación y Manejo de Sesiones

---

### HU-01: Inicio de Sesión y Control de Acceso por Rol
* **Rol:** Usuario del sistema (`Administrador`, `Propietario`, `Inquilino`).
* **Alcance:** Cursada.
* **Valor para el PO:** 5 / 5 (Crítico).
* **Riesgo:** Alto (Riesgo de flujo: condiciona toda la arquitectura de filtros y permisos del proyecto).
* **Estimación:** 5 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** Ninguna (base del sistema).
* **Descripción:** 
  > **Como** usuario registrado del sistema,  
  > **quiero** autenticarme con mi correo electrónico y contraseña,  
  > **para** acceder al panel privado correspondiente a mis permisos y gestionar mis operaciones de forma segura.

#### Criterios de Aceptación (Gherkin & Usabilidad)
* **Escenario 1: Autenticación exitosa con redirección por rol**
  * **Dado** que soy un usuario activo registrado en el sistema,
  * **Cuando** ingreso mi correo electrónico válido y mi contraseña correcta en el formulario de login (`/auth/login`) y presiono "Ingresar",
  * **Entonces** el controlador de CodeIgniter verifica la clave mediante `password_verify()` contra el hash BCRYPT almacenado en `usuarios`,
  * **Y** genera una sesión protegida guardando `user_id`, `nombre`, `email` y `rol`,
  * **Y** redirige automáticamente al usuario según su rol:
    * `/admin/dashboard` si el rol es Administrador,
    * `/propietario/dashboard` si el rol es Propietario,
    * `/inquilino/estado-cuenta` si el rol es Inquilino.

* **Escenario 2: Credenciales incorrectas y prevención de fuga de información**
  * **Dado** que un usuario intenta autenticarse,
  * **Cuando** ingresa un correo no registrado o una contraseña errónea,
  * **Entonces** el sistema rechaza la petición sin distinguir si falló el correo o la clave (*"Credenciales inválidas"*),
  * **Y** preserva el correo en el campo (mediante `old('email')`) para evitar que el usuario deba reescribirlo, pero limpia el campo de contraseña por seguridad.

* **Escenario 3: Intercepción por Filtros de Sesión (`AuthFilter` y `RoleFilter`)**
  * **Dado** que un usuario sin sesión intenta acceder a una URL protegida (ej. `/admin/inmuebles`),
  * **Cuando** se ejecuta `AuthFilter`,
  * **Entonces** es redirigido a `/auth/login` con un mensaje flash: *"Debe iniciar sesión para continuar"*.
  * **Dado** que un usuario autenticado con rol `Inquilino` intenta acceder a una ruta de rol `Administrador`,
  * **Cuando** se ejecuta `RoleFilter`,
  * **Entonces** el sistema responde con error HTTP 403 (Forbidden) y lo redirige a su panel correspondiente informando *"Acceso no autorizado"*.

* **Escenario 4: Usuario inactivo o dado de baja (*Soft Delete*)**
  * **Dado** que un usuario posee una fecha en `deleted_at`,
  * **Cuando** intenta iniciar sesión,
  * **Entonces** el sistema le impide el ingreso informando que su cuenta se encuentra deshabilitada.

---

### HU-02: Registro de Clientes Externos (Propietarios e Inquilinos)
* **Rol:** Visitante / Nuevo Cliente.
* **Alcance:** Cursada.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-01.
* **Descripción:** 
  > **Como** cliente nuevo (propietario o inquilino),  
  > **quiero** completar un formulario de registro en línea,  
  > **para** crear mi cuenta y operar en la plataforma.

#### Criterios de Aceptación
* **Escenario 1: Registro exitoso con validación dual**
  * **Dado** que un visitante accede a la página de registro (`/auth/register`),
  * **Cuando** completa Nombre, Apellido, DNI, Email, Teléfono, Rol deseado (`Propietario` o `Inquilino`) y una contraseña de al menos 8 caracteres,
  * **Entonces** el formulario valida en vivo con Vanilla JavaScript el formato del email y la coincidencia de contraseñas,
  * **Y** en el servidor, CodeIgniter valida unicidad de email (`is_unique[usuarios.email]`) y DNI (`is_unique[usuarios.dni]`),
  * **Y** cifra la contraseña con `password_hash($pass, PASSWORD_BCRYPT)`,
  * **Y** almacena el registro en la tabla `usuarios`,
  * **Y** redirige a `/auth/login` con notificación de registro exitoso.

* **Escenario 2: Control de duplicados y retención de datos**
  * **Dado** que el usuario ingresa un correo ya existente,
  * **Entonces** el servidor retorna el error *"El correo electrónico ya se encuentra registrado"*,
  * **Y** el formulario conserva todos los datos ingresados previamente (`old(...)`) para no frustrar al usuario, salvo el campo de clave.

---

### HU-03: Cierre de Sesión Seguro
* **Rol:** Usuario autenticado.
* **Alcance:** Cursada.
* **Valor para el PO:** 3 / 5.
* **Riesgo:** Bajo.
* **Estimación:** 1 Story Point.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-01.
* **Descripción:** 
  > **Como** usuario autenticado en la plataforma,  
  > **quiero** cerrar mi sesión voluntariamente con un clic,  
  > **para** garantizar que nadie más pueda usar mi cuenta en el dispositivo actual.

#### Criterios de Aceptación
* **Escenario 1: Cierre voluntario y destrucción de credenciales volátiles**
  * **Dado** que un usuario se encuentra autenticado,
  * **Cuando** presiona el botón "Cerrar Sesión" en la barra de navegación superior,
  * **Entonces** el servidor destruye completamente la sesión activa (`session()->destroy()`),
  * **Y** borra la cookie de sesión del navegador,
  * **Y** redirige a la pantalla pública/login con el mensaje: *"Sesión cerrada correctamente"*.

---

### HU-03b: Restablecimiento y Recuperación de Contraseña
* **Rol:** Usuario registrado (Admin, Propietario, Inquilino).
* **Alcance:** Cursada.
* **Valor para el PO:** Alto (5 / 5).
* **Riesgo:** Alto (Vulnerabilidad de acceso indebido si la verificación falla).
* **Estimación:** 3 Story Points.
* **Prioridad:** P1 (Alta).
* **Dependencias:** HU-01.
* **Descripción:** 
  > **Como** usuario del sistema que ha olvidado su clave,  
  > **quiero** solicitar el restablecimiento seguro de mi contraseña,  
  > **para** recuperar el acceso a mi cuenta sin intervención manual de terceros.

#### Criterios de Aceptación
* **Escenario 1: Solicitud válida de restablecimiento**
  * **Dado** que un usuario accede a `/auth/recuperar-password`,
  * **Cuando** ingresa su correo electrónico registrado,
  * **Entonces** el sistema verifica la existencia del usuario activo,
  * **Y** genera un token de seguridad temporal y seguro almacenado en la base de datos o sesión,
  * **Y** presenta las instrucciones para definir una nueva clave.

* **Escenario 2: Cambio efectivo de clave con BCRYPT**
  * **Dado** que el usuario accede con una solicitud válida,
  * **Cuando** ingresa una nueva contraseña que cumple con los requisitos de seguridad,
  * **Entonces** el sistema cifra la nueva clave mediante `password_hash($pass, PASSWORD_BCRYPT)`,
  * **Y** actualiza la base de datos invalidando el token utilizado,
  * **Y** confirma el restablecimiento exitoso redirigiendo al login.
