# Informe de Avance y Traspaso (Handoff) — HU-08

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-08 — Catálogo y filtros de inmuebles · [#3](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/3) |
| **Sprint / Hito** | Sprint 1 — Inmuebles y contratos |
| **Rama** | `RamaActualizacion-HorasEnHU` |
| **Último commit** | Sin commit en esta sesión |
| **Pull Request** | Sin PR |
| **Fecha de actualización** | 2026-11-10 |
| **Inicio / Fin** | 2026-11-10 21:16 → 2026-11-10 22:26 |
| **Tiempo invertido** | 56 min efectivos · 56 min de reloj · Medido en dos sesiones; se descontó la pausa de más de 10 minutos. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Joaquín (`joaquincorbalan`), con Copilot SDK en VS Code |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se agregaron filtros al catálogo administrativo, una consulta privada y filtrable para el propietario y un catálogo público limitado a inmuebles disponibles y datos de publicación. La implementación y sus pruebas están completas. Se creó la base descartable `inmogestion2026_test`, se configuraron sus credenciales en el `.env` local ignorado por Git y pasan los tests de HU-08 y la regresión HU-07.

## 2. Alcance y Criterios de Aceptación

Referencia: [HU-08 — Consultar y filtrar el catálogo de inmuebles](../historias_de_usuario/HU-08_catalogo_filtros_inmuebles.md)

| # | Criterio de aceptación | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Administración: lista inmuebles activos y permite filtrar por estado, tipo, precio y propietario | ✅ | Implementado en `PropertyController::index` y `PropertyModel::paginateWithOwner`; casos incluidos en `PropertyCatalogTest` |
| CA-2 | Propietario: ve solo sus inmuebles y sus estados, con filtros | ✅ | Ruta protegida por `role:owner`; alcance por `user_id` en `paginateForOwner`; casos incluidos en `PropertyCatalogTest` |
| CA-3 | Público: ve solo inmuebles activos y disponibles, sin datos internos | ✅ | `paginatePublic` selecciona campos de publicación y fija estado disponible; casos incluidos en `PropertyCatalogTest` |
| CA-4 | Sin coincidencias: se informa que no hay resultados | ✅ | Mensajes en las tres vistas y casos incluidos en `PropertyCatalogTest` |

**Fuera de alcance / diferido:** El detalle individual de un inmueble, fotos y otros datos no guardados actualmente no forman parte de HU-08.

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Modelo | `app/Models/PropertyModel.php` | Modificado | Añade filtros compartidos, catálogo por propietario, catálogo público de campos seleccionados y opciones de propietario para administración |
| Controlador | `app/Controllers/PropertyController.php` | Modificado | Valida filtros GET y prepara catálogos por rol |
| Rutas | `app/Config/Routes.php` | Modificado | Añade `GET /catalogo-inmuebles` y `GET /propietario/inmuebles` bajo sus permisos correspondientes |
| Vistas | `app/Views/admin/properties/index.php`, `app/Views/properties/owner_catalog.php`, `app/Views/properties/public_catalog.php` | Modificadas / nuevas | Filtros, listados por rol, paginación y estados vacíos |
| Navegación | `app/Views/layouts/panel.php`, `app/Views/auth/login.php` | Modificadas | Acceso a “Mis inmuebles” para propietarios y al catálogo desde el login |
| Estilos | `public/css/panel.css`, `public/css/auth.css` | Modificados | Formularios de filtros y tarjetas responsive para el catálogo público |
| Pruebas | `tests/feature/PropertyCatalogTest.php` | Nuevo | Casos para filtros administrativos, aislamiento del propietario, exposición pública, roles y resultados vacíos |
| Documentación | `.docs/historias_de_usuario/HU-07_alta_edicion_inmueble.md`, `.docs/historias_de_usuario/HU-08_catalogo_filtros_inmuebles.md`, `.docs/exec-plans/HU-08_handoff.md`, `.docs/REGISTRO_DE_TIEMPOS.md` | Modificados / nuevo | Aclara alcance de carga de fotos; Definition of Done y alcance por rol; handoff; registro de tiempo |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:**
- `GET /admin/inmuebles` conserva permisos `auth` + `role:admin,employee` y ahora acepta filtros.
- `GET /propietario/inmuebles` requiere `auth` + `role:owner`; la consulta restringe resultados al usuario autenticado.
- `GET /catalogo-inmuebles` es público y solo lista inmuebles activos disponibles.

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Reutilizar la consulta paginada del modelo con métodos de alcance explícito para propietario y público | Repetir SQL en cada controlador | Mantiene los filtros coherentes y hace explícitas las restricciones de visibilidad |
| Forzar estado disponible en la consulta pública, sin aceptar filtro de estado | Permitir que el visitante solicite estados internos | Cumple la regla pública aun manipulando la URL |
| Devolver campos seleccionados al público, sin propietario ni auditoría | Seleccionar todas las columnas | Evita exponer datos internos en HTML y en la respuesta |
| Mantener la lista administrativa existente y sumar rutas/vistas para propietario y visitante | Reemplazar el listado administrativo por una página nueva | Conserva la integración establecida en HU-07 y cubre los otros escenarios de HU-08 |

**Seguridad aplicada:** filtros validados en servidor; consultas con Query Builder y valores enlazados; grupo de rutas con autenticación y rol; alcance de propietario derivado de la sesión, nunca de un parámetro de la URL; catálogo público limitado a registros activos/disponibles y columnas de publicación; salida HTML escapada.

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Pruebas HU-08 y regresión HU-07 | `php vendor\bin\phpunit --no-coverage tests\feature\PropertyCatalogTest.php tests\feature\PropertyManagementTest.php` | ✅ 56 tests / 611 assertions |
| Pruebas unitarias sin base de datos | `php vendor\bin\phpunit tests\unit --no-coverage` | ✅ 2 tests / 3 assertions |
| Suite completa | `php vendor\bin\phpunit --no-coverage` | 176 tests; 173 pasan, 2 errores en `ProfileManagementTest` (DOMParser) y 1 fallo en `PublicRegistrationTest` |
| Rutas | `php spark routes` | ✅ Confirma `/catalogo-inmuebles` y `/propietario/inmuebles` con filtros esperados |
| Sintaxis PHP y formato del diff | `php -l` en archivos PHP modificados; `git diff --check` | ✅ Sin errores |
| Prueba manual / visual | No realizada | No ejecutada en esta sesión |

**Sin verificar:** Revisión visual manual. Los 3 resultados no exitosos de la suite completa son de pruebas fuera de HU-08 y no se modificaron dentro de este alcance.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Deuda de verificación | La suite completa aún tiene 2 errores en `ProfileManagementTest` al parsear HTML con DOMParser y 1 fallo en `PublicRegistrationTest`; HU-08 y HU-07 sí pasan aisladas. | Baja | Revisar esos tests fuera del alcance de HU-08 en otra tarea |

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| Opcional | Revisar visualmente catálogos y filtros en escritorio/móvil | Vistas de `app/Views/properties/`, `app/Views/admin/properties/` | `frontend-html-css` |
| Opcional | Investigar los 3 resultados no exitosos de la suite completa (fuera de HU-08) | Pruebas de perfil y registro | `ci4-testing` |
| 1 | Registrar el tiempo en el issue de GitHub y crear el commit de cierre | Issue #3 y cambios HU-08 | — |

**Siguiente HU sugerida:** HU-09 — Baja lógica de inmueble.
**Prerrequisitos del entorno:** PHP 8.2, dependencias Composer instaladas y MySQL/MariaDB de test descartable configurada en `database.tests.*`.

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-10 | Joaquín + Copilot SDK en VS Code | 37 min | Implementación HU-08 y pruebas añadidas | Sin commit / PR |
| 2026-11-10 | Joaquín + Copilot SDK en VS Code | 19 min | Conexión MySQL de test configurada localmente; HU-08/HU-07 pasan; suite completa revisada | Sin commit / PR |
| 2026-11-10 | Joaquín + Copilot SDK en VS Code | 19 min | Conexión MySQL de test configurada localmente; HU-08/HU-07 pasan; suite completa revisada | Sin commit / PR |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio de la nueva sesión con `Get-Date -Format "yyyy-MM-dd HH:mm"`.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar el handoff y el registro de tiempos y dejar el tiempo como comentario en el issue de GitHub.
