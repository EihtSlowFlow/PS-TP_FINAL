# Módulo 9: Dashboard y Reportes de Gestión

---

### HU-25: Dashboard Ejecutivo de la Inmobiliaria
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 5 / 5 (Visión estratégica del negocio).
* **Riesgo:** Alto (Cálculo consolidado de métricas en tiempo real).
* **Estimación:** 8 Story Points.
* **Prioridad:** P2 (Media - Alcance Final).
* **Dependencias:** HU-11, HU-14, HU-17, HU-19.
* **Descripción:** 
  > **Como** Administrador de la inmobiliaria,  
  > **quiero** acceder a un panel de control con métricas clave en tiempo real,  
  > **para** evaluar de un vistazo la ocupación del catálogo, el volumen de cobranzas, la morosidad y las obligaciones financieras pendientes.

#### Criterios de Aceptación
* **Escenario 1: Indicadores clave de rendimiento (KPIs) en Dashboard**
  * **Dado** que el Administrador inicia sesión y accede a `/admin/dashboard`,
  * **Entonces** el controlador calcula y pasa en `$data` a la vista los siguientes indicadores para renderizar en tarjetas y gráficos:
    1. **Porcentaje de ocupación del catálogo:**  
       $$\text{Ocupación} = \left(\frac{\text{Inmuebles Alquilados}}{\text{Inmuebles Totales Activos}}\right) \times 100$$
    2. **Monto total recaudado en el mes:** Suma de pagos aprobados en el mes en curso.
    3. **Índice y monto de morosidad:** Monto total en cuotas con estado "Vencida" y cantidad de inquilinos en mora sobre el total de inquilinos activos.
    4. **Saldo pendiente de liquidación a propietarios:** Suma de montos netos devengados por cuotas cobradas aún no transferidas a los propietarios.
  * **Y** la interfaz presenta un diseño de Admin Template limpio, moderno y adaptable a pantallas móviles.

---

### HU-26: Panel de Control y Resumen Patrimonial del Propietario
* **Rol:** Propietario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 3 Story Points.
* **Prioridad:** P3 (Baja - Alcance Final).
* **Dependencias:** HU-08, HU-21.
* **Descripción:** 
  > **Como** Propietario de uno o más inmuebles,  
  > **quiero** ver un resumen consolidado de mis propiedades en mi portal,  
  > **para** conocer rápidamente cuántas están alquiladas y el acumulado de ingresos recibidos.

#### Criterios de Aceptación
* **Escenario 1: Métricas de mis propiedades**
  * **Dado** que el Propietario ingresa a `/propietario/dashboard`,
  * **Entonces** visualiza:
    * Total de inmuebles registrados a su nombre,
    * Cantidad de inmuebles alquilados vs. disponibles,
    * Total acumulado acreditado en concepto de liquidaciones durante el último año,
    * Alertas de contratos próximos a vencer.

---

### HU-27: Reportes de Recaudación y Morosidad
* **Rol:** Administrador / Empleado Inmobiliario.
* **Alcance:** Examen Final.
* **Valor para el PO:** 4 / 5.
* **Riesgo:** Medio.
* **Estimación:** 5 Story Points.
* **Prioridad:** P3 (Baja - Alcance Final).
* **Dependencias:** HU-25.
* **Descripción:** 
  > **Como** Administrador,  
  > **quiero** generar y exportar listados de cobranzas, morosidad y contratos,  
  > **para** respaldar reuniones de auditoría, balances contables y control operativo.

#### Criterios de Aceptación
* **Escenario 1: Filtro y vista de informe de morosos**
  * **Dado** que el Administrador accede a `/admin/reportes/morosidad`,
  * **Cuando** aplica filtros por rango de fechas o días de atraso,
  * **Entonces** la tabla muestra los inquilinos con deuda, dirección del inmueble, cuotas impagas, recargos calculados y teléfono de contacto para reclamo,
  * **Y** dispone de un botón "Imprimir / Exportar" con CSS `@media print` optimizado para impresión limpia sin elementos de navegación innecesarios.
