# Organización del proyecto — InmoGestión 2026

> Última actualización: 28/10/2026. Este documento resume cómo está organizado el trabajo del equipo: quién hace qué, en qué orden, con qué convenciones y qué falta decidir. La fuente de cada dato se indica entre paréntesis. Si algo cambia en GitHub, hay que actualizarlo acá.

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
| Roles del sistema | Administrador, Propietario, Inquilino |

### Fases y sprints

Según el [análisis de requerimientos](ANALISIS_DE_REQUERIMIENTOS.md) y el [README](../README.md):

| Fase | Contenido | Historias |
|---|---|---|
| **Sprint 1 (semana 1)** | Seguridad, usuarios, inmuebles y contratos | HU-01 a HU-12 |
| **Sprint 2 (semana 2)** | Cuotas, mora, portal del inquilino, pagos, integración y demo | HU-13 a HU-18 |
| **Fase 2 (examen final)** | Liquidaciones, notificaciones, dashboard y reportes | HU-19 a HU-27 (todavía sin issues) |
| **Fase 3 (post-entrega)** | Garantía de 60 días, capacitación, mantenimiento y backups | — |

Presupuesto del documento de requerimientos: 83,33 horas-hombre a $18.000 ARS por hora, con un tope de $1.500.000 ARS.

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
- Todavía **no hay `.env.example`**, así que cada integrante arma su `.env` a mano. Está como tarea en el issue de base de datos.

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
| [`.docs/ANALISIS_DE_REQUERIMIENTOS.md`](ANALISIS_DE_REQUERIMIENTOS.md) | Alcance, roles, requerimientos RF/RNF, riesgos, sprints y presupuesto |
| [`.docs/2026 - inmogestion.md`](2026%20-%20inmogestion.md) | Enunciado del trabajo y tabla de alcances por etapa |
| [`.docs/GUIA TP-FINAL.md`](GUIA%20TP-FINAL.md) | Índice que pide la cátedra para el informe (incluye DER y modelo relacional en la sección 3.3) |
| [`.docs/TarjetasCRC.md`](TarjetasCRC.md) | Tarjetas CRC por módulo: usuarios, propiedades y contratos, cuotas y mora, pagos, liquidaciones, notificaciones |
| [`.docs/historias_de_usuario/`](historias_de_usuario/) | Una HU por archivo con escenarios, reglas y Definition of Done. El [`README`](historias_de_usuario/README.md) trae el backlog priorizado |
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
| **Start date / Target date** | fechas | Solo el issue de base de datos las tiene (29/10 y 30/10) |

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
- **Contenido del issue:** 11 tablas propuestas (`roles`, `usuarios`, `password_resets`, `inmuebles`, `contratos`, `cuotas`, `pagos`, `comprobantes`, `liquidaciones`, `liquidacion_cuotas`, `notificaciones`), una tabla que relaciona cada HU con las tablas que usa, reglas de integridad, tareas y criterios de aceptación.
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

Las etapas 1 a 5 corresponden a la **semana 1** y las etapas 5 a 10 a la **semana 2**.

Desde HU-13 la cadena es estricta: 13, 14, 15, 16, 17, 18 (28 puntos en fila). Por eso conviene que quien espera adelante pantallas, formularios y validaciones.

---

## 11. Decisiones pendientes

### Base de datos (issue #22)

1. **Idioma de los nombres:** el glosario y `AGENTS.md` piden inglés (`users`, `properties`), pero el análisis de requerimientos usa español (`contrato_id`, `inquilino_id`).
2. **Unicidad con baja lógica:** email y DNI únicos solo entre usuarios activos; un `UNIQUE` simple impide volver a registrar a alguien dado de baja.
3. **Estados:** `ENUM` o tabla de catálogo.
4. **Liquidaciones:** una por contrato y período, o una por propietario con detalle por contrato.
5. **Día de vencimiento:** los documentos dicen "día válido del mes"; se propone 1 a 28.
6. **Base de pruebas:** el grupo `tests` usa SQLite3 en memoria con prefijo `db_`; `ENUM` y columnas generadas son propios de MySQL.
7. **Cierre de contratos:** si se guardan fecha y motivo de la finalización o rescisión.
8. **Período de las cuotas:** texto `AAAA-MM` o fecha del primer día del mes.
9. **Propietario en `contratos` y `liquidaciones`:** guardarlo evita perder historial si el inmueble cambia de dueño, pero duplica el dato.

### Historias de usuario (secciones "Por definir" de cada issue)

| Historia | Duda abierta |
|---|---|
| HU-03b | Vigencia del token y cómo se entrega (correo o enlace en pantalla) |
| HU-04 | Contraseña inicial de un usuario creado por el administrador; qué "auditorías básicas" se guardan |
| HU-05 | Si hay reactivación de usuarios; si se puede dar de baja a quien tiene contratos vigentes; si se protege al último administrador |
| HU-07 | Si ciudad, superficie y servicios incluidos se cargan en esta historia |
| HU-08 | Filtro por ubicación, ordenamiento y qué datos son públicos |
| HU-09 | Si hay reactivación de inmuebles |
| HU-10 | Rango de los porcentajes, tope del día de vencimiento, porcentaje de actualización del canon y de dónde sale el propietario |
| HU-12 | Fecha y motivo del cierre; qué pasa con las cuotas pendientes |
| HU-13 | Formato del período y si hay emisión individual |
| HU-14 | Redondeo, tope del recargo y si se guarda o se recalcula |
| HU-16 | Medios de pago disponibles y qué pasa si el monto no coincide con la cuota |

---

## 12. Observaciones sobre el repositorio

Cosas detectadas al revisar el proyecto, para corregir cuando convenga:

- **Enlaces rotos en el `README`:** apunta a `.docs/PRESUPUESTO_Y_PLANIFICACION.md`, que no existe, y a `.docs/[BORRADOR]ANALISIS_DE_REQUERIMIENTOS.md`, que hoy se llama `ANALISIS_DE_REQUERIMIENTOS.md`.
- **Versión de PHP:** el `README` dice "PHP ≥ 7.4", pero `composer.json` exige `^8.2`.
- **Ruta de las skills:** `AGENTS.md` cita `.agents/skills/...`, pero los archivos están en `.agents/.agents/skills/...`.
- **Puntos del backlog:** el resumen del backlog dice 66 puntos para la cursada, pero la suma de su tabla da 70 (73 contando HU-03b).
- **Referencias a la rama de prototipos:** los issues dicen que los prototipos están "en la rama `prototipos-pantalla` hasta su merge". Ya se mergeó, así que ese texto quedó viejo.
- **Estados del tablero:** hay 12 issues en *In progress* a la vez, lo que no encaja con el orden de la sección 10. Convendría dejar así solo los de la etapa actual.
- **Fechas del issue de base de datos:** inicio 29/10 y objetivo 30/10 para 8 puntos; es un plazo muy justo.
- **Issues reasignados que ya estaban en curso:** HU-02, HU-03, HU-04, HU-06, HU-08 y HU-09 cambiaron de responsable estando en *In progress*. Si alguien ya había empezado alguno, hay que coordinarlo.
- **`.env` en el historial:** ver la sección 3.

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
