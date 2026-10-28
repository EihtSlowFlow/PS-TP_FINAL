# HU-04 — Gestionar usuarios del sistema

> **Rol:** Administrador · **Alcance:** Cursada / Final · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-01

## Historia de usuario

Como administrador,

quiero dar de alta, consultar, buscar y modificar los datos de los usuarios (inquilinos, propietarios y empleados),

para mantener actualizado el padrón de clientes y operadores del sistema.

## Criterios de aceptación

### Escenario 1: Alta de un usuario

**Dado** que el administrador tiene permiso para gestionar usuarios,

**cuando** ingresa los datos personales (nombre, apellido, DNI, correo electrónico, teléfono, dirección) y selecciona un rol,

**entonces** el sistema crea el usuario con su contraseña cifrada y lo muestra en el listado con un mensaje de éxito.

### Escenario 2: Alta con datos duplicados

**Dado** que ya existe un usuario activo con el mismo correo electrónico o DNI,

**cuando** el administrador intenta registrar otro con esos datos,

**entonces** el sistema rechaza el alta e informa qué dato está duplicado.

### Escenario 3: Listar y buscar usuarios

**Dado** que existen usuarios registrados,

**cuando** el administrador consulta el listado y busca por nombre, DNI, correo o filtra por rol,

**entonces** el sistema muestra solo los usuarios coincidentes, de forma paginada.

### Escenario 4: Modificar un usuario

**Dado** que el administrador edita un usuario existente,

**cuando** modifica sus datos de contacto o su rol y deja la contraseña en blanco,

**entonces** el sistema actualiza los datos sin alterar la contraseña vigente.

## Reglas de negocio

- El correo electrónico y el DNI deben ser únicos entre los usuarios activos.
- Solo el administrador puede crear usuarios con cualquier rol.
- Dejar la contraseña en blanco al editar no la modifica.
- Las validaciones se realizan en el servidor.

## Definition of Done

- [ ] El administrador puede crear, listar, buscar y editar usuarios.
- [ ] Se valida la unicidad de correo y DNI.
- [ ] La contraseña se almacena cifrada y no se altera al editar sin cambiarla.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
