# Organización del proyecto — InmoGestión 2026

> Última actualización: 09/11/2026. Este documento resume cómo está organizado el trabajo del equipo: quién hace qué, en qué orden, con qué convenciones y qué falta decidir. La fuente de cada dato se indica entre paréntesis. Si algo cambia en GitHub, hay que actualizarlo acá.

## Índice

1. [Resumen del proyecto](#1-resumen-del-proyecto)
2. [Equipo](#2-equipo)
3. [Repositorio y flujo de trabajo](#3-repositorio-y-flujo-de-trabajo)
4. [Mapa de la documentación](#4-mapa-de-la-documentación)
5. [Tablero de GitHub (Proyecto PS)](#5-tablero-de-github-proyecto-ps)
6. [Etiquetas](#6-etiquetas)
7. [Backlog de issues](#7-backlog-de-issues)
8. [Base de datos (issue #22)](#8-base-de-datos-issue-22)
9. [Reparto del trabajo](#9-reparto-del-trabajo)
10. [Orden cronológico](#10-orden-cronológico)
11. [Decisiones pendientes](#11-decisiones-pendientes)
12. [Observaciones sobre el repositorio](#12-observaciones-sobre-el-repositorio)
13. [Registro de lo organizado hasta ahora](#13-registro-de-lo-organizado-hasta-ahora)

---

## 1. Resumen del proyecto

**InmoGestión 2026** es el trabajo final de Proyecto de Software (UNRN): una plataforma web para gestionar inmuebles, usuarios, contratos de alquiler, cuotas, pagos y liquidaciones a propietarios.

| Tema | Detalle |
|---|---|
| Backend | PHP `^8.2` con CodeIgniter 4 (MVC), según `composer.json` |
| Base de datos | MySQL/MariaDB (driver MySQLi), `utf8mb4` |
| Frontend | HTML5, CSS3 responsive y JavaScript ES6 sin frameworks |
| Servidor | Apache (LAMP/WAMP local) |
| Roles del sistema | Administrador, Empleado, Propietario, Inquilino |

### Fases y sprints

Según el [análisis de requerimientos](ANALISIS_DE_REQUERIMIENTOS.md) y el [README](../README.md):

| Fase | Contenido | Historias |
|---|---|---|
| **Sprint 1 (semana 1)** | Seguridad, usuarios, inmuebles y contratos | HU-01 a HU-12 |
| **Sprint 2 (semana 2)** | Cuotas, mora, portal del inquilino, pagos, integración y demo | HU-13 a HU-18 |
| **Fase 2 (examen final)** | Liquidaciones, notificaciones, dashboard y reportes | HU-19 a HU-27 (todavía sin issues) |
| **Fase 3 (post-entrega)** | Garantía de 60 días, capacitación, mantenimiento y backups | — |

Presupuesto del documento de requerimientos: 83,33 horas-hombre (42 puntos) a $18.000 ARS por hora, con un tope de $1.500.000 ARS (de los cuales 50 horas y $900.000 corresponden a la cursada). Es la estimación vigente. Los criterios, las restricciones y las inconsistencias de la estimación están en [`PRESUPUESTO_Y_PLANIFICACION.md`](PRESUPUESTO_Y_PLANIFICACION.md). Los puntos del backlog de la sección 7 (73 de la cursada y 41 del final) son otra escala, más fina, para repartir el trabajo; la correspondencia entre ambas está en el [README de historias](historias_de_usuario/README.md).

**Entrega:** segunda semana de noviembre de 2026 (del 17 al 24), según el enunciado.

---

## 2. Equipo

El equipo son tres integrantes. Todos tienen permisos sobre el repositorio y el tablero.

| Usuario de GitHub | Observaciones |
|---|---|
| `EihtSlowFlow` | Dueño del repositorio y del tablero. Es el único en `CODEOWNERS` (`* @EihtSlowFlow`) |
| `joaquincorbalan` | Colaborador. Abrió el issue de base de datos |
| `LauPerisse` | Colaborador |

---

## 3. Repositorio y flujo de trabajo

Repositorio: <https://github.com/EihtSlowFlow/PS-TP_FINAL>

### Reglas vigentes

- **`main` está protegida:** GitHub rechaza los push directos con el mensaje "Changes must be made through a pull request". Todo cambio entra por pull request.
- **`CODEOWNERS`** asigna a `EihtSlowFlow` como revisor de todo el repositorio.
- **`.env` ya no se versiona.** Se quitó del índice de git y se agregó al `.gitignore`. Entró en `main` con el pull request #2.
- El `.env` anterior **sigue en el historial** de commits previos. Si tenía contraseñas o claves reales, conviene rotarlas.
- El `.env.example` entra con el pull request de la base de datos (rama `feature/base-de-datos`, issue #22). Hasta que se mergee, cada integrante arma su `.env` a mano; después alcanza con copiarlo y completarlo.

### Flujo sugerido

Esto es una propuesta, no una regla ya acordada:

1. Actualizar `main` (`git pull`).
2. Crear una rama por historia, por ejemplo `feature/hu-04-gestion-usuarios`.
3. Hacer commits chicos con mensajes del estilo que ya usa el repo (`docs:`, `chore:`, `refactor:`).
4. Abrir un pull request a `main` y pedir revisión.
5. Al mergear, cerrar el issue y mover la tarjeta a *Done* en el tablero.

### Estado de las ramas

| Rama | Estado |
|---|---|
| `main` | Incluye el pull request #2 (`.gitignore` con `.env` y los prototipos de pantallas) |
| `prototipos-pantalla` | Ya mergeada a `main`. Se puede borrar |

### Convenciones de código

Salen de [`.agents/AGENTS.md`](../.agents/AGENTS.md):

- Identificadores y comentarios del código en **inglés**.
- Seguir las convenciones de CodeIgniter 4: controladores que coordinan, modelos para consultas, filtros para autenticación y roles, migraciones y seeders para el esquema.
- Validar todo en el servidor. La validación del navegador no alcanza.
- Verificar la autorización de cada operación protegida en el servidor.
- No agregar frameworks, dependencias ni capas nuevas sin una necesidad demostrada.
- Nunca usar la base de producción para pruebas. Las pruebas usan un grupo de conexión propio y datos descartables.
- Las contraseñas se guardan cifradas con BCRYPT. No hay credenciales reales en el código, los ejemplos ni la documentación.

---

## 4. Mapa de la documentación

| Ruta | Contenido |
|---|---|
| [`README.md`](../README.md) | Descripción general, tecnologías, cronograma y presupuesto |
| [`.docs/ANALISIS_DE_REQUERIMIENTOS.md`](ANALISIS_DE_REQUERIMIENTOS.md) | **Fuente de verdad del alcance.** Alcance, roles, requerimientos RF/RNF, riesgos, sprints y presupuesto; la sección 9 fija el glosario de estados y los valores que el análisis deja abiertos |
| [`.docs/PRESUPUESTO_Y_PLANIFICACION.md`](PRESUPUESTO_Y_PLANIFICACION.md) | Cómo se llegó a la estimación: método, restricciones de tiempo y capacidad, tarifa, alcance, inconsistencias detectadas y decisiones pendientes |
| [`.docs/REGISTRO_DE_TIEMPOS.md`](REGISTRO_DE_TIEMPOS.md) | Cuánto se tardó en hacer cada issue (hoy #2, #22, #19, #12 y #15), cómo se calcula y cómo registrar el tiempo de los que vienen |
| [`.docs/documentacion/`](documentacion/) | Notas de las reuniones del equipo. Hoy: la reunión de Discord del 29/10 sobre estimación y presupuesto y la del 6/11 sobre la revisión de la documentación, la base de datos y el plan de entrega |
| [`.docs/base_de_datos/`](base_de_datos/MODELO_DE_DATOS.md) | Base de datos: DER, modelo relacional, decisiones resueltas, esquema en SQL para MySQL Workbench y cómo usarlos. La fuente de verdad son las migraciones de `app/Database/Migrations/` |
| [`.docs/2026 - inmogestion.md`](2026%20-%20inmogestion.md) | Enunciado del trabajo y tabla de alcances por etapa |
| [`.docs/GUIA TP-FINAL.md`](GUIA%20TP-FINAL.md) | Índice que pide la cátedra para el informe (incluye DER y modelo relacional en la sección 3.3) |
| [`.docs/TarjetasCRC.md`](TarjetasCRC.md) | Tarjetas CRC por módulo: usuarios, propiedades y contratos, cuotas y mora, pagos, liquidaciones, notificaciones |
| [`.docs/historias_de_usuario/`](historias_de_usuario/) | Una HU por archivo con escenarios, reglas y Definition of Done. El [`README`](historias_de_usuario/README.md) trae el backlog priorizado y su correspondencia con el presupuesto del análisis |
| [`.docs/base_de_datos/`](base_de_datos/MODELO_DE_DATOS.md) | Modelo de datos (DER, modelo relacional, decisiones del issue #22) y el esquema en SQL para MySQL Workbench |
| [`.docs/exec-plans/`](exec-plans/) | Plantilla de traspaso (`HANDOFF_TEMPLATE.md`) y un handoff por tarea (`BD-22_handoff.md`, `HU-01_handoff.md`, `HU-04_handoff.md`, `HU-05_handoff.md`). [`REVIEW-HU-01-04-05_handoff.md`](exec-plans/REVIEW-HU-01-04-05_handoff.md) documenta el review de esas tres historias y sus correcciones |
| [`.docs/prototipos_pantallas/`](prototipos_pantallas/) | Prototipos de pantallas (`code.html` y `screen.png` por pantalla), `DESIGN.md` y los prompts usados para generarlos (`PROMPTS_STITCH_PROTOTIPOS.md`, que mapea cada pantalla a su HU y su ruta) |
| [`.agents/`](../.agents/) | Reglas para asistentes de IA: `AGENTS.md`, glosario del dominio y skills por tecnología |
| `.context/` | Transcripciones de las clases (un `.txt` por clase) |
| `.sprint2_referencia/` | Código y dump SQL de referencia del Sprint 2 (`abm.php`, `bd.php`, `Sprint2_P_ABM.sql`) |

---

## 5. Tablero de GitHub (Proyecto PS)

Tablero: <https://github.com/users/EihtSlowFlow/projects/9>

### Vistas

Backlog (tablero por estado), Priority board, Team items (tabla agrupada por persona), Roadmap y My items.

### Estados

`Backlog` → `Ready` → `In progress` → `In review` → `Done`.

### Campos y convenciones

| Campo | Valores | Cómo se carga |
|---|---|---|
| **Priority** | P0, P1, P2 | La prioridad de cada HU viene del backlog: P1 alta, P2 media. P0 solo para la base de datos, porque bloquea todo |
| **Estimate** | número | Puntos de historia (story points) de cada HU, tomados de su documento |
| **Size** | XS, S, M, L, XL | Derivado de los puntos: 1 = XS, 2 = S, 3 = M, 5 = L, 8 = XL |
| **Start date / Target date** | fechas | Solo las tienen el issue de base de datos (29/10 y 30/10) y el pull request de prototipos (#2, 28/10 y 28/10) |

El total cargado en el tablero es de 81 puntos: 73 de las 19 historias y 8 de la base de datos.

---

## 6. Etiquetas

| Etiqueta | Uso | Issues |
|---|---|---|
| `enhancement` | Toda historia de usuario | Los 20 |
| `usuarios-seguridad` | Módulo Usuarios y Seguridad | HU-01 a HU-06 |
| `inmuebles-contratos` | Módulo Propiedades y Contratos | HU-07 a HU-12 |
| `cuotas-mora` | Módulo Financiero: cuotas, mora y estado de cuenta | HU-13 a HU-15 |
| `pagos` | Módulo Pagos y Validaciones | HU-16 a HU-18 |
| `riesgo-alto` | Historias con riesgo Alto en el backlog | HU-01, 03b, 10, 13, 16, 17 |
| `base-de-datos` | Esquema, migraciones y seeders | #22 |

El issue de base de datos también lleva las cuatro etiquetas de módulo, porque el esquema toca todos los módulos. El resto de las etiquetas de GitHub (`bug`, `question`, etc.) quedaron sin usar.

---

## 7. Backlog de issues

Cada issue tiene la historia completa (escenarios Dado/Cuando/Entonces, reglas de negocio, Definition of Done), sus dependencias como links entre issues y una sección "Por definir" con las dudas abiertas. La especificación completa sigue en [`historias_de_usuario/`](historias_de_usuario/).

| Issue | HU | Historia | Rol | Pts | Tam. | Prior. | Riesgo | Responsable | Depende de |
|---|---|---|---|---|---|---|---|---|---|
| [#22](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/22) | — | Base de datos | Todos | 8 | XL | P0 | — | Los tres, en conjunto | — |
| [#21](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/21) | HU-01 | Iniciar sesión y control de acceso | Usuario registrado | 5 | L | P1 | Alto | EihtSlowFlow | #22 |
| [#20](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/20) | HU-02 | Registro de propietarios e inquilinos | Visitante | 3 | M | P1 | Medio | joaquincorbalan | HU-01 |
| [#19](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/19) | HU-03 | Cierre de sesión | Usuario autenticado | 1 | XS | P1 | Bajo | LauPerisse | HU-01 |
| [#18](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/18) | HU-03b | Restablecer contraseña | Usuario registrado | 3 | M | P1 | Alto | EihtSlowFlow | HU-01 |
| [#17](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/17) | HU-04 | Gestión de usuarios | Administrador | 5 | L | P1 | Medio | EihtSlowFlow | HU-01 |
| [#10](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/10) | HU-05 | Baja lógica de usuarios | Administrador | 2 | S | P1 | Medio | EihtSlowFlow | HU-04 |
| [#11](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/11) | HU-06 | Gestión del perfil propio | Propietario / Inquilino | 2 | S | P2 | Bajo | joaquincorbalan | HU-02 |
| [#12](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/12) | HU-07 | Alta y edición de inmuebles | Administrador | 5 | L | P1 | Medio | LauPerisse | HU-04 |
| [#3](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/3) | HU-08 | Catálogo y filtros de inmuebles | Admin / Propietario / Público | 3 | M | P1 | Bajo | joaquincorbalan | HU-07 |
| [#13](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/13) | HU-09 | Baja lógica de inmuebles | Administrador | 2 | S | P1 | Medio | EihtSlowFlow | HU-07 |
| [#15](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/15) | HU-10 | Alta de contratos de alquiler | Administrador | 8 | XL | P1 | Alto | LauPerisse | HU-04, HU-07 |
| [#16](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/16) | HU-11 | Consulta de contratos | Administrador / Propietario | 3 | M | P1 | Medio | joaquincorbalan | HU-10 |
| [#9](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/9) | HU-12 | Finalizar o rescindir contratos | Administrador | 3 | M | P2 | Medio | joaquincorbalan | HU-10 |
| [#6](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/6) | HU-13 | Emisión masiva de cuotas | Administrador | 5 | L | P1 | Alto | LauPerisse | HU-10 |
| [#8](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/8) | HU-14 | Recargo automático por mora | Sistema / Administrador | 5 | L | P1 | Medio | LauPerisse | HU-13 |
| [#7](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/7) | HU-15 | Estado de cuenta del inquilino | Inquilino | 3 | M | P1 | Medio | joaquincorbalan | HU-13, HU-14 |
| [#5](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/5) | HU-16 | Registro de pago y comprobante | Inquilino | 8 | XL | P1 | Alto | EihtSlowFlow | HU-15 |
| [#4](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/4) | HU-17 | Verificación y aprobación de pagos | Administrador | 5 | L | P1 | Alto | joaquincorbalan | HU-16 |
| [#14](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/14) | HU-18 | Historial de pagos y comprobantes | Inquilino / Administrador | 2 | S | P2 | Bajo | joaquincorbalan | HU-17 |

Todas las historias dependen además del issue de base de datos (#22).

---

## 8. Base de datos (issue #22)

Issue: <https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/22>

- **Se hace entre los tres**, en conjunto. Cada integrante aporta 8/3 ≈ 2,7 puntos al total.
- **Estimación:** 8 puntos (tamaño XL, prioridad P0). El desglose está en el issue: DER y decisiones 2, migraciones de la Fase 1 3, migraciones de la Fase 2 1, seeders, `.env.example` y README 1, pruebas de integridad 1.
- **Contenido del issue:** 11 tablas propuestas, que quedaron con nombres en inglés (`roles`, `users`, `password_resets`, `properties`, `contracts`, `installments`, `payments`, `payment_receipts`, `settlements`, `settlement_installments`, `notifications`), una tabla que relaciona cada HU con las tablas que usa, reglas de integridad, tareas y criterios de aceptación.
- **Orden sugerido de migraciones:** primero el esquema de la Fase 1 (HU-01 a HU-18) y después `liquidaciones`, `liquidacion_cuotas` y `notificaciones`, pero con el DER completo desde el inicio.
- **Bloquea** a todas las historias que guardan datos.

---

## 9. Reparto del trabajo

El reparto busca igualar los puntos de historia entre los tres. Como 73 no se divide en 3, la mejor combinación es 25 / 24 / 24, y con la base de datos en conjunto quedan **27,7 / 26,7 / 26,7** puntos.

| Persona | Historias | Puntos de historias | Semana 1 | Semana 2 |
|---|---|---|---|---|
| **EihtSlowFlow** | HU-01, HU-04, HU-03b, HU-05, HU-09, HU-16 | 25 | 17 | 8 |
| **LauPerisse** | HU-03, HU-07, HU-10, HU-13, HU-14 | 24 | 14 | 10 |
| **joaquincorbalan** | HU-02, HU-06, HU-08, HU-11, HU-12, HU-15, HU-17, HU-18 | 24 | 14 | 10 |

Criterios usados:

- Se respetó el plan de dos sprints del proyecto.
- Se evitó mover issues de más: 11 de los 19 cambiaron de responsable.
- Cada persona tiene un tramo coherente: EihtSlowFlow lleva la seguridad y los usuarios más el registro de pagos; LauPerisse la cadena inmuebles, contratos y cuotas; joaquincorbalan las consultas de contratos y el portal del inquilino.
- EihtSlowFlow queda más liviano en la semana 2, así que le conviene llevar la integración y las pruebas de punta a punta de ese sprint.
- El reparto cuenta puntos y no distingue experiencia. Si alguien domina más un área, se pueden intercambiar issues de igual valor.

---

## 10. Orden cronológico

Dentro de cada etapa las historias se hacen en paralelo. Una historia arranca cuando está listo lo que enumera en "Depende de" (sección 7); las pantallas y los formularios se pueden preparar antes con los prototipos y datos del seeder.

| Etapa | EihtSlowFlow | LauPerisse | joaquincorbalan |
|---|---|---|---|
| **0. Base de datos** | Los tres juntos: DER y decisiones pendientes, migraciones, seeders, `.env.example` y pruebas | | |
| **1** | HU-01 | Preparar vistas desde los prototipos | Preparar vistas desde los prototipos |
| **2** | HU-04 | HU-03 y empezar HU-07 con propietarios del seeder | HU-02 |
| **3** | HU-03b y HU-05 | HU-07 | HU-06 |
| **4** | HU-09 | HU-10 | HU-08 |
| **5** | Apoyar y revisar | HU-13 | HU-11 y HU-12 |
| **6** | | HU-14 | |
| **7** | | | HU-15 |
| **8** | HU-16 | | |
| **9** | | | HU-17 |
| **10** | | | HU-18 |

La **semana 1** abarca las etapas 1 a 4 y las HU-11 y HU-12 de la etapa 5; la **semana 2**, desde la HU-13 (etapa 5) hasta la etapa 10.

Desde HU-13 la cadena es estricta: 13, 14, 15, 16, 17, 18 (28 puntos en fila). Por eso conviene que quien espera adelante pantallas, formularios y validaciones.

---

## 11. Decisiones

### Base de datos (issue #22): resueltas el 06/11/2026

Las nueve decisiones están resueltas y explicadas en la [sección 5 del modelo de datos](base_de_datos/MODELO_DE_DATOS.md#5-decisiones-del-issue-22). Están anotadas en el issue #22; falta que EihtSlowFlow y joaquincorbalan las confirmen.

| # | Tema | Resolución |
|---|---|---|
| 1 | Idioma de los nombres | Inglés (`users`, `properties`, `contract_id`) |
| 2 | Unicidad con baja lógica | Columnas generadas con índice único: email y DNI únicos solo entre usuarios activos |
| 3 | Estados | `ENUM` |
| 4 | Liquidaciones | Una por contrato y período, con las cuotas en una tabla intermedia |
| 5 | Día de vencimiento | 1 a 28 |
| 6 | Base de pruebas | Grupo `tests` en MySQL/MariaDB, no SQLite |
| 7 | Cierre de contratos | Se guardan fecha y motivo |
| 8 | Período de las cuotas | Fecha del primer día del mes |
| 9 | Propietario en `contracts` y `settlements` | Se guarda también ahí |

### Historias de usuario (secciones "Por definir" de cada issue)

| Historia | Duda abierta |
|---|---|
| HU-03b | Vigencia del token y cómo se entrega (correo o enlace en pantalla) |
| HU-04 | Resuelto: el administrador define la contraseña inicial (mínimo 8 caracteres, con un generador sugerido) y las "auditorías básicas" son las fechas de alta, última modificación y baja lógica; no se guarda quién las hizo |
| HU-05 | Resuelto: se protege al último administrador (no se puede dar de baja a sí mismo ni quitarle el rol de Administrador al único administrador activo) y la baja se confirma en el servidor. Abierto: si hay reactivación de usuarios (no está implementada) y si se puede dar de baja a quien tiene contratos vigentes (hoy se permite) |
| HU-07 | Resuelto: ciudad obligatoria; superficie y servicios incluidos opcionales. Al editar se puede pasar entre Disponible y En mantenimiento; «Alquilado» lo asigna el contrato y no se cambia a mano. Pueden usarlo el administrador y el empleado (el análisis deja la función del empleado "por definir") |
| HU-08 | Filtro por ubicación, ordenamiento y qué datos son públicos |
| HU-09 | Si hay reactivación de inmuebles |
| HU-10 | Resuelto: porcentajes de 0 a 100, día de vencimiento de 1 a 28, el propietario es el del inmueble. Abierto: cómo y cuándo se aplica el porcentaje de actualización al canon |
| HU-12 | Resuelto: se guardan fecha y motivo del cierre. Abierto: qué pasa con las cuotas pendientes |
| HU-13 | Resuelto: el período es el primer día del mes y hay emisión individual además de la masiva |
| HU-14 | Redondeo, tope del recargo y si se guarda o se recalcula |
| HU-16 | Resuelto: medios de pago transferencia, depósito y pasarela digital (etiqueta de carga manual); si el monto no coincide, el pago se registra y se marca la diferencia |

### Presupuesto y alcance

Detalle en [`PRESUPUESTO_Y_PLANIFICACION.md`](PRESUPUESTO_Y_PLANIFICACION.md) (secciones 5 y 6). Fuente: la charla del equipo del 29/10.

1. **Qué se exige el 17 al 24 de noviembre:** el enunciado dice que se deben cumplir "todas las funcionalidades para aprobar la cursada", pero su matriz separa cursada y final. Conviene confirmarlo con la cátedra.
2. **Cifra de referencia:** resuelto. La estimación vigente es la del análisis (83,33 horas y $1.500.000, sección 9.1); las 50 horas y $900.000 son el subtotal de la cursada, aunque en la charla se las trató como el total del desarrollo.
3. **Fase 2 incompleta:** resuelto. Las notificaciones forman parte del esfuerzo de la Fase 2 aunque no tengan fila propia en la tabla (análisis, sección 9.2), y la consulta de usuarios dados de baja, pedida para el final, es el escenario 5 de HU-04.
4. **Equivalencia de puntos a horas:** no es uniforme (por ejemplo, 5 SP van de 6,0 a 8,0 horas) y los puntos de las historias no se pueden convertir a horas con la escala del análisis.

### Proceso de trabajo

1. **Scrum y GitHub Projects:** la cátedra plantea Scrum como método (clase del 20/10) y el informe pide describir la metodología (sección 1.4 de la [guía](GUIA%20TP-FINAL.md)). El equipo trabaja con issues y un tablero de GitHub Projects, y en la charla se dudó de si eso alcanza para llamarlo Scrum. Falta decidir cómo se describe y qué ceremonias se usan.
2. **Sprint en el tablero:** el tablero no tiene un campo de iteración (Sprint 1 y Sprint 2). Agregarlo permitiría ver el backlog a la izquierda y el sprint actual a continuación, como se propuso en la charla.
3. **Reunión breve periódica:** se habló de la reunión diaria de 15 minutos de Scrum y uno de los integrantes dudó de su utilidad. No se decidió nada.

### Charla del 6/11: lo que se habló y cómo quedó

Fuente: [`documentacion/discord proyecto 6-11-26.md`](documentacion/discord%20proyecto%206-11-26.md). Esa reunión fue una revisión de los documentos, la base de datos y los prototipos, y un reparto del tiempo que quedaba hasta la entrega. La columna de la derecha se contrastó con el estado actual del repositorio.

| Tema | Qué se habló | Cómo está hoy en el repositorio |
|---|---|---|
| Cifras de estimación | Se compararon tres totales en documentos distintos (42, 81 y 104 puntos). Se acordó que el bueno es el del análisis de requerimientos: 42 puntos (33 de cursada y 9 del final), 83,33 horas y $1.500.000 | Es la estimación vigente ([`PRESUPUESTO_Y_PLANIFICACION.md`](PRESUPUESTO_Y_PLANIFICACION.md), sección 1). Los puntos del backlog son otra escala, explicada en el README de historias |
| README de historias | Faltaba HU-03 y decía 27 historias cuando había 28 archivos | Corregido: incluye HU-03 y suma 28 historias |
| Roles | El enunciado habla de cuatro roles pero describe tres | Cuatro roles: Administrador, Empleado, Propietario e Inquilino (nota del enunciado) |
| Prototipos | Se comprobó que las pantallas coinciden con lo definido. Al actualizar los roles faltaban pantallas y se pidió generarlas con Stitch | `PROMPTS_STITCH_PANTALLAS_FALTANTES.md` y los prototipos nuevos en `.docs/prototipos_pantallas/` |
| PHP | Se subió la versión del README de 7.4 a 8.2 | README y `composer.json` indican PHP `^8.2` |
| Base de datos | Se revisaron las migraciones (una por tabla) y se pidió además un esquema y datos de carga para MySQL Workbench. La base quedó creada y verificada con 12 tablas, 16 claves foráneas, 24 restricciones CHECK, roles y administrador | Las 11 tablas del modelo más la tabla `migrations` de CodeIgniter suman las 12; en el esquema SQL se cuentan 16 claves foráneas y 24 CHECK. Esquema y datos en [`base_de_datos/`](base_de_datos/MODELO_DE_DATOS.md) |
| Datos de prueba | Se notó que faltaba el empleado entre los usuarios | Agregado el rol Empleado y el usuario `employee.demo@example.com` en el `DemoSeeder` |
| Rama de la base de datos | No hubo pull request sino una rama nueva. Faltaban las pruebas de integridad y el pull request | Trabajada en `feature/base-de-datos` (issue #22) |
| Cierre de sesión (HU-03) | Se la consideró extra chica: destruir la sesión y ajustar los segundos de duración | Implementada en la rama `feature/hu-03-cierre-sesion-hu-07-inmuebles` (destruye la sesión). La duración de la sesión no se cambió: no figura en los criterios del issue |
| PHPUnit | En la computadora de uno de los integrantes no había Composer ni PHPUnit (usa WAMP) | El README explica cómo correr las pruebas con el `phpunit-11.phar` suelto |
| Issues de la Fase 2 | Solo se habían creado los issues de la cursada; los 9 del final no existían | Sigue así: HU-19 a HU-27 no tienen issues (sección 1) |
| Plan de entrega | Se contaron 19 historias (las de la cursada) y unos 19 días hasta el 24, que se menciona como fecha de entrega "o antes". Hacer una por día no era realista. Se fijó una meta mínima: al menos 7 historias el domingo siguiente (se las nombró "1 a 7", que probablemente son HU-01 a HU-07) | Al 09/11 están mergeadas HU-01, HU-04 y HU-05 (pull requests #27, #28 y #29) |

**Quedó sin precisar:** la fecha exacta del cierre del primer sprint ("el domingo siguiente") y qué historias son exactamente las "1 a 7" de la meta mínima.

---

## 12. Observaciones sobre el repositorio

Cosas detectadas al revisar el proyecto, para corregir cuando convenga:

- **Ruta de las skills:** `AGENTS.md` cita `.agents/skills/...`, pero los archivos están en `.agents/.agents/skills/...`.
- **Referencias a la rama de prototipos:** los issues dicen que los prototipos están "en la rama `prototipos-pantalla` hasta su merge". Ya se mergeó, así que ese texto quedó viejo.
- **Estados del tablero:** hay 12 issues en *In progress* a la vez, lo que no encaja con el orden de la sección 10. Convendría dejar así solo los de la etapa actual.
- **Fechas del issue de base de datos:** inicio 29/10 y objetivo 30/10 para 8 puntos; es un plazo muy justo.
- **Issues reasignados que ya estaban en curso:** HU-02, HU-03, HU-04, HU-06, HU-08 y HU-09 cambiaron de responsable estando en *In progress*. Si alguien ya había empezado alguno, hay que coordinarlo.
- **`.env` en el historial:** ver la sección 3.

Resueltas el 06/11/2026 (ya no hace falta corregirlas): los enlaces rotos y la versión de PHP del `README`, los totales del resumen del backlog (cursada 73, final 41, total 114 puntos) y la relación entre las dos escalas de puntos (README de historias y sección 9.1 del análisis).

---

## 13. Registro de lo organizado hasta ahora

| Fecha | Qué se hizo |
|---|---|
| 28/10/2026 | `.env` agregado al `.gitignore` y fuera del seguimiento de git |
| 28/10/2026 | Prototipos de pantallas versionados en `.docs/prototipos_pantallas/` y mergeados a `main` (pull request #2) |
| 28/10/2026 | Issue de base de datos (#22) ampliado: entidades, trazabilidad HU → tablas, reglas, decisiones, tareas y criterios de aceptación |
| 28/10/2026 | Los 19 issues de historias de usuario completados con los escenarios, reglas, Definition of Done, dependencias y referencias |
| 28/10/2026 | Prioridad, tamaño y estimación cargados en el tablero para todos los issues |
| 28/10/2026 | Etiquetas de módulo y de riesgo creadas y asignadas |
| 28/10/2026 | Estimación del issue de base de datos corregida de 999 a 8 puntos, con desglose |
| 28/10/2026 | Reparto en partes iguales entre los tres integrantes y orden cronológico de trabajo |
| 28/10/2026 | Este documento |
| 06/11/2026 | Analizada la reunión del 29/10 (notas en `.docs/documentacion/`). Se creó `PRESUPUESTO_Y_PLANIFICACION.md` y se agregaron a este documento la entrega, las decisiones pendientes de presupuesto y de proceso, y las observaciones sobre los puntos |
| 06/11/2026 | Documentación alineada con el análisis de requerimientos: sección 9 del análisis, totales y correspondencia de puntos en el README de historias, campos de inmueble y contrato, estados, alertas y prototipos |
| 06/11/2026 | Base de datos (#22) en la rama `feature/base-de-datos`: 11 migraciones, seeders, datos de prueba, esquema para Workbench y pruebas de integridad con PHPUnit. Se agregó el rol Empleado (cuatro roles), se actualizaron 19 issues de GitHub y se agregaron los prompts y los prototipos de las pantallas que faltaban |
| 09/11/2026 | HU-01, HU-04 y HU-05 implementadas y mergeadas (pull requests #27, #28 y #29) |
| 09/11/2026 | Agregadas las notas de la reunión del 6/11 (`.docs/documentacion/discord proyecto 6-11-26.md`) y resumido lo acordado en la sección 11 |
| 09/11/2026 | Review de HU-01, HU-04 y HU-05 y corrección de los 9 hallazgos (3 medios y 6 menores), con PHPUnit instalado: 85 tests y 512 aserciones en verde. Detalle en [`REVIEW-HU-01-04-05_handoff.md`](exec-plans/REVIEW-HU-01-04-05_handoff.md), en la rama `fix/review-hu-01-04-05` |
| 09/11/2026 | HU-03 (cierre de sesión seguro, issue #19) implementada en la rama `feature/hu-03-cierre-sesion-hu-07-inmuebles`: `POST /auth/logout`, botón en el sidebar de todos los paneles y 17 casos de prueba (102 tests y 636 aserciones en verde). Detalle en [`HU-03_handoff.md`](exec-plans/HU-03_handoff.md) |
| 09/11/2026 | HU-07 (alta y edición de inmuebles, issue #12) implementada en la misma rama `feature/hu-03-cierre-sesion-hu-07-inmuebles`: `/admin/inmuebles` (listado mínimo, alta y edición), propietarios activos, estados y 49 casos de prueba (151 tests y 1200 aserciones en verde). Detalle en [`HU-07_handoff.md`](exec-plans/HU-07_handoff.md) |
| 10/11/2026 | Registro de tiempos por issue ([`REGISTRO_DE_TIEMPOS.md`](REGISTRO_DE_TIEMPOS.md)): tiempos reconstruidos de #22 (1 h 42 min), #19 (16 min) y #12 (28 min), y método para registrar el de los issues siguientes en el handoff, en el registro y en un comentario del issue |
| 10/11/2026 | Completado en el tablero el ítem del pull request #2 (prototipos de pantallas), que había quedado vacío: prioridad P1, tamaño L, 5 puntos, fechas del 28/10, descripción del pull request y comentario de tiempo (44 min, reconstruido; fila agregada a [`REGISTRO_DE_TIEMPOS.md`](REGISTRO_DE_TIEMPOS.md)) |
| 10/11/2026 | HU-10 (alta y vinculación de contratos, issue #15) implementada en la rama `feature/hu-10-alta-contratos`: `/admin/contratos/nuevo`, guardado atómico del contrato y del estado «Alquilado» del inmueble, ficha del contrato y 85 casos de prueba (241 tests y 2006 aserciones; 1 falla previa de HU-02). Tiempo: 20 min, medido. Detalle en [`HU-10_handoff.md`](exec-plans/HU-10_handoff.md) |
