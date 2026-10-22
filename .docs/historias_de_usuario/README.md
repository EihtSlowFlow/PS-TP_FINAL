# Product Backlog & Matriz de Historias de Usuario
## InmoGestión 2026 - Plataforma Integral de Gestión Inmobiliaria

Este documento consolida el **Product Backlog priorizado**, alineado a las directrices de la cátedra **Proyecto de Software (UNRN)**, el modelo de negocio inmobiliario y las técnicas ágiles explicadas en clase (Planning Poker con sucesión de Fibonacci, evaluación de riesgo técnico/flujo y valoración del Product Owner).

---

### 🏛️ Estrategia de Priorización y Modelo de Negocio
Siguiendo lo analizado en clase:
1. **Riesgo para el Flujo del Proyecto:** Los módulos de **Autenticación y Filtros de Sesión/Roles** se ejecutan en primer término (Sprint 1), ya que posponer la seguridad obligaría a intervenir todas las entidades y rutas a mitad del desarrollo, frenando el avance del equipo.
2. **Valor para el Product Owner (Inmobiliaria):** El mayor valor de negocio para una inmobiliaria radica en **controlar el estado de los contratos de alquiler y la cobranza de cuotas**. Para alcanzar este núcleo rápidamente, se prioriza la cadena de dependencias:
   $$\text{Usuarios (Clientes)} \longrightarrow \text{Inmuebles} \longrightarrow \text{Contratos Locativos} \longrightarrow \text{Emisión de Cuotas y Pagos}$$
3. **Estimación Ágil (Story Points - Fibonacci):** Se utiliza la serie $1, 2, 3, 5, 8, 13$ para cuantificar el esfuerzo relativo de diseño, backend (MVC CodeIgniter 4), frontend (HTML5/CSS3/JS) y pruebas de validación.

---

### 📊 Tabla Maestra del Product Backlog (Priorizada)

| ID | Historia de Usuario | Rol Principal | Valor PO (1-5) | Riesgo | Story Points | Prioridad | Dependencias | Hito |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: | :--- | :--- |
| **HU-01** | Inicio de Sesión y Control de Acceso | Todos los roles | 5 | **Alto (Flujo)** | 5 | **P1 (Alta)** | Ninguna | **Cursada** |
| **HU-02** | Registro de Clientes Externos | Inquilino / Prop. | 4 | Medio | 3 | **P1 (Alta)** | HU-01 | **Cursada** |
| **HU-03** | Cierre de Sesión Seguro | Todos los roles | 3 | Bajo | 1 | **P1 (Alta)** | HU-01 | **Cursada** |
| **HU-04** | ABMC de Personas / Usuarios | Administrador | 5 | Medio | 5 | **P1 (Alta)** | HU-01 | **Cursada** |
| **HU-05** | Baja Lógica (*Soft Delete*) de Usuarios | Administrador | 4 | Medio | 2 | **P1 (Alta)** | HU-04 | **Cursada** |
| **HU-06** | Gestión y Edición de Perfil de Usuario | Inquilino / Prop. | 3 | Bajo | 2 | **P2 (Media)**| HU-02 | **Cursada** |
| **HU-07** | Alta y Edición de Inmuebles | Administrador | 5 | Medio | 5 | **P1 (Alta)** | HU-04 | **Cursada** |
| **HU-08** | Catálogo y Filtrado de Inmuebles | Admin / Público | 4 | Bajo | 3 | **P1 (Alta)** | HU-07 | **Cursada** |
| **HU-09** | Baja Lógica de Inmuebles | Administrador | 4 | Medio | 2 | **P1 (Alta)** | HU-07 | **Cursada** |
| **HU-10** | Alta y Vinculación de Contratos | Administrador | 5 | **Alto (Core)** | 8 | **P1 (Alta)** | HU-04, HU-07 | **Cursada** |
| **HU-11** | Consulta de Contratos y Estados | Admin / Propietario | 5 | Medio | 3 | **P1 (Alta)** | HU-10 | **Cursada** |
| **HU-12** | Rescisión o Finalización de Contratos | Administrador | 4 | Medio | 3 | **P2 (Media)**| HU-10 | **Cursada** |
| **HU-13** | Emisión Masiva de Cuotas Mensuales | Administrador | 5 | **Alto (Core)** | 5 | **P1 (Alta)** | HU-10 | **Cursada** |
| **HU-14** | Cálculo Automático de Recargo por Mora | Sistema / Admin | 5 | Medio | 5 | **P1 (Alta)** | HU-13 | **Cursada** |
| **HU-15** | Estado de Cuenta del Inquilino | Inquilino | 5 | Medio | 3 | **P1 (Alta)** | HU-13, HU-14 | **Cursada** |
| **HU-16** | Registro de Pago y Carga de Comprobante| Inquilino | 5 | **Alto (Core)** | 8 | **P1 (Alta)** | HU-15 | **Cursada** |
| **HU-17** | Verificación y Aprobación de Pagos | Administrador | 5 | **Alto (Core)** | 5 | **P1 (Alta)** | HU-16 | **Cursada** |
| **HU-18** | Historial y Comprobantes de Pagos | Inquilino | 4 | Bajo | 2 | **P2 (Media)**| HU-17 | **Cursada** |
| **HU-19** | Liquidación Financiera con Comisión | Administrador | 5 | **Alto (Cálculo)**| 8 | **P2 (Media)**| HU-17 | **Final** |
| **HU-20** | Registro de Transferencia a Propietarios| Administrador | 4 | Medio | 3 | **P2 (Media)**| HU-19 | **Final** |
| **HU-21** | Consulta de Liquidaciones y Recibos | Propietario | 4 | Bajo | 3 | **P2 (Media)**| HU-20 | **Final** |
| **HU-22** | Notificaciones de Pagos y Validaciones | Inquilino / Admin | 4 | Medio | 5 | **P3 (Baja)** | HU-17 | **Final** |
| **HU-23** | Alertas de Vencimiento de Cuotas | Inquilino | 3 | Bajo | 3 | **P3 (Baja)** | HU-13 | **Final** |
| **HU-24** | Alertas de Vencimiento de Contratos | Admin / Prop. | 3 | Bajo | 3 | **P3 (Baja)** | HU-10 | **Final** |
| **HU-25** | Dashboard Ejecutivo de la Inmobiliaria | Administrador | 5 | **Alto (Métricas)**| 8 | **P2 (Media)**| HU-11, 14, 17, 19 | **Final** |
| **HU-26** | Panel de Control del Propietario | Propietario | 4 | Medio | 3 | **P3 (Baja)** | HU-08, HU-21 | **Final** |
| **HU-27** | Reportes de Recaudación y Morosidad | Administrador | 4 | Medio | 5 | **P3 (Baja)** | HU-25 | **Final** |

---

### 📦 Resumen de Esfuerzo (Story Points)
* **Alcance Cursada (Regularización):** 66 Story Points (18 Historias de Usuario - Core transaccional y seguridad).
* **Alcance Examen Final:** 38 Story Points (9 Historias de Usuario - Liquidaciones, Notificaciones, Dashboards y Reportes).
* **Esfuerzo Total del Proyecto:** 104 Story Points.

---

### 📑 Documentos de Detalle por Módulo
* [01_seguridad_y_autenticacion.md](01_seguridad_y_autenticacion.md)
* [02_gestion_usuarios.md](02_gestion_usuarios.md)
* [03_gestion_inmuebles.md](03_gestion_inmuebles.md)
* [04_contratos_alquiler.md](04_contratos_alquiler.md)
* [05_cuotas_y_mora.md](05_cuotas_y_mora.md)
* [06_pagos_y_comprobantes.md](06_pagos_y_comprobantes.md)
* [07_liquidaciones_propietarios.md](07_liquidaciones_propietarios.md)
* [08_notificaciones.md](08_notificaciones.md)
* [09_dashboard_y_reportes.md](09_dashboard_y_reportes.md)
