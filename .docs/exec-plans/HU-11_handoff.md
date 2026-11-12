# Informe de Avance y Traspaso (Handoff) — HU-11

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-11 — Consulta de contratos y estados · [#16](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/16) |
| **Sprint / Hito** | Sprint 1 · Inmuebles y contratos |
| **Rama** | `main` |
| **Último commit base** | `484ce4c` — `Merge pull request #37 from EihtSlowFlow/RamaActualizacion-HorasEnHU` |
| **Estado de los cambios** | Implementación en el árbol de trabajo, sin commit |
| **Pull Request** | Sin PR |
| **Fecha de actualización** | 2026-11-11 17:27 |
| **Inicio / Fin** | 2026-11-11 16:40 → 2026-11-11 17:23 (horas informadas por el usuario) |
| **Tiempo invertido** | 43 min efectivos · 43 min de reloj · Medido según el intervalo informado; sin pausas informadas |
| **Autor / Agente** | Copilot SDK en VS Code |
| **Estado global** | 🟡 Implementación completada y pruebas focalizadas aprobadas; falta commit, PR y cierre del issue |
| **Avance estimado** | 100 % del alcance de implementación |

**Resumen:** Se implementaron el listado administrativo de contratos con búsqueda y filtro por estado, y el portal de consulta del propietario limitado a sus propios contratos. Se agregó cobertura automatizada para búsquedas, estados históricos y acceso a fichas ajenas. Los cambios siguen sin commit ni PR; no se hizo validación manual en navegador.

## 2. Alcance y Criterios de Aceptación

Referencia: [`HU-11_consulta_contratos.md`](../historias_de_usuario/HU-11_consulta_contratos.md) · Issue [#16](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/16)

| # | Criterio de aceptación | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Administración lista todos los contratos y permite buscar por inquilino, propietario o dirección, y filtrar por estado | ✅ | `ContractCreationTest::testAdministratorsCanSearchContractsAndFilterHistoricalStatuses` |
| CA-2 | El propietario ve únicamente sus contratos, con contacto del inquilino, monto, vigencia y comisión | ✅ | `ContractCreationTest::testOwnerSeesOnlyOwnContractsAndMayOpenOnlyOwnContractFiles` |
| CA-3 | El servidor impide que un propietario consulte la ficha de un contrato ajeno | ✅ | `findWithDetails($id, $ownerId)` aplica el filtro de propietario en la consulta; prueba de acceso ajeno del test anterior |
| CA-4 | Contratos finalizados y rescindidos siguen consultables | ✅ | Prueba del listado con contratos `finished` y `rescinded`, incluidos en búsqueda y filtro |

**Fuera de alcance / diferido:** Cierre o rescisión de contratos (HU-12); emisión y consulta de cuotas; pruebas visuales/manuales en navegador.

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/ContractModel.php` | Modificado | Consulta paginada con datos de inmueble y partes, filtros de estado y búsqueda; detalle opcionalmente restringido por propietario. |
| Controller | `app/Controllers/ContractController.php` | Modificado | Listado administrativo, listado propio del propietario, validación de filtros y ficha con autorización por `owner_id`. |
| Routes | `app/Config/Routes.php` | Modificado | `GET /admin/contratos`; `GET /propietario/contratos` y `GET /propietario/contratos/{id}` con filtros de autenticación y rol. |
| Views | `app/Views/contracts/index.php` | Nuevo | Listado compartido, búsqueda, filtro de estado, paginación y datos condicionados al rol. |
| Views | `app/Views/admin/contracts/show.php` | Modificado | Enlace de regreso dinámico al listado administrativo o al del propietario. |
| Views | `app/Views/admin/contracts/form.php` | Modificado | Regreso al listado de contratos. |
| Layout | `app/Views/layouts/panel.php` | Modificado | Navegación a contratos para administración y a «Mis contratos» para propietarios. |
| Tests | `tests/feature/ContractCreationTest.php` | Modificado | Casos para búsqueda, estados históricos, aislamiento de lista y acceso al detalle. |
| Tests | `tests/feature/AuthAccessControlTest.php` | Modificado | Verificación de enlaces de navegación por rol. |
| Docs | `.docs/historias_de_usuario/HU-11_consulta_contratos.md` | Modificado | DoD actualizado y notas de integración reflejan la implementación. |

**Cambios de esquema de BD:** Ninguno. Se reutilizan `contracts`, `properties` y `users`.

**Cambios de rutas / permisos:**
- `GET /admin/contratos` — `auth` + `role:admin,employee`.
- `GET /admin/contratos/(:num)` — permanece bajo `auth` + `role:admin,employee`.
- `GET /propietario/contratos` y `GET /propietario/contratos/(:num)` — `auth` + `role:owner`; las consultas aplican `contracts.owner_id` del usuario en sesión.

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Reutilizar la vista de ficha para ambos roles, con URL de retorno según el contexto | Mantener una ficha separada para el propietario | Evita duplicar la presentación existente y conserva navegación contextual. |
| Restringir propietario directamente en la consulta del modelo, tanto en el detalle como en el listado | Cargar el contrato y comprobar el propietario después | La autorización queda aplicada en servidor y la ficha ajena se trata igual que una inexistente. |
| Usar el listado común para administración y propietarios, pasando el alcance como `ownerId` | Duplicar consulta y vista | Mantiene criterios de búsqueda, estado y paginación coherentes, sin omitir estados históricos. |

**Seguridad aplicada:** Rutas con filtros de autenticación y rol; alcance del propietario limitado en la consulta SQL; filtros GET validados en servidor; salida de vistas escapada con `esc()`. Las rutas de escritura existentes conservan CSRF global.

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Pruebas HU-10/HU-11 de contratos | `php .\vendor\bin\phpunit --no-coverage tests\feature\ContractCreationTest.php` | ✅ 77 tests, 780 aserciones |
| Pruebas de roles y navegación | `php .\vendor\bin\phpunit --no-coverage tests\feature\AuthAccessControlTest.php` | ✅ 24 tests, 142 aserciones |
| Casos HU-11 focalizados | Filtro de PHPUnit para búsqueda/filtros y aislamiento del propietario | ✅ 2 tests, 40 aserciones |
| Sintaxis PHP | `php -l` en controlador, modelo, vista y pruebas modificados | ✅ Sin errores |
| Formato de diff | `git diff --check` | ✅ Sin errores |
| Base de datos | BD aislada de pruebas (`database.tests`); se limpiaron tablas huérfanas `users`, `roles` y `migrations` para permitir las migraciones del harness | ✅ Suites ejecutadas posteriormente |
| Prueba manual / visual | No ejecutada | ⏳ Pendiente |

**Sin verificar:** Suite completa del proyecto y recorrido manual en navegador. La ejecución del fixer quedó sin realizar: el comando invocado rechazó múltiples rutas sin configuración explícita.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Pendiente de entrega | Cambios no confirmados en `main`; no existe PR y el issue #16 permanece abierto. | Media | Revisar el diff, crear rama/commit y PR siguiendo el flujo del equipo; actualizar el issue tras la revisión. |
| R-2 | Verificación pendiente | No se realizó prueba visual/manual ni se ejecutó la suite completa. | Baja | Probar `/admin/contratos` como admin/empleado y `/propietario/contratos` como propietario; ejecutar suite completa antes del merge. |
| R-3 | Registro pendiente | El intervalo fue medido desde el primer mensaje hasta las 17:23; el usuario no informó pausas. | Baja | Confirmar/ajustar si hubo pausas de más de 10 minutos. |

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisar cambios, probar interfaz y ejecutar pruebas completas; luego crear rama, commit y PR | Archivos listados en la sección 3 | Ninguno obligatorio |
| 2 | Actualizar el issue #16 y el registro de tiempo con datos confirmados | Issue #16, `.docs/REGISTRO_DE_TIEMPOS.md` | Ninguno |
| 3 | Continuar con HU-12 — Rescisión o finalización de contratos | HU-12 y componentes de contratos | Consultar las skills del proyecto si corresponde |

**Siguiente HU sugerida:** HU-12 — Rescisión o finalización de contratos ([#9](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/9)).
**Prerrequisitos del entorno:** PHP 8.2+, dependencias Composer instaladas y MySQL de pruebas configurado en `database.tests`. No hay migraciones nuevas para HU-11.

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-11 | Copilot SDK en VS Code | 43 min (medido) | Implementación y pruebas focalizadas de HU-11; handoff documentado. Falta revisión visual y suite completa. | `main`, cambios sin commit; sin PR |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md`, este handoff y [`HU-11_consulta_contratos.md`](../historias_de_usuario/HU-11_consulta_contratos.md).
2. Verificar `git status`, `git branch --show-current` y `git log -n 5`; no asumir que los cambios siguen sin commit.
3. Revisar la interfaz y ejecutar las verificaciones pendientes de la sección 5.
4. No sobrescribir ni descartar cambios de otros integrantes; separar HU-11 en una rama antes de confirmar si sigue en `main`.
5. Al completar entrega, actualizar este handoff, el issue #16 y el registro de tiempo solo con información verificada.
