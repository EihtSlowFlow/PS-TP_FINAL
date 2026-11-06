# InmoGestión 2026

**Proyecto final** del ramo de Desarrollo de Software – gestión integral de inmuebles, usuarios y contratos de alquiler.

## Tecnologías
- **Backend:** PHP ≥ 7.4 con **CodeIgniter 4** (MVC). 
- **Base de datos:** MySQL (driver MySQLi). 
- **Frontend:** HTML5, CSS3 (responsive) y JavaScript ES6 vanilla.
- **Infraestructura:** LAMP/WAMP (Apache, PHP, MySQL) en entorno local.

## Puesta en marcha de la base de datos

Requiere MySQL 8.0.16 o superior, o MariaDB (probado en MySQL 8.4 y MariaDB 11.4), con una cuenta que pueda crear bases de datos. Los comandos se ejecutan en la raíz del proyecto.

1. Copiar `.env.example` a `.env` y completar el usuario y la clave de la base (`database.default.*`) y los datos de los seeders (`seed.adminEmail`, `seed.adminPassword`, `seed.demoPassword`). El `.env` no se versiona.
2. Crear la base: `php spark db:create inmogestion2026`
3. Crear las tablas: `php spark migrate`
4. Cargar los roles y el administrador inicial: `php spark db:seed DatabaseSeeder`
5. Opcional, datos de prueba para desarrollo: `php spark db:seed DemoSeeder`

Otros comandos útiles:

- Revertir todo: `php spark migrate:rollback`
- Rehacer todo desde cero: `php spark migrate:refresh` y después los seeders de los pasos 4 y 5
- Ver el estado: `php spark migrate:status`

En Windows con WAMP, `php` suele no estar en el PATH. Se puede usar la ruta completa del PHP de WAMP, por ejemplo `C:\wamp64\bin\php\php8.2.29\php.exe spark migrate`, o agregarla al PATH.

Las pruebas de integridad (`composer test`) usan una base vacía y descartable distinta de la de desarrollo, definida en `database.tests.*`. Requieren `composer install` para instalar PHPUnit.

El diagrama, el modelo relacional, las decisiones, el esquema en SQL y los datos de prueba para MySQL Workbench están en [`.docs/base_de_datos/`](.docs/base_de_datos/MODELO_DE_DATOS.md).

## Alcance y módulos principales
- Seguridad y autenticación (BCRYPT, filtros de sesión/rol). 
- ABM de usuarios, inmobiliarias, contratos y pagos. 
- Generación automática de cuotas y recargos por mora. 
- Portal de inquilinos con carga de comprobantes. 
- Dashboard administrativo y reportes KPI. 
- Soporte y garantía post‑entrega (60 días).

## Cronograma resumido
- **Sprint 1 (Semana 1):** Seguridad, usuarios, inmuebles y contratos. 
- **Sprint 2 (Semana 2):** Cuotas, moras, portal de inquilinos, validación de pagos y demo cursada. 
- **Fase 2 (Examen final):** Liquidaciones, notificaciones y dashboard directivo. 
- **Fase 3 (Post‑entrega):** Mesa de ayuda, capacitación y mantenimiento.

## Presupuesto y tarifa
- **Carga horaria total estimada:** **83,33 HH** (Horas-Hombre / 42 SP):
  - **Fase 1 (Cursada regularizada):** 50,0 HH
  - **Fase 2 (Examen final):** 11,0 HH
  - **Fase 3 (Servicios post-entrega):** 22,33 HH
- **Tarifa profesional:** **$ 18.000 ARS / hora** (según *PRESUPUESTO_Y_PLANIFICACION.md*). 
- **Presupuesto total límite:** **$ 1.500.000 ARS**.
- El desglose de horas y conceptos está detallado en el documento de planificación (Sujeto a cambios por el cliente).

## Servicios post‑entrega
- Mesa de ayuda y resolución de incidencias (10 HH). 
- Capacitación y entrega de manual operativo (5 HH). 
- Mantenimiento preventivo y optimización LAMP (4 HH). 
- Back‑up diario de MySQL y políticas de resguardo (3,33 HH).

## Documentación
- [Planificación y presupuesto](.docs/PRESUPUESTO_Y_PLANIFICACION.md) 
- [Análisis de requerimientos](.docs/[BORRADOR]ANALISIS_DE_REQUERIMIENTOS.md) 
- [Historias de usuario](.docs/historias_de_usuario/)
- [Modelo de datos y base de datos](.docs/base_de_datos/MODELO_DE_DATOS.md)
