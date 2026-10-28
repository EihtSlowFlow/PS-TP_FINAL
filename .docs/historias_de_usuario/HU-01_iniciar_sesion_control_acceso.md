# HU-01 — Iniciar sesión y acceder según el rol

> **Rol:** Usuario registrado · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Alto (condiciona todos los permisos) · **Dependencias:** Ninguna

## Historia de usuario

Como usuario registrado,

quiero autenticarme con mi correo electrónico y contraseña,

para acceder al panel privado correspondiente a mis permisos y operar de forma segura.

## Criterios de aceptación

### Escenario 1: Autenticación exitosa

**Dado** que soy un usuario activo con credenciales válidas,

**cuando** ingreso mi correo electrónico y mi contraseña correctos,

**entonces** el sistema inicia mi sesión y me redirige al panel correspondiente a mi rol (administrador, propietario o inquilino).

### Escenario 2: Credenciales incorrectas

**Dado** que intento autenticarme con un correo no registrado o una contraseña errónea,

**cuando** envío el formulario,

**entonces** el sistema rechaza el ingreso con un mensaje genérico de credenciales inválidas, sin indicar cuál de los datos falló.

Además, se conserva el correo ingresado y se limpia el campo de contraseña.

### Escenario 3: Acceso sin sesión

**Dado** que no he iniciado sesión,

**cuando** intento acceder a una página protegida,

**entonces** el sistema me redirige al inicio de sesión e informa que debo autenticarme para continuar.

### Escenario 4: Acceso a una sección de otro rol

**Dado** que estoy autenticado con un rol sin permiso sobre una sección,

**cuando** intento acceder a ella,

**entonces** el sistema deniega el acceso, informa que no estoy autorizado y me dirige a mi panel.

### Escenario 5: Usuario dado de baja

**Dado** que mi usuario fue dado de baja lógicamente,

**cuando** intento iniciar sesión,

**entonces** el sistema impide el ingreso e informa que la cuenta está deshabilitada.

## Reglas de negocio

- Las contraseñas se almacenan únicamente cifradas, nunca en texto plano.
- La autorización se verifica siempre en el servidor, no solo en la interfaz.
- Tras autenticarse, se regenera el identificador de sesión.
- La sesión solo conserva los datos mínimos necesarios.

## Definition of Done

- [ ] El usuario activo puede iniciar sesión y es redirigido según su rol.
- [ ] Las credenciales inválidas se rechazan sin revelar qué dato falló.
- [ ] Las rutas protegidas exigen sesión y verifican el rol en el servidor.
- [ ] Los usuarios dados de baja no pueden ingresar.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
