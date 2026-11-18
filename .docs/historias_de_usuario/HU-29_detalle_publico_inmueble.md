# HU-29 — Detalle público de un inmueble

> **Rol:** Público (visitante) / Inquilino · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-08 (#3)

## Historia de usuario

Como visitante o inquilino,

quiero consultar la ficha completa de un inmueble disponible,

para decidir si me interesa y saber cómo registrarme, solicitarlo o contactar a la inmobiliaria.

## Criterios de aceptación

### Escenario 1: Ficha pública

**Dado** que existe un inmueble activo y disponible,

**cuando** abro `/catalogo-inmuebles/{id}`,

**entonces** veo dirección, ciudad, tipo, ambientes, superficie, servicios, precio mensual y descripción, sin propietario ni datos internos.

### Escenario 2: Llamado a la acción según el rol

**Dado** que estoy en el detalle,

**cuando** la página reconoce mi estado de sesión,

**entonces** un visitante ve Ingresar y Registrarme, un inquilino ve Solicitar alquiler y los demás roles ven un aviso de vista pública sin botón de solicitud.

### Escenario 3: Inmueble no disponible

**Dado** que el inmueble está alquilado, en mantenimiento, dado de baja o pendiente de aprobación,

**cuando** alguien intenta abrir su ficha,

**entonces** el sistema responde 404 sin revelar su existencia.

### Escenario 4: Contacto con la inmobiliaria

**Dado** que estoy en el detalle,

**cuando** consulto la tarjeta «¿Te interesa?»,

**entonces** veo teléfono, correo y enlace de WhatsApp.

## Reglas de negocio

- La visibilidad exige inmueble activo y disponible, y aprobado una vez implementada HU-32.
- La consulta es exclusivamente GET y no expone `owner_id`, contratos ni campos de auditoría.
- Volver al catálogo conserva los filtros cuando corresponda.
- Los identificadores inválidos o inexistentes responden 404.

## Por definir

- Fotos: la propuesta es mostrar un marcador visual hasta incorporar carga de imágenes.
- Solicitud de visita sin cuenta: fuera de alcance; se muestran los datos de contacto.

## Definition of Done

- [x] Existe `/catalogo-inmuebles/{id}` sin exposición de datos internos.
- [x] Los llamados a la acción dependen del rol.
- [x] Los inmuebles no disponibles responden 404.
- [x] Los enlaces desde el catálogo y la página de inicio llegan al detalle.
- [x] Se prueban los escenarios y se verifica que el HTML no exponga datos internos.
- [ ] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue. (Handoff y registro hechos; falta publicar el comentario en el issue.)

## Dependencias

- HU-08 (#3): catálogo público.
- HU-28 (#53): layout público compartido.
- Habilita HU-30 (#55).

## Referencias

- Pantalla: AUT-05 «Detalle público de inmueble».
- Código: `app/Controllers/PropertyController.php`, `app/Models/PropertyModel.php`, `app/Views/properties/public_catalog.php`.
- Issue: [#54](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/54).
