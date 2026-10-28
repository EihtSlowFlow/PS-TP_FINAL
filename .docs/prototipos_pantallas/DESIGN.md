---
version: alpha
name: InmoGestión 2026
description: Admin template sobrio y moderno para la gestión inmobiliaria y de alquileres de Nico Tech. Tokens tomados de public/css/styles.css.
colors:
  primary: "#4F46E5"
  primary-hover: "#4338CA"
  primary-soft: "#E0E7FF"
  on-primary: "#FFFFFF"
  heading: "#111827"
  text: "#1F2937"
  text-muted: "#6B7280"
  background-start: "#F0F4F8"
  background-end: "#D9E2EC"
  surface: "#FFFFFF"
  surface-muted: "#F9FAFB"
  row-hover: "#F3F4F6"
  border: "#E5E7EB"
  border-input: "#D1D5DB"
  secondary: "#E5E7EB"
  secondary-text: "#374151"
  success: "#10B981"
  success-soft: "#ECFDF5"
  success-text: "#065F46"
  danger: "#EF4444"
  danger-hover: "#DC2626"
  danger-soft: "#FEF2F2"
  danger-text: "#B91C1C"
  warning: "#F59E0B"
  warning-soft: "#FFFBEB"
  warning-text: "#92400E"
  info: "#2563EB"
  info-soft: "#DBEAFE"
  info-text: "#1E40AF"
typography:
  page-title:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1.25
  heading:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: 500
    lineHeight: 1.3
  body:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.4
  table-header:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: 0.05em
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
    padding: 10px 24px
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.on-primary}"
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.secondary-text}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 10px 24px
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 10px 24px
  button-success:
    backgroundColor: "{colors.success}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 10px 24px
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: 10px 16px
    height: 44px
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: 24px
  table-header:
    backgroundColor: "{colors.surface-muted}"
    textColor: "{colors.text-muted}"
    typography: "{typography.table-header}"
    padding: 16px
  sidebar:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    width: 260px
  sidebar-item-active:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary-hover}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: 10px 12px
  badge-success:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 4px 12px
  badge-danger:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 4px 12px
  badge-warning:
    backgroundColor: "{colors.warning-soft}"
    textColor: "{colors.warning-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 4px 12px
  badge-info:
    backgroundColor: "{colors.info-soft}"
    textColor: "{colors.info-text}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    padding: 4px 12px
---

## Overview

InmoGestión 2026 es una plataforma web de gestión inmobiliaria y de alquileres. Digitaliza el ciclo completo: catálogo de inmuebles, contrato, emisión mensual de cuotas, pago informado por el inquilino con comprobante, verificación del administrador y liquidación al propietario con descuento de la comisión.

Hay tres roles con menús distintos (Administrador/Empleado, Propietario e Inquilino) y pantallas públicas (login, registro, catálogo). Es un **admin template** limpio, moderno y sobrio, no una landing de marketing. Todo el texto de la interfaz va en español rioplatense.

## Colors

- **Primary (`#4F46E5`, índigo):** acciones principales, ítem activo del menú y foco. Su variante suave (`#E0E7FF` con texto `#4338CA`) marca el ítem activo y los roles.
- **Fondo de la app:** degradado suave de `#F0F4F8` a `#D9E2EC`. Las superficies (tarjetas, tablas, sidebar) son blancas.
- **Texto:** `#1F2937` para el contenido, `#6B7280` para lo secundario y `#111827` para títulos.
- **Semánticos:** éxito verde, peligro rojo, advertencia ámbar e informativo azul, cada uno con su fondo suave y su texto oscuro.
- **Estados de negocio:**
  - Inmueble: Disponible (verde), Alquilado (azul), En Mantenimiento (ámbar).
  - Contrato: Vigente (verde), Finalizado (gris), Rescindido (rojo).
  - Cuota: Pendiente (ámbar), Vencida (rojo), En Revisión (azul), Pagada (verde).
  - Pago: Pendiente de Validación (ámbar), Aprobado (verde), Rechazado (rojo).
  - Liquidación: Pendiente de Transferencia (ámbar), Transferido (verde).

## Typography

Fuente **Inter**. Título de página 28px/600, H2 20px/500, cuerpo 15px/400, etiquetas 13px/500. Los encabezados de tabla van en mayúscula con `letterSpacing` 0.05em. Los montos usan cifras tabulares.

## Layout

- **Desktop (1440px):** sidebar fijo de 260px a la izquierda (logo arriba, ítems con ícono y texto, abajo una tarjeta con nombre y rol del usuario y el botón "Cerrar Sesión"). Barra superior de 64px con breadcrumb a la izquierda y, a la derecha, la campana de notificaciones con contador rojo de no leídas y el menú del usuario. Contenido central de hasta 1200px con padding de 32px.
- **Mobile (390px):** el sidebar pasa a un drawer con botón hamburguesa y overlay; las tablas pasan a tarjetas apiladas; los botones principales ocupan todo el ancho.
- **Menú Administrador:** Dashboard, Usuarios, Inmuebles, Contratos, Cuotas (Emitir cuotas y Cobranzas), Pagos, Liquidaciones, Reportes.
- **Menú Propietario:** Dashboard, Mis inmuebles, Mis contratos, Liquidaciones, Mi perfil.
- **Menú Inquilino:** Estado de cuenta, Mis pagos, Mi perfil.
- **Formatos:** fechas dd/mm/aaaa, períodos como "Noviembre 2026", moneda en pesos argentinos con punto de miles ($ 420.000). La fecha "hoy" del prototipo es 06/11/2026.
- **Logo:** cuadrado redondeado índigo con una casa blanca y el texto "InmoGestión".

## Elevation & Depth

Sombra suave en tarjetas y tablas (`0 10px 40px -10px rgba(0,0,0,0.1)`) y borde de 1px `#E5E7EB`. Sin sombras fuertes ni efectos decorativos. En las vistas de impresión no hay sombras ni degradados.

## Shapes

Radio de 8px en botones e inputs, 12px en tarjetas y tablas, 16px en contenedores grandes y píldora completa en badges. Íconos de línea de 20px, siempre acompañando texto.

## Components

- **Botones:** primario índigo, secundario gris, peligro rojo, éxito verde (aprobar pagos) y advertencia ámbar (editar). Hover un tono más oscuro y foco con borde primario y anillo de 3px al 15% de opacidad.
- **Formularios:** etiqueta arriba (con `*` si es obligatorio), ayuda debajo del campo y error en vivo debajo del campo con ícono y texto. El alta y la edición van en página propia, nunca en modal.
- **Tablas:** cabecera `#F9FAFB`, filas con hover `#F3F4F6`, columna de acciones con botones de ícono, fila de totales y paginación inferior.
- **Mensajes flash:** arriba del contenido, con ícono, barra de color de 4px a la izquierda y botón de cierre.
- **Modales:** la baja de usuarios e inmuebles siempre pide confirmación; el rechazo de un pago exige un motivo.
- **Tarjetas de KPI:** ícono en círculo suave, etiqueta, valor grande y detalle.
- **Estado vacío:** ilustración de línea, título, texto y botón, con borde punteado `#D1D5DB`.

## Do's and Don'ts

- Hacer: comunicar cada estado con ícono + texto + color; mantener contraste mínimo de 4.5:1; foco visible; objetivos táctiles de al menos 44px en mobile.
- Hacer: reutilizar siempre los mismos componentes, colores y layout en todas las pantallas.
- No hacer: informar un estado solo con color.
- No hacer: usar otra fuente, otro color primario o sombras pesadas.
- No hacer: poner el alta o la edición de entidades en un modal.
