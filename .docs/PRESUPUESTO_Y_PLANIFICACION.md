# Presupuesto y Planificación del Proyecto
## InmoGestión 2026 - Nico Tech
### Asignatura: Proyecto de Software (UNRN)

Este documento formaliza la planificación ágil, el análisis del **Triángulo de Calidad del Proyecto** y el **Presupuesto Económico en Pesos Argentinos (ARS)** solicitado por la cátedra para el desarrollo de la plataforma integral inmobiliaria "InmoGestión 2026".

---

## 1. El Triángulo de Calidad del Proyecto

Conforme a lo desarrollado en clase, la **Calidad** de un proyecto de software es el resultado del balance estricto entre tres restricciones fundamentales:

```text
                  ALCANCE (Scope)
                        ▲
                       / \
                      /   \
                     /  ★  \  CALIDAD
                    /       \
                   /_________\
   TIEMPO (Schedule)         COSTO (Budget en ARS)
```

### Reglas de Gestión de Restricciones:
* **Ampliación de Alcance:** Si el cliente solicita más funcionalidades dentro del mismo plazo y presupuesto, **la calidad cae drásticamente** (deuda técnica, falta de testing, validaciones apresuradas).
* **Reducción de Tiempo:** Si se adelanta la fecha de entrega manteniendo alcance y costo, se incrementa el riesgo de bugs y código no mantenible.
* **Reducción de Costo:** Menor presupuesto implica menos horas dedicadas a diseño de arquitectura, seguridad y refactorización.

Para **InmoGestión 2026**, se fijan con precisión el **Alcance** y el **Cronograma** para garantizar la máxima calidad técnica: arquitectura MVC limpia en CodeIgniter 4, *Soft Delete*, contraseñas seguras con BCRYPT y cumplimiento de estándares W3C.

---

## 2. Definición del Alcance y Esfuerzo Estimado

Conforme al relevamiento, a la matriz de objetivos funcionales (V.E.R.) y a la incorporación de servicios de post-lanzamiento, el desarrollo se dimensiona con un **tope presupuestario de $ 1.500.000 ARS** a una tarifa de **$ 18.000 ARS / hora**:

| Fase / Hito | Módulos y Servicios Requeridos | Story Points (SP) | Horas-Hombre (HH) | Presupuesto Subtotal |
| :--- | :--- | :---: | :---: | :---: |
| **Fase 1: Cursada (Regularización)** | Seguridad/Usuarios, Inmuebles, Contratos, Cobranzas y Registro de Pagos | **33 SP** | **50,0 HH** | **$ 900.000 ARS** |
| **Fase 2: Examen Final** | Liquidaciones a Propietarios, Dashboard Directivo y Consultas Contables | **9 SP** | **11,0 HH** | **$ 198.000 ARS** |
| **Fase 3: Servicios Post-Entrega** | Garantía Operativa 60d, Capacitación, Mantenimiento y Backups de BD | — | **22,33 HH** | **$ 402.000 ARS** |
| **TOTAL GENERAL CONSOLIDADO** | **Plataforma Integral InmoGestión 2026** | **42 SP** | **83,33 HH** | **$ 1.500.000 ARS** |

---

## 3. Planificación Temporal y Cronograma de Sprints

El proyecto se estructura en **3 Sprints de Cursada**, **2 Sprints de Cierre para el Examen Final** y un período de **Soporte y Garantía Post-Lanzamiento (60 días)**:

```text
[Sprint 1] ───► [Sprint 2] ───► [Sprint 3] ══════════════► [Sprint 4 y 5] ══════════════► [FASE POST-ENTREGA] ═══► CIERRE
 Seguridad y     Inmuebles y     Cobranzas, Pagos ★ ENTREGA  Liquidaciones, Dashboards  ★ EXAMEN  Garantía 60 días,      ★ TOPE
 Personas        Contratos       y Verificación    CURSADA   y Reportes Directivos       FINAL    Capacitación y Backups $ 1.5M
 (11 SP - 16 HH) (10 SP - 15 HH) (12 SP - 19 HH)  (Noviembre)(9 SP - 11 HH)             (Diciembre)(22,33 HH)
```

### Detalle de Fases y Sprints:
* **Sprint 1 (Semanas 1 y 2 - 11 SP | 16,5 HH):**
  * Seguridad, login con BCRYPT, filtros de sesión y rol (`AuthFilter`, `RoleFilter`).
  * Mecanismo de restablecimiento de contraseña.
  * ABMC de usuarios / personas con *Soft Delete* y perfil del propietario/inquilino.
  * *Entregable:* Padrón de usuarios activo y portal de acceso protegido.

* **Sprint 2 (Semanas 3 y 4 - 10 SP | 15,0 HH):**
  * ABMC de inmuebles con catálogo, filtros por disponibilidad y asignación de propietario.
  * ABMC de contratos de alquiler: vinculación formal Inmueble + Inquilino + Propietario, canon locativo y comisiones.
  * *Entregable:* Asignación de propiedades y contratos vigentes.

* **Sprint 3 (Semanas 5 y 6 - 12 SP | 18,5 HH) — [HITO CURSADA]:**
  * Emisión masiva o individual de cuotas mensuales y cálculo automático de recargos por mora.
  * Portal del inquilino: estado de cuenta y formulario de carga de comprobantes de pago (PDF/JPG).
  * Panel administrativo de verificación, conciliación y aprobación/rechazo de pagos.
  * *Entregable:* **Demo funcional completa para la aprobación de la cursada (33 SP | 50,0 HH | $ 900.000 ARS).**

* **Sprints 4 y 5 (Semanas 7 a 10 - 9 SP | 11,0 HH) — [HITO EXAMEN FINAL]:**
  * Módulo de liquidaciones automáticas a propietarios con deducción de comisión.
  * Dashboard directivo con KPIs de ocupación y morosidad; balances patrimoniales del propietario.
  * *Entregable:* **Defensa y aprobación del Examen Final.**

* **Fase Post-Entrega (60 días post-lanzamiento - 22,33 HH):**
  * Mesa de ayuda, resolución de incidencias en garantía (10,0 HH).
  * Jornadas de capacitación y entrega de manual operativo de usuario (5,0 HH).
  * Mantenimiento preventivo, optimización LAMP y verificación de logs (4,0 HH).
  * Puesta en marcha de políticas de resguardo y backup diario de MySQL (3,33 HH).

---

## 4. Presupuesto Económico Detallado (Tope: $ 1.500.000 ARS)

* **Tarifa Profesional por Hora:** **$ 18.000 ARS / hora**.

### Desglose Consolidado:

| Concepto / Módulo | Story Points (SP) | Horas-Hombre (HH) | Presupuesto Subtotal |
| :--- | :---: | :---: | :---: |
| **Subtotal Gestión Administrativa (Fase 1)** | 16 SP | 24,0 HH | $ 432.000 |
| **Subtotal Funcionalidades Propietario (Fase 1)** | 5 SP | 7,5 HH | $ 135.000 |
| **Subtotal Funcionalidades Inquilino (Fase 1)** | 12 SP | 18,5 HH | $ 333.000 |
| **SUBTOTAL FASE 1 (CURSADA REGULARIZADA):** | **33 SP** | **50,0 HH** | **$ 900.000** |
| | | | |
| **SUBTOTAL FASE 2 (MÓDULOS EXAMEN FINAL):** | **9 SP** | **11,0 HH** | **$ 198.000** |
| | | | |
| • Garantía Operativa y Soporte Correctivo (60 días) | — | 10,0 HH | $ 180.000 |
| • Capacitación y Manual de Usuario Inmobiliario | — | 5,0 HH | $ 90.000 |
| • Mantenimiento Preventivo y Monitoreo LAMP | — | 4,0 HH | $ 72.000 |
| • Plan de Backups Automatizados y Recuperación BD | — | 3,33 HH | $ 60.000 |
| **SUBTOTAL FASE 3 (SERVICIOS POST-ENTREGA):** | — | **22,33 HH** | **$ 402.000** |
| | | | |
| **TOTAL GENERAL CONSOLIDADO:** | **42 SP** | **83,33 HH** | **$ 1.500.000 ARS** |

---

### Esquema de Facturación y Condiciones Comerciales:
1. **Anticipo de Inicio (Kick-off del proyecto):** 20% = **$ 300.000 ARS**.
2. **Segundo Pago contra entrega de Cursada Regularizada (Fase 1 aprobada):** 40% = **$ 600.000 ARS**.
3. **Tercer Pago contra entrega y defensa del Examen Final (Fase 2 aprobada):** 20% = **$ 300.000 ARS**.
4. **Pago Final de Cierre e inicio de Servicios Post-Entrega (Fase 3 activada):** 20% = **$ 300.000 ARS**.

---

## 5. Matriz de Riesgos y Planes de Contingencia

| Riesgo Detectado | Probabilidad | Impacto | Plan de Mitigación / Contingencia |
| :--- | :---: | :---: | :--- |
| **Riesgo de Flujo por Seguridad tardía** | Alta | Crítico | Se implementan `AuthFilter` y `RoleFilter` en el Sprint 1 antes de programar los CRUDs. |
| **Inconsistencias en cálculo de mora** | Media | Alto | Pruebas unitarias sobre la función de mora con fechas relativas (`DATEDIFF`) y días límite fijos. |
| **Vulnerabilidades en subida de comprobantes** | Media | Alto | Validación de tipo MIME real, tamaño máximo (5MB) y almacenamiento en directorio no público `writable/uploads/`. |
| **Ruptura de integridad histórica** | Baja | Crítico | Prohibición de borrado físico (`DELETE`); uso mandatorio de `$useSoftDeletes` y `deleted_at`. |
