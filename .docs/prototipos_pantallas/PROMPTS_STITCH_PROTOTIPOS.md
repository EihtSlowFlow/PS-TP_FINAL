# Prompts de Google Stitch para prototipar InmoGestión 2026

**Un prompt por pantalla.** Son 36 prompts, uno por cada pantalla del sistema. Cada bloque es **autocontenido** (incluye el estilo, el menú del rol y los datos de ejemplo que necesita), así que se puede pegar solo, sin ningún prompt de base. **Ninguno supera los 2.000 caracteres** (el más largo tiene 1.937; se midió uno por uno).

Cubren las 27 historias de usuario de `historias_de_usuario/`, usan los tokens que ya existen en `public/css/styles.css` (Inter, índigo `#4F46E5`, radios 8/12/16 px) y siguen el formato "admin template" que pidió la cátedra: menú lateral + barra superior + contenido central.

---

## Cómo usarlos

1. Entrá a Stitch (stitch.withgoogle.com) con tu cuenta de Google y creá un proyecto nuevo.
2. Elegí el dispositivo **Web** (escritorio) en el selector del proyecto.
3. **Recomendado:** cargá [`DESIGN.md`](DESIGN.md) como sistema de diseño del proyecto (Stitch permite importar un `DESIGN.md`; buscá la opción de design system del proyecto, el lugar exacto puede variar según la versión). Así todas las pantallas comparten estilo. Con el `DESIGN.md` cargado, el bloque ESTILO / LAYOUT del final de cada prompt es redundante y podés borrarlo si necesitás espacio. Sin `DESIGN.md`, los prompts funcionan igual porque ya lo incluyen.
4. Pegá **un prompt por vez** (solo el contenido del bloque, sin las líneas de triple acento grave) y generá la pantalla. Empezá por `AUT-01`, `ADM-01`, `INQ-01` y `PRO-01` para validar el estilo antes de seguir con el resto.
5. Revisá la pantalla con el checklist del final. Si algo no cierra, corregilo con un mensaje corto sobre esa pantalla (por ejemplo: *"el botón primario tiene que ser #4F46E5"*).
6. Si una pantalla sale con otro estilo que las anteriores, pedile: *"Mantené el mismo estilo, colores y layout que la pantalla de Login"*.

**Qué no está en los prompts (a propósito):** modales, errores y estados vacíos. Si los necesitan para la presentación, pídanlos como ajuste sobre la pantalla ya generada. Por ejemplo, en `ADM-02`: *"Mostrá el modal de confirmación al dar de baja a Elena Arias"*.

**Nota técnica:** el proyecto es CodeIgniter 4 con HTML5, CSS3 y JS vanilla (ver `.agents/AGENTS.md`). Lo que genere Stitch se usa como **referencia visual**; el código se rehace a mano con HTML semántico y CSS propio.

### Decisiones de UX ya tomadas (para que no las decida la IA)

- **Layout:** sidebar fijo a la izquierda, barra superior con campana de notificaciones y menú de usuario, contenido central.
- **ABMC:** listado con filtros arriba y botón primario "Nuevo …". El alta y la edición van en **página propia** (no en modal). La baja siempre pide **confirmación en modal**. El resultado se informa con un **mensaje flash** arriba del contenido.
- **Formularios:** etiqueta arriba, asterisco en obligatorios, ayuda debajo y error en vivo debajo del campo (ícono + texto).
- **Estados:** nunca solo color. Cada badge lleva ícono + texto + color.
- **Mobile:** el sidebar pasa a drawer con hamburguesa y las tablas pasan a tarjetas apiladas (se resuelve en la implementación).
- **Fecha ficticia del prototipo:** hoy = 06/11/2026. Los datos de ejemplo están calculados para esa fecha.

---

## Índice de pantallas

| ID | Pantalla | Rol | HU | Ruta |
| :--- | :--- | :--- | :--- | :--- |
| AUT-01 | Iniciar sesión | Público | HU-01, HU-03 | `/auth/login` |
| AUT-02 | Crear cuenta | Público | HU-02 | `/auth/register` |
| AUT-03 | Recuperar contraseña | Público | HU-03b | `/auth/recuperar-password` |
| AUT-04 | Catálogo público de inmuebles | Público | HU-08 | a definir (/inmuebles) |
| COM-01 | Mi perfil | Inquilino | HU-06 | a definir (/perfil) |
| COM-02a | Panel de notificaciones (campana) | Administrador | HU-22, HU-23, HU-24 | componente de la barra superior |
| COM-02b | Notificaciones: Administradora | Administrador | HU-22, HU-24 | a definir (/notificaciones) |
| COM-02c | Notificaciones: Inquilino | Inquilino | HU-22, HU-23 | a definir (/notificaciones) |
| COM-02d | Notificaciones: Propietario | Propietario | HU-24 | a definir (/notificaciones) |
| ADM-01 | Dashboard ejecutivo | Administrador | HU-25 | `/admin/dashboard` |
| ADM-02 | Usuarios: listado | Administrador | HU-04, HU-05 | `/admin/usuarios` |
| ADM-03a | Usuario: nuevo | Administrador | HU-04 | `/admin/usuarios/nuevo` |
| ADM-03b | Usuario: editar | Administrador | HU-04 | `/admin/usuarios/editar/:id` |
| ADM-04 | Inmuebles: listado | Administrador | HU-08, HU-09 | `/admin/inmuebles` |
| ADM-05 | Inmueble: nuevo | Administrador | HU-07 | `/admin/inmuebles/nuevo` |
| ADM-06 | Contratos: listado | Administrador | HU-11 | `/admin/contratos` |
| ADM-07 | Contrato: nuevo | Administrador | HU-10 | `/admin/contratos/nuevo` |
| ADM-08 | Contrato: ficha | Administrador | HU-10, HU-12, HU-24 | a definir (/admin/contratos/:id) |
| ADM-09a | Emitir cuotas: período y vista previa | Administrador | HU-13 | `/admin/cuotas/emitir` |
| ADM-09b | Emitir cuotas: informe de resultado | Administrador | HU-13 | a definir |
| ADM-10 | Cobranzas (cuotas y mora) | Administrador | HU-14 | a definir (/admin/cuotas) |
| ADM-11 | Pagos: bandeja | Administrador | HU-17, HU-18 | `/admin/pagos/pendientes` |
| ADM-12 | Pago: revisión | Administrador | HU-17 | a definir (/admin/pagos/:id) |
| ADM-13 | Liquidaciones: generar | Administrador | HU-19 | `/admin/liquidaciones/generar` |
| ADM-14a | Liquidaciones: listado | Administrador | HU-20 | `/admin/liquidaciones` |
| ADM-14b | Liquidación: detalle y transferencia | Administrador | HU-20 | a definir |
| ADM-15a | Reporte de morosidad | Administrador | HU-27 | `/admin/reportes/morosidad` |
| ADM-15b | Reporte de morosidad: impresión | Impresión | HU-27 | vista @media print |
| INQ-01 | Estado de cuenta | Inquilino | HU-15, HU-23 | `/inquilino/estado-cuenta` |
| INQ-02 | Informar pago | Inquilino | HU-16 | `/inquilino/pagos/registrar/:cuota_id` |
| INQ-03 | Mis pagos | Inquilino | HU-18 | `/inquilino/mis-pagos` |
| PRO-01 | Dashboard del propietario | Propietario | HU-26, HU-24 | `/propietario/dashboard` |
| PRO-02 | Mis inmuebles | Propietario | HU-08 | `/propietario/mis-inmuebles` |
| PRO-03 | Mis contratos | Propietario | HU-11 | `/propietario/mis-contratos` |
| PRO-04a | Liquidaciones: listado | Propietario | HU-21 | `/propietario/liquidaciones` |
| PRO-04b | Recibo de liquidación (imprimible) | Impresión | HU-21 | a definir |

---

## Autenticación y público

### AUT-01 Iniciar sesión (HU-01, HU-03)

```text
Diseñá la pantalla «Iniciar sesión» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar. Dos columnas. Izquierda: panel índigo #4F46E5 con el logo, el título "Gestión de alquileres, simple y transparente" y tres puntos con ícono ("Cuotas y mora automáticas", "Pagos con comprobante", "Liquidaciones claras"). Derecha: tarjeta blanca centrada con título "Ingresar a InmoGestión", campos "Correo electrónico" y "Contraseña" (con ojo para mostrar u ocultar), enlace "¿Olvidaste tu contraseña?", botón primario de ancho completo "Ingresar" y debajo "¿No tenés cuenta? Registrate".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

### AUT-02 Crear cuenta (HU-02)

```text
Diseñá la pantalla «Crear cuenta» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar. Tarjeta centrada de 640px, título "Crear cuenta" y subtítulo "Completá tus datos para operar en InmoGestión". Campos en dos columnas: Nombre*, Apellido*, DNI*, Correo electrónico*, Teléfono* y "Quiero registrarme como*" con dos tarjetas de selección (Propietario / Inquilino, cada una con ícono y una línea de descripción; Inquilino seleccionada). Luego Contraseña* (mínimo 8 caracteres, indicador de fortaleza, ojo) y Repetir contraseña*. Botón primario "Crear cuenta" y enlace "¿Ya tenés cuenta? Ingresá". Ayudas debajo de cada campo.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

### AUT-03 Recuperar contraseña (HU-03b)

```text
Diseñá la pantalla «Recuperar contraseña» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar, a la que se llega desde "¿Olvidaste tu contraseña?" del login. Una sola tarjeta centrada de 480px con el logo arriba, título "Recuperar contraseña" y texto "Definí tu nueva contraseña". Campos "Nueva contraseña*" y "Repetir nueva contraseña*" (ambos con ojo para mostrar u ocultar). Lista de requisitos con tildes: "Mínimo 8 caracteres" y "Las contraseñas coinciden". Botón primario de ancho completo "Cambiar contraseña" y enlace "Volver a ingresar".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

### AUT-04 Catálogo público de inmuebles (HU-08)

```text
Diseñá la pantalla «Catálogo público de inmuebles» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla pública, sin sidebar. Cabecera con el logo y, a la derecha, los botones "Ingresar" (secundario) y "Registrarse" (primario). Título "Encontrá tu próximo alquiler" y barra de filtros: búsqueda por dirección, Tipo (Departamento, Casa, Local comercial, Terreno, Oficina), Ambientes, Precio mínimo, Precio máximo, "Filtrar" y "Limpiar". Grilla de 6 tarjetas en 3 columnas: foto de inmueble, badge verde "Disponible", dirección, tipo y ambientes, precio mensual destacado y botón "Ver detalle". La primera: Roca 1020, Piso 2 · Oficina · 2 amb. · $ 510.000; las otras 5, ficticias con el mismo formato. Paginación al pie.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000. Logo: casa blanca en cuadrado índigo + "InmoGestión".
```

## Pantallas compartidas

### COM-01 Mi perfil (HU-06)

```text
Diseñá la pantalla «Mi perfil» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Dos tarjetas apiladas. 1) «Datos personales»: avatar con iniciales; Nombre y Apellido (Joaquín Medina), DNI y Correo electrónico en solo lectura con candado y la ayuda "Para modificarlo, contactá a la inmobiliaria"; Teléfono* (2920 45-6789) y Dirección de correspondencia* editables; botón primario "Guardar cambios". 2) «Cambiar contraseña»: Contraseña actual*, Nueva contraseña* (mínimo 8 caracteres, indicador de fortaleza) y Repetir nueva contraseña*; botón "Actualizar contraseña". Cada tarjeta se guarda por separado.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Joaquín Medina, Inquilino) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Estado de cuenta, Mis pagos, Mi perfil. Activo: Mi perfil.
```

### COM-02a Panel de notificaciones (campana) (HU-22, HU-23, HU-24)

```text
Diseñá la pantalla «Panel de notificaciones (campana)» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Página del Administrador con el panel desplegable de notificaciones abierto bajo la campana (contador rojo "2") y el resto del contenido atenuado. Panel de 380px con título "Notificaciones", la lista y el enlace "Ver todas" al pie. Cada ítem: ícono, mensaje, tiempo relativo y punto azul si no está leído. Ítems: "El inquilino Camila Rojas ha informado un pago para la cuota Noviembre 2026 del inmueble Belgrano 1250, 4°B" (hace 3 horas, no leído); "El contrato del inmueble Mitre 845 con el inquilino Joaquín Medina vencerá el 15/12/2026" (no leído); "El contrato del inmueble Belgrano 1250, 4°B con el inquilino Camila Rojas vencerá el 31/12/2026" (leído).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Dashboard.
```

### COM-02b Notificaciones: Administradora (HU-22, HU-24)

```text
Diseñá la pantalla «Notificaciones: Administradora» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Página «Notificaciones» con el botón "Marcar todas como leídas", pestañas "Todas" y "No leídas (2)" y la lista agrupada en "Hoy" y "Anteriores". Cada ítem: ícono según el tipo, mensaje, tiempo relativo, punto azul si no está leído y un enlace de acción. Ítems: "El inquilino Camila Rojas ha informado un pago para la cuota Noviembre 2026 del inmueble Belgrano 1250, 4°B" (no leído, enlace "Revisar pago"); "El contrato del inmueble Mitre 845 con el inquilino Joaquín Medina vencerá el 15/12/2026" (no leído, "Ver contrato"); "El contrato del inmueble Belgrano 1250, 4°B con el inquilino Camila Rojas vencerá el 31/12/2026" (leído, "Ver contrato").

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Dashboard.
```

### COM-02c Notificaciones: Inquilino (HU-22, HU-23)

```text
Diseñá la pantalla «Notificaciones: Inquilino» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Página «Notificaciones» con el botón "Marcar todas como leídas", pestañas "Todas" y "No leídas (2)". Arriba de la lista, una tarjeta de alerta ámbar destacada: "Tu cuota de alquiler de Noviembre 2026 vence el día 10/11/2026. Evita recargos por mora" con el botón "Informar Pago". Lista agrupada en "Hoy" y "Anteriores", cada ítem con ícono, mensaje, tiempo relativo, punto azul si no está leído y enlace de acción: "Tu pago fue rechazado. Motivo: Comprobante ilegible. Por favor, reenvía el comprobante" (no leído, "Reenviar comprobante"); "Tu pago para el período Septiembre 2026 ha sido aprobado exitosamente" (leído, "Ver mis pagos").

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Joaquín Medina, Inquilino) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Estado de cuenta, Mis pagos, Mi perfil. Activo: Estado de cuenta.
```

### COM-02d Notificaciones: Propietario (HU-24)

```text
Diseñá la pantalla «Notificaciones: Propietario» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Página «Notificaciones» con el botón "Marcar todas como leídas", pestañas "Todas" y "No leídas (1)". Lista con un único ítem, con ícono de calendario, tiempo relativo, punto azul (no leído) y enlace "Ver contrato": "El contrato del inmueble Belgrano 1250, 4°B con el inquilino Camila Rojas vencerá el 31/12/2026". Debajo, una nota discreta: "Te avisamos cuando un contrato esté a 60 días o menos de vencer".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Dashboard.
```

## Administrador / Empleado inmobiliario

### ADM-01 Dashboard ejecutivo (HU-25)

```text
Diseñá la pantalla «Dashboard ejecutivo» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Panel de control» con selector de período (Noviembre 2026) y accesos rápidos "Emitir cuotas", "Nuevo contrato" y "Generar liquidaciones". Cuatro tarjetas de KPI: Ocupación 67% (4 de 6 inmuebles alquilados, barra de progreso); Recaudado en Noviembre $ 780.000; Morosidad 50% (2 de 4 inquilinos) con "$ 1.569.230 vencido" en rojo; Saldo pendiente de liquidar a propietarios $ 2.530.800. Gráfico de barras simple «Recaudación de los últimos 6 meses»: Jun $ 1.850.000, Jul $ 1.850.000, Ago $ 2.240.000, Sep $ 2.240.000, Oct $ 1.200.000, Nov $ 780.000 (en curso). Tarjeta «Alertas»: "1 pago pendiente de verificación"; "Contrato Mitre 845 vence el 15/12/2026 (39 días)"; "Contrato Belgrano 1250, 4°B vence el 31/12/2026 (55 días)". Tabla corta «Inquilinos en mora»: Joaquín Medina $ 737.750 y Tomás Aguirre $ 831.480.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Dashboard.
```

### ADM-02 Usuarios: listado (HU-04, HU-05)

```text
Diseñá la pantalla «Usuarios: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Usuarios», subtítulo "Padrón de clientes y personal" y botón primario "Nuevo usuario". Filtros: búsqueda por nombre, DNI o email; select de rol (Todos, Administrador, Propietario, Inquilino); casilla "Incluir usuarios dados de baja"; botones "Filtrar" y "Limpiar". Tabla paginada: avatar con iniciales + nombre, DNI (inventados, 12.345.678), correo, teléfono, badge de rol, fecha de alta y acciones (editar, dar de baja). 10 filas: Laura Benítez; Ricardo Sosa, Marta Quiroga, Hernán Paz (propietarios); Camila Rojas, Joaquín Medina, Valentina Funes, Tomás Aguirre, Lucía Paredes, Elena Arias (inquilinos). Pie con "Mostrando 1–10 de 10" y paginación.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Usuarios.
```

### ADM-03a Usuario: nuevo (HU-04)

```text
Diseñá la pantalla «Usuario: nuevo» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Formulario en página propia (no modal). Breadcrumb «Usuarios / Nuevo usuario». Tarjeta «Datos personales» en dos columnas: Nombre*, Apellido*, DNI*, Correo electrónico*, Teléfono*, Dirección. Tarjeta «Acceso y permisos»: Rol* con tres tarjetas de selección (Administrador, Propietario, Inquilino, cada una con una línea de descripción) y Contraseña* (mínimo 8 caracteres, con ojo). Acciones al pie: "Guardar usuario" (primario) y "Cancelar" (secundario).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Usuarios.
```

### ADM-03b Usuario: editar (HU-04)

```text
Diseñá la pantalla «Usuario: editar» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Formulario en página propia con los datos de Joaquín Medina cargados. Breadcrumb «Usuarios / Editar usuario». Tarjeta «Datos personales» en dos columnas (Nombre*, Apellido*, DNI*, Correo*, Teléfono*, Dirección) y tarjeta «Acceso y permisos» con Rol* (tres tarjetas de selección, Inquilino elegida) y Contraseña en blanco con la ayuda "Dejá en blanco para no modificar la contraseña actual". A la derecha, un bloque lateral con "Creado el 12/03/2025" y "Última modificación 20/10/2026". Acciones: "Guardar cambios" (primario) y "Cancelar".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Usuarios.
```

### ADM-04 Inmuebles: listado (HU-08, HU-09)

```text
Diseñá la pantalla «Inmuebles: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Inmuebles» con botón primario "Nuevo inmueble". Filtros: búsqueda por dirección; selects de propietario, tipo y estado; rango de precio (mínimo y máximo); "Filtrar" y "Limpiar". Tabla: dirección, tipo, ambientes, precio base mensual, propietario, badge de estado (Disponible verde, Alquilado azul, En Mantenimiento ámbar) y acciones (ver, editar, dar de baja). Filas: Belgrano 1250, 4°B · Departamento · 3 · $ 420.000 · Ricardo Sosa · Alquilado; Mitre 845 · Casa · 4 · $ 650.000 · Marta Quiroga · Alquilado; San Martín 322, Local 3 · Local comercial · 1 · $ 780.000 · Ricardo Sosa · Alquilado; Moreno 560, PB · Departamento · 2 · $ 390.000 · Hernán Paz · Alquilado; Roca 1020, Piso 2 · Oficina · 2 · $ 510.000 · Hernán Paz · Disponible; Sarmiento 77, 1°A · Departamento · 2 · $ 350.000 · Marta Quiroga · En Mantenimiento.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Inmuebles.
```

### ADM-05 Inmueble: nuevo (HU-07)

```text
Diseñá la pantalla «Inmueble: nuevo» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Formulario en página propia (no modal). Breadcrumb «Inmuebles / Nuevo inmueble». Sección «Datos del inmueble»: Propietario* (select con búsqueda, solo propietarios), Dirección exacta*, Tipo* (Departamento, Casa, Local comercial, Terreno, Oficina), Cantidad de ambientes* (entero positivo) y Precio base de alquiler sugerido* (prefijo "$", mayor a cero). Sección «Descripción»: textarea grande "Estado y comodidades" con contador de caracteres. A la derecha, un bloque informativo "Estado inicial: Disponible" (badge verde) con la ayuda "Se asigna automáticamente al guardar". Acciones: "Guardar Inmueble" (primario) y "Cancelar".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Inmuebles.
```

### ADM-06 Contratos: listado (HU-11)

```text
Diseñá la pantalla «Contratos: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Contratos» con botón primario "Nuevo contrato". Filtros: búsqueda, select de estado (Vigente, Finalizado, Rescindido), "Filtrar" y "Limpiar". Tabla con acción "Ver"; columnas N° · inmueble · inquilino · propietario · vigencia · monto · comisión · estado. Filas: C-001 · Belgrano 1250 · Camila Rojas · R. Sosa · 01/01/2025–31/12/2026 · $ 420.000 · 10% · Vigente + chip ámbar "Vence en 55 días"; C-002 · Mitre 845 · Joaquín Medina · M. Quiroga · 16/12/2024–15/12/2026 · $ 650.000 · 10% · Vigente + chip "Vence en 39 días"; C-003 · San Martín 322 · Valentina Funes · R. Sosa · 01/06/2026–31/05/2028 · $ 780.000 · 8% · Vigente; C-004 · Moreno 560 · Tomás Aguirre · H. Paz · 01/08/2026–31/07/2028 · $ 390.000 · 10% · Vigente; C-005 · Roca 1020 · Lucía Paredes · H. Paz · 01/03/2025–28/02/2027 · $ 510.000 · 10% · Rescindido; C-006 · Sarmiento 77 · Elena Arias · M. Quiroga · 01/02/2024–31/01/2026 · $ 350.000 · 10% · Finalizado.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Contratos.
```

### ADM-07 Contrato: nuevo (HU-10)

```text
Diseñá la pantalla «Contrato: nuevo» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Pantalla clave del negocio: clara y confiable. Dos columnas: formulario (2/3) en tres tarjetas numeradas y panel fijo «Resumen del contrato» (1/3). 1 «Partes»: Inmueble* (select solo con inmuebles Disponibles, ej. Roca 1020, Piso 2 · $ 510.000) e Inquilino* (select con búsqueda). 2 «Plazo»: Fecha de inicio* y Fecha de finalización*, con la duración calculada ("24 meses"). 3 «Condiciones económicas»: Monto mensual pactado*, % de comisión inmobiliaria*, Día límite de vencimiento mensual* (1 a 28) y % de recargo diario por mora*. Resumen en vivo: cuota mensual $ 510.000, comisión 10% = $ 51.000, neto mensual al propietario $ 459.000, vencimiento día 10, mora 0,5% = $ 2.550 por día. Acciones: "Generar Contrato" (primario) y "Cancelar".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Contratos.
```

### ADM-08 Contrato: ficha (HU-10, HU-12, HU-24)

```text
Diseñá la pantalla «Contrato: ficha» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Contrato C-002 · Mitre 845» con badge "Vigente" y chip ámbar "Vence en 39 días"; a la derecha, "Finalizar contrato" (secundario) y "Rescindir" (peligro). Tarjeta de alerta ámbar: "Este contrato vence el 15/12/2026. Gestioná la prórroga, la renovación o la rescisión". Tarjetas: «Partes» (inmueble Mitre 845, propietaria Marta Quiroga, inquilino Joaquín Medina, 2920 45-6789); «Condiciones» ($ 650.000, comisión 10%, vencimiento día 10, mora 0,5% diario); «Vigencia» (barra de progreso del 16/12/2024 al 15/12/2026 con hoy marcado); «Cuotas del contrato», tabla con período, vencimiento, base, mora, total y badge: Jul, Ago y Sep Pagadas ($ 650.000); Oct Vencida (mora $ 87.750, total $ 737.750); Nov Pendiente ($ 650.000).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Contratos.
```

### ADM-09a Emitir cuotas: período y vista previa (HU-13)

```text
Diseñá la pantalla «Emitir cuotas: período y vista previa» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Operación mensual crítica: debe dar seguridad. Bloque «Período»: selector de mes y año (Diciembre 2026) y botón "Ver vista previa". Bloque «Vista previa»: tabla de contratos vigentes que cubren ese mes, con N°, inquilino, inmueble, vencimiento, monto base y estado "Se emitirá" (verde): C-001 Camila Rojas · Belgrano 1250 · 10/12/2026 · $ 420.000; C-002 Joaquín Medina · Mitre 845 · 10/12/2026 · $ 650.000; C-003 Valentina Funes · San Martín 322 · 15/12/2026 · $ 780.000; C-004 Tomás Aguirre · Moreno 560 · 05/12/2026 · $ 390.000. Pie de tabla: "4 cuotas a emitir · $ 2.240.000". Botón primario "Emitir cuotas".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Cuotas > Emitir cuotas.
```

### ADM-09b Emitir cuotas: informe de resultado (HU-13)

```text
Diseñá la pantalla «Emitir cuotas: informe de resultado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Resultado de emitir las cuotas de Diciembre 2026. Tarjeta de éxito con tilde grande: "4 cuotas emitidas", "0 omitidas por duplicado" y "Monto global a recaudar: $ 2.240.000". Debajo, tabla de las 4 cuotas emitidas: N° de cuota, inquilino, inmueble, período Diciembre 2026, vencimiento y monto (Camila Rojas $ 420.000, vence 10/12/2026; Joaquín Medina $ 650.000, 10/12/2026; Valentina Funes $ 780.000, 15/12/2026; Tomás Aguirre $ 390.000, 05/12/2026). Botones "Ver cobranzas" (primario) y "Emitir otro período" (secundario).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Cuotas > Emitir cuotas.
```

### ADM-10 Cobranzas (cuotas y mora) (HU-14)

```text
Diseñá la pantalla «Cobranzas (cuotas y mora)» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Cobranzas». Mini-tarjetas: A cobrar $ 1.070.000, Cobrado $ 1.980.000 y Vencido $ 1.569.230 (rojo). Filtros: período, estado (Pendiente, Vencida, En Revisión, Pagada) y búsqueda. Tabla con acción "Ver detalle"; columnas inquilino · inmueble · período · vencimiento · base · mora (rojo) · total · estado. Filas: Camila · Belgrano 1250 · Nov 2026 · 10/11 · $ 420.000 · — · $ 420.000 · En Revisión; Joaquín · Mitre 845 · Nov · 10/11 · $ 650.000 · — · $ 650.000 · Pendiente; Valentina · San Martín 322 · Nov · 15/11 · $ 780.000 · — · $ 780.000 · Pagada; Tomás · Moreno 560 · Nov · 05/11 · $ 390.000 · 1 día $ 1.560 · $ 391.560 · Vencida; Camila · Oct · 10/10 · $ 420.000 · — · $ 420.000 · Pagada; Joaquín · Oct · 10/10 · $ 650.000 · 27 días $ 87.750 · $ 737.750 · Vencida; Valentina · Oct · 15/10 · $ 780.000 · — · $ 780.000 · Pagada; Tomás · Oct · 05/10 · $ 390.000 · 32 días $ 49.920 · $ 439.920 · Vencida.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Cuotas > Cobranzas.
```

### ADM-11 Pagos: bandeja (HU-17, HU-18)

```text
Diseñá la pantalla «Pagos: bandeja» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Pagos informados». Pestañas con contador: "Pendientes de validación (1)", "Aprobados", "Rechazados" y "Todos" (activa). Filtros: búsqueda por inquilino, rango de fechas y medio de pago. Tabla: fecha de envío, inquilino, inmueble, período, monto, medio, N° de operación, badge de estado, ícono para ver el comprobante y acción. Filas: 06/11/2026 · Camila Rojas · Belgrano 1250 · Nov 2026 · $ 420.000 · Transferencia · 0098123 · Pendiente de Validación (botón "Revisar"); 05/11/2026 · Valentina Funes · San Martín 322 · Nov 2026 · $ 780.000 · Transferencia · 0087456 · Aprobado; 12/10/2026 · Joaquín Medina · Mitre 845 · Oct 2026 · $ 650.000 · Transferencia · Rechazado (motivo "Comprobante ilegible" debajo); 08/10/2026 · Camila Rojas · Belgrano 1250 · Oct 2026 · $ 420.000 · Transferencia · Aprobado.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Pagos.
```

### ADM-12 Pago: revisión (HU-17)

```text
Diseñá la pantalla «Pago: revisión» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Pago de Camila Rojas · Noviembre 2026» con badge "Pendiente de Validación". Dos columnas. Izquierda: visor del comprobante (vista previa de una transferencia bancaria) con zoom, "Abrir en una pestaña nueva" y "Descargar"; debajo, nombre del archivo y tamaño. Derecha: tarjeta «Datos del pago»: inmueble Belgrano 1250, 4°B, cuota Noviembre 2026, vencimiento 10/11/2026, monto exigible $ 420.000, monto transferido $ 420.000 con indicador verde "Coincide", fecha de la operación 06/11/2026, medio Transferencia bancaria, N° 0098123, informado el 06/11/2026 14:32. Debajo, sección «Decisión»: botón verde "Aprobar Pago" y un textarea "Motivo del rechazo* (obligatorio si rechazás)" con el botón rojo en contorno "Rechazar Pago".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Pagos.
```

### ADM-13 Liquidaciones: generar (HU-19)

```text
Diseñá la pantalla «Liquidaciones: generar» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Generar liquidaciones», subtítulo "Cuotas cobradas pendientes de liquidar". Aviso informativo: "Las cuotas vencidas, en revisión o con pago rechazado no se pueden liquidar hasta que el pago sea aprobado". Tabla con casillas (y "Seleccionar todas"): propietario, inmueble, período, bruto cobrado, % de comisión, comisión en $ y neto. Filas (seleccionadas): Ricardo Sosa · Belgrano 1250 · Oct 2026 · $ 420.000 · 10% · $ 42.000 · $ 378.000; Ricardo Sosa · San Martín 322 · Oct 2026 · $ 780.000 · 8% · $ 62.400 · $ 717.600; Ricardo Sosa · San Martín 322 · Nov 2026 · $ 780.000 · 8% · $ 62.400 · $ 717.600. Fórmula visible: "Neto = Bruto − (Bruto × % comisión)". Barra fija al pie: "3 seleccionadas · Bruto $ 1.980.000 · Comisión $ 166.800 · Neto $ 1.813.200" y botón primario "Generar liquidaciones".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Liquidaciones.
```

### ADM-14a Liquidaciones: listado (HU-20)

```text
Diseñá la pantalla «Liquidaciones: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Liquidaciones» con el botón "Generar liquidaciones". Filtros: propietario, estado (Pendiente de Transferencia, Transferido) y período. Tabla: N°, propietario, inmueble, período, bruto, comisión (% y $), neto, badge de estado, fecha de acreditación y acción "Ver". Filas: LQ-001 · Marta Quiroga · Mitre 845 · Sep 2026 · $ 650.000 · 10% $ 65.000 · $ 585.000 · Transferido · 12/10/2026; LQ-002 · Ricardo Sosa · Belgrano 1250 · Sep 2026 · $ 420.000 · 10% $ 42.000 · $ 378.000 · Transferido · 12/10/2026; LQ-003 · Ricardo Sosa · San Martín 322 · Sep 2026 · $ 780.000 · 8% $ 62.400 · $ 717.600 · Pendiente de Transferencia · —; LQ-004 · Hernán Paz · Moreno 560 · Sep 2026 · $ 390.000 · 10% $ 39.000 · $ 351.000 · Transferido · 12/10/2026.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Liquidaciones.
```

### ADM-14b Liquidación: detalle y transferencia (HU-20)

```text
Diseñá la pantalla «Liquidación: detalle y transferencia» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Liquidación LQ-003» con badge "Pendiente de Transferencia". Tarjeta de desglose: "Bruto $ 780.000 − Comisión 8% $ 62.400 = Neto a transferir $ 717.600", con el neto destacado. Tarjeta de datos: propietario Ricardo Sosa, inmueble San Martín 322, Local 3, inquilino Valentina Funes, contrato C-003, período Septiembre 2026. Tarjeta «Registrar transferencia»: Fecha de transferencia* (no puede ser futura) y N° de transacción o referencia bancaria*, con el botón primario "Confirmar transferencia". Enlace "Volver al listado".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Liquidaciones.
```

### ADM-15a Reporte de morosidad (HU-27)

```text
Diseñá la pantalla «Reporte de morosidad» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Filtros: rango de fechas de vencimiento (desde y hasta), días de atraso (Cualquiera, +15, +30, +60) y "Aplicar filtros"; a la derecha, botón "Imprimir / Exportar". Tres tarjetas: Total adeudado $ 1.569.230, Inquilinos en mora 2, Cuotas impagas 3. Tabla: inquilino, teléfono (enlace), inmueble, cuotas impagas, base, recargos, total adeudado, días de atraso máximo. Filas: Joaquín Medina · 2920 45-6789 · Mitre 845 · 1 · $ 650.000 · $ 87.750 · $ 737.750 · 27 días; Tomás Aguirre · 2920 51-2233 · Moreno 560, PB · 2 · $ 780.000 · $ 51.480 · $ 831.480 · 32 días. Fila de totales: $ 1.430.000 · $ 139.230 · $ 1.569.230.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Laura Benítez, Administradora) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes. Activo: Reportes.
```

### ADM-15b Reporte de morosidad: impresión (HU-27)

```text
Diseñá la pantalla «Reporte de morosidad: impresión» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Diseñá la vista de impresión del reporte de morosidad, sin sidebar, barra superior, filtros ni botones de la app. Contenido: logo, título "Reporte de morosidad", "Fecha de emisión: 06/11/2026", "Filtros aplicados: ninguno", tabla (inquilino, teléfono, inmueble, cuotas impagas, base, recargos, total adeudado, días de atraso máximo) con las filas Joaquín Medina · 2920 45-6789 · Mitre 845 · 1 · $ 650.000 · $ 87.750 · $ 737.750 · 27 días y Tomás Aguirre · 2920 51-2233 · Moreno 560, PB · 2 · $ 780.000 · $ 51.480 · $ 831.480 · 32 días, y la fila de totales $ 1.430.000 · $ 139.230 · $ 1.569.230. Pie: "Generado por Laura Benítez · InmoGestión 2026".

ESTILO: documento A4 vertical imprimible, fondo blanco, sin sombras ni degradados, legible en blanco y negro, fuente Inter, español rioplatense. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
```

## Inquilino

### INQ-01 Estado de cuenta (HU-15, HU-23)

```text
Diseñá la pantalla «Estado de cuenta» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

La pantalla más usada del inquilino: debe dejar clarísimo qué debe y cómo pagarlo. Encabezado «Estado de cuenta» con "Mitre 845 · Contrato C-002 vigente hasta el 15/12/2026". Tarjeta de alerta ámbar: "Tu cuota de alquiler de Noviembre 2026 vence el día 10/11/2026. Evita recargos por mora". Resumen «Total a pagar hoy»: $ 1.387.750. Bloques: 1) «Cuotas pendientes del mes»: Noviembre 2026, base $ 650.000, fecha límite 10/11/2026, "faltan 4 días", botón primario "Informar Pago". 2) «Cuotas vencidas en mora» (acentos en rojo): Octubre 2026, venció el 10/10/2026, "27 días de mora", base $ 650.000 + recargo $ 87.750 (0,5% diario) = total $ 737.750, botón "Informar Pago". 3) «Historial de cuotas pagadas»: tabla con período, fecha de pago, medio, monto y enlace "Ver comprobante": Septiembre (09/09/2026, Transferencia, $ 650.000), Agosto (08/08/2026, Transferencia, $ 650.000), Julio (09/07/2026, Depósito, $ 650.000).

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Joaquín Medina, Inquilino) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Estado de cuenta, Mis pagos, Mi perfil. Activo: Estado de cuenta.
```

### INQ-02 Informar pago (HU-16)

```text
Diseñá la pantalla «Informar pago» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Formulario para la cuota Octubre 2026 (vencida). Dos columnas. Izquierda: "Cuota a abonar" (bloqueado con candado: "Octubre 2026 · Mitre 845"); "Monto transferido*" ($, ayuda "Monto exigible hoy: $ 737.750"); "Fecha de la operación*" (no puede ser futura); "Medio de pago*" con tres tarjetas de selección (Transferencia bancaria, Depósito, Pasarela digital); "Número de comprobante o transacción*"; "Comprobante*" con zona de carga (arrastrar o elegir, PDF, JPG o PNG, máximo 5 MB). Botones "Enviar Pago" (primario) y "Cancelar". Derecha: tarjeta «Resumen de la cuota»: período, vencimiento 10/10/2026, base $ 650.000, mora 27 días $ 87.750 y total exigible $ 737.750 en grande.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Joaquín Medina, Inquilino) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Estado de cuenta, Mis pagos, Mi perfil. Activo: Estado de cuenta.
```

### INQ-03 Mis pagos (HU-18)

```text
Diseñá la pantalla «Mis pagos» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado «Mis pagos», subtítulo "Historial de pagos informados y sus comprobantes". Filtro por estado (Todos, Aprobado, Pendiente de Validación, Rechazado). Tabla del más reciente al más antiguo: fecha de envío, período abonado, monto, medio de pago, badge de estado y botón "Ver comprobante". Filas: 12/10/2026 · Octubre 2026 · $ 650.000 · Transferencia bancaria · Rechazado; 09/09/2026 · Septiembre 2026 · $ 650.000 · Transferencia bancaria · Aprobado; 08/08/2026 · Agosto 2026 · $ 650.000 · Transferencia bancaria · Aprobado; 09/07/2026 · Julio 2026 · $ 650.000 · Depósito · Aprobado. Bajo la fila Rechazado, un recuadro "Motivo: Comprobante ilegible" y el botón "Reenviar comprobante".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Joaquín Medina, Inquilino) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Estado de cuenta, Mis pagos, Mi perfil. Activo: Mis pagos.
```

## Propietario

### PRO-01 Dashboard del propietario (HU-26, HU-24)

```text
Diseñá la pantalla «Dashboard del propietario» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Encabezado "Hola, Ricardo" y subtítulo "Resumen de tus propiedades". Cuatro tarjetas de KPI: Inmuebles registrados 2; Alquilados / Disponibles 2 / 0 (barra dividida); Acreditado en los últimos 12 meses $ 6.310.800; Pendiente de transferir $ 717.600. Tarjeta de alerta ámbar «Contratos próximos a vencer»: "El contrato del inmueble Belgrano 1250, 4°B con el inquilino Camila Rojas vencerá el 31/12/2026 (55 días)" con enlace "Ver contrato". Lista «Mis inmuebles»: Belgrano 1250, 4°B (Alquilado, Camila Rojas) y San Martín 322, Local 3 (Alquilado, Valentina Funes). Tabla corta «Últimas liquidaciones» (3 filas, ej. Sep 2026 Belgrano 1250 $ 378.000 Transferido) con "Ver todas".

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Dashboard.
```

### PRO-02 Mis inmuebles (HU-08)

```text
Diseñá la pantalla «Mis inmuebles» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Solo consulta, sin editar ni dar de baja. Encabezado «Mis inmuebles» y filtro por estado (Todos, Alquilado, Disponible, En Mantenimiento). Grilla de tarjetas en 2 columnas: foto, dirección, tipo y ambientes, precio base, badge de estado y, si está alquilado, el inquilino con su contrato y vigencia y el enlace "Ver contrato". Tarjeta 1: Belgrano 1250, 4°B · Departamento · 3 amb. · $ 420.000 · Alquilado · Camila Rojas · contrato C-001 hasta el 31/12/2026. Tarjeta 2: San Martín 322, Local 3 · Local comercial · 1 amb. · $ 780.000 · Alquilado · Valentina Funes · contrato C-003 hasta el 31/05/2028.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Mis inmuebles.
```

### PRO-03 Mis contratos (HU-11)

```text
Diseñá la pantalla «Mis contratos» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Solo consulta. Encabezado «Mis contratos» y filtro por estado (Todos, Vigente, Finalizado, Rescindido). Tabla: N° de contrato, inmueble, inquilino con teléfono y correo, monto pactado, comisión acordada, vigencia, badge de estado y chip de vencimiento. Filas: C-001 · Belgrano 1250, 4°B · Camila Rojas (2920 41-1122, camila.rojas@correo.com) · $ 420.000 · 10% · 01/01/2025 – 31/12/2026 · Vigente · chip ámbar "Vence en 55 días"; C-003 · San Martín 322, Local 3 · Valentina Funes (2920 47-8899, valentina.funes@correo.com) · $ 780.000 · 8% · 01/06/2026 – 31/05/2028 · Vigente.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Mis contratos.
```

### PRO-04a Liquidaciones: listado (HU-21)

```text
Diseñá la pantalla «Liquidaciones: listado» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Debe dejar claro cuánto se cobró, cuánto se descontó y cuánto le llegó. Encabezado «Liquidaciones» y dos tarjetas: Acreditado en los últimos 12 meses $ 6.310.800 y Pendiente de transferir $ 717.600. Filtros: año, inmueble y estado. Tabla: período, inmueble, bruto cobrado, comisión retenida (% e importe), neto, badge de estado, fecha de acreditación, N° de operación y acciones "Ver recibo" e "Imprimir". Filas: Sep 2026 · Belgrano 1250 · $ 420.000 · 10% $ 42.000 · $ 378.000 · Transferido · 12/10/2026 · 0051878; Sep 2026 · San Martín 322 · $ 780.000 · 8% $ 62.400 · $ 717.600 · Pendiente de Transferencia; Ago 2026 · Belgrano 1250 · $ 420.000 · 10% $ 42.000 · $ 378.000 · Transferido · 11/09/2026 · 0049011; Ago 2026 · San Martín 322 · $ 780.000 · 8% $ 62.400 · $ 717.600 · Transferido · 11/09/2026 · 0049012.

ESTILO: web desktop 1440px, admin template limpio y sobrio, todo en español rioplatense. Fuente Inter. Primario índigo #4F46E5 (botones, ítem activo, foco); fondo degradado #F0F4F8 a #D9E2EC; tarjetas blancas con radio 12px, sombra suave y borde #E5E7EB; texto #1F2937 y secundario #6B7280. Éxito #10B981, peligro #EF4444, advertencia #F59E0B, info #2563EB. Badges con ícono + texto. Íconos de línea. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
LAYOUT: sidebar blanco de 260px con logo (casa blanca en cuadrado índigo + "InmoGestión"), ítems con ícono (activo: fondo #E0E7FF, texto #4338CA) y abajo el usuario (Ricardo Sosa, Propietario) con "Cerrar Sesión". Barra superior con breadcrumb, campana con contador rojo y avatar. Menú: Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil. Activo: Liquidaciones.
```

### PRO-04b Recibo de liquidación (imprimible) (HU-21)

```text
Diseñá la pantalla «Recibo de liquidación (imprimible)» de InmoGestión 2026, una plataforma de gestión inmobiliaria y de alquileres.

Diseñá el comprobante imprimible de la liquidación LQ-002. Contenido: logo, título "Comprobante de liquidación LQ-002", propietario Ricardo Sosa, inmueble Belgrano 1250, 4°B, inquilino Camila Rojas, período Septiembre 2026, desglose "Monto bruto $ 420.000 − Comisión (10%) $ 42.000 = Neto transferido $ 378.000" con el neto destacado, fecha de acreditación 12/10/2026, referencia bancaria 0051878 y badge "Transferido". Botones "Imprimir" y "Descargar PDF" arriba, fuera del área imprimible.

ESTILO: documento A4 vertical imprimible, fondo blanco, sin sombras ni degradados, legible en blanco y negro, fuente Inter, español rioplatense. Hoy es 06/11/2026; fechas dd/mm/aaaa; montos como $ 420.000.
```

---

## Checklist para revisar lo que devuelve Stitch

- [ ] **Estilo:** ¿usa Inter, el índigo `#4F46E5` y las tarjetas con radio 12px en todas las pantallas? ¿Coincide con `public/css/styles.css`?
- [ ] **Consistencia:** ¿el sidebar, la barra superior y el menú del rol son iguales entre pantallas?
- [ ] **Estados:** ¿todo badge tiene ícono + texto + color?
- [ ] **HU:** ¿están los campos y mensajes de los criterios de aceptación? (por ejemplo "Credenciales inválidas", "No es posible dar de baja un inmueble con contrato de locación vigente", "El archivo debe ser un comprobante válido (PDF, JPG, PNG)").
- [ ] **Datos:** ¿los montos coinciden con los del prompt? (mora de Joaquín = $ 87.750, total $ 737.750; neto de Valentina = $ 717.600).
- [ ] **Formularios:** ¿obligatorios con asterisco, ayudas debajo del campo y alta/edición en página propia?
- [ ] **Accesibilidad:** ¿contraste suficiente, foco visible, nada informado solo por color?
- [ ] **Implementable:** ¿se puede armar con HTML semántico + CSS propio (flex/grid) sin librerías de componentes ni de gráficos?
- [ ] **Ortografía:** acentos, "¿" y "¡", y formato de moneda y fechas.
