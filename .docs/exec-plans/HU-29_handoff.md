# Informe de Avance y Traspaso (Handoff) — HU-29

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-29 (Detalle público de un inmueble) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-29 — Detalle público de un inmueble · [#54](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/54) (P1, 3 pts) |
| **Sprint / Hito** | Sprint 2 (ampliación detectada el 17/11/2026) |
| **Rama** | `feature/hu-28-29-34-inicio-detalle-y-contratos-inquilino` (antes `hu-28-pagina-inicio-publica`; base `origin/Sprint-2` en `f7625ec`) |
| **Último commit** | Ver el pull request |
| **Pull Request** | [#65](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/65) |
| **Fecha de cierre** | 2026-11-18 09:35 |
| **Inicio / Fin** | 2026-11-17 23:35 → 2026-11-18 00:35 (implementación) · 2026-11-18 09:14 → 2026-11-18 09:35 (correcciones y pruebas) |
| **Tiempo invertido** | 1 h 00 min de implementación + 21 min de correcciones, pruebas y handoff (compartidas con HU-28). Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Joaquín Corbalán (`joaquincorbalan`); correcciones con Claude Code |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** La ficha pública de un inmueble disponible (`/catalogo-inmuebles/{id}`): dirección, ciudad, tipo, ambientes, superficie, servicios incluidos, precio base, descripción y estado «Disponible», sin propietario, contratos ni campos de auditoría. El llamado a la acción depende de quién mira (visitante, inquilino u otro rol), un inmueble que no está disponible responde 404 y la tarjeta lateral trae los datos de contacto de la inmobiliaria con un enlace de WhatsApp. «Ver detalle» de las tarjetas del catálogo y de la página de inicio llega a la ficha. En la revisión del 18/11/2026 se corrigieron tres defectos (ver sección 6) y se escribieron las pruebas y este handoff, que faltaban.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-29_detalle_publico_inmueble.md`](../historias_de_usuario/HU-29_detalle_publico_inmueble.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | La ficha muestra los datos públicos del inmueble, sin propietario, contratos ni campos de auditoría | ✅ | `PublicPropertyDetailTest::testVisitorSeesTheFullSheetWithoutOwnerContractsOrAuditFields`, `testThePublicQueryDoesNotSelectTheOwnerNorAuditColumns` |
| CA-2 | El llamado a la acción cambia según el rol (visitante, inquilino, propietario / administrador / empleado) | ✅ | `testVisitorIsOfferedToSignInOrRegisterAsTenant`, `testTenantSeesTheRequestButton`, `testOwnersAdministratorsAndEmployeesSeeNoRequestButtonButAPublicViewNotice` |
| CA-3 | Un inmueble alquilado, en mantenimiento, dado de baja, pendiente, rechazado o de un propietario dado de baja responde 404; también un identificador inexistente o no numérico | ✅ | `testPropertiesThatAreNotPubliclyAvailableAnswer404`, `testUnknownAndMalformedIdentifiersAnswer404` |
| CA-4 | La tarjeta lateral muestra teléfono, correo y el enlace de WhatsApp | ✅ | `testContactCardShowsPhoneEmailAndWhatsAppLinkFromTheConfiguration`, `testContactCardShowsTheDefaultAgencyData` |
| RN-1 | Solo GET | ✅ | `testTheDetailIsReadOnly` |
| RN-2 | «Ver detalle» del catálogo lleva a la ficha | ✅ | `testCatalogCardsLinkToTheDetail` |
| RN-3 | «Volver al catálogo» conserva los filtros cuando se llegó desde el catálogo | ✅ | `testBackToTheCatalogKeepsTheFiltersWhenComingFromTheCatalog`, `testBackToTheCatalogIgnoresAnyOtherReferer` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Config]` | `app/Config/Routes.php` | Modificado | `GET catalogo-inmuebles/(:num)` |
| `[Controller]` | `app/Controllers/PropertyController.php` | Modificado | `publicShow()` (404 si no es público) y `publicCatalogBackUrl()` |
| `[Model]` | `app/Models/PropertyModel.php` | Modificado | `findPublicDetail()`: activo, aprobado, disponible y de un propietario activo; solo columnas públicas |
| `[View]` | `app/Views/properties/public_show.php` | Nuevo | Ficha pública con el llamado a la acción por rol y el contacto |
| `[CSS]` | `public/css/panel.css` | Modificado | Estilos de la ficha (solo variables de `tokens.css`) |
| `[Config]` | `app/Config/Agency.php` | Nuevo (18/11) | Teléfono, correo, horario, dirección y WhatsApp de la inmobiliaria, compartidos con la página de inicio |
| `[Test]` | `tests/feature/PublicPropertyDetailTest.php` | Nuevo (18/11) | 13 pruebas de integración |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** `GET /catalogo-inmuebles/{id}`, pública.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Consulta propia `findPublicDetail()` con columnas públicas | Reutilizar `findWithOwner()` | No trae `owner_id` ni auditoría; una prueba revisa las columnas devueltas |
| `publicShow(string $id)` con validación | `publicShow(int $id)` | Con `int`, un número de 20 cifras producía un error de tipo (500) en lugar de 404 |
| «Volver al catálogo» a partir del `Referer`, reconociendo el catálogo por `site_url()` y copiando solo `type`, `min_price`, `max_price` y `page` | Comparar contra `/catalogo-inmuebles` | La app genera URLs con `index.php` (o desde una subcarpeta): la comparación fija nunca coincidía. Cualquier otro `Referer`, incluido el de otro sitio, se ignora |
| Datos de contacto en `Config\Agency` | Escribirlos en las vistas | Los usan la ficha y la página de inicio; se cambian en un solo lugar o desde el `.env` (`agency.phone = …`). Los valores son provisorios hasta que el equipo confirme los reales |
| Sin fotos ni «Solicitar visita» | Galería y modal del prototipo | Propuestas del issue (sección «Por definir»): quedan para después |

**Seguridad aplicada:** consulta pública sin datos internos, solo GET, escape contextual con `esc()`, URL de retorno armada con `site_url()` y una lista fija de parámetros.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Pruebas de HU-29 | `phpunit --no-coverage tests/feature/PublicPropertyDetailTest.php` | ✅ 13 pasan (153 aserciones) |
| Regresión de la suite completa | `phpunit --no-coverage` en copia aislada, partida en tres corridas paralelas | ✅ 818 pruebas: ninguna falla nueva. Solo fallan 7 que ya fallan en `origin/Sprint-2` (`PaymentReviewTest` ×3, `ProfileManagementTest` ×2 y `RentalRequestAdminTest` ×2); las dos regresiones del catálogo quedaron corregidas |
| «Volver al catálogo» en la app real | Servidor local con datos de demostración y `fetch` con el `Referer` del catálogo | ✅ conserva los filtros (antes de la corrección no los conservaba) |

---

## 6. Riesgos, Bloqueos y Deuda Técnica

Defectos hallados en la revisión del 18/11/2026 y corregidos en esa misma fecha:

| ID | Tipo | Descripción | Estado |
|---|---|---|---|
| R-1 | Defecto | «Volver al catálogo» no conservaba los filtros (comparaba la ruta del `Referer` con `/catalogo-inmuebles`, pero la app agrega `index.php`) | ✅ Corregido |
| R-2 | Defecto | `catalogo-inmuebles/99999999999999999999` respondía 500 en lugar de 404 | ✅ Corregido |
| R-3 | Deuda | La historia no tenía pruebas ni handoff | ✅ Pruebas y este documento |
| R-4 | Deuda | Los datos de contacto estaban escritos en las vistas | ✅ `Config\Agency` |
| R-5 | Deuda | Falta el comentario «⏱️ Tiempo invertido» en el issue #54 | ⏳ Pendiente: publicarlo al mergear |
| R-6 | Deuda | Fotos de los inmuebles y «Solicitar visita» para visitantes sin cuenta | ⏳ Fuera de alcance por decisión del issue |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Publicar el comentario «⏱️ Tiempo invertido» en #54 | issue #54 | — |
| 2 | Cargar fotos de los inmuebles (mejora posterior) | migración, `PropertyModel`, formularios | — |

**Siguiente HU sugerida:** HU-34 (#59), ya implementada en la misma rama.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Joaquín Corbalán (Antigravity + Gemini) | 1 h 00 min | Ficha pública, ruta, consulta, vista y estilos | `3bb5ca9` |
| 2026-11-18 | Joaquín Corbalán (con Claude Code) | 21 min | Corrección de «Volver al catálogo» y del identificador largo, datos de contacto en `Config\Agency`, pruebas y handoff | [#65](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/65) |
