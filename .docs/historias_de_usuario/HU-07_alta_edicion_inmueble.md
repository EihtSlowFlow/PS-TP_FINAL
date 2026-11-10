# HU-07 — Dar de alta y editar un inmueble

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-04

## Historia de usuario

Como administrador,

quiero dar de alta y editar inmuebles vinculándolos a su propietario,

para ampliar el catálogo y gestionar el inventario de bienes en alquiler.

## Criterios de aceptación

### Escenario 1: Alta de un inmueble

**Dado** que el administrador tiene permiso para gestionar inmuebles,

**cuando** completa propietario, dirección, ciudad, tipo de inmueble, cantidad de ambientes, superficie, servicios incluidos, precio base de alquiler y descripción,

**entonces** el sistema registra el inmueble con estado inicial "Disponible" y lo muestra en el listado con un mensaje de éxito.

### Escenario 2: Datos inválidos

**Dado** que el administrador completa el formulario con datos inválidos (por ejemplo, precio menor o igual a cero, ambientes no positivos, superficie menor o igual a cero o ciudad vacía),

**cuando** intenta guardar,

**entonces** el sistema rechaza la operación e indica los errores de cada campo.

### Escenario 3: Modificar un inmueble

**Dado** que existe un inmueble registrado,

**cuando** el administrador modifica datos como el precio base o la descripción,

**entonces** el sistema actualiza el registro y deja constancia de la fecha de modificación.

## Reglas de negocio

- Todo inmueble debe estar vinculado a un usuario con rol propietario activo.
- El tipo de inmueble pertenece a un conjunto definido (departamento, casa, local comercial, terreno u oficina).
- La dirección y la ciudad son obligatorias. La superficie y los servicios incluidos son opcionales; si se informa la superficie, debe ser mayor a cero.
- El precio base debe ser un valor mayor a cero y los ambientes un entero positivo, obligatorio salvo para terrenos.
- Las validaciones se realizan en el servidor.

## Definition of Done

- [x] El administrador puede crear y editar inmuebles.
- [x] El inmueble nuevo queda en estado "Disponible".
- [x] Solo pueden asignarse propietarios activos.
- [x] Se verifican los permisos de acceso.
- [x] Se prueban los escenarios de aceptación y los casos de error relevantes.

## Notas de integración (desde HU-05)

- Para el selector de propietarios usar `UserModel::findActiveOwnersForProperty()`.
  No consultar `users` directamente ni usar `findAll()`: la baja lógica
  (`deleted_at`) se excluye en ese método.
- Validar también en el servidor, no solo en el selector. Antes de guardar,
  comprobar que el propietario siga activo con `UserModel::findActiveById()`;
  si no lo está, rechazar la operación.
- Un propietario dado de baja que ya tiene inmuebles los conserva: al listar o
  editar inmuebles existentes, el JOIN con `users` debe incluir también los
  inactivos (no filtrar por `deleted_at`).
- Cubrir con una prueba: un propietario dado de baja no aparece en el selector
  y un POST con su id es rechazado.
