# Reunión del equipo del 6/11/2026 — Revisión de la documentación, la base de datos y el plan de entrega

> Reunión de Discord en la que el equipo revisó los documentos, los prototipos y la base de datos del proyecto, y organizó el tiempo que quedaba hasta la entrega. Cómo quedó cada tema en el repositorio está en la sección 11 de [`ORGANIZACION_DEL_PROYECTO.md`](../ORGANIZACION_DEL_PROYECTO.md).

## 1. Revisión de la documentación

- **Cifras de estimación.** Se compararon tres totales que aparecían en documentos distintos (42, 81 y 104 puntos). Se acordó que el correcto es el del análisis de requerimientos: **42 puntos** (33 de cursada y 9 del final), **83,33 horas** y **$1.500.000**. Los otros totales son del backlog de historias, que usa otra escala de puntos, más fina, para repartir el trabajo.
- **README de historias de usuario.** No incluía HU-03 y decía 27 historias cuando había 28 archivos.
- **Roles.** El enunciado habla de cuatro roles pero describe solo tres, porque junta al administrador con el empleado. El análisis los separa en cuatro: Administrador, Empleado, Propietario e Inquilino.
- **Prototipos.** Se comprobó que las pantallas coinciden con lo definido. Al actualizar los roles faltaban pantallas, y se pidió generarlas con Stitch a partir de nuevos prompts.
- **Estilo visual.** Los colores y el estilo se tomaron de los prototipos. Se decidió no cambiarlos por ahora y priorizar primero la base de datos.
- **Tecnología.** El README pasó de PHP 7.4 a PHP 8.2.

## 2. Base de datos

- La base se construye con las **migraciones** de CodeIgniter, una por tabla. Para entenderla mejor se pidió además un **esquema en SQL y datos de carga para MySQL Workbench**.
- Se repasaron las tablas y sus nombres en inglés: `roles`, `users`, `contracts` (contratos), `installments` (cuotas), `payments` y `payment_receipts` (pagos y comprobantes), `settlements` (liquidaciones, con una tabla intermedia que las relaciona con las cuotas), `password_resets` (restablecimiento de contraseñas) y `notifications`.
- La base quedó **creada y verificada** con 12 tablas (las 11 del modelo más la tabla de migraciones de CodeIgniter), 16 claves foráneas, 24 restricciones CHECK, los roles y el administrador inicial.
- Se notó que **faltaba el empleado** entre los usuarios de prueba. Se actualizó el seeder de roles y se agregó un empleado.
- Las pruebas de integridad comprobaron que la base rechaza datos inválidos y la suite completa pasó.
- Pasos para probarla: configurar la contraseña de la base en el `.env`, instalar las dependencias, crear la base de pruebas y correr `composer test`. En una de las computadoras no había Composer ni PHPUnit (se usa WAMP), así que había que instalarlos.
- La base se trabajó en una **rama nueva** (`feature/base-de-datos`), sin pull request todavía: faltaban las pruebas de integridad y el pull request para pasar la tarjeta a revisión.
- **Limitación:** con datos de prueba todavía no se puede ver nada en pantalla. Para probar la base hacen falta las pantallas que la manipulen (las del administrador, el empleado y los demás roles).

## 3. Plan de entrega

- Se contaron **19 historias** de la cursada y unos **19 días** hasta el 24 de noviembre, que se mencionó como fecha de entrega "o antes". Hacer una historia por día no era realista.
- Se fijó una **meta mínima para el primer sprint**: al menos **7 historias** para el domingo siguiente. En la reunión se las nombró "1 a 7", que probablemente corresponden a HU-01 a HU-07. Si el tiempo lo permite, se suman más.
- Se priorizó empezar por la **seguridad** (login y control de acceso).
- **HU-03 (cierre de sesión seguro)** se consideró de tamaño extra chico: alcanza con destruir la sesión y ajustar los segundos de duración.
- Los issues de la Fase 2 (las 9 historias del final) todavía no estaban creados; solo existían los de la cursada.

## Pendientes al cierre de la reunión

- Programar las pantallas para poder probar la base de datos con los distintos roles.
- Terminar las pruebas de integridad y abrir el pull request de la base de datos.
- Precisar qué historias entran en el primer sprint y la fecha exacta de su cierre.
- Crear los issues de la Fase 2 cuando corresponda.
