# HU-34 — Consultar mis contratos

> **Rol:** Inquilino · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P2 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-10 (#15), HU-11 (#16), HU-12 (#9)

## Historia de usuario

Como inquilino,

quiero consultar mis contratos de alquiler,

para conocer las condiciones pactadas, la vigencia y el estado de cada contrato.

## Criterios de aceptación

### Escenario 1: Listado de mis contratos

**Dado** que soy un inquilino con contratos,

**cuando** abro `/inquilino/contratos`,

**entonces** veo inmuebles, vigencia, canon y estado, con los vigentes primero.

### Escenario 2: Ficha del contrato

**Dado** que abro uno de mis contratos,

**cuando** se carga la ficha,

**entonces** veo inmueble, fechas, canon, actualización, vencimiento, mora, estado y datos de cierre si corresponde, con enlaces a Estado de cuenta y Mis pagos.

### Escenario 3: Contrato ajeno

**Dado** que intento abrir el contrato de otro inquilino,

**cuando** realizo la solicitud,

**entonces** el sistema responde 404.

### Escenario 4: Sin contratos

**Dado** que no tengo contratos,

**cuando** abro «Mi contrato»,

**entonces** veo enlaces al catálogo y a «Mis solicitudes».

### Escenario 5: Acceso desde Estado de cuenta

**Dado** que tengo un contrato vigente,

**cuando** elijo «Ver contrato digital» en Estado de cuenta,

**entonces** se abre la ficha del contrato.

## Reglas de negocio

- Solo lectura y acceso exclusivo a contratos propios.
- No se muestra la comisión inmobiliaria ni datos personales del propietario.
- Los montos y fechas provienen del servidor.
- Se reutiliza el patrón de consulta del propietario filtrando por `tenant_id`.

## Por definir

- Nombre del propietario: propuesta, no mostrarlo; el contacto es la administración.
- Contrato descargable: fuera de alcance; se ofrece una vista imprimible opcional.

## Definition of Done

- [x] El inquilino lista y abre sus contratos.
- [x] La ficha muestra las condiciones sin comisión ni datos del propietario.
- [x] Los contratos ajenos responden 404.
- [x] Estado de cuenta enlaza a la ficha.
- [x] Se agrega «Mi contrato» al menú del inquilino.
- [x] Se prueban los escenarios y casos de error.
- [ ] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue. (Handoff y registro hechos; falta publicar el comentario en el issue.)

## Dependencias

- HU-10 (#15), HU-11 (#16), HU-12 (#9) y HU-30 (#55).

## Referencias

- Rutas propuestas: `GET /inquilino/contratos`, `GET /inquilino/contratos/{id}`.
- Código: `app/Controllers/ContractController.php`, `app/Models/ContractModel.php`, `app/Views/contracts/index.php`.
- Issue: [#59](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/59).
