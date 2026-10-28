# HU-16 — Registrar un pago y adjuntar el comprobante

> **Rol:** Inquilino · **Alcance:** Cursada · **Prioridad:** P1 · **Story Points:** 8 · **Riesgo:** Alto (archivos adjuntos y seguridad) · **Dependencias:** HU-15

## Historia de usuario

Como inquilino,

quiero informar un pago realizado adjuntando sus datos y el comprobante digital,

para que la administración pueda conciliarlo y registrar la cuota como pagada.

## Criterios de aceptación

### Escenario 1: Registro de un pago válido

**Dado** que tengo una cuota pendiente o vencida,

**cuando** informo el monto transferido, la fecha de la operación, el medio de pago, el número de transacción y adjunto un comprobante válido,

**entonces** el sistema registra el pago como "Pendiente de validación" y la cuota pasa a "En revisión".

### Escenario 2: Archivo no permitido

**Dado** que intento adjuntar un archivo que no es un PDF o una imagen permitida,

**cuando** envío el formulario,

**entonces** el sistema rechaza el envío e informa que el comprobante debe ser un archivo válido, sin almacenar nada.

### Escenario 3: Archivo demasiado grande

**Dado** que el comprobante supera el tamaño máximo permitido,

**cuando** envío el formulario,

**entonces** el sistema rechaza el envío e informa el límite de tamaño.

### Escenario 4: Datos inválidos

**Dado** que el monto no es positivo o la fecha de la operación es futura,

**cuando** envío el formulario,

**entonces** el sistema rechaza el pago e indica los errores de cada campo.

### Escenario 5: Cuota ajena o ya pagada

**Dado** que intento informar un pago sobre una cuota que no es mía o que ya está pagada o en revisión,

**cuando** envío la solicitud,

**entonces** el sistema rechaza la operación.

## Reglas de negocio

- Formatos permitidos: PDF, JPG y PNG, con un tamaño máximo de 5 MB.
- El tipo de archivo se verifica en el servidor y no se confía en el nombre ni el tipo declarado por el cliente.
- Los comprobantes se almacenan con nombre generado y fuera del acceso público directo.
- Solo se puede informar pago sobre cuotas propias pendientes o vencidas.

## Definition of Done

- [ ] El inquilino puede informar un pago con su comprobante.
- [ ] El pago queda "Pendiente de validación" y la cuota "En revisión".
- [ ] Se validan formato, tamaño y contenido del archivo en el servidor.
- [ ] Los comprobantes no son accesibles públicamente sin autorización.
- [ ] Se prueban los escenarios de aceptación y los casos de error relevantes.
