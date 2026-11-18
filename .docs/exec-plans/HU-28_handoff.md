# Informe de Avance y Traspaso (Handoff) — HU-28

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-28 (Página de inicio pública) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-28 — Página de inicio pública · [#53](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/53) |
| **Sprint / Hito** | Sprint 2 / Cursada |
| **Rama** | `hu-28-pagina-inicio-publica` |
| **Último commit** | Pendiente de commit |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-17 23:10 |
| **Inicio / Fin** | 2026-11-17 22:49 → 2026-11-17 23:10 |
| **Tiempo invertido** | 21 min efectivos · 21 min de reloj · Medido · sin pausas |
| **Autor / Agente** | Antigravity |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó la página de inicio pública en `/` para visitantes (propuesta de valor, buscador rápido por tipo y precio máximo, últimas propiedades disponibles y mensaje informativo si no hay, explicación de servicios para inquilinos y propietarios, llamados a la acción y datos de contacto), redirigiendo a los usuarios con sesión a sus respectivos paneles. Se creó el layout público unificado `layouts/public.php` reutilizado por inicio y catálogo, y se dio soporte a la preselección de rol en el registro (`role=owner` o `role=tenant`).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-28_pagina_inicio_publica.md`](../historias_de_usuario/HU-28_pagina_inicio_publica.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Visitante sin sesión entra a `/` y ve la página de inicio con todas sus secciones | ✅ | `PublicLandingTest::testGuestVisitingRootSeesPublicLandingWithAllSections` |
| CA-2 | Llamados a la acción llevan al catálogo, registro de propietario o registro general | ✅ | `PublicLandingTest::testCallsToActionLeadToExpectedDestinations`, `testRegistrationAcceptsOwnerOrTenantPreselection` |
| CA-3 | Usuario con sesión entra a `/` y es redirigido a su panel según su rol | ✅ | `PublicLandingTest::testAuthenticatedUserVisitingRootIsRedirectedToPanel` |
| CA-4 | Sin propiedades disponibles se muestra un mensaje informativo sin sección vacía | ✅ | `PublicLandingTest::testEmptyPropertiesShowsInformativeMessage` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Controller]` | `app/Controllers/Home.php` | Modificado | Muestra la landing a visitantes y redirige a los autenticados a su panel |
| `[Controller]` | `app/Controllers/AuthController.php` | Modificado | Lee parámetro query `role` y pasa `preselectedRole` si es `owner` o `tenant` |
| `[Controller]` | `app/Controllers/PropertyController.php` | Modificado | Pasa metadatos de navegación al catálogo público (`pageTitle`, `activeNav`) |
| `[Model]` | `app/Models/PropertyModel.php` | Modificado | Agrega método `findLatestPublic($limit)` para propiedades activas, aprobadas y disponibles con propietario activo |
| `[View]` | `app/Views/layouts/public.php` | Nuevo | Layout público unificado con encabezado y pie de página reutilizable |
| `[View]` | `app/Views/home/landing.php` | Nuevo | Vista de la página de inicio pública |
| `[View]` | `app/Views/properties/public_catalog.php` | Modificado | Refactorizado para extender `layouts/public` |
| `[View]` | `app/Views/auth/register.php` | Modificado | Aplica preselección de rol con fallback a `preselectedRole` |
| `[CSS]` | `public/css/panel.css` | Modificado | Estilos para la landing page, tarjetas informativas, footer público y buscador |
| `[Test]` | `tests/feature/PublicLandingTest.php` | Nuevo | 6 pruebas de integración (50 aserciones) cubriendo todos los criterios |
| `[Test]` | `tests/feature/AuthAccessControlTest.php` | Modificado | Actualizada la aserción de `/` para reflejar la landing pública |
| `[Docs]` | `.docs/historias_de_usuario/HU-28_pagina_inicio_publica.md` | Modificado | Tildados los ítems de Definition of Done |
| `[Docs]` | `.docs/REGISTRO_DE_TIEMPOS.md` | Modificado | Registrada la fila de tiempo de HU-28 |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** La ruta raíz `GET /` ahora entrega la vista `home/landing` para visitantes no autenticados en lugar de redirigir a `/auth/login`.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Layout compartido `layouts/public.php` | Mantener layouts separados en landing y catálogo | Requisito explícito de HU-28 para unificar header, navegación y footer público |
| Preselección en `AuthController::register()` con validación estricta de rol | Permitir cualquier valor o forzarlo en JavaScript | Seguridad: solo `owner` o `tenant` son roles válidos para registro público; evita estados inconsistentes |
| `findLatestPublic()` filtra por `approval_status = 'approved'` y propietario activo | Reutilizar `paginatePublic()` | Optimización de consulta directa `LIMIT 6` ordenada por `id DESC` sin sobrecarga de paginación |

**Seguridad aplicada:** Escape contextual en vistas con `esc()`, validación estricta de parámetros en controlador, mantenimiento de redirección para usuarios autenticados.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de integración HU-28 | `phpunit --no-coverage tests/feature/PublicLandingTest.php` | ✅ 6 pasan (50 aserciones) |
| Regresión Control de acceso (HU-01) | `phpunit --no-coverage tests/feature/AuthAccessControlTest.php` | ✅ 24 pasan (146 aserciones) |
| Regresión Catálogo (HU-08) | `phpunit --no-coverage tests/feature/PropertyCatalogTest.php` | ✅ 7 pasan (51 aserciones) |
| Estilos | Cumple `tokens.css` sin hex/rgb literales fuera de tokens; responsive | ✅ |

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Deuda | Ficha pública del inmueble (HU-29) pendiente de implementación | Baja | Reutilizar `layouts/public.php` cuando se desarrolle HU-29 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Commit, push y creación de PR para HU-28 | `git commit`, `git push` | — |
| 2 | HU-29: Detalle público de inmueble | `app/Controllers/PropertyController.php`, vistas | `ci4-core` |

**Siguiente HU sugerida:** HU-29 — Detalle público de inmueble.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Antigravity | 15 min | Implementación completa de HU-28, layout público, tests y documentación | Pendiente |
