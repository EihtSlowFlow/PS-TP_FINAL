# HU-06 — Gestionar mi perfil

> **Rol:** Propietario / Inquilino · **Alcance:** Cursada · **Prioridad:** P2 · **Story Points:** 2 · **Riesgo:** Bajo · **Dependencias:** HU-02

## Historia de usuario

Como propietario o inquilino autenticado,

quiero consultar y actualizar mi información de contacto y mi contraseña,

para mantener mis datos al día y proteger mi cuenta.

## Criterios de aceptación

### Escenario 1: Actualizar datos de contacto

**Dado** que estoy autenticado y accedo a mi perfil,

**cuando** modifico mi teléfono (entre 6 y 15 dígitos; se permiten formatos internacionales) o mi dirección con datos válidos,

**entonces** el sistema guarda los cambios y muestra un mensaje de confirmación.

### Escenario 2: Cambiar la contraseña

**Dado** que deseo cambiar mi contraseña,

**cuando** ingreso mi contraseña actual correcta, una nueva que no sea igual a la actual y la confirmo,

**entonces** el sistema verifica que la nueva tenga al menos 8 caracteres, una mayúscula, un número y un símbolo, y la almacena cifrada.

### Escenario 3: Contraseña actual incorrecta

**Dado** que deseo cambiar mi contraseña,

**cuando** ingreso una contraseña actual incorrecta,

**entonces** el sistema rechaza el cambio e informa el error sin modificar la contraseña vigente.

## Reglas de negocio

- Cada usuario solo puede ver y editar su propio perfil.
- Para cambiar la contraseña se exige verificar la contraseña actual.
- La nueva contraseña debe diferir de la actual y contener al menos 8 caracteres, una mayúscula, un número y un símbolo.
- Los teléfonos deben tener entre 6 y 15 dígitos; se permiten `+`, espacios, paréntesis, puntos y guiones como formato.
- El rol y el DNI no pueden modificarse desde el perfil propio.

## Definition of Done

- [x] El usuario puede consultar y editar sus datos de contacto.
- [x] El cambio de contraseña exige la contraseña actual, confirmación, política de complejidad y se almacena cifrada.
- [x] Un usuario no puede acceder al perfil de otro.
- [ ] Se ejecutan los escenarios de aceptación y los casos de error relevantes. (Pruebas agregadas; ejecución pendiente por incompatibilidad del PHPUnit disponible.)
