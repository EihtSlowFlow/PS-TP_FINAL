# Informe de Avance y Traspaso (Handoff) — HU-13

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-13 — Emisión masiva de cuotas · [#6](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/6) |
| **Sprint / Hito** | Sprint 2 (Módulo financiero: cuotas y mora) |
| **Rama** | `feature/hu-13-emision-cuotas` (creada desde `feature/hu-10-alta-contratos`, que ya estaba fusionada en `main`; luego se fusionó `origin/main` dos veces: la segunda con HU-11 y HU-12) |
| **Último commit** | `4886e8b` — fix: reiniciar el estado de transacción de la conexión tras revertir un guardado fallido (antes: `5026276` feat, `5683474` docs y `f0037f9` merge de `main`) |
| **Pull Request** | [#39](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/39) → `main` |
| **Fecha de cierre** | 2026-11-11 00:20 |
| **Inicio / Fin** | 2026-11-10 23:28 → 2026-11-11 00:20 (implementación 23:28–23:52; commit, merge de `main`, corrección, push y pull request 23:53–00:20) |
| **Tiempo invertido** | 51 min efectivos · 51 min de reloj · Medido (implementación, 24 min) y reconstruido por los commits (cierre, 27 min) · sin pausas de más de 10 minutos. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟢 Completada (pull request abierto; falta el comentario del tiempo en el issue y la revisión del equipo) |
| **Avance estimado** | 100 % del alcance del issue |

**Resumen:** Se implementó la emisión de cuotas mensuales para administradores y empleados: la pantalla `ADM-09a` (`/admin/cuotas/emitir`, selector de mes y año, vista previa y confirmación, masiva o de un solo contrato) y el informe `ADM-09b` (`/admin/cuotas/emitir/resultado`, cuotas emitidas, omitidas y monto a recaudar). La emisión es una transacción que bloquea los contratos, omite los que ya tienen cuota del período, numera de forma correlativa por contrato y revierte todo si algo falla. Se agregaron 110 pruebas (82 de integración y 28 unitarias).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-13_emision_masiva_cuotas.md`](../historias_de_usuario/HU-13_emision_masiva_cuotas.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: con contratos vigentes que cubren el período, la emisión masiva crea una cuota «Pendiente» por contrato, con vencimiento según el día pactado y monto igual al alquiler mensual, e informa la cantidad y el monto total | ✅ | `InstallmentIssuingTest::testScenario1OperatorIssuesOneInstallmentPerContractInForceThatCoversThePeriod` (administrador y empleado), `testScenario1TheReportShowsTheCountAndTheTotalToCollect`, `testTheMonthAcceptsALeadingZeroAndTheAmountOfTheContractIsCopiedExactly` |
| CA-2 | Escenario 2: un contrato que ya tiene la cuota del período se omite sin duplicarla ni modificarla | ✅ | `testScenario2AnExistingInstallmentIsNotDuplicatedNorModified` (compara la fila completa antes y después), `testScenario2IssuingTheSamePeriodAgainCreatesNothing`, `testAnInstallmentOfAnotherPeriodDoesNotBlockTheIssuing` |
| CA-3 | Escenario 3: contratos finalizados, rescindidos o que no cubren el período no reciben cuota | ✅ | `testScenario3AContractThatIsNotInForceOrDoesNotCoverThePeriodGetsNoInstallment` (6 casos), `testScenario3TheExcludedContractsAreAbsentFromThePreviewAndTheyDoNotStopTheOthers` (6 casos), `testAContractWhoseDueDateIsOnTheEdgeOfItsTermIsCovered` (3 casos), `testAContractOfTwentyFourMonthsIsCoveredByExactlyTwentyFourPeriodsAndEachDueDateIsInsideItsTerm` (5 casos) |
| CA-4 | Escenario 4: un período inválido se rechaza con el error y no se emite nada | ✅ | `testScenario4AnInvalidPeriodIsRejectedAndNothingIsIssued` (15 casos), `testScenario4TheFormShowsTheErrorWhenThePeriodIsInvalid`, `testTheLimitsOfTheAcceptedYearsAreValidPeriods`, `InstallmentPeriodTest` (valida `buildPeriod()` con 24 casos) |
| CA-5 | Escenario 5: la emisión se puede limitar a un contrato desde la vista previa, con las mismas reglas | ✅ | `testScenario5TheIssuingCanBeLimitedToOneContract`, `testScenario5AnIndividualIssuingOfAContractThatAlreadyHasTheInstallmentChangesNothing`, `testScenario5AnIndividualIssuingOfAContractThatIsNotEligibleIsRejected` (3 casos), `testScenario5AnIndividualIssuingOfAMissingContractIsRejected`, `testAMalformedContractIdIsRejectedInsteadOfIssuingEverything` (8 casos), `testThePreviewListsTheContractsThatWillBeIssuedWithTheirDataAndTheTotal` (botón por fila) |
| RN-1 | Solo se emiten cuotas para contratos vigentes que cubran el período | ✅ | Las pruebas del escenario 3 |
| RN-2 | Como máximo una cuota por contrato y período | ✅ | Chequeo bajo bloqueo en el modelo + índice único `uq_installments_contract_period` (ya cubierto por `SchemaIntegrityTest`) |
| RN-3 | Numeración correlativa por contrato | ✅ | `testNumbersAreSequentialPerContractAcrossPeriods`, `testEveryContractHasItsOwnNumbering`, `testTheNextNumberContinuesFromTheHighestOneAlreadyUsedByTheContract`, `testNumbersFollowTheOrderInWhichThePeriodsAreIssued` |
| RN-4 | Toda cuota nueva inicia «Pendiente» y con recargo en cero | ✅ | Las pruebas del escenario 1 y del escenario 5 (`status = pending`, `late_fee = 0.00`) |
| DoD | Permisos de acceso | ✅ | `testAdministratorsAndEmployeesCanUseTheIssuingScreens`, `testOwnersAndTenantsCannotReachAnyIssuingRoute`, `testVisitorsAreRedirectedToTheLogin`, `testThePostWithoutACsrfTokenIsRejected` |
| DoD | Operación atómica (no pedida por el issue, pero necesaria para una operación masiva) | ✅ | `testAFailureWhileStoringOneInstallmentDiscardsTheWholeIssuing` (disparador de MySQL que hace fallar la última cuota) |

**Fuera de alcance / diferido:**
- «Notificar por email a los inquilinos», «Descargar reporte PDF», «Enviar notificaciones», «Ver cupón» y «Descargar PDF» del prototipo `ADM-09a/09b`: no figuran en los criterios del issue y las notificaciones son de HU-22.
- Lote y código de auditoría del informe (`Lote #EM-…`, `Registro #AUD-…`) y los códigos de cuota (`CUO-2612-01`): no hay tabla que los guarde; el informe muestra el número correlativo de la cuota y el contrato.
- «Ir a Cobranzas» del informe: la pantalla de cobranzas es de otra historia (HU-14/15).
- Las pantallas `ADM-09a` y `ADM-09b` del prototipo muestran un «Período destino» con sparklines y varias tarjetas decorativas; se adaptó al estilo del panel con las cuatro tarjetas que sí aportan datos.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/InstallmentModel.php` | Nuevo | Estados y etiquetas, `buildPeriod()` (año y mes → primer día del mes, con límites 2000–2100), `labelForPeriod()`, `dueDateFor()`, `sumAmounts()` (suma exacta en centavos), `issueForPeriod()` (transacción con bloqueo, omisión de duplicados y numeración correlativa) y `findWithDetails()` para el informe |
| Model | `app/Models/ContractModel.php` | Modificado | `findInForceCoveringPeriod()` (vista previa, con la cuota ya emitida si existe), `lockInForceCoveringPeriod()` (`FOR UPDATE`, en orden de `id`) y `coverageCondition()` (la regla de «cubre el período»). También `resetTransStatus()` tras el rollback de `createAndRentProperty()` (ver R-8) |
| Controller | `app/Controllers/InstallmentController.php` | Nuevo | `emit` (formulario y vista previa), `issue` (validación y emisión) y `result` (informe), con los mensajes |
| Routes | `app/Config/Routes.php` | Modificado | `GET /admin/cuotas/emitir`, `POST /admin/cuotas/emitir`, `GET /admin/cuotas/emitir/resultado` dentro del grupo `auth` + `role:admin,employee` |
| View | `app/Views/admin/installments/emit.php` | Nuevo | Selector de período, cuatro tarjetas de resumen, tabla de vista previa con el botón «Emitir solo esta» por fila y confirmación (pantalla `ADM-09a`) |
| View | `app/Views/admin/installments/result.php` | Nuevo | Informe: emitidas, omitidas, monto total y detalle de las cuotas (pantalla `ADM-09b`) |
| View | `app/Views/layouts/panel.php` | Modificado | Enlace «Cuotas» en el menú de administrador y empleado |
| Assets | `public/css/panel.css` | Modificado | Estilos del selector de período, del informe, de las etiquetas `badge-installment-*` y del aviso informativo |
| Test | `tests/feature/InstallmentIssuingTest.php` | Nuevo | 82 casos de prueba (los escenarios 1 a 5, vista previa, numeración, atomicidad y permisos) |
| Test | `tests/unit/InstallmentPeriodTest.php` | Nuevo | 28 casos: período, etiqueta, vencimiento, suma de importes y etiquetas de estado |
| Test | `tests/feature/AuthAccessControlTest.php` | Modificado | La prueba del menú por rol incluye «Cuotas» |
| Test | `tests/feature/ContractCreationTest.php` | Modificado | La prueba de atomicidad comprueba que la conexión no queda en estado de transacción fallida (R-8) |
| Docs | `.docs/historias_de_usuario/HU-13…`, `.docs/REGISTRO_DE_TIEMPOS.md` y este informe | Modificado / Nuevo | DoD de HU-13, decisiones y notas de integración, fila del tiempo |

**Cambios de esquema de BD:** Ninguno (usa `installments`, `contracts`, `properties` y `users`, creadas en el issue #22; la tabla ya tiene la clave única `(contract_id, period)`, la de `(contract_id, number)` y las restricciones `CHECK`).
**Cambios de rutas / permisos:**
- `GET /admin/cuotas/emitir` → `InstallmentController::emit` (acepta `?month=&year=` para ver la vista previa)
- `POST /admin/cuotas/emitir` → `InstallmentController::issue` (CSRF; `month`, `year` y, para la emisión individual, `contract_id`)
- `GET /admin/cuotas/emitir/resultado` → `InstallmentController::result` (el informe viaja una sola vez en la sesión)
- Todas con `auth` y `role:admin,employee`.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Un contrato «cubre» el período si está vigente y **el día de vencimiento de ese mes cae dentro de su plazo** | (a) Que el contrato tenga algún día dentro del mes; (b) que cubra el mes entero; (c) que esté vigente el día 1 | El análisis no lo define. Con (a) un contrato de 24 meses que empieza el día 15 tendría 25 cuotas y la primera vencería antes de empezar el contrato; con (b) perdería meses en los extremos. La regla elegida da siempre N cuotas para N meses y nunca emite una cuota que vence fuera del plazo (hay prueba con cinco combinaciones de inicio y vencimiento). Está concentrada en `ContractModel::coverageCondition()` por si el equipo prefiere otra |
| Se permiten períodos pasados y futuros (años 2000 a 2100) | Solo el mes en curso o el siguiente | El issue deja la pregunta «por definir». HU-10 permite contratos con inicio en el pasado, que necesitan emitir meses atrasados, y el prototipo emite «Próximo período». Los contratos acotan el rango real |
| El selector ofrece de dos años atrás a cinco adelante; el servidor acepta 2000–2100 | Un selector con todos los años | Mantiene corta la lista sin impedir períodos fuera de ella por la dirección (la página muestra el año pedido igual) |
| Bloquear las filas de `contracts` (`SELECT … FOR UPDATE`, en orden de `id`) al empezar la transacción | Confiar solo en las claves únicas | Con el bloqueo, dos emisiones simultáneas se serializan y la segunda encuentra la cuota ya creada (en vez de fallar con un error de clave duplicada); sin él, dos períodos distintos del mismo contrato podían chocar en la numeración. Las claves únicas quedan como última defensa. Sin bloquear `properties` ni `users` para no chocar con HU-10 |
| El número de cuota es `MAX(number) + 1` del contrato, en orden de emisión | Calcularlo por la posición del período dentro del contrato | Es lo que dice la regla («correlativa por contrato») y evita huecos; el costo es que emitir febrero antes que enero deja a febrero con el 1 (con prueba que lo documenta) |
| Todo o nada: si falla la inserción de una cuota se revierten todas | Emitir las que se pueda e informar los errores | Una emisión masiva a medias es difícil de reparar y de explicar; se informa el error y se puede repetir porque la emisión es idempotente |
| La emisión masiva sin contratos elegibles es un error con mensaje (no un informe vacío); la ya emitida entera sí da informe «0 emitidas / N omitidas» | Informe vacío en ambos casos | «No hay contratos» casi siempre es un período equivocado; «todo omitido» es el resultado correcto del escenario 2 |
| Patrón Post/Redirect/Get con el informe en la sesión (flash de una sola vista) | Mostrar el informe en la respuesta del `POST` | Recargar no repite el `POST`; si se recarga el informe se vuelve al formulario. Sigue el patrón de HU-10 |
| El monto es `monthly_rent` sin aplicar `update_percentage` | Calcular el ajuste | El contrato no guarda cada cuánto se actualiza y el criterio dice «monto igual al alquiler mensual» |
| Sin migración nueva ni clase «gestor» aparte | Una clase `GestorEmisionCuotas` en `app/Libraries` (como en la tarjeta CRC) | Se sigue el patrón del repositorio (la lógica transaccional vive en el modelo, como `createAndRentProperty()`); la responsabilidad de la tarjeta CRC queda en `InstallmentModel::issueForPeriod()` |
| Rutas para administrador **y empleado** | Solo administrador | La misma decisión que HU-07, HU-10 y HU-01 (R-7); si se decide otra cosa, se cambia el filtro de rol de las tres rutas |

**Seguridad aplicada:** autorización en el servidor (`AuthFilter` + `RoleFilter`), CSRF en el `POST` (con prueba), validación del período y del `contract_id` en el servidor con expresiones regulares estrictas (rechaza listas, decimales, negativos y fragmentos de SQL, con prueba), consultas con parámetros enlazados (el período se enlaza, no se concatena), escape de salida con `esc()` (con prueba de que un `<script>` en la dirección se muestra escapado), el monto y el vencimiento salen siempre del contrato (el cliente no los envía) y el período del cliente nunca llega a la base sin pasar por `buildPeriod()`.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de HU-13 | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/InstallmentIssuingTest.php` | ✅ 82 casos / 1202 aserciones |
| Tests unitarios | `… tests/unit/InstallmentPeriodTest.php` | ✅ 28 casos / 41 aserciones |
| Suite completa | Igual, sin archivo | Sobre la rama con `main` fusionado: **399 tests / 3614 aserciones** · ✅ 396 pasan · ❌ 3 errores previos, los mismos que da `origin/main` sin estos cambios (ver R-1). Antes de la corrección de la conexión compartida daba 20 errores: esos 3 más 17 de `PasswordResetTest` (ver R-8) |
| Comparación con `main` | Los tres archivos con errores, corridos en una copia limpia de `origin/main` | ✅ Fallan igual, sin cambios de HU-13 |
| Sintaxis | `php -l` en los 10 PHP nuevos o modificados | ✅ |
| Revisión visual | Páginas renderizadas por la aplicación con los datos de demostración en la base de pruebas y abiertas en el navegador: vista previa del mes siguiente (4 contratos «Se emitirá»), del mes en curso (4 «Ya emitida», total 0) e informe | ✅ Se ven como el prototipo (adaptado al estilo del panel). Se corrigieron dos desbordes que mostró la revisión (importes partidos en dos líneas y etiqueta de estado demasiado larga) |
| Hallazgos de la propia revisión | Una selección de año enviada como lista (`year[]=…`) y volviendo a mostrar el formulario | Corregido (ver sección 8) |

**Sin verificar:**
- Dos emisiones realmente simultáneas desde dos conexiones: la protección (bloqueo de contratos + claves únicas) se probó con envíos en secuencia y con el diseño, no con concurrencia real.
- Recorrido en vivo contra la base de desarrollo con sesión iniciada: las páginas se renderizaron con la base de pruebas y los datos de demostración; no se emitió ninguna cuota en la base de desarrollo (para no modificar los datos de demo).
- Que el informe desaparezca al recargar (flash de una sola vista): el entorno de pruebas no vence los flash entre peticiones; es el comportamiento estándar de `Session` de CodeIgniter.
- Comportamiento de `DATE_ADD(CAST(? AS DATE), …)` en MariaDB: las pruebas corrieron con el MySQL 8.4 local de WAMP.
- La hora del informe sale en UTC (la zona de la aplicación), igual que `created_at`.
- Validador W3C de HTML5/CSS3 y navegadores distintos del de la aplicación.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Falla previa | Tres pruebas de `main` fallan en esta máquina con el mismo error del `DOMParser` del framework («Attempt to read property "length" on bool»): `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` (HU-02) y `ProfileManagementTest::testClientCanSeeOnlyTheirOwnProfileAndImmutableAccountDetails` con `owner` y con `tenant` (HU-06). Fallan igual en una copia limpia de `origin/main` | Media | Avisar a quienes hicieron HU-02 y HU-06; el selector que usan no lo soporta el `DOMParser` (ver cómo lo resuelve `InstallmentIssuingTest::seeXPath()`) |
| R-2 | Pendiente de definir | La regla de «cubre el período» y la política de períodos pasados y futuros son decisiones mías, no del análisis | Media | Confirmarlas con el equipo; el cambio de la regla es una función (`ContractModel::coverageCondition()`) y se refleja en la prueba de los 24 períodos |
| R-3 | Resuelto | La rama salía de `feature/hu-10-alta-contratos`; al abrir el pull request se comprobó que HU-10 ya estaba en `main`, así que el PR #39 solo trae los commits de HU-13 | — | Cerrado |
| R-4 | Pendiente de definir | Permisos del empleado sobre la emisión (el análisis: «por definir») | Baja | Confirmarlo con el equipo; es un cambio de filtro en las tres rutas |
| R-5 | Deuda | `formatMoney` y `formatDate` están repetidos como funciones locales en cada vista (también en las de HU-07 y HU-10) | Baja | Moverlos a un helper cuando se toquen esas vistas |
| R-6 | Caso borde | Un contrato vigente cuyo inquilino fue dado de baja sigue emitiendo cuotas (la vista previa lo marca como «dado de baja») | Baja | Decidirlo con el equipo junto con R-7 de `REVIEW-HU-01-04-05_handoff.md` (baja de usuarios con contratos vigentes) |
| R-7 | Alcance | No hay forma de «deshacer» una emisión (anular cuotas emitidas por error) | Baja | Si hace falta, es una historia aparte; mientras tanto la cuota emitida es modificable solo por las historias de mora y pagos |
| R-8 | Deuda (resuelta aquí) | Tras un guardado fallido dentro de una transacción, la conexión compartida de las pruebas quedaba en estado «fallida» (modo estricto) y la siguiente transacción se revertía sola: la prueba de atomicidad de HU-10 hacía fallar 17 pruebas de `PasswordResetTest` (HU-03b), también en `main`. Se agregó `resetTransStatus()` tras el rollback en `ContractModel` (HU-10) y en `InstallmentModel`, con una aserción de regresión en cada prueba de atomicidad | Media | Cualquier modelo con `transBegin()`/`transRollback()` manual debe hacer lo mismo. `ContractModel::closeContract()` (HU-12) todavía no lo hace; no rompe hoy porque su prueba no provoca un fallo de base de datos, pero lo haría si se agrega |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisión del equipo del [PR #39](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/39) y comentar el tiempo en el issue #6 (el `gh` de esta máquina no tiene sesión: `gh auth login`; el PR se abrió desde Chrome) | — | — |
| 2 | Confirmar con el equipo las decisiones de la sección 4 (regla de «cubre el período», períodos pasados y futuros, empleado) | `app/Models/ContractModel.php` | `ci4-data-layer` |
| 3 | HU-14 — Cálculo del recargo por mora (pasar `pending` vencidas a `overdue` y calcular `late_fee`) | `app/Models/InstallmentModel.php`, `app/Controllers/`, `app/Views/` | `ci4-core`, `ci4-data-layer`, `qa-regression` |
| 4 | HU-15 — Estado de cuenta del inquilino (consume las cuotas emitidas) | `app/Controllers/DashboardController.php`, `app/Models/InstallmentModel.php` | `ci4-core`, `ci4-data-layer`, `frontend-html-css` |

**Siguiente HU sugerida:** HU-14 (cálculo del recargo por mora).
**Prerrequisitos del entorno:** `.env` con `database.default.*` y `database.tests.*`; base `inmogestion2026_test` creada; el usuario de MySQL de pruebas necesita permiso `TRIGGER` (la prueba de atomicidad crea y borra un disparador temporal). Para correr los tests: `php phpunit-11.phar -c phpunit.dist.xml --no-coverage` (el phar de PHPUnit 11 se descarga de phar.phpunit.de, fuera del proyecto).

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-10 | Claude Code con `LauPerisse` | 24 min | Lectura del issue #6, de HU-10 y del código de contratos; `InstallmentModel` con la transacción, `InstallmentController`, rutas, vistas, estilos, 110 pruebas, revisión visual con datos de demostración, corrección de la selección de año enviada como lista y documentación. Suite completa: 349 tests / 3243 aserciones, 1 falla previa de HU-02 (antes de las dos últimas pruebas). | `5026276`, `5683474` (rama `feature/hu-13-emision-cuotas`) |
| 2026-11-10 / 11 | Claude Code con `LauPerisse` | 27 min | Commit de la implementación, merge de `origin/main` con conflictos en el registro de tiempos y en `panel.css`, diagnóstico de 17 errores de `PasswordResetTest` (ya presentes en `main`, causados por el estado de transacción de la conexión compartida) y su corrección, comparación con una copia limpia de `main`, push y apertura del pull request. Suite completa: 399 tests / 3614 aserciones, 3 errores previos de HU-02 y HU-06 | `f0037f9`, `4886e8b` · [PR #39](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/39) |
| 2026-11-12 | Claude Code con `LauPerisse` | No medido (retrabajo pedido en la revisión del PR) | La revisión del PR señaló que el merge anterior había pisado tiempos ya escritos en `REGISTRO_DE_TIEMPOS.md` (se había quitado la fila de subtotal de `main` y reemplazado su total). Se fusionó de nuevo `origin/main` (entró HU-11 y HU-12), se dejó el registro exactamente como está en `main` y solo se agregó la fila y el detalle de HU-13 con el total actualizado (44 pts, 8 h 58 min efectivos, 10 h 09 min de reloj); en `ContractModel` y en la prueba del menú se conservaron los cambios de HU-11 y HU-12 junto con los de esta historia | `cbceac4` · [PR #39](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/39) |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio del issue (`Get-Date -Format "yyyy-MM-dd HH:mm"`); si se retoma el issue, anotar la hora de inicio de la nueva sesión.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8), completar la fila del issue en [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) y dejar el tiempo como comentario en el issue de GitHub.
