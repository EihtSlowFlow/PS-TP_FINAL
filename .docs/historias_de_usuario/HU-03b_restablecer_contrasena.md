# HU-03b — Restablecer la contraseña

> **Rol:** Usuario registrado · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Alto (acceso indebido si la verificación falla) · **Dependencias:** HU-01

## Historia de usuario

Como usuario que olvidó su contraseña,

quiero solicitar su restablecimiento de forma segura,

para recuperar el acceso a mi cuenta sin intervención de terceros.

## Criterios de aceptación

### Escenario 1: Solicitud de restablecimiento

**Dado** que soy un usuario activo y olvidé mi contraseña,

**cuando** ingreso mi correo electrónico registrado y mi DNI,

**entonces** el sistema genera un token temporal de un solo uso y me dirige al formulario para definir una nueva contraseña.

### Escenario 2: Definir una nueva contraseña

**Dado** que dispongo de un token válido y vigente,

**cuando** ingreso una nueva contraseña que cumple los requisitos de seguridad (mínimo 8 caracteres, una mayúscula, un número y un símbolo),

**entonces** el sistema actualiza mi contraseña, invalida el token utilizado y me redirige al inicio de sesión con un mensaje de confirmación.

### Escenario 3: Token inválido o vencido

**Dado** que el token es inválido, ya fue utilizado o venció,

**cuando** intento definir una nueva contraseña,

**entonces** el sistema rechaza la operación e informa que debo solicitar un nuevo restablecimiento.

### Escenario 4: Credenciales de recuperación incorrectas

**Dado** que ingreso un correo o un DNI que no corresponden a un usuario activo (o que corresponden a un usuario dado de baja),

**cuando** solicito el restablecimiento,

**entonces** el sistema permanece en la pantalla de solicitud e informa un mensaje de error genérico (`"Las credenciales ingresadas son incorrectas."`) sin revelar cuál de los dos datos falló ni si el correo existe. Al tercer intento fallido se activa un enfriamiento de 2 minutos.

> **Decisión aceptada.** Esta HU se pensó inicialmente con un mensaje neutro ("si el correo existe, recibirás instrucciones"), pero eso exige un canal externo (correo o SMS). El equipo consultó al docente si convenía usar uno y la respuesta fue que no: complicaría el proyecto, que termina con la entrega, y además requiere un tiempo de codificación que el equipo no tiene. Sin canal externo se pide correo + DNI y, ante cualquier combinación que no coincida con una cuenta activa, se devuelve un mensaje de error genérico sin permitir avanzar al formulario de nueva contraseña. Ver los riesgos aceptados en [`HU-03b_handoff.md`](../exec-plans/HU-03b_handoff.md).

## Reglas de negocio

- El token es temporal, de un solo uso y no predecible.
- La solicitud exige el correo y el DNI de la cuenta (segundo dato, porque no hay canal externo que verifique la identidad).
- Tres solicitudes seguidas que no coinciden con una cuenta activa activan de inmediato un bloqueo de 2 minutos (por IP y por correo), para frenar la adivinación del DNI.
- La nueva contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo (misma política que el registro y el perfil), y se almacena cifrada.
- Los usuarios dados de baja no pueden restablecer su contraseña (si ingresan su correo y DNI reciben `"Usuario dado de baja, hable con un administrador."`).

## Definition of Done

- [x] El usuario puede solicitar y completar el restablecimiento.
- [x] El token expira y se invalida tras su uso.
- [x] El sistema rechaza credenciales que no coinciden con un mensaje genérico que no revela si falló el correo o el DNI.
- [x] Los intentos de solicitud que no coinciden tienen un enfriamiento de 2 minutos en el tercer intento.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes.
