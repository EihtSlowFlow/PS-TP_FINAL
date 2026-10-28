# HU-03b — Restablecer la contraseña

> **Rol:** Usuario registrado · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Alto (acceso indebido si la verificación falla) · **Dependencias:** HU-01

## Historia de usuario

Como usuario que olvidó su contraseña,

quiero solicitar su restablecimiento de forma segura,

para recuperar el acceso a mi cuenta sin intervención de terceros.

## Criterios de aceptación

### Escenario 1: Solicitud de restablecimiento

**Dado** que soy un usuario activo y olvidé mi contraseña,

**cuando** ingreso mi correo electrónico registrado,

**entonces** el sistema genera un token temporal de un solo uso y me brinda las instrucciones para definir una nueva contraseña.

### Escenario 2: Definir una nueva contraseña

**Dado** que dispongo de un token válido y vigente,

**cuando** ingreso una nueva contraseña que cumple los requisitos de seguridad,

**entonces** el sistema actualiza mi contraseña, invalida el token utilizado y me redirige al inicio de sesión con un mensaje de confirmación.

### Escenario 3: Token inválido o vencido

**Dado** que el token es inválido, ya fue utilizado o venció,

**cuando** intento definir una nueva contraseña,

**entonces** el sistema rechaza la operación e informa que debo solicitar un nuevo restablecimiento.

### Escenario 4: Correo no registrado

**Dado** que ingreso un correo que no pertenece a un usuario activo,

**cuando** solicito el restablecimiento,

**entonces** el sistema responde con un mensaje neutro que no revela si el correo existe.

## Reglas de negocio

- El token es temporal, de un solo uso y no predecible.
- La nueva contraseña debe tener al menos 8 caracteres y se almacena cifrada.
- Los usuarios dados de baja no pueden restablecer su contraseña.

## Definition of Done

- [ ] El usuario puede solicitar y completar el restablecimiento.
- [ ] El token expira y se invalida tras su uso.
- [ ] El sistema no revela la existencia de correos registrados.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
