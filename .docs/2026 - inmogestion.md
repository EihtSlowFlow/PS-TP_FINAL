Trabajo entregable

# “InmoGestión 2026”

# Plataforma Integral de Gestión Inmobiliaria y Administración

# de Alquileres

### Contexto y Descripción General

El sector inmobiliario enfrenta cotidianamente una alta complejidad operativa derivada

de la administración descentralizada de propiedades, el seguimiento de contratos

locativos y el control de flujos financieros mensuales. En las inmobiliarias tradicionales,

la gestión manual de cobros de alquileres, la verificación de comprobantes de pago, el

cálculo de comisiones administrativas y la posterior liquidación a los propietarios

genera frecuentes inconsistencias contables, demoras en las acreditaciones y falta de

transparencia hacia los clientes.

Para dar respuesta a esta problemática, la empresa “Nico Tech” requiere el diseño e

implementación de una plataforma web integral denominada “InmoGestión 2026”. El

sistema actuará como un portal centralizado que conectará a la administración de la

inmobiliaria con los propietarios de inmuebles, los inquilinos y el público general en

busca de propiedades.

El objetivo central del software es digitalizar todo el ciclo de vida del alquiler: desde la

publicación y catalogación del inmueble, el registro formal del contrato locativo, la

emisión automática y mensual de cuotas de alquiler, la recepción y validación de pagos

presentados por los inquilinos, hasta la liquidación final de haberes a los propietarios

con el correspondiente descuento de la comisión inmobiliaria.

  - 
  - 

● Dashboard y Panel de Control : Visualización de métricas clave del negocio,

tales como porcentaje de ocupación del catálogo, monto total recaudado en el

mes, índice de morosidad de inquilinos y saldo pendiente de liquidación a

propietarios.

#### Funcionalidades del Propietario

● Registro en el Sistema y Perfil: Habilidad para completar el registro inicial y

administrar su información personal y de contacto.

● Consulta de Propiedades y Contratos: Listado detallado de sus inmuebles

registrados en la inmobiliaria, identificando cuáles se encuentran alquilados, el

contrato locativo asociado y los datos de contacto del inquilino.

● Consulta de Liquidaciones y Pagos Recibidos: Acceso al historial de

liquidaciones emitidas por la inmobiliaria, indicando fecha de acreditación, monto

bruto cobrado, desglose de comisión descontada y monto neto transferido a su

cuenta bancaria.

#### Funcionalidades del Inquilino

● Registro en el Sistema y Perfil: Creación de cuenta y gestión de sus datos

personales.

● Estado de Cuenta y Detalle de Cuotas: Acceso a su panel privado para consultar

las cuotas de alquiler emitidas por la inmobiliaria, distinguiendo entre cuotas

pagadas, cuotas pendientes del mes en curso y cuotas vencidas con indicación

de recargos por mora.

● Registro de Pago (Carga de Comprobantes): Formulario interactivo que permite

al inquilino informarle a la inmobiliaria un pago realizado. Se deberá ingresar:

Cuota a abonar, Monto transferido, Fecha de la operación, Medio de pago

(Transferencia bancaria, Depósito, Pasarela digital), Número de comprobante o

transacción y adjunto digital del comprobante (PDF o imagen).

### Roles del Sistema

Para garantizar el control de acceso y la seguridad de la información, la aplicación

definirá cuatro roles con permisos diferenciados:

1. Administrador / Empleado Inmobiliario: Usuario interno responsable de gestionar

el catálogo global de propiedades, registrar a los clientes, confeccionar los

contratos de alquiler, supervisar la cobranza mensual y autorizar las

liquidaciones financieras a los propietarios.

2. Propietario: Usuario externo titular de una o más propiedades en alquiler, con

acceso a un portal privado para consultar el estado de ocupación de sus

inmuebles, revisar los contratos vigentes y auditar las liquidaciones de alquileres

recibidas.

3. Inquilino: Usuario externo locatario de un inmueble, habilitado para acceder a su

estado de cuenta mensual, consultar el histórico de cuotas abonadas y realizar

el registro formal de sus pagos adjuntando comprobantes de transferencia o

depósito.

### Régimen de Aprobación

El trabajo práctico está estructurado en dos hitos de entrega evaluativos con requisitos

funcionales acumulativos:

## Matriz de Requisitos (Cursada vs. Final)

Para brindar máxima claridad sobre los alcances de cada etapa, la siguiente tabla

resume los módulos requeridos para la regularización de la cursada frente a los

exigidos para el examen final:

### Para la aprobación de la cursada se deberán implementar las siguientes

### funcionalidades

● Seguridad y Login de Usuarios: Autenticación, manejo de sesión y permisos.

● ABMC de Personas / Usuarios: Administrador registra usuarios

● ABMC de Inmuebles: Asignación de propietario y estado

● ABMC de Contratos de Alquiler: Alta de contrato y vinculación

● Emisión de Cuotas y Recargos de Mora: Generación masiva y cálculo de

mora

● Módulo de Registro de Pago (Inquilino): Carga de comprobante y monto

### Para la aprobación final de la materia deberá implementarse las siguientes

### funcionalidades

● ABMC de Personas / Usuarios: Gestión completa e historial

● Gestión de Notificaciones: El sistema deberá mostrar las notificaciones de

pagos, próximos vencimientos de cuotas y de contratos

● Liquidación Financiera a Propietarios: Cálculo automático de comisión

● Dashboard y Reportes de Gestión: Métricas de ocupación y morosidad.

Sobre la implementación

El sistema se desarrollará usando HTML5, Javascript, PHP y base de datos MySQL y deberá cumplir con las especificaciones de la W3C HTML 5 y CSS3. Se debe desarrollar respetando el código según los lineamientos planteados en codeigniter.

Todo formulario debe respetar un nivel estándar de usabilidad, proporcionando las indicaciones correspondientes para cada caso, también se deberán realizar las validaciones correspondientes para los campos requeridos y con formatos especiales.

Para mejorar la usabilidad del sistema se pueden utilizar complementos como JQuery, Bootstrap, AJAX, etc.

### Entrega

La entrega consiste en:

● Una Presentación grupal del desarrollo del proyecto, deben contar y mostrar todo el proceso de desarrollo del Proyecto de Software. ● Una demo del proyecto funcionando, con las funcionalidades especificadas para la etapa correspondiente. ● La entrega del código fuente con scripts de bd en un archivo comprimido.

La entrega final del trabajo será la segunda semana (17 al 24) de Noviembre de 2026, en esta etapa se debe cumplir con todas las funcionalidades para aprobar la cursada . En caso de no cumplir con la entrega en la fecha estipulada, el grupo deberá rendir un coloquio sobre un tema a asignar por la cátedra.