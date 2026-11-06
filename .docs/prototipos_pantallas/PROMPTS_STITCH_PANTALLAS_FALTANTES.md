# Prompts de Google Stitch: pantallas que faltan

Complementa a [`PROMPTS_STITCH_PROTOTIPOS.md`](PROMPTS_STITCH_PROTOTIPOS.md). Reúne **8 prompts** para las pantallas que no tenían prototipo en esta carpeta (ya generados: ver [Estado de los prototipos](#estado-de-los-prototipos)), más **3 ajustes** por el nuevo rol Empleado. Cada prompt de pantalla es **autocontenido** (incluye estilo, menú del rol y datos de ejemplo) y **ninguno supera los 2.000 caracteres** (el más largo tiene 1.774; se midió uno por uno). Usan los mismos datos ficticios, el mismo formato y la misma fecha (hoy = 06/11/2026) que los prompts existentes, y los montos coinciden con ellos.

La forma de usarlos, las decisiones de UX ya tomadas y el checklist de revisión son los del archivo principal.

## Qué falta y por qué

El archivo principal cuenta 37 pantallas y dice que solo falta una. En la carpeta hay 35 prototipos de pantallas, así que faltan dos con prompt y, además, hay pantallas que las historias piden y no tenían prompt.

| ID | Pantalla | Rol | HU | Alcance | Por qué falta |
| :--- | :--- | :--- | :--- | :--- | :--- |
| AUT-03a | Recuperar contraseña: solicitud | Público | HU-03b | Cursada | Tiene prompt, falta generar el prototipo |
| PRO-04a | Liquidaciones: listado | Propietario | HU-21 | Final | Tiene prompt, falta generar el prototipo (la carpeta `liquidaciones_listado` es la del administrador) |
| AUT-05 | Detalle público de inmueble | Público | HU-08 | Cursada | El botón "Ver detalle" del catálogo no lleva a ninguna pantalla |
| ADM-04b | Inmueble: ficha | Administrador | HU-08, HU-09 | Cursada | El listado tiene la acción "ver" y no hay pantalla |
| ADM-05b | Inmueble: editar | Administrador | HU-07 | Cursada | Existe "nuevo" pero no "editar" (HU-07 pide alta y edición) |
| ADM-10b | Cuota: detalle | Administrador | HU-14, HU-17 | Cursada | Cobranzas tiene la acción "Ver detalle" y no hay pantalla |
| ADM-15c | Reporte de recaudación | Administrador | HU-27 | Final | HU-27 pide recaudación además de morosidad; solo está morosidad |
| ADM-15d | Reporte de recaudación: impresión | Impresión | HU-27 | Final | HU-27 pide una versión imprimible de cada reporte |

**Orden sugerido.** Primero los de Cursada, que son los del primer tramo de la entrega (del 17 al 24 de noviembre): AUT-03a, ADM-05b, AUT-05, ADM-04b y ADM-10b. Después los de Final: PRO-04a, ADM-15c y ADM-15d. Los ajustes del rol Empleado, al final.

---

## Ya tenían prompt: falta generar el prototipo

Son idénticos a los del archivo principal, repetidos acá para pegarlos sin buscar.

### AUT-03a Recuperar contraseña: solicitud (HU-03b)

```text
Diseñá la pantalla «Recuperar contraseña: solicitud» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar, a la que se llega desde "¿Olvidaste tu contraseña?" del login. Una sola tarjeta centrada de 480px con el logo arriba, título "Recuperar contraseña" y texto "Ingresá el correo electrónico de tu cuenta y te brindamos las instrucciones para definir una nueva contraseña". Campo "Correo electrónico*". Botón primario de ancho completo "Enviar instrucciones" y enlace "Volver a ingresar". Debajo del botón, un aviso informativo neutro: "Si el correo pertenece a una cuenta activa, te brindamos las instrucciones" (no revela si el correo existe).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

### PRO-04a Liquidaciones: listado, propietario (HU-21)

```text
Diseñá la pantalla «Liquidaciones: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Debe dejar claro cuánto se cobró, cuánto se descontó y cuánto le llegó. Encabezado «Liquidaciones» y dos tarjetas: Acreditado en los últimos 12 meses $ 6.310.800 y Pendiente de transferir $ 717.600. Filtros: año, inmueble y estado. Tabla: período, inmueble, bruto cobrado, comisión retenida (% e importe), neto, badge de estado, fecha de acreditación, N° de operación y acciones "Ver recibo" e "Imprimir". Filas: Sep 2026 · Belgrano 1250 · $ 420.000 · 10% $ 42.000 · $ 378.000 · Transferido · 12/10/2026 · 0051878; Sep 2026 · San Martín 322 · $ 780.000 · 8% $ 62.400 · $ 717.600 · Pendiente de Transferencia; Ago 2026 · Belgrano 1250 · $ 420.000 · 10% $ 42.000 · $ 378.000 · Transferido · 11/09/2026 · 0049011; Ago 2026 · San Martín 322 · $ 780.000 · 8% $ 62.400 · $ 717.600 · Transferido · 11/09/2026 · 0049012.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Liquidaciones.
```

---

## Pantallas nuevas

### AUT-05 Detalle público de inmueble (HU-08)

```text
Diseñá la pantalla «Detalle público de inmueble» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar, a la que se llega desde «Ver detalle» del catálogo. Cabecera con el logo y los botones "Ingresar" (secundario) y "Registrarse" (primario). Breadcrumb «Catálogo / Roca 1020, Piso 2». Galería con una foto grande y 4 miniaturas. Título «Roca 1020, Piso 2», badge verde "Disponible", "Viedma" y el precio mensual destacado "$ 510.000". Ficha en dos columnas: Tipo Oficina, 2 ambientes, 48 m², Servicios incluidos "Agua y expensas". Descripción: "Oficina luminosa en pleno centro, ideal para estudio profesional". Tarjeta lateral «¿Te interesa?» con el texto "Registrate para seguir tus alquileres o contactá a la inmobiliaria" y botones "Registrarme" y "Volver al catálogo". No muestra propietario, contratos ni datos internos.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

### ADM-04b Inmueble: ficha (HU-08, HU-09)

```text
Diseñá la pantalla «Inmueble: ficha» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Mitre 845» con badge "Alquilado"; a la derecha "Editar" (secundario) y "Dar de baja" (peligro, deshabilitado) con el aviso debajo: "No es posible dar de baja un inmueble con contrato de locación vigente". Tarjeta «Datos del inmueble»: propietaria Marta Quiroga, Viedma, Casa, 4 ambientes, 120 m², servicios incluidos "Agua, luz y gas", precio base $ 650.000, creado el 14/11/2024, última modificación 20/10/2026. Tarjeta «Contrato vigente»: C-002 · Joaquín Medina · 16/12/2024 – 15/12/2026 · $ 650.000, chip ámbar "Vence en 39 días" y enlace "Ver contrato". Tarjeta «Historial de contratos» con la fila C-002 · Vigente.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Inmuebles.
```

### ADM-05b Inmueble: editar (HU-07)

```text
Diseñá la pantalla «Inmueble: editar» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Formulario en página propia con los datos de Sarmiento 77, 1°A cargados. Breadcrumb «Inmuebles / Editar inmueble». Sección «Datos del inmueble»: Propietario* (Marta Quiroga, select con búsqueda), Dirección exacta*, Ciudad* (Viedma), Tipo* (Departamento), Cantidad de ambientes* (2), Superficie en m² (54), Servicios incluidos (Agua y luz) y Precio base de alquiler sugerido* ($ 350.000). Sección «Descripción»: textarea con contador. A la derecha, bloque «Estado» con select (Disponible o En Mantenimiento; En Mantenimiento elegido) y la ayuda "«Alquilado» se asigna solo al generar un contrato", y debajo "Creado el 20/02/2024 · Última modificación 28/10/2026". Acciones: "Guardar cambios" (primario) y "Cancelar".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Inmuebles.
```

### ADM-10b Cuota: detalle (HU-14, HU-17)

```text
Diseñá la pantalla «Cuota: detalle» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Cuota Octubre 2026 · Mitre 845» con badge rojo "Vencida" y enlace "Contrato C-002". Tarjeta «Desglose»: vencimiento 10/10/2026, 27 días de atraso, monto base $ 650.000, mora 0,5% diario ($ 3.250 por día) = $ 87.750 y total exigible $ 737.750 destacado, con la nota "El recargo queda fijo mientras un pago está en revisión". Tarjeta «Partes»: inquilino Joaquín Medina (2920 45-6789) y propietaria Marta Quiroga. Tarjeta «Pagos informados», tabla: 12/10/2026 · Transferencia · $ 650.000 · badge Rechazado · motivo "Comprobante ilegible" · enlace "Ver pago". Botón "Volver a cobranzas".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Cuotas > Cobranzas.
```

### ADM-15c Reporte de recaudación (HU-27)

```text
Diseñá la pantalla «Reporte de recaudación» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Filtros: período (desde Octubre 2026 hasta Noviembre 2026), medio de pago (Todos, Transferencia, Depósito, Pasarela digital) y "Aplicar filtros"; a la derecha, "Imprimir / Exportar". Tarjetas: Total recaudado $ 1.980.000 y Pagos aprobados 3. Tabla de pagos aprobados: fecha de pago, inquilino, inmueble, período abonado, medio, N° de operación y monto. Filas: 05/11/2026 · Valentina Funes · San Martín 322, Local 3 · Nov 2026 · Transferencia · 0087456 · $ 780.000; 14/10/2026 · Valentina Funes · San Martín 322, Local 3 · Oct 2026 · Transferencia · 0079321 · $ 780.000; 08/10/2026 · Camila Rojas · Belgrano 1250, 4°B · Oct 2026 · Transferencia · 0071650 · $ 420.000. Pie con subtotales: Octubre $ 1.200.000, Noviembre $ 780.000 y total $ 1.980.000.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Reportes.
```

### ADM-15d Reporte de recaudación: impresión (HU-27)

```text
Diseñá la pantalla «Reporte de recaudación: impresión» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Diseñá la vista de impresión del reporte de recaudación, sin sidebar, barra superior, filtros ni botones de la app. Contenido: logo, título "Reporte de recaudación", "Fecha de emisión: 06/11/2026", "Período: Octubre – Noviembre 2026 · Medio de pago: todos", tabla (fecha de pago, inquilino, inmueble, período abonado, medio, N° de operación, monto) con las filas 05/11/2026 · Valentina Funes · San Martín 322, Local 3 · Nov 2026 · Transferencia · 0087456 · $ 780.000; 14/10/2026 · Valentina Funes · San Martín 322, Local 3 · Oct 2026 · Transferencia · 0079321 · $ 780.000; 08/10/2026 · Camila Rojas · Belgrano 1250, 4°B · Oct 2026 · Transferencia · 0071650 · $ 420.000, los subtotales "Octubre $ 1.200.000 · Noviembre $ 780.000" y el total "$ 1.980.000". Pie: "Generado por Laura Benítez · InmoGestión 2026".

ESTILO: documento A4 vertical imprimible, fondo blanco, sin sombras ni degradados, legible en blanco y negro, fuente Inter, español rioplatense. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
```

---

## Ajustes por el rol Empleado

Los prototipos de usuarios (`usuarios_listado`, `usuario_nuevo` y `usuario_editar`) se generaron con tres roles. No hace falta regenerarlos: pegá cada mensaje como ajuste sobre la pantalla ya generada. Estos no tienen límite de largo.

### Usuarios: listado

```text
En la pantalla «Usuarios: listado» agregá el rol Empleado: el select de rol pasa a "Todos, Administrador, Empleado, Propietario, Inquilino", y sumá una fila nueva, Gabriela Ortiz, justo debajo de Laura Benítez, con un badge de rol "Empleado" (mismo estilo que los otros badges de rol, con ícono + texto). La página sigue mostrando 10 filas, así que Elena Arias pasa a la página 2: el pie dice "Mostrando 1–10 de 11" y la paginación tiene 2 páginas. Dejá el resto igual.
```

### Usuario: nuevo

```text
En la pantalla «Usuario: nuevo», en la tarjeta «Acceso y permisos», pasá el selector de Rol de tres a cuatro tarjetas de selección: Administrador, Empleado, Propietario e Inquilino. La tarjeta de Empleado lleva ícono y la descripción "Personal operativo de la inmobiliaria". Dejá el resto igual.
```

### Usuario: editar

```text
En la pantalla «Usuario: editar», en la tarjeta «Acceso y permisos», pasá el selector de Rol de tres a cuatro tarjetas de selección: Administrador, Empleado, Propietario e Inquilino, con Inquilino elegida. La tarjeta de Empleado lleva ícono y la descripción "Personal operativo de la inmobiliaria". Dejá el resto igual.
```

---

## Qué no se incluyó, y por qué

- **Modales** (confirmar una baja, cerrar un contrato con fecha y motivo, rechazar un pago): igual que en el archivo principal, se piden como ajuste sobre la pantalla ya generada.
- **Pantalla de acceso denegado:** HU-01 (escenario 4) pide denegar el acceso, informar que no se está autorizado y **redirigir al panel del usuario**. Es un mensaje sobre el panel, no una pantalla propia.
- **Pantallas propias del Empleado:** los permisos del Empleado todavía no están definidos (análisis, sección 3), así que por ahora usa las del Administrador.
- **Mis contratos del inquilino:** ninguna historia lo pide.
- **Editar contrato y reporte de contratos:** el análisis menciona modificar contratos y el enunciado de HU-27 habla de "listados de cobranzas, morosidad y contratos", pero ningún escenario define esas pantallas. Si el equipo las quiere, hay que definirlas antes de prototiparlas.
- **Mensajes de error de formularios y estados vacíos:** son estados de pantallas ya existentes.

## Estado de los prototipos

Las 8 pantallas ya se generaron con estos prompts. Están en [`stitch_inmogesti_n_login_portal/`](stitch_inmogesti_n_login_portal/), la carpeta tal como la exportó Stitch, cada una con `code.html` y `screen.png`. Se dejó anidada porque `liquidaciones_listado_inmogesti_n_2026` ahí es el del propietario (PRO-04a) y chocaría con la carpeta del mismo nombre del administrador (ADM-14a), que ya estaba en la carpeta principal. La exportación trae además una copia de `DESIGN.md` idéntica a la versión anterior de `inmogesti_n_modern_admin/DESIGN.md`; no se versionó.

Los 3 ajustes del rol Empleado no figuran en la exportación, así que siguen pendientes de aplicar sobre los prototipos de usuarios.
