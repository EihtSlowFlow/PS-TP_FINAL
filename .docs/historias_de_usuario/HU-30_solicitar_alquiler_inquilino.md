# HU-30 — Solicitar el alquiler de un inmueble

> **Rol:** Inquilino · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 5 · **Riesgo:** Medio · **Dependencias:** HU-29 (#54)

## Historia de usuario

Como inquilino,

quiero solicitar el alquiler de un inmueble disponible y seguir el estado de mi solicitud,

para iniciar la contratación sin depender de que la administración me busque.

## Criterios de aceptación

### Escenario 1: Enviar la solicitud

**Dado** que soy un inquilino activo y el inmueble está disponible,

**cuando** informo fecha de inicio, plazo, teléfono y opcionalmente un mensaje,

**entonces** se registra una solicitud pendiente y se informa a la administración.

### Escenario 2: Datos inválidos

**Dado** que ingreso una fecha pasada, un plazo inválido o un teléfono vacío o incorrecto,

**cuando** intento enviar,

**entonces** el sistema rechaza la operación y muestra los errores.

### Escenario 3: Solicitud repetida

**Dado** que ya tengo una solicitud pendiente sobre el inmueble,

**cuando** intento enviar otra,

**entonces** el sistema la rechaza indicando que ya existe.

### Escenario 4: Inmueble no disponible

**Dado** que el inmueble fue alquilado, está en mantenimiento, dado de baja o no fue aprobado,

**cuando** intento solicitarlo,

**entonces** el sistema informa que ya no está disponible.

### Escenario 5: Seguimiento

**Dado** que envié solicitudes,

**cuando** abro «Mis solicitudes»,

**entonces** veo inmueble, fecha, estado y motivo de rechazo o el acceso al contrato si fue aprobada.

### Escenario 6: Cancelar

**Dado** que una solicitud está pendiente,

**cuando** la cancelo y confirmo,

**entonces** pasa a Cancelada y deja de aparecer en la bandeja administrativa.

### Escenario 7: Estado de cuenta sin contrato

**Dado** que soy un inquilino sin contratos ni cuotas,

**cuando** abro «Estado de cuenta»,

**entonces** veo enlaces a buscar inmuebles y «Mis solicitudes».

## Reglas de negocio

- Solo el rol Inquilino puede solicitar; `tenant_id` sale de la sesión.
- Máximo una solicitud pendiente por inquilino e inmueble.
- Solicitar no reserva ni cambia el estado del inmueble.
- Solo se pueden ver y cancelar solicitudes propias.
- Las operaciones POST usan token CSRF y validación en servidor.

## Modelo de datos

Crear la tabla `rental_requests` con inmueble, inquilino, fecha y plazo deseados, teléfono, mensaje, estado, motivo de rechazo, datos de resolución, contrato vinculado y marcas temporales.

Agregar al glosario los estados Pendiente, Aprobada, Rechazada y Cancelada.

## Definition of Done

- [x] Se puede enviar una solicitud desde un inmueble disponible.
- [x] «Mis solicitudes» muestra estados, motivos y contrato aprobado.
- [x] No se aceptan solicitudes duplicadas ni sobre inmuebles no disponibles.
- [x] El inquilino cancela solicitudes propias y no accede a las ajenas.
- [x] El estado vacío ofrece los accesos definidos.
- [x] Se prueban los escenarios y casos de error.
- [x] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue.

## Dependencias

- HU-29 (#54): botón «Solicitar alquiler».
- HU-02 (#20) y HU-06 (#11): cuenta y teléfono.
- Habilita HU-31 (#56) y HU-34 (#59).

## Referencias

- Rutas propuestas: `GET/POST /inquilino/inmuebles/{id}/solicitar`, `GET /inquilino/solicitudes`, `POST /inquilino/solicitudes/{id}/cancelar`.
- Código de referencia: `app/Controllers/PaymentController.php`, `app/Views/dashboard/tenant_account_status.php`.
- Issue: [#55](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/55).
