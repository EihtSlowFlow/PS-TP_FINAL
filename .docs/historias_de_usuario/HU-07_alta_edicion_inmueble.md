# HU-07 — Dar de alta y editar un inmueble

> **Rol:** Administrador · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-04

## Historia de usuario

Como administrador,

quiero dar de alta y editar inmuebles vinculándolos a su propietario,

para ampliar el catálogo y gestionar el inventario de bienes en alquiler.

## Criterios de aceptación

### Escenario 1: Alta de un inmueble

**Dado** que el administrador tiene permiso para gestionar inmuebles,

**cuando** completa propietario, dirección, tipo de inmueble, cantidad de ambientes, precio base de alquiler y descripción,

**entonces** el sistema registra el inmueble con estado inicial "Disponible" y lo muestra en el listado con un mensaje de éxito.

### Escenario 2: Datos inválidos

**Dado** que el administrador completa el formulario con datos inválidos (por ejemplo, precio menor o igual a cero o ambientes no positivos),

**cuando** intenta guardar,

**entonces** el sistema rechaza la operación e indica los errores de cada campo.

### Escenario 3: Modificar un inmueble

**Dado** que existe un inmueble registrado,

**cuando** el administrador modifica datos como el precio base o la descripción,

**entonces** el sistema actualiza el registro y deja constancia de la fecha de modificación.

## Reglas de negocio

- Todo inmueble debe estar vinculado a un usuario con rol propietario activo.
- El tipo de inmueble pertenece a un conjunto definido (departamento, casa, local comercial, terreno u oficina).
- El precio base debe ser un valor mayor a cero y los ambientes un entero positivo.
- Las validaciones se realizan en el servidor.

## Definition of Done

- [ ] El administrador puede crear y editar inmuebles.
- [ ] El inmueble nuevo queda en estado "Disponible".
- [ ] Solo pueden asignarse propietarios activos.
- [ ] Se verifican los permisos de acceso.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
