# Documento de Análisis de Requisitos, Estimaciones y Servicios Post-Entrega
## InmoGestión 2026 - Plataforma de Gestión Inmobiliaria
### Asignatura: Proyecto de Software (UNRN)
**Borrador de Presupuesto con Tope:** **$ 1.500.000 ARS** (Desarrollo + Puesta en Marcha + Servicios Post-Entrega)

---

# Índice del Documento

* [1. Introducción y Formato de Estimación](#1-introducción-y-formato-de-estimación)
* [2. Identificación y Alcance del Sistema (Límites del Proyecto)](#2-identificación-y-alcance-del-sistema-límites-del-proyecto)
* [3. Tipología de Usuarios y Matriz de Permisos](#3-tipología-de-usuarios-y-matriz-de-permisos)
* [4. Especificación Detallada de Objetivos Funcionales](#4-especificación-detallada-de-objetivos-funcionales)
* [5. Requerimientos Funcionales (RF) y No Funcionales (RNF)](#5-lista-de-requerimientos-funcionales-rf-y-no-funcionales-rnf)
* [6. Matriz de Análisis de Riesgos y Contingencias](#6-matriz-de-análisis-de-riesgos-y-contingencias)
* [7. Servicios Post-Entrega y Garantía Operativa](#7-servicios-post-entrega-y-garantía-operativa)
* [8. Tabla Consolidada de Estimaciones y Presupuesto ($ 1.500.000 ARS)](#8-tabla-consolidada-de-estimaciones-y-presupuesto)

---

# 1. Introducción y Formato de Estimación

En esta sección se especifica el estándar utilizado para la asignación de Story Points (SP), esfuerzo requerido, tiempo estimado (Horas-Hombre - HH), complejidad de las tareas y nivel de riesgo o certeza de concreción:

* **Escala V.E.R. (Valor - Esfuerzo - Riesgo):**
  * **Valor para el Negocio:** Impacto y prioridad para el cliente/inmobiliaria (Alto, Medio, Bajo).
  * **Esfuerzo:** Puntuación relativa basada en la sucesión de Fibonacci ($1, 2, 3, 5, 8, 13$).
  * **Riesgo:** Probabilidad e impacto de bloqueos técnicos o de flujo de desarrollo (Alto, Medio, Bajo).
* **Parámetros Económicos:**
  * **Valor hora profesional estimado:** **$ 18.000 ARS / hora**.
  * **Presupuesto Tope Acordado:** **$ 1.500.000 ARS** (83,33 Horas-Hombre totales).

---

# 2. Identificación y Alcance del Sistema (Límites del Proyecto)

* **Objetivo General:** Desarrollar una plataforma web integral bajo patrón MVC (CodeIgniter 4) para la gestión de contratos de alquiler, cobro de cuotas, liquidaciones financieras y catalogación de inmuebles.
* **Límites e Inclusiones (*In-Scope*):**
  * Autenticación segura con hash BCRYPT (`password_hash()`), gestión de sesiones y control de acceso por roles (`Administrador`, `Propietario`, `Inquilino`).
  * Mecanismo de restablecimiento y recuperación de contraseña.
  * ABMC de Personas/Usuarios con regla estricta de unicidad en correo y borrado lógico (*Soft Delete* mediante `deleted_at`).
  * Catalogación de inmuebles vinculados a sus propietarios y estados (`Disponible`, `Alquilado`, `Mantenimiento`).
  * Administración de contratos de alquiler, canon locativo, día de vencimiento mensual y porcentaje de comisión inmobiliaria.
  * Emisión masiva o individual de cuotas con recargos automáticos por mora diaria.
  * Módulo de autogestión para inquilinos con carga digital de comprobantes de transferencia (PDF/JPG) y flujo de verificación/aprobación por administración.
  * Liquidación financiera mensual a propietarios con deducción automática de comisión y registro de transferencias.
  * Dashboard directivo con métricas clave en tiempo real: ocupación del catálogo, recaudación mensual acumulada e índice de morosidad.
  * **Servicios Post-Entrega:** Garantía de 60 días, capacitación de usuarios, mantenimiento preventivo y configuración de backups automatizados.
* **Exclusiones Explícitas (*Out-of-Scope*):**
  * Procesamiento directo con pasarelas de pago bancarias de terceros en tiempo real con Webhooks (se valida por carga manual de comprobante de transferencia bancaria / depósito).
  * Firma digital o electrónica homologada de contratos mediante token criptográfico (los contratos se registran como términos y condiciones acordados en el sistema).

---

# 3. Tipología de Usuarios y Matriz de Permisos

| Rol de Usuario | Descripción y Responsabilidades | Permisos Principales en el Sistema |
| :--- | :--- | :--- |
| **Administrador / Empleado** | Personal operativo de la inmobiliaria encargado del control financiero y gestión del catálogo. | **Acceso total:** ABMC de usuarios, inmuebles, contratos, emisión de cuotas, verificación de comprobantes, liquidaciones a propietarios y Dashboard directivo. |
| **Propietario** | Dueño titular de los inmuebles administrados por la inmobiliaria. | **Acceso de consulta y auditoría:** Estado de sus propiedades, contratos vigentes, histórico de liquidaciones recibidas y recibos de transferencias. |
| **Inquilino** | Arrendatario que ocupa un inmueble en alquiler. | **Acceso de autogestión:** Consulta de estado de cuenta mensual, detalle de cuotas emitidas/vencidas y carga de comprobantes de pago. |

---

# 4. Especificación Detallada de Objetivos Funcionales

## 1. SEGURIDAD Y LOGIN DE USUARIOS
* **Nombre del Objetivo:** Permitir el acceso seguro al sistema según el rol de cada usuario.
* **V.E.R.:**
  * **Valor:** Alto (Protege la información confidencial y restringe el acceso a las funciones operativas).
  * **Esfuerzo:** 3 SP
  * **Riesgo:** Alto (Una falla en autenticación o autorización puede permitir accesos indebidos o escalamiento de privilegios).
* **Condiciones de Satisfacción:**
  1. El sistema permite iniciar sesión mediante credenciales válidas.
  2. El sistema rechaza el acceso cuando las credenciales son incorrectas mediante mensaje unificado.
  3. El sistema mantiene la sesión del usuario autenticado en el servidor.
  4. El sistema restringe las funcionalidades y rutas según los permisos del rol asignado vía `Filters`.
  5. El sistema permite cerrar sesión y finalizar el acceso autenticado destruyendo cookies y sesión.
  6. El sistema impide el acceso a las funcionalidades protegidas cuando no existe una sesión válida.

## 2. ABMC DE PERSONAS / USUARIOS
* **Nombre del Objetivo:** Permitir al administrador gestionar los usuarios registrados en el sistema.
* **V.E.R.:**
  * **Valor:** Alto (Centraliza la información personal y administra los accesos y roles de los usuarios).
  * **Esfuerzo:** 3 SP
  * **Riesgo:** Medio (Posibles inconsistencias de datos, duplicación de emails o errores en las relaciones con los roles).
* **Condiciones de Satisfacción:**
  1. El administrador puede dar de alta usuarios ingresando nombre, apellido, DNI/CUIT, email, teléfono, dirección y rol.
  2. El sistema valida los campos obligatorios y los formatos correspondientes en cliente y servidor.
  3. El sistema impide registrar dos usuarios con el mismo email o DNI.
  4. El administrador puede consultar los usuarios registrados y sus datos con filtros por rol y buscador.
  5. El administrador puede modificar los datos de un usuario existente sin exigir cambio de clave.
  6. El administrador puede dar de baja usuarios respetando las restricciones de integridad mediante borrado lógico.
  7. El sistema permite asignar los roles de Administrador, Propietario e Inquilino.
  8. El sistema informa si una operación se realizó correctamente o si ocurrió un error.

## 3. RESTABLECIMIENTO DE CONTRASEÑA
* **Nombre del Objetivo:** Permitir restablecer la contraseña de un usuario para recuperar el acceso al sistema.
* **V.E.R.:**
  * **Valor:** Alto (Garantiza la continuidad del acceso ante pérdida u olvido de credenciales).
  * **Esfuerzo:** 3 SP
  * **Riesgo:** Alto (Una implementación defectuosa permitiría a atacantes vulnerar cuentas ajenas).
* **Condiciones de Satisfacción:**
  1. El sistema ofrece un mecanismo para iniciar la solicitud de restablecimiento de contraseña.
  2. El sistema verifica la solicitud antes de permitir el cambio de contraseña.
  3. El sistema permite establecer una nueva contraseña cuando se cumplen las condiciones de seguridad.
  4. El sistema almacena la nueva contraseña cifrada mediante `PASSWORD_BCRYPT`.
  5. El sistema informa si el restablecimiento se realizó correctamente.
  6. El sistema impide completar el proceso cuando la solicitud no es válida o expiró.

## 4. ABMC DE INMUEBLES
* **Nombre del Objetivo:** Permitir al administrador gestionar las propiedades administradas por la inmobiliaria.
* **V.E.R.:**
  * **Valor:** Alto (Mantiene actualizado el catálogo de inmuebles y su disponibilidad comercial).
  * **Esfuerzo:** 5 SP
  * **Riesgo:** Medio (Una asignación incorrecta de propietarios o estado inconsistente afecta la gestión locativa).
* **Condiciones de Satisfacción:**
  1. El administrador puede registrar un inmueble ingresando dirección completa, ciudad, tipo de propiedad, cantidad de ambientes, superficie, servicios incluidos y valor locativo base.
  2. El sistema permite asignar obligatoriamente un propietario registrado al inmueble.
  3. El sistema permite establecer el estado del inmueble: `Disponible`, `Alquilado` o `En Mantenimiento`.
  4. El sistema valida los datos obligatorios y los valores ingresados.
  5. El administrador puede consultar los inmuebles registrados y sus características.
  6. El administrador puede modificar los datos y el estado de un inmueble existente.
  7. El administrador puede dar de baja un inmueble respetando las restricciones relacionadas con contratos vigentes.
  8. El sistema informa el resultado de cada operación.

## 5. ABMC DE CONTRATOS DE ALQUILER
* **Nombre del Objetivo:** Permitir registrar y gestionar los contratos de alquiler vinculando inmuebles, inquilinos y propietarios.
* **V.E.R.:**
  * **Valor:** Alto (Formaliza las relaciones locativas y gobierna la emisión de cuotas de alquiler).
  * **Esfuerzo:** 5 SP
  * **Riesgo:** Alto (Errores contractuales impactan directamente en la facturación y la liquidación de haberes).
* **Condiciones de Satisfacción:**
  1. El administrador puede registrar un contrato seleccionando el inmueble, el inquilino y el propietario correspondientes.
  2. El sistema registra la fecha de inicio y la fecha de finalización del contrato.
  3. El sistema registra el canon locativo mensual inicial.
  4. El sistema permite establecer el porcentaje de incremento por actualización.
  5. El sistema registra el día de vencimiento mensual de la cuota (dentro del rango fijado para el proyecto).
  6. El sistema registra el porcentaje de comisión inmobiliaria aplicable.
  7. El sistema permite establecer los estados: `Vigente`, `Finalizado` y `Rescindido`.
  8. El sistema valida los datos y las relaciones necesarias para registrar el contrato.
  9. El administrador puede consultar los contratos y modificar sus datos según corresponda.
  10. El sistema impide registrar relaciones con inmuebles, inquilinos o propietarios inexistentes o inactivos.

## 6. EMISIÓN DE CUOTAS Y CÁLCULO DE RECARGOS POR MORA
* **Nombre del Objetivo:** Permitir generar las cuotas mensuales de los contratos vigentes y calcular los recargos por mora.
* **V.E.R.:**
  * **Valor:** Alto (Automatiza las obligaciones mensuales y cuantifica la mora con precisión).
  * **Esfuerzo:** 3 SP
  * **Riesgo:** Alto (Cálculos inexactos o duplicación de cuotas provocan inconsistencias contables graves).
* **Condiciones de Satisfacción:**
  1. El sistema permite generar periódicamente o de manera masiva las cuotas asociadas a contratos vigentes.
  2. Cada cuota queda vinculada al contrato correspondiente.
  3. El sistema calcula el importe de la cuota según las condiciones contractuales aplicables.
  4. El sistema establece la fecha límite de pago sin recargo de acuerdo con el contrato.
  5. El sistema identifica automáticamente las cuotas vencidas.
  6. El sistema calcula el recargo por mora según las reglas contractuales y días de atraso.
  7. El sistema permite distinguir entre cuotas `Pendientes`, `Pagadas` o `Vencidas`.
  8. El sistema evita generar cuotas duplicadas para el mismo período y contrato.
  9. El sistema informa si la generación de cuotas se realizó correctamente.

## 7. REGISTRO DE PAGO Y CARGA DE COMPROBANTES
* **Nombre del Objetivo:** Permitir al inquilino registrar un pago de alquiler y adjuntar el comprobante correspondiente.
* **V.E.R.:**
  * **Valor:** Alto (Facilita la comunicación del pago y habilita la verificación bancaria administrativa).
  * **Esfuerzo:** 5 SP
  * **Riesgo:** Alto (Comprobantes erróneos o importes discordantes generan discrepancias financieras).
* **Condiciones de Satisfacción:**
  1. El inquilino puede seleccionar la cuota a la que corresponde el pago.
  2. El sistema permite ingresar el monto transferido y la fecha de la operación.
  3. El sistema permite seleccionar el medio de pago: Transferencia bancaria, Depósito o Pasarela digital.
  4. El sistema permite ingresar el número de comprobante o transacción.
  5. El sistema permite adjuntar un comprobante digital en formato PDF o imagen (JPG/PNG).
  6. El sistema valida los datos requeridos y el formato y peso del archivo adjunto.
  7. El sistema registra el pago y lo vincula con la cuota correspondiente.
  8. El sistema permite que la inmobiliaria consulte el comprobante presentado y verifique la acreditación.
  9. El sistema permite registrar el resultado de la verificación: `Pendiente`, `Aprobada` y `Rechazada`.
  10. El sistema informa al inquilino si el registro del pago se realizó correctamente.

---

# 5. Lista de Requerimientos Funcionales (RF) y No Funcionales (RNF)

#### A. Requerimientos Funcionales (RF)
* **RF-01 (Autenticación y Seguridad):** El sistema debe permitir el inicio y cierre de sesión seguro mediante correo electrónico y clave, redirigiendo al dashboard correspondiente según el rol.
* **RF-02 (ABMC Usuarios):** El sistema debe permitir registrar, listar, editar y dar de baja de forma lógica a usuarios, validando que el correo electrónico no esté duplicado en la base de datos.
* **RF-03 (ABMC Inmuebles):** El sistema debe permitir catalogar inmuebles asignándoles obligatoriamente un propietario registrado.
* **RF-04 (Gestión de Contratos):** El sistema debe registrar contratos de alquiler asociando Inmueble + Inquilino + Propietario, definiendo el canon locativo y la comisión de la inmobiliaria.
* **RF-05 (Emisión y Cobro de Cuotas):** El sistema debe emitir las cuotas mensuales de forma individual o en lote para contratos vigentes y aplicar recargos por mora en caso de pago extemporáneo.
* **RF-06 (Carga de Comprobantes - Inquilino):** El sistema debe permitir al inquilino subir un comprobante adjunto (PDF/JPG) e ingresar el monto transferido.
* **RF-07 (Auditoría de Pagos):** El administrador debe poder verificar los comprobantes cargados, aprobándolos (lo que imputa el pago en la cuota) o rechazándolos con un motivo.
* **RF-08 (Liquidación a Propietarios):** El sistema debe calcular el monto neto a acreditar a cada propietario deduciendo la comisión inmobiliaria sobre las cuotas efectivamente cobradas.
* **RF-09 (Dashboard Directivo):** El sistema debe calcular y renderizar en tiempo real el porcentaje de ocupación, recaudación acumulada y porcentaje de mora.

#### B. Requerimientos No Funcionales (RNF)
* **RNF-01 (Seguridad y Cifrado):** Las contraseñas deben cifrarse obligatoriamente mediante el algoritmo BCRYPT (`password_hash()`) antes de ser almacenadas en MySQL.
* **RNF-02 (Integridad de Datos):** La eliminación de usuarios o propiedades se realiza mediante borrado lógico (`deleted_at`), manteniendo la integridad referencial histórica en contratos y facturación.
* **RNF-03 (Patrón de Arquitectura):** La aplicación debe desarrollarse bajo el patrón **Modelo-Vista-Controlador (MVC)** utilizando el framework CodeIgniter 4.
* **RNF-04 (Usabilidad y Validación):** Todos los formularios deben incluir validación en tiempo real (*live validation*) en el cliente (JavaScript) y validación estricta de respaldo en el servidor (PHP).

---

# 6. Matriz de Análisis de Riesgos y Contingencias

| ID | Riesgo Identificado | Impacto | Probabilidad | Plan de Contingencia / Acción Correctiva |
| :---: | :--- | :---: | :---: | :--- |
| **R-01** | **Inconsistencia de Permisos en Rutas:** Acceso no autorizado de inquilinos o propietarios a funciones administrativas. | **Alto** | **Media** | Implementar **Filtros de Seguridad (`Filters`)** en CodeIgniter 4 que validen el rol de sesión antes de ejecutar cualquier controlador. |
| **R-02** | **Fallo en Borrado Físico:** Pérdida accidental de relaciones contractuales o contables por eliminar registros en cascada. | **Crítico** | **Alta** | Habilitar `$useSoftDeletes = true` en los modelos para realizar únicamente marcas de tiempo en `deleted_at`. |
| **R-03** | **Carga de Archivos Maliciosos:** Subida de scripts ejecutables en el formulario de comprobantes del inquilino. | **Alto** | **Media** | Aplicar reglas de validación en PHP restringiendo tipos MIME exclusivamente a `image/jpeg`, `image/png` y `application/pdf`, guardando archivos fuera del directorio público accesible web (`writable/uploads/comprobantes/`). |
| **R-04** | **Sobrecosto de Rendimiento en Consultas Agregadas:** Lentitud en el Dashboard por escaneo completo de tablas contables. | **Medio** | **Media** | Indexar las claves foráneas (`contrato_id`, `inquilino_id`, `propietario_id`) y crear vistas/queries optimizadas con `GROUP BY` e índices compuestos. |

---

# 7. Servicios Post-Entrega y Garantía Operativa

Para asegurar la viabilidad técnica, la adopción fluida de los usuarios y la estabilidad de la plataforma tras el despliegue en producción, se incorporan **22,33 Horas-Hombre ($ 402.000 ARS)** dedicadas a servicios post-entrega:

### 1. Garantía Operativa y Soporte Correctivo (60 días post-lanzamiento)
* **Horas Asignadas:** **10,0 HH** ($ 180.000 ARS).
* **Alcance:**
  * Resolución prioritaria de cualquier incidencia, anomalía o bug no detectado durante la fase de pruebas.
  * Canal de atención técnica para soporte administrativo ante errores de ejecución.
  * Correcciones de código sin costo adicional durante el período de garantía.

### 2. Capacitación y Transferencia de Conocimiento al Personal Inmobiliario
* **Horas Asignadas:** **5,0 HH** ($ 90.000 ARS).
* **Alcance:**
  * Dictado de 2 sesiones prácticas de capacitación dirigidas a los administradores y empleados inmobiliarios.
  * Entrenamiento en flujos críticos: confección de contratos, emisión masiva de cuotas, verificación de comprobantes bancarios y liquidación neta a propietarios.
  * Entrega de una **Guía Rápida de Administración y Operación** en formato digital (PDF).

### 3. Mantenimiento Preventivo y Monitoreo del Entorno LAMP
* **Horas Asignadas:** **4,0 HH** ($ 72.000 ARS).
* **Alcance:**
  * Auditoría periódica de logs del servidor Apache y CodeIgniter en `writable/logs/` para detección temprana de advertencias de memoria o cuellos de botella.
  * Optimización de consultas SQL y reconstrucción periódica de índices en la base de datos `inmogestion2026`.
  * Verificación de permisos de escritura y seguridad en carpetas de uploads.

### 4. Políticas de Resguardo y Plan de Backup Automatizado (Disaster Recovery)
* **Horas Asignadas:** **3,33 HH** ($ 60.000 ARS).
* **Alcance:**
  * Configuración de un script automatizado para la creación de copias de seguridad diarias de la base de datos MySQL (`mysqldump` programado).
  * Política de resguardo de archivos de comprobantes adjuntos de inquilinos (`writable/uploads/comprobantes/`).
  * Procedimiento documentado para la restauración rápida del sistema ante eventuales caídas del servidor local o fallas de disco.

---

# 8. Tabla Consolidada de Estimaciones y Presupuesto

* **Tarifa Profesional por Hora:** **$ 18.000 ARS / hora**.
* **Tope Máximo Presupuestado:** **$ 1.500.000 ARS** (83,33 Horas-Hombre totales).

### Desglose Completo por Fases y Módulos:

| Fase / Concepto | Puntos de Historia (SP) | Horas-Hombre (HH) | Complejidad / Riesgo | Presupuesto Subtotal |
| :--- | :---: | :---: | :---: | :---: |
| **FASE 1: ALCANCE CURSADA (REGULARIZACIÓN)** | | | | |
| • Gestión de Personas / Usuarios (Admin) | **3 SP** | **4,5 HH** | Mínima / Bajo | $ 81.000 |
| • Gestión de Inmuebles y Catálogo (Admin) | **5 SP** | **7,5 HH** | Moderada / Moderado | $ 135.000 |
| • Gestión de Contratos de Alquiler (Admin) | **5 SP** | **7,5 HH** | Moderada / Moderado | $ 135.000 |
| • Cobro y Emisión de Cuotas / Moras (Admin) | **3 SP** | **4,5 HH** | Baja / Bajo | $ 81.000 |
| • Registro y Perfil (Propietario) | **3 SP** | **4,5 HH** | Baja / Bajo | $ 81.000 |
| • Consulta de Inmuebles y Contratos (Prop.) | **2 SP** | **3,0 HH** | Baja / Ninguno | $ 54.000 |
| • Registro y Perfil (Inquilino) | **2 SP** | **3,0 HH** | Baja / Ninguno | $ 54.000 |
| • Estado de Cuenta y Detalle de Cuotas (Inq.) | **5 SP** | **7,5 HH** | Moderada / Moderado | $ 135.000 |
| • Registro de Pagos y Carga Comprobante (Inq.) | **5 SP** | **8,0 HH** | Moderada / Moderado | $ 144.000 |
| *Subtotal Fase 1 (Cursada):* | **33 SP** | **50,0 HH** | **Regularización** | **$ 900.000** |
| | | | | |
| **FASE 2: MÓDULOS DE CIERRE (EXAMEN FINAL)** | | | | |
| • Liquidación Financiera a Propietarios | **2 SP** | **2,5 HH** | Baja / Ninguno | $ 45.000 |
| • Dashboard y Panel de Control Directivo | **5 SP** | **6,0 HH** | Moderada / Moderado | $ 108.000 |
| • Consulta y Recibos de Liquidación (Prop.) | **2 SP** | **2,5 HH** | Baja / Ninguno | $ 45.000 |
| *Subtotal Fase 2 (Examen Final):* | **9 SP** | **11,0 HH** | **Examen Final** | **$ 198.000** |
| | | | | |
| **FASE 3: SERVICIOS POST-ENTREGA Y OPERACIONES** | | | | |
| • Garantía Operativa y Soporte Correctivo (60 d) | — | **10,0 HH** | Crítica / Soporte | $ 180.000 |
| • Capacitación y Manual de Usuario Inmobiliario | — | **5,0 HH** | Media / Adopción | $ 90.000 |
| • Mantenimiento Preventivo y Auditoría LAMP | — | **4,0 HH** | Media / Preventivo | $ 72.000 |
| • Plan de Backups Automatizados y Recuperación | — | **3,33 HH** | Alta / Seguridad | $ 60.000 |
| *Subtotal Fase 3 (Servicios Post-Entrega):* | — | **22,33 HH** | **Post-Lanzamiento** | **$ 402.000** |
| | | | | |
| **TOTAL GENERAL CONSOLIDADO:** | **42 SP** | **83,33 HH** | **Tope Máximo Acordado** | **$ 1.500.000 ARS** |

---

### Esquema de Facturación Escalonada:
1. **Anticipo de Inicio (Kick-off del proyecto):** 20% = **$ 300.000 ARS**.
2. **Segundo Pago contra entrega de Cursada Regularizada (Fase 1 completada):** 40% = **$ 600.000 ARS**.
3. **Tercer Pago contra entrega y defensa del Examen Final (Fase 2 completada):** 20% = **$ 300.000 ARS**.
4. **Pago de Cierre e inicio de Servicios Post-Entrega (Fase 3 activada):** 20% = **$ 300.000 ARS**.



---

### Detalle de Fases y Sprints (Cronograma Intensivo de 2 Semanas de Cursada):
* **Sprint 1 (Semana 1 - 16 SP | 24,0 HH):**
  * Seguridad, login con BCRYPT, filtros de sesión y rol (`AuthFilter`, `RoleFilter`).
  * Mecanismo de restablecimiento de contraseña.
  * ABMC de usuarios / personas con *Soft Delete* y perfil del propietario/inquilino.
  * ABMC de inmuebles con catálogo, filtros por disponibilidad y asignación de propietario.
  * ABMC de contratos de alquiler: vinculación formal Inmueble + Inquilino + Propietario, canon locativo y comisiones.
  * *Entregable:* Padrón de usuarios, catálogo de inmuebles y contratos activos en portal seguro.

* **Sprint 2 (Semana 2 - 17 SP | 26,0 HH) — [HITO REGULARIZACIÓN DE CURSADA]:**
  * Emisión masiva o individual de cuotas mensuales de alquiler.
  * Motor de cálculo automático de recargos por mora e intereses por días de atraso.
  * Portal del inquilino: estado de cuenta y formulario de carga de comprobantes de pago (PDF/JPG).
  * Panel administrativo de verificación, conciliación y aprobación/rechazo de pagos presentados.
  * Integración end-to-end, testing final y demo funcional completa para la cátedra.
  * *Entregable:* **Demo funcional completa para la aprobación de la cursada (33 SP | 50,0 HH | $ 900.000 ARS).**

* **Fase 2: Módulos de Cierre (Turno Examen Final - 9 SP | 11,0 HH):**
  * Módulo de liquidaciones automáticas a propietarios con deducción de comisión.
  * Dashboard directivo con KPIs de ocupación y morosidad; balances patrimoniales del propietario.
  * Sistema de notificaciones automáticas (alertas de pagos, vencimientos de cuotas y contratos).
  * *Entregable:* **Defensa y aprobación del Examen Final.**

* **Fase 3: Servicios Post-Entrega (60 días post-lanzamiento - 22,33 HH):**
  * Mesa de ayuda, resolución de incidencias en garantía (10,0 HH).
  * Jornadas de capacitación y entrega de manual operativo de usuario (5,0 HH).
  * Mantenimiento preventivo, optimización LAMP y verificación de logs (4,0 HH).
  * Puesta en marcha de políticas de resguardo y backup diario de MySQL (3,33 HH).