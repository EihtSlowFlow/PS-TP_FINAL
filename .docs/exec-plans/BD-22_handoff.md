# Informe de Avance y Traspaso (Handoff) — Base de datos

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | Elaboración de la base de datos · [#22](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/22) |
| **Sprint / Hito** | Etapa 0 (base de datos, bloquea a todas las historias) |
| **Rama** | `feature/base-de-datos` (desde `origin/main` en `270912e`) |
| **Último commit** | Sin commit: los cambios están en el árbol de trabajo |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-06 |
| **Autor / Agente** | Claude, con `LauPerisse` |
| **Estado global** | 🟡 En curso: todo escrito y verificado en instancias descartables; falta la prueba contra la base local del equipo y la suite PHPUnit |
| **Avance estimado** | 90 % |

**Resumen:** quedaron escritas las 11 migraciones (Fases 1 y 2 del issue), los seeders, `.env.example`, la configuración del grupo de pruebas, las pruebas de integridad, el esquema en SQL y los datos de prueba para Workbench, y la documentación (DER, modelo relacional, decisiones). Se verificó en MySQL 8.4.7 y MariaDB 11.4.9. No se pudo probar contra el MySQL local del equipo ni ejecutar PHPUnit.

---

## 2. Alcance y Criterios de Aceptación

Referencia: issue #22 y [`../base_de_datos/MODELO_DE_DATOS.md`](../base_de_datos/MODELO_DE_DATOS.md).

| # | Criterio de aceptación | Estado | Evidencia |
|---|---|---|---|
| CA-1 | En una BD vacía `php spark migrate` crea todas las tablas y `migrate:rollback` las revierte | ✅ | Probado en MySQL 8.4.7 y MariaDB 11.4.9, también `migrate:refresh` |
| CA-2 | El seeder deja los roles y el administrador listos para iniciar sesión | ✅ | `DatabaseSeeder`: el hash BCRYPT verifica la clave de `.env`. El inicio de sesión real (HU-01) no existe todavía |
| CA-3 | Cada HU (HU-01 a HU-27) tiene sus datos cubiertos por el esquema | ✅ | Tabla de trazabilidad en el documento del modelo de datos |
| CA-4 | Los duplicados los rechaza la propia BD o una validación documentada | ✅ | 64 comprobaciones de integridad con script equivalente; ver sección 5 |
| CA-5 | DER y modelo relacional en el repositorio | ✅ | `.docs/base_de_datos/MODELO_DE_DATOS.md` |

**Pedidos adicionales atendidos:** esquema completo en SQL para MySQL Workbench y datos de prueba tanto para CodeIgniter como para Workbench.

**Fuera de alcance / diferido:** modelos, controladores y vistas (se hacen en cada HU); backups con `mysqldump` (Fase 3); ajuste fino de índices con `EXPLAIN`; anotar las decisiones en el issue #22 (no se tocó GitHub).

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
| Tests automatizados | `composer test` | ⏳ **No ejecutado** |
| Prueba manual | Inicio de sesión y flujos | ⏳ No aplica: no hay modelos ni pantallas todavía |

**Sin verificar:**

- **`SchemaIntegrityTest` con PHPUnit.** No hay Composer ni PHPUnit en la máquina. Se verificó con un script equivalente que no está en el repositorio, así que errores de sintaxis de PHPUnit o del trait de pruebas podrían aparecer al correrlo.
- **El MySQL local del equipo** (servicio `MySQL80`, puerto 3306): el `.env` de esta máquina tiene una clave que el servidor rechaza (error 1045) y no se probaron claves. Todo se probó en instancias descartables en otros puertos, que se apagaron y borraron. Se desconoce la versión de ese servidor; con una anterior a 8.0.16 los `CHECK` no se hacen cumplir.
- Versiones intermedias de MySQL y MariaDB.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Riesgo | `inmogestion_schema.sql` es una copia: queda desactualizada si cambia una migración | Media | Regenerarla en cada cambio (pasos en el documento del modelo de datos, sección 9) |
| R-2 | Riesgo | Agregar un valor a un `ENUM` requiere una migración nueva | Baja | Aceptado por simplicidad; migrar a tabla de catálogo solo si hiciera falta |
| R-3 | Bloqueo | La clave de `database.default.password` en el `.env` local no funciona con el MySQL local | Alta | Corregirla y correr `php spark migrate` |
| R-4 | Riesgo | `settlements.period` identifica el mes de alquiler; si HU-19 liquida otra cosa habrá que ajustarlo | Media | Confirmarlo al implementar HU-19 |
| R-5 | Deuda | `payment_receipts` de demostración no existen: necesitarían archivos reales | Baja | Aceptado |
| R-6 | Deuda | Las decisiones resueltas no están anotadas en el issue #22 ni en `ORGANIZACION_DEL_PROYECTO.md` (sección 11) | Baja | Anotarlas; el segundo archivo cambia en el PR #25 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Corregir el `.env`, correr `migrate` y `db:seed DatabaseSeeder` en la base real del equipo | `.env` | `ci4-data-layer` |
| 2 | Instalar dependencias (`composer install`), crear la base de pruebas y correr `composer test` | `.env`, `tests/database/` | `ci4-testing` |
| 3 | Revisar el diagrama en Workbench (ver el documento del modelo de datos, sección 7) | `.docs/base_de_datos/inmogestion_schema.sql` | — |
| 4 | Commit, push y PR de la rama `feature/base-de-datos` | — | — |
| 5 | Anotar las decisiones en el issue #22 | — | — |

**Siguiente HU sugerida:** HU-01 — Iniciar sesión y control de acceso (#21), que ya puede usar `users`, `roles` y el administrador sembrado.
**Prerrequisitos del entorno:** `.env` con las variables de `.env.example`; `php spark migrate`; `php spark db:seed DatabaseSeeder`.

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| 2026-11-06 | Claude con `LauPerisse` | Diseño del esquema, 11 migraciones, seeders, esquema y datos para Workbench, pruebas de integridad y documentación | Sin commit |
