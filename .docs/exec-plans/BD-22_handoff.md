# Informe de Avance y Traspaso (Handoff) — Base de datos

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | Elaboración de la base de datos · [#22](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/22) |
| **Sprint / Hito** | Etapa 0 (base de datos, bloquea a todas las historias) |
| **Rama** | `feature/base-de-datos` (desde `origin/main` en `270912e`) |
| **Último commit** | `0cbe08d` — feat: add the employee role and document the PHPUnit verification. Es el último commit de código; después hay commits de documentación y el merge de `main` |
| **Pull Request** | [#26](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/26), abierto el 06/11/2026 hacia `main`: mergeable, cierra #22 al mergear, `main` exige 1 aprobación de EihtSlowFlow |
| **Fecha de cierre** | 2026-11-06 |
| **Inicio / Fin** | 2026-11-06 11:03 → 2026-11-06 19:14 (dos bloques: 11:03 a 11:53 y 17:56 a 19:14) |
| **Tiempo invertido** | 1 h 42 min efectivos · 2 h 08 min de reloj · Reconstruido a partir de los registros de sesión y los commits · descontados ≈ 9 min de prompts para Stitch. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude, con `LauPerisse` |
| **Estado global** | 🟡 En curso: todo escrito, verificado en instancias descartables y en la base real del equipo (MySQL 8.0.45), y pusheado; la suite PHPUnit pasa; PR #26 abierto: falta la revisión y la confirmación del equipo |
| **Avance estimado** | 98 % |

**Resumen:** quedaron escritas las 11 migraciones (Fases 1 y 2 del issue), los seeders, `.env.example`, la configuración del grupo de pruebas, las pruebas de integridad, el esquema en SQL y los datos de prueba para Workbench, y la documentación (DER, modelo relacional, decisiones). Se verificó en MySQL 8.4.7 y MariaDB 11.4.9. Además se creó y verificó la base real del equipo (MySQL 8.0.45), donde también pasa la suite PHPUnit (`SchemaIntegrityTest`, 34 pruebas).

---

## 2. Alcance y Criterios de Aceptación

Referencia: issue #22 y [`../base_de_datos/MODELO_DE_DATOS.md`](../base_de_datos/MODELO_DE_DATOS.md).

| # | Criterio de aceptación | Estado | Evidencia |
|---|---|---|---|
| CA-1 | En una BD vacía `php spark migrate` crea todas las tablas y `migrate:rollback` las revierte | ✅ | Probado en MySQL 8.4.7 y MariaDB 11.4.9, también `migrate:refresh` |
| CA-2 | El seeder deja los roles y el administrador listos para iniciar sesión | ✅ | `DatabaseSeeder`: el hash BCRYPT verifica la clave de `.env`. El inicio de sesión real (HU-01) no existe todavía |
| CA-3 | Cada HU (HU-01 a HU-27) tiene sus datos cubiertos por el esquema | ✅ | Tabla de trazabilidad en el documento del modelo de datos |
| CA-4 | Los duplicados los rechaza la propia BD o una validación documentada | ✅ | `SchemaIntegrityTest` con PHPUnit (34 pruebas en MySQL 8.0.45) y 64 comprobaciones con un script equivalente en MySQL 8.4.7 y MariaDB 11.4.9; ver sección 5 |
| CA-5 | DER y modelo relacional en el repositorio | ✅ | `.docs/base_de_datos/MODELO_DE_DATOS.md` |

**Pedidos adicionales atendidos:** esquema completo en SQL para MySQL Workbench y datos de prueba tanto para CodeIgniter como para Workbench.

**Fuera de alcance / diferido:** modelos, controladores y vistas (se hacen en cada HU); backups con `mysqldump` (Fase 3); ajuste fino de índices con `EXPLAIN`.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Migration | `app/Database/Migrations/2026-11-06-1001xx` a `101100` (11 archivos) | Nuevo | `roles`, `users`, `password_resets`, `properties`, `contracts`, `installments`, `payments`, `payment_receipts`, `settlements`, `settlement_installments`, `notifications` |
| Seed | `app/Database/Seeds/{RoleSeeder,AdminSeeder,DatabaseSeeder,DemoSeeder}.php` | Nuevo | Datos base y de demostración |
| Seed | `app/Database/Seeds/demo_data.sql` | Nuevo | Datos de prueba, usados por `DemoSeeder` y por Workbench |
| Config | `app/Config/Database.php` | Modificado | El grupo `tests` pasa de SQLite en memoria a MySQLi con `database.tests.*` |
| Config | `.env.example`, `.gitignore`, `writable/uploads/comprobantes/.gitkeep` | Nuevo / Modificado | Plantilla de entorno; la carpeta de comprobantes viaja con el repo |
| Test | `tests/database/SchemaIntegrityTest.php` | Nuevo | Pruebas de integridad y de seeders |
| Docs | `.docs/base_de_datos/MODELO_DE_DATOS.md`, `inmogestion_schema.sql` | Nuevo | DER, modelo relacional, decisiones, esquema para Workbench |
| Docs | `README.md` | Modificado | Sección de puesta en marcha de la base de datos y enlace |

**Cambios de esquema de BD:** las 11 tablas nuevas. Sin datos reales ni credenciales.
**Cambios de rutas / permisos:** ninguno.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Nombres en inglés | Español | Lo exigen `AGENTS.md` y `code-conventions` |
| Unicidad entre activos con columnas generadas + `UNIQUE` | Validación solo en la aplicación | La base rechaza los duplicados |
| `ENUM` para estados | Tabla de catálogo | Más simple |
| Liquidación por contrato y período | Por propietario y período | Es lo que propone el issue; calza con HU-21 |
| Grupo de pruebas en MySQL | SQLite en memoria | `ENUM`, `CHECK` y columnas generadas son propios de MySQL |
| `demo_data.sql` como fuente única de datos de prueba | Seeder en PHP y SQL separados | Una sola copia que mantener; ambos caminos cargan lo mismo |
| Usuarios de Workbench con hash inutilizable | Clave conocida en el repo | Nadie inicia sesión por accidente; con el seeder la clave sale de `.env` |

El resto de las decisiones del issue está en la sección 5 del documento del modelo de datos.

**Seguridad aplicada:** claves siempre con BCRYPT y desde `.env`; `DemoSeeder` se niega a correr en producción; claves foráneas con `RESTRICT`, sin cascada; token de recuperación solo como hash; comprobantes fuera de `public/`.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Sintaxis PHP | `php -l` sobre los 17 archivos PHP nuevos o modificados, repetido tras los últimos cambios | ✅ sin errores |
| Migraciones | `migrate`, `migrate:rollback`, `migrate`, `migrate:refresh` | ✅ MySQL 8.4.7 y MariaDB 11.4.9 |
| Integridad | Script equivalente a `SchemaIntegrityTest` | ✅ 64 de 64 en MySQL 8.4.7 y en MariaDB 11.4.9 |
| Seeders | `db:seed DatabaseSeeder` y `DemoSeeder`, dos veces cada uno | ✅ idempotentes, mismos conteos y coherencias en ambos motores |
| Camino Workbench | Esquema `.sql` desde cero y de nuevo, luego `demo_data.sql` con el cliente SQL | ✅ MySQL y MariaDB; mismos conteos y coherencias que el camino de CodeIgniter |
| Base real del equipo | `db:create`, `migrate` y `db:seed DatabaseSeeder` en el MySQL local, con verificación de solo lectura | ✅ MySQL 8.0.45: 12 tablas (11 más `migrations`), 11 migraciones registradas, 16 claves foráneas, 24 `CHECK`, 3 columnas generadas, todo InnoDB, los roles y el administrador activo con hash BCRYPT que verifica la clave de `.env`. Luego `db:seed DemoSeeder`: 9 usuarios de demostración más el administrador, 8 inmuebles, 6 contratos, 24 cuotas, 19 pagos, 15 liquidaciones y 7 notificaciones, con las 28 comprobaciones de coherencia en verde |
| Tests automatizados | PHPUnit 11.5.57 suelto (`phpunit-11.phar`, de phar.phpunit.de) sobre la base de pruebas `inmogestion2026_test` del MySQL local | ✅ `SchemaIntegrityTest`: 34 pruebas, 56 aserciones. Suite completa del proyecto: 39 pruebas, 63 aserciones. Las pruebas migran y revierten el esquema en cada caso, y la base real no cambió |
| Prueba manual | Inicio de sesión y flujos | ⏳ No aplica: no hay modelos ni pantallas todavía |

**Sin verificar:**

- **`composer test`.** La suite se corrió con PHPUnit 11.5.57 suelto, no con Composer ni con el `vendor/` del proyecto, que no tiene `composer.lock`. Con las versiones que instale Composer (^10.5.16 o ^11.2) podrían aparecer diferencias.
- **Otras versiones de MySQL.** Los `CHECK` se hacen cumplir desde MySQL 8.0.16; cada integrante debería confirmar la suya con `SELECT VERSION();` antes de correr las pruebas.
- Versiones intermedias de MySQL y MariaDB.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Riesgo | `inmogestion_schema.sql` es una copia: queda desactualizada si cambia una migración | Media | Regenerarla en cada cambio (pasos en el documento del modelo de datos, sección 9) |
| R-2 | Riesgo | Agregar un valor a un `ENUM` requiere una migración nueva | Baja | Aceptado por simplicidad; migrar a tabla de catálogo solo si hiciera falta |
| R-3 | Bloqueo (resuelto el 06/11/2026) | La clave del `.env` local era rechazada por el MySQL local | — | Resuelto: se corrigió el `.env` (no versionado) y la base real se creó y migró |
| R-4 | Riesgo | `settlements.period` identifica el mes de alquiler; si HU-19 liquida otra cosa habrá que ajustarlo | Media | Confirmarlo al implementar HU-19 |
| R-5 | Deuda | `payment_receipts` de demostración no existen: necesitarían archivos reales | Baja | Aceptado |
| R-6 | Deuda | Las decisiones resueltas ya figuran en `ORGANIZACION_DEL_PROYECTO.md` (sección 11) y en el issue #22, pero las tomó una sola persona | Baja | Que EihtSlowFlow y joaquincorbalan las confirmen |
| R-7 | Deuda | Los permisos del rol `employee` no están definidos: el análisis (sección 3) los marca por definir y los prototipos no distinguen administrador de empleado | Media | Definir qué funciones del administrador puede usar el empleado antes de implementar HU-01 (filtros por rol) |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | ✅ Hecho el 06/11/2026 en la máquina de `LauPerisse`: `.env` corregido, base creada, migrada y sembrada. Cada integrante debe repetirlo en la suya (README, sección de la base de datos) | `.env` | `ci4-data-layer` |
| 2 | ✅ Hecho el 06/11/2026 en la máquina de `LauPerisse`: base `inmogestion2026_test` creada y suite corrida con PHPUnit suelto. Cada integrante debe correrla en la suya (README) | `.env`, `tests/database/` | `ci4-testing` |
| 3 | Revisar el diagrama en Workbench (ver el documento del modelo de datos, sección 7) | `.docs/base_de_datos/inmogestion_schema.sql` | — |
| 4 | ✅ PR [#26](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/26) abierto el 06/11/2026. Falta la aprobación de `EihtSlowFlow` | — | — |
| 5 | ✅ Decisiones anotadas en el issue #22 el 06/11/2026. Falta que los otros dos integrantes las confirmen | — | — |

**Siguiente HU sugerida:** HU-01 — Iniciar sesión y control de acceso (#21), que ya puede usar `users`, `roles` y el administrador sembrado.
**Prerrequisitos del entorno:** `.env` con las variables de `.env.example`; `php spark migrate`; `php spark db:seed DatabaseSeeder`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-06 | Claude con `LauPerisse` | Diseño del esquema, 11 migraciones, seeders, esquema y datos para Workbench, pruebas de integridad y documentación | `e949323` |
| 2026-11-06 | Claude con `LauPerisse` | Revisión de coherencia de `.docs/` tomando el análisis de requerimientos como fuente de verdad: sección 9 del análisis, README de historias, historias HU-04/07/08/10/12/13/16/17/20 a 24/26, CRC, DESIGN, prompts, modelo de datos (alertas en `notifications`) y `ORGANIZACION_DEL_PROYECTO.md`. Sin cambios de esquema ni de código | `dad590e` |
| 2026-11-06 | Claude con `LauPerisse` | Se actualizó el `.env` local (credenciales, base de pruebas y seeders) y se creó, migró y sembró la base real `inmogestion2026` en el MySQL 8.0.45 del equipo, con verificación de solo lectura. Después se cargó `DemoSeeder` en esa base y se comprobaron en solo lectura los conteos y la coherencia de los datos (28 comprobaciones). Sin cambios de código; el `.env` no se versiona | — |
| 2026-11-06 | Claude con `LauPerisse` | Se agregó el rol `employee`, para tener cuatro roles: administrador, empleado, propietario e inquilino. Cambios en `RoleSeeder`, `demo_data.sql`, `SchemaIntegrityTest`, el glosario de agentes y la documentación de la base y de requisitos (enunciado, análisis, historias, CRC, prototipos y organización). Se insertó en la base real con `RoleSeeder`. Sin cambios de esquema. Los permisos del empleado quedaron por definir | `0cbe08d`, `dad590e` |
| 2026-11-06 | Claude con `LauPerisse` | Se actualizaron 19 issues de GitHub (#3 a #18, #20, #21 y #22) para que coincidan con los documentos y la base. Se descargó PHPUnit 11.5.57 suelto (`phpunit-11.phar`) a la carpeta temporal de la sesión, fuera del proyecto, se creó `inmogestion2026_test` y pasó la suite: `SchemaIntegrityTest` 34 pruebas y suite completa 39. Sin cambios de código | `0cbe08d` |
| 2026-11-06 | Claude con `LauPerisse` | Se incorporó `main` (PR #25) a la rama y se resolvió el conflicto de `ORGANIZACION_DEL_PROYECTO.md`. Se commiteó todo en commits por tema (base de datos, documentos, prototipos), se pusheó la rama y se abrió el pull request #26: mergeable, con EihtSlowFlow como revisor y que cierra #22 al mergear | #26 |
