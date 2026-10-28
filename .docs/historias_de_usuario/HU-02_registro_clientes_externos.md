# HU-02 — Registrarse como propietario o inquilino

> **Rol:** Visitante · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Medio · **Dependencias:** HU-01

## Historia de usuario

Como cliente nuevo (propietario o inquilino),

quiero completar un formulario de registro en línea,

para crear mi cuenta y operar en la plataforma.

## Criterios de aceptación

### Escenario 1: Registro exitoso

**Dado** que soy un visitante sin cuenta,

**cuando** completo nombre, apellido, DNI, correo electrónico, teléfono, rol deseado (propietario o inquilino) y una contraseña válida,

**entonces** el sistema crea mi cuenta y me redirige al inicio de sesión con un mensaje de registro exitoso.

### Escenario 2: Correo o DNI ya registrado

**Dado** que ya existe una cuenta con el mismo correo electrónico o DNI,

**cuando** intento registrarme con esos datos,

**entonces** el sistema rechaza el registro e informa que el dato ya se encuentra registrado.

Además, el formulario conserva los datos ingresados, excepto la contraseña.

### Escenario 3: Datos inválidos

**Dado** que ingreso un correo con formato inválido, una contraseña menor a 8 caracteres o contraseñas que no coinciden,

**cuando** envío el formulario,

**entonces** el sistema indica los errores de cada campo y no crea la cuenta.

## Reglas de negocio

- El registro público solo permite los roles propietario e inquilino; nunca administrador.
- El correo electrónico y el DNI deben ser únicos.
- La contraseña debe tener al menos 8 caracteres y se almacena cifrada.
- Las validaciones se realizan en el servidor, además de las del navegador.

## Definition of Done

- [ ] El visitante puede registrarse como propietario o inquilino.
- [ ] Se valida la unicidad de correo y DNI.
- [ ] La contraseña se almacena cifrada.
- [ ] No es posible registrarse con rol administrador.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
