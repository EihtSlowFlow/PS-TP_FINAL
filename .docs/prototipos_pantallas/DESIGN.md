---
version: alpha
name: InmoGestión 2026
description: Identidad visual «madera y melamina» para la gestión inmobiliaria y de alquileres de Nico Tech. La fuente de verdad de los valores es public/css/tokens.css; este documento los describe y debe quedar alineado con ese archivo.
colors:
  primary: "#7A4B2A"
  primary-hover: "#5F3A20"
  primary-soft: "#F0E2CF"
  on-primary: "#FFFFFF"
  accent: "#D9A566"
  on-accent: "#2B1D14"
  sidebar-bg: "#3B2A20"
  sidebar-bg-raised: "#4A372B"
  sidebar-edge: "#241811"
  sidebar-line: "#5A4535"
  sidebar-text: "#F3E9DC"
  sidebar-text-muted: "#D2C1AE"
  bg-app: "#F5EFE6"
  surface: "#FFFDF9"
  surface-muted: "#F8F1E7"
  row-hover: "#F4ECDF"
  border: "#E4D9C9"
  border-input: "#8C7B6B"
  edge: "#D3C3AE"
  heading: "#2B1D14"
  text: "#3A2E26"
  text-muted: "#6B5E52"
  success: "#10B981"
  success-soft: "#ECFDF5"
  success-text: "#065F46"
  success-hover: "#064E3B"
  danger: "#EF4444"
  danger-soft: "#FEF2F2"
  danger-text: "#B91C1C"
  danger-hover: "#991B1B"
  danger-border: "#FECACA"
  warning: "#F59E0B"
  warning-soft: "#FFFBEB"
  warning-text: "#92400E"
  warning-hover: "#D97706"
  info: "#2563EB"
  info-soft: "#DBEAFE"
  info-text: "#1E40AF"
  info-border: "#BFDBFE"
typography:
  page-title:
    fontFamily: Fraunces
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1.25
  brand:
    fontFamily: Fraunces
    fontSize: 19px
    fontWeight: 600
    lineHeight: 1.2
  heading:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: 600
    lineHeight: 1.3
  body:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: 600
    lineHeight: 1.4
  table-header:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: 0.04em
rounded:
  sm: 8px
  md: 12px
  lg: 16px
  full: 999px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 0 20px
    height: 42px
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.on-primary}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 0 20px
    height: 42px
  button-danger:
    backgroundColor: "{colors.danger-text}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 0 20px
    height: 42px
  button-success:
    backgroundColor: "{colors.success-text}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 0 20px
    height: 42px
  button-warning:
    backgroundColor: "{colors.warning}"
    textColor: "{colors.heading}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 0 20px
    height: 42px
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: 0 14px
    height: 42px
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: 28px 32px
  table-header:
    backgroundColor: "{colors.surface-muted}"
    textColor: "{colors.text-muted}"
    typography: "{typography.table-header}"
    padding: 14px 18px
  sidebar:
    backgroundColor: "{colors.sidebar-bg}"
    textColor: "{colors.sidebar-text}"
    width: 260px
  sidebar-item-active:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 10px 14px
  badge-success:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 3px 10px
  badge-danger:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 3px 10px
  badge-warning:
    backgroundColor: "{colors.warning-soft}"
    textColor: "{colors.warning-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 3px 10px
  badge-info:
    backgroundColor: "{colors.info-soft}"
    textColor: "{colors.info-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 3px 10px
---

## Overview

InmoGestión 2026 es una plataforma web de gestión inmobiliaria y de alquileres. Digitaliza el ciclo completo: catálogo de inmuebles, contrato, emisión mensual de cuotas, pago informado por el inquilino con comprobante, verificación del administrador y liquidación al propietario con descuento de la comisión.

Hay cuatro roles (Administrador, Empleado, Propietario e Inquilino) con menús distintos y pantallas públicas (login, registro, catálogo). Los prototipos del Administrador sirven también para el Empleado hasta que se definan sus diferencias de permisos. Es un **admin template** limpio y sobrio, no una landing de marketing, pero con identidad propia: **madera y melamina**, como un mueble de melamina en tonos nogal y beige. Todo el texto de la interfaz va en español rioplatense.

**Cómo se usa la temática.** De los cuatro diseños de melamina (color sólido, maderada, imitación de materiales y patrones) el sistema usa dos:

- **Color sólido** para todo lo que se lee: tarjetas, tablas, formularios y fondo de la app. Superficies lisas, sin textura, para no perjudicar la lectura.
- **Maderada** solo como detalle decorativo y sutil: la veta del sidebar y del panel de marca del login. Nunca va detrás de contenido.

Imitación de materiales y patrones no se usan.

## Fuente de verdad e implementación

- **Valores:** `public/css/tokens.css` es el **único** archivo donde se declara un color, una fuente, un radio, una sombra o la textura. `panel.css` y `auth.css` solo usan `var(--token)`. Si este documento y `tokens.css` difieren, manda el CSS y hay que corregir este documento.
- **Guía viva:** [`guia_de_estilos.html`](guia_de_estilos.html) muestra la paleta y cada componente con los CSS reales. Se abre directo en el navegador y cambia sola si cambian los CSS.
- **Orden de carga:** `tokens.css` → `panel.css` (panel y catálogo público) o `auth.css` (login, registro y recuperación).
- **Pantalla nueva:** extender `layouts/panel`, reutilizar los nombres de la guía y no escribir ningún hex, rgb ni degradado. Si falta un color, se agrega primero a `tokens.css` y a este documento.

## Colors

- **Primario, nogal (`#7A4B2A`):** botones primarios, enlaces y foco. Blanco sobre nogal: 7,32:1. Hover `#5F3A20` (9,94:1). Su variante suave, roble claro `#F0E2CF` con texto `#5F3A20` (7,80:1), marca chips y rellenos suaves.
- **Acento, miel (`#D9A566`):** ítem activo del sidebar y pequeños detalles de marca. Texto `#2B1D14` sobre miel: 7,39:1. No se usa como color de acción en el contenido.
- **Sidebar y panel de marca, café (`#3B2A20`):** texto `#F3E9DC` (11,38:1), secundario `#D2C1AE`, tarjeta de usuario `#4A372B`, divisores `#5A4535` y borde derecho más oscuro `#241811`.
- **Fondo de la app, lino (`#F5EFE6`):** plano, sin degradado. Texto `#3A2E26` sobre lino: 11,49:1.
- **Superficies, melamina blanca (`#FFFDF9`):** tarjetas, tablas, formularios y topbar (texto 12,93:1). `#F8F1E7` para cabeceras de tabla, campos de solo lectura y cajas anidadas; `#F4ECDF` para la fila con hover.
- **Bordes:** `#E4D9C9` para separadores decorativos y `#8C7B6B` para campos y botones secundarios (4,01:1; el mínimo para controles es 3:1). **Canto:** `#D3C3AE`, un borde inferior de 2 px en tarjetas, KPI, topbar y modales, como el canto de una placa de melamina.
- **Texto:** `#3A2E26` para el contenido, `#6B5E52` para lo secundario (6,17:1 sobre la superficie y 5,49:1 sobre el lino) y `#2B1D14` para títulos.
- **Semánticos (sin cambios):** éxito verde, peligro rojo, advertencia ámbar e informativo azul, cada uno con su fondo suave y su texto oscuro (de 5,9:1 a 7,3:1). Los **botones sólidos** de peligro y éxito usan el tono `-text` (`#B91C1C`, `#065F46`) para que el texto blanco cumpla 4,5:1; el de advertencia lleva texto oscuro.
- **Estados de negocio** (conservan su color para reconocerse entre pantallas):
  - Inmueble: Disponible (verde), Alquilado (azul), En Mantenimiento (ámbar).
  - Contrato: Vigente (verde), Finalizado (gris), Rescindido (rojo).
  - Cuota: Pendiente (ámbar), Vencida (rojo), En Revisión (azul), Pagada (verde).
  - Pago: Pendiente de Validación (ámbar), Aprobado (verde), Rechazado (rojo).
  - Liquidación: Pendiente de Transferencia (ámbar), Transferido (verde).

## Typography

- **Inter** para tablas, formularios, menús y todo el contenido: cuerpo 15px/400, etiquetas 13px/600, encabezados de tabla 12px/600 en mayúscula con `letterSpacing` 0.04em, títulos de sección 18px/600.
- **Fraunces** (serif de Google Fonts, pesos 600 y 700) **solo** para el título de página (28px/600), la marca «InmoGestión», el título del login y los títulos de estado vacío. Si no carga, cae a Georgia.
- Los montos usan cifras tabulares.
- Las dos familias se declaran en `tokens.css` (`--font-body` y `--font-display`); pasar los títulos a Inter es cambiar una línea.

## Layout

- **Desktop (1440px):** sidebar fijo de 260px a la izquierda, color café con veta sutil (logo arriba, ítems de texto, abajo una tarjeta con nombre y rol del usuario y el botón de cerrar sesión). Barra superior de 64px con breadcrumb a la izquierda y el rol a la derecha. Contenido central de hasta 1200px con padding de 32px.
- **Mobile (390px):** el sidebar pasa a un **drawer** (fuera de pantalla por defecto) que se abre con el botón hamburguesa de 44px de la barra superior, con overlay; se cierra con el overlay o con Escape y atrapa el foco mientras está abierto. Las tablas se mantienen y se desplazan en horizontal dentro de su tarjeta (la página nunca se desplaza de costado); los formularios pasan a una columna y los botones a todo el ancho. Sin JavaScript el sidebar queda apilado arriba del contenido. La lógica está en `public/js/layout.js`.
- **Menú Administrador:** Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Historial de pagos, Reportes.
- **Menú Propietario:** Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil.
- **Menú Inquilino:** Estado de cuenta, Mis pagos, Mi perfil.
- **Formatos:** fechas dd/mm/aaaa, períodos como "Noviembre 2026", moneda en pesos argentinos con punto de miles ($ 420.000). La fecha "hoy" del prototipo es 06/11/2026.
- **Logo:** cuadrado redondeado con una casa de línea. Sobre café va en miel con la casa café; sobre claro va en nogal con la casa blanca. El texto «InmoGestión» va en Fraunces.

## Elevation & Depth

Sombra suave y cálida en tarjetas y tablas (`0 10px 40px -10px` en café al 14%), borde de 1px `#E4D9C9` y canto inferior de 2px `#D3C3AE`. Los modales y el drawer usan una sombra más marcada y un fondo de pantalla café al 55%. Sin sombras fuertes ni efectos decorativos.

**Textura.** Veta de madera hecha con un SVG liviano en línea (dos capas de `feTurbulence`, sin imágenes ni librerías), declarada una sola vez en `tokens.css` (`--texture-wood-grain`). Va solo en el sidebar y en el panel de marca del login, es sutil (opacidad 22%) y nunca está detrás de tablas, formularios ni tarjetas.

**Impresión.** En las vistas imprimibles (recibo de liquidación, reporte de morosidad) no hay texturas, sombras ni degradados: `tokens.css` las anula en `@media print` y `panel.css` oculta el sidebar y la barra superior.

## Shapes

Radio de 8px en botones e inputs, 12px en tarjetas y tablas, 16px en contenedores grandes y píldora completa en badges y avatares (`--radius-sm`, `--radius-md`, `--radius-lg` y `--radius-pill`). Íconos de línea de 20px, siempre acompañando texto.

## Components

- **Botones:** primario nogal, secundario blanco con borde `#8C7B6B`, peligro rojo, éxito verde (aprobar pagos) y advertencia ámbar (editar). Hover un tono más oscuro. Los deshabilitados bajan al 55% y muestran cursor de no permitido.
- **Foco:** todo elemento interactivo muestra un contorno sólido de 3px (nogal sobre claro, miel sobre el sidebar) con 2px de separación; los campos suman además un halo suave. Nunca se quita el contorno.
- **Formularios:** etiqueta arriba (con `*` si es obligatorio), ayuda debajo del campo y error debajo del campo con texto. El alta y la edición van en página propia, nunca en modal.
- **Tablas:** cabecera `#F8F1E7`, filas con hover `#F4ECDF`, columna de acciones con botones de ícono, fila de totales y paginación inferior. Clase `data-table` dentro de `table-responsive`.
- **Badges de estado:** ícono + texto + color. El ícono lo genera el CSS (✓ éxito, ! advertencia, ✕ peligro, i información, – finalizado), así que la vista solo escribe `badge-pill badge-<entidad>-<estado>`. Nunca se informa un estado solo con color.
- **Mensajes flash:** arriba del contenido, con ícono, barra de color de 4px a la izquierda y fondo suave. Variantes: éxito, peligro, advertencia e información.
- **Modales:** la baja de usuarios e inmuebles siempre pide confirmación; el rechazo de un pago exige un motivo.
- **Tarjetas de KPI:** ícono en círculo suave, etiqueta, valor grande y detalle.
- **Estado vacío:** título en Fraunces, texto y botón, con borde punteado de 2px `#D3C3AE` (`empty-state`).

## Nombres de clases (vocabulario común)

Los nombres no se atan a una entidad: una pantalla nueva reutiliza los existentes y no inventa `users-…` ni `properties-…`.

| Parte de la pantalla | Clases |
|---|---|
| Encabezado de página | `page-header`, `page-heading-row`, `panel-title`, `panel-subtitle` |
| Contenedores | `panel-card`, `table-card`, `section-header`, `section-title`, `form-layout` (+ `has-sidebar`), `form-main`, `form-aside` |
| Tablas | `table-responsive`, `data-table`, `cell-identity`, `cell-title`, `cell-sub`, `cell-note`, `mono-cell`, `text-right`, `table-actions`, `action-icon-btn`, `pagination-*` |
| KPI | `kpi-grid`, `kpi-card`, `kpi-label`, `kpi-value`, `kpi-note-*`, `kpi-icon-*` |
| Formularios | `form-field`, `form-label`, `form-control`, `field-help`, `field-error`, `form-grid-two`, `form-actions`, `form-actions-footer`, `filter-grid`, `filter-form` |
| Botones | `btn` + `btn-primary` \| `btn-secondary` \| `btn-danger` \| `btn-success` \| `btn-warning` \| `btn-danger-outline`, `btn-sm` |
| Estados | `badge-pill` + `badge-property-*`, `badge-contract-*`, `badge-installment-*`, `badge-payment-*`, `badge-role-*`, `badge-neutral` |
| Mensajes y vacíos | `panel-flash` + `panel-flash-success` \| `-danger` \| `-warning` \| `-info`, `empty-state` |

## Do's and Don'ts

- Hacer: comunicar cada estado con ícono + texto + color; mantener contraste mínimo de 4.5:1 en texto y 3:1 en controles; foco visible; objetivos táctiles de al menos 44px en mobile.
- Hacer: reutilizar siempre los mismos componentes, colores y layout en todas las pantallas, y declarar cualquier color nuevo en `tokens.css`.
- Hacer: probar la pantalla a 1440px y a 390px, y con el teclado.
- No hacer: escribir un hex, rgb o degradado fuera de `tokens.css`, ni dejar rastro del índigo `#4F46E5` o del degradado azul del template original.
- No hacer: poner la textura detrás de texto de contenido, ni usar imágenes pesadas o librerías para decorar.
- No hacer: informar un estado solo con color.
- No hacer: poner el alta o la edición de entidades en un modal.
- Fuera de alcance: modo oscuro.
