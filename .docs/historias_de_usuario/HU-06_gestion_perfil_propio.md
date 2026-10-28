# HU-06 — Gestionar mi perfil

> **Rol:** Propietario / Inquilino · **Alcance:** Cursada · **Prioridad:** P2 · **Story Points:** 2 · **Riesgo:** Bajo · **Dependencias:** HU-02

## Historia de usuario

Como propietario o inquilino autenticado,

quiero consultar y actualizar mi información de contacto y mi contraseña,

para mantener mis datos al día y proteger mi cuenta.

## Criterios de aceptación

### Escenario 1: Actualizar datos de contacto

**Dado** que estoy autenticado y accedo a mi perfil,

**cuando** modifico mi teléfono o mi dirección con datos válidos,

**entonces** el sistema guarda los cambios y muestra un mensaje de confirmación.

### Escenario 2: Cambiar la contraseña

**Dado** que deseo cambiar mi contraseña,

**cuando** ingreso mi contraseña actual correcta y una nueva de al menos 8 caracteres,

**entonces** el sistema actualiza mi contraseña almacenándola cifrada.

### Escenario 3: Contraseña actual incorrecta

**Dado** que deseo cambiar mi contraseña,

**cuando** ingreso una contraseña actual incorrecta,

**entonces** el sistema rechaza el cambio e informa el error sin modificar la contraseña vigente.

## Reglas de negocio

- Cada usuario solo puede ver y editar su propio perfil.
- Para cambiar la contraseña se exige verificar la contraseña actual.
- El rol y el DNI no pueden modificarse desde el perfil propio.

## Definition of Done

- [ ] El usuario puede consultar y editar sus datos de contacto.
- [ ] El cambio de contraseña exige la contraseña actual y se almacena cifrada.
- [ ] Un usuario no puede acceder al perfil de otro.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
