# Informe de Avance y Traspaso (Handoff) — HU-34

> **Propósito:** Documento de trazabilidad del avance de la historia de usuario HU-34 (Consultar mis contratos, inquilino) y traspaso de contexto entre agentes/sesiones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-34 — Consultar mis contratos (inquilino) · [#59](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/59) (P2, 3 pts) |
| **Sprint / Hito** | Sprint 2 (ampliación detectada el 17/11/2026) |
| **Rama** | `feature/hu-28-29-34-inicio-detalle-y-contratos-inquilino` (antes `hu-28-pagina-inicio-publica`; la misma de HU-28 y HU-29; base `origin/Sprint-2` en `f7625ec`) |
| **Último commit** | Ver el pull request |
| **Pull Request** | [#65](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/65) |
| **Fecha de cierre** | 2026-11-18 09:14 |
| **Inicio / Fin** | 2026-11-18 08:39 → 2026-11-18 09:14 |
| **Tiempo invertido** | 34 min efectivos · 35 min de reloj · Reconstruido por el registro de la sesión (sección 4 de [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md)) · sin pausas > 10 min |
| **Autor / Agente** | Joaquín Corbalán (`joaquincorbalan`), con Claude Code |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen:** Se implementó la consulta de contratos del inquilino: el listado «Mi contrato» (`/inquilino/contratos`, con los vigentes primero) y la ficha de cada contrato (`/inquilino/contratos/{id}`) con el inmueble, el plazo, el canon, la actualización, el vencimiento mensual, el recargo por mora y, si cerró, la fecha y el motivo. Todo es de solo lectura, cada inquilino ve únicamente sus contratos (los ajenos o inexistentes responden 404) y nunca se muestra la comisión ni datos del propietario. «Mi contrato» se agregó al menú del inquilino y «Estado de cuenta» muestra una tarjeta «Tu contrato vigente» con el botón «Ver contrato digital».

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-34_consultar_contratos_inquilino.md`](../historias_de_usuario/HU-34_consultar_contratos_inquilino.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Un inquilino con contratos abre `/inquilino/contratos` y ve inmueble, vigencia, canon y estado, con los vigentes primero | ✅ | `TenantContractsTest::testListShowsOnlyOwnContractsWithTheOnesInForceFirst`, `testHistoryStaysConsultableWhenThePropertyAndTheOwnerWereDeactivated` |
| CA-2 | La ficha muestra inmueble, fechas, canon, actualización, vencimiento, mora, estado y datos de cierre, con enlaces a «Estado de cuenta» y «Mis pagos» | ✅ | `testFileShowsThePropertyAndTheAgreedTermsWithoutCommissionNorOwner`, `testFileOfAClosedContractShowsTheClosingDateAndReason` |
| CA-3 | El contrato de otro inquilino responde 404 | ✅ | `testTheContractOfAnotherTenantAnswers404`, `testUnknownOrMalformedIdentifiersAnswer404` |
| CA-4 | Sin contratos, un mensaje con accesos al catálogo y a «Mis solicitudes» | ✅ | `testEmptyStateOffersTheCatalogAndMyRequests` |
| CA-5 | Desde «Estado de cuenta» con un contrato vigente, «Ver contrato digital» abre la ficha | ✅ | `testAccountStatementLinksToTheFileOfTheContractInForce`, `testAccountStatementLinksToTheContractEvenBeforeTheFirstInstallmentIsIssued`, `testAccountStatementOffersNoContractLinkWithoutAContractInForce` |
| RN-1 | Solo lectura y acceso exclusivo a contratos propios | ✅ | `testTheContractPagesAreReadOnly`, `testOnlyTenantsCanOpenTheContractPages` |
| RN-2 | No se muestra la comisión ni datos del propietario | ✅ | `testListNeverShowsTheCommissionNorTheOwner`, `testQueriesForTheTenantNeverSelectTheCommissionNorTheOwner` |
| DoD | «Mi contrato» en el menú del inquilino | ✅ | `testTheTenantMenuHasAMiContratoEntryThatIsActiveOnTheContractPages` |

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Model]` | `app/Models/ContractModel.php` | Modificado | `findForTenant()` (vigentes primero, filtro opcional por estado), `findOneForTenant()` (solo si el contrato es del inquilino) y `tenantSelect()` con las columnas permitidas una por una |
| `[Controller]` | `app/Controllers/ContractController.php` | Modificado | `tenantIndex()` y `tenantShow()`; el identificador se recibe como texto para que uno demasiado largo responda 404 y no un error de tipo |
| `[Controller]` | `app/Controllers/DashboardController.php` | Modificado | «Estado de cuenta» pasa los contratos vigentes del inquilino a la vista |
| `[Config]` | `app/Config/Routes.php` | Modificado | `GET inquilino/contratos` y `GET inquilino/contratos/(:num)` en el grupo `inquilino` (`auth` + `role:tenant`) |
| `[View]` | `app/Views/tenant/contracts/index.php` | Nuevo | Listado «Mis contratos» con estado vacío |
| `[View]` | `app/Views/tenant/contracts/show.php` | Nuevo | Ficha del contrato: inmueble, plazo y condiciones, estado, accesos |
| `[View]` | `app/Views/dashboard/tenant_account_status.php` | Modificado | Tarjeta «Tu contrato vigente» con «Ver contrato digital» |
| `[View]` | `app/Views/layouts/panel.php` | Modificado | Entrada «Mi contrato» en el menú del inquilino |
| `[CSS]` | `public/css/panel.css` | Modificado | Estilos de la tarjeta de contratos y de las acciones del estado vacío (solo variables de `tokens.css`) |
| `[Test]` | `tests/feature/TenantContractsTest.php` | Nuevo | 15 pruebas de integración (199 aserciones) |
| `[Docs]` | `.docs/historias_de_usuario/HU-34_consultar_contratos_inquilino.md` | Modificado | Tildados los ítems de Definition of Done |
| `[Docs]` | `.docs/REGISTRO_DE_TIEMPOS.md` | Modificado | Fila de tiempo de HU-34 |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** Dos rutas GET nuevas bajo `inquilino/`, protegidas por `auth` y `role:tenant`. No hay rutas POST: la consulta es de solo lectura.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Consultas propias del inquilino con columnas explícitas (`tenantSelect()`) | Reutilizar `findWithDetails()` / `paginateWithDetails()` y ocultar campos en la vista | Esas consultas traen `contracts.*` (incluida la comisión) y los datos del propietario. Con columnas explícitas ninguna vista puede imprimirlos por error; una prueba revisa las columnas devueltas |
| 404 para un contrato ajeno o inexistente | Redirigir al listado con un aviso, como hace `ownerShow()` | Lo pide el criterio 3 y no revela qué identificadores existen |
| `tenantShow(string $id)` con validación | `tenantShow(int $id)` | Con `int`, un número de 20 cifras produce un error de tipo (500) en lugar de 404 |
| Sin paginación ni filtros en el listado | Reutilizar los filtros del listado del propietario | Un inquilino tiene pocos contratos; el issue solo pide verlos con los vigentes primero |
| Los montos y fechas se muestran como están guardados; solo la duración se arma en el controlador | Recalcular en la vista (como la ficha de administración) | Regla de negocio: «los montos y fechas provienen del servidor, no se recalculan en la vista» |
| Tarjeta «Tu contrato vigente» visible también sin cuotas emitidas | Mostrar el botón solo junto a la tabla de cuotas | Un contrato recién creado todavía no tiene cuotas y el inquilino igual debe poder abrirlo |
| No se muestra el nombre del propietario y no hay vista imprimible | Mostrarlo; agregar impresión | Propuestas del issue (sección «Por definir»); la impresión es opcional |

**Seguridad aplicada:** filtros `auth` + `role:tenant`, filtro por `tenant_id` en la consulta, solo GET, escape contextual con `esc()` en las vistas.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Pruebas de HU-34 | `phpunit --no-coverage tests/feature/TenantContractsTest.php` | ✅ 15 pasan (199 aserciones) |
| Prueba de que las pruebas detectan una fuga | Se agregó la comisión a `tenantSelect()` en una copia | ✅ falla `testQueriesForTheTenantNeverSelectTheCommissionNorTheOwner` |
| Regresión de la suite completa | `phpunit --no-coverage` en copia aislada (MySQL en 3307), partida en tres corridas paralelas: 818 pruebas | ✅ 818 pruebas: ninguna falla nueva. Solo fallan 7 que ya fallan en `origin/Sprint-2` (`PaymentReviewTest` ×3, `ProfileManagementTest` ×2 y `RentalRequestAdminTest` ×2); las dos regresiones del catálogo quedaron corregidas |
| Revisión visual en la app | Servidor local con datos de demostración: estado de cuenta, listado y ficha; 404 de un contrato ajeno y de un identificador de 20 cifras | ✅ |

---

## 6. Riesgos, Bloqueos y Deuda Técnica

Hallazgos de la revisión de HU-28 (#53) y HU-29 (#54), hechos antes de esta historia. Se corrigieron a pedido, en la misma rama y el mismo día (R-1 a R-4 y R-6); quedan pendientes R-5 y R-7.

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Defecto | HU-29: «Volver al catálogo» no conserva los filtros. `PropertyController::publicCatalogBackUrl()` compara la ruta del `Referer` con `/catalogo-inmuebles`, pero el catálogo se abre con `site_url()`, que agrega `index.php` (`indexPage = 'index.php'` en `app/Config/App.php`), o desde una subcarpeta: la comparación falla siempre y el enlace vuelve sin filtros | Media | ✅ Corregido: se compara contra la ruta de `site_url('catalogo-inmuebles')`; pruebas en `PublicPropertyDetailTest` |
| R-2 | Deuda | HU-29 (#54) no tiene pruebas, ni `HU-29_handoff.md`, ni tildados los ítems de su Definition of Done | Media | ✅ Hecho: `PublicPropertyDetailTest` (13 pruebas, incluido que el HTML no contiene `owner_id` ni el propietario) y [`HU-29_handoff.md`](HU-29_handoff.md) |
| R-3 | Defecto | HU-29: `catalogo-inmuebles/99999999999999999999` responde 500 (`publicShow(int $id)` recibe un texto que no cabe en un entero) en vez de 404 | Baja | ✅ Corregido: `publicShow(string $id)` valida el identificador como `tenantShow()` |
| R-4 | Deuda | HU-28 y HU-29 escriben el teléfono, el correo, el horario y el número de WhatsApp directamente en las vistas (el issue propone leerlos de un archivo de configuración) | Baja | ✅ Movidos a `app/Config/Agency.php`, compartido por la página de inicio y la ficha |
| R-5 | Deuda | Faltan los comentarios «⏱️ Tiempo invertido» en #53, #54 y #59 | Baja | Publicarlos al hacer el commit |
| R-6 | Regresión | La refactorización del catálogo público de HU-28 (`public_catalog.php` sobre `layouts/public.php`) quitó dos cosas que ya existían: el enlace «Publicar mi inmueble» (`#publish-property-link`, hacia `propietario/inmuebles/nuevo`, de HU-32) y el botón «Solicitar alquiler» de cada tarjeta (HU-30). Por eso fallan `PropertyOwnerSubmissionTest::testThePublicCatalogOffersTheLinkToPublishAProperty` y `RentalRequestTest::testScenario1TenantSubmitsRequestForAvailablePropertyAndPropertyStaysAvailable`. El handoff de HU-28 solo corrió 3 archivos de prueba, no la suite completa | Media | ✅ Corregido: el enlace volvió al pie del layout público y «Solicitar alquiler» a la tarjeta del inquilino con sesión (también está en la ficha) |
| R-7 | Deuda previa | `RentalRequestAdminTest::testScenario1AdminAndEmployeeSeeTrayWithPendingFirstSidebarCounterFilterAndSearch` (admin y empleado) ya falla en `origin/Sprint-2` («Pending requests must be listed first») | Baja | Revisarla con quien hizo HU-31 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisión y merge del pull request de HU-28, HU-29 y HU-34 (comparten la rama) | pull request | — |
| 2 | Publicar el comentario «⏱️ Tiempo invertido» en #59 | issue #59 | — |
| 3 | Publicar los comentarios «⏱️ Tiempo invertido» en #53 y #54 (R-5) | issues #53 y #54 | — |

**Siguiente HU sugerida:** revisar R-7 (`RentalRequestAdminTest`, HU-31) y seguir con HU-35 / Fase 2.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-18 | Joaquín Corbalán (con Claude Code) | 34 min | Revisión de HU-28 y HU-29, e implementación completa de HU-34: modelo, rutas, vistas, menú, acceso desde el estado de cuenta, pruebas y documentación | [#65](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/65) |
