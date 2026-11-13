# Informe de Avance y Traspaso (Handoff) — HU-14

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-14 — Cálculo automático de recargo por mora · [#8](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/8) |
| **Sprint / Hito** | Sprint 2 (Módulo financiero: cuotas y mora) |
| **Rama** | `feature/hu-14-recargo-mora` (creada desde `origin/main` con HU-13 ya fusionada, PR #39) |
| **Último commit** | El que agrega este informe (`feat: calcular automáticamente el recargo por mora de las cuotas vencidas (HU-14)`) |
| **Pull Request** | Abierto desde Chrome tras el push (el `gh` de esta máquina no tiene sesión): ver el enlace en el issue #8 y en el historial de la rama |
| **Fecha de cierre** | 2026-11-13 07:34 |
| **Inicio / Fin** | 2026-11-13 06:26 → 2026-11-13 07:34 |
| **Tiempo invertido** | 50 min efectivos · 1 h 08 min de reloj · Medido · pausa de 18 min (06:46 a 07:04) descontada. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟢 Completada (pull request abierto; falta la revisión del equipo) |
| **Avance estimado** | 100 % del alcance del issue |

**Resumen:** Se implementó el cálculo automático del recargo por mora. `LateFeeCalculator` calcula con aritmética entera (sin decimales binarios) `base × % diario ÷ 100 × días de atraso`, contando desde el día siguiente al vencimiento y redondeando al centavo (la mitad hacia arriba). `InstallmentModel::refreshLateFees()` pasa a «Vencida» las cuotas `pending` con vencimiento anterior a hoy y guarda su recargo en `installments.late_fee`; lo hace en cada consulta y nunca toca las cuotas en revisión ni las pagadas. Hay una pantalla nueva de cobranzas (`/admin/cuotas`, `ADM-10`) con el desglose base, mora y total, totales, filtros y paginación, y el detalle de una cuota (`/admin/cuotas/{id}`). Se agregaron 174 pruebas (101 unitarias y 73 de integración).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-14_calculo_recargo_mora.md`](../historias_de_usuario/HU-14_calculo_recargo_mora.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: una cuota «Pendiente» con la fecha actual por encima de su vencimiento pasa a «Vencida» al consultar las cobranzas y se calcula su recargo según los días de atraso y el porcentaje diario del contrato; el total exigible se muestra desglosado en base y recargo | ✅ | `LateFeeTest::testScenario1ConsultingTheCollectionsMarksThePastDueInstallmentOverdueAndCalculatesItsFee` (administrador y empleado), `testScenario1TheListShowsTheBreakdownOfBaseLateFeeAndTotal`, `testScenario1TheDetailShowsTheBreakdownAndTheFormula`, `testTheLateFeeUsesTheDailyPercentageOfEachContract`, `testAnInstallmentIssuedForAPastPeriodIsOverdueTheFirstTimeItIsConsulted` |
| CA-2 | Escenario 2: una cuota pagada a término conserva el recargo en cero al consultar su detalle | ✅ | `testScenario2AnInstallmentPaidOnTimeKeepsAZeroLateFee`, `testScenario2TheDetailOfAnInstallmentPaidOnTimeShowsAZeroLateFee`, `testAPaidInstallmentNeverChangesNotEvenWhenItHasAFeeAlready` |
| CA-3 | Escenario 3: una cuota en revisión no modifica su recargo al recalcular la mora mientras dure la revisión | ✅ | `testScenario3TheLateFeeOfAnInstallmentUnderReviewDoesNotChangeWhileTheReviewLasts` (4 fechas), `testScenario3AnInstallmentUnderReviewWithoutFeeDoesNotStartAccruingOne`, `testScenario3TheScreensDoNotModifyAnInstallmentUnderReviewAndShowItsFee` |
| RN-1 | Recargo = base × (% diario ÷ 100) × días de atraso | ✅ | `LateFeeCalculatorTest::testLateFeeIsTheBaseTimesTheDailyPercentageTimesTheDays` (14 casos), `testTheFeeGrowsByTheSameStepEveryDayUntilItIsRounded`, `LateFeeTest::testTheLateFeeGrowsOneDayAtATimeAndKeepsTheInstallmentOverdue` |
| RN-2 | Los días de atraso se cuentan desde el día posterior al vencimiento | ✅ | `LateFeeCalculatorTest::testDaysLateAreCountedFromTheDayAfterTheDueDate` (13 casos: víspera, día del vencimiento, primer día, fin de mes, fin de año, febrero común y bisiesto), `LateFeeTest::testTheDelayIsCountedFromTheDayAfterTheDueDate` (5 días límite) |
| RN-3 | Las cuotas pagadas no generan ni modifican recargos | ✅ | Las pruebas del escenario 2 y `testAPaidInstallmentNeverChangesNotEvenWhenItHasAFeeAlready` (compara la fila completa antes y después) |
| RN-4 | El cálculo se hace en el servidor y es reproducible | ✅ | `testTheSameInputsAlwaysGiveTheSameResult`, `testCalculatingTwiceForTheSameDayChangesNothing` (la fila, con `updated_at`, queda igual), `testTheResultIsTheSameWhateverTheOrderOrTheNumberOfConsultations` |
| DoD | Valores límite y casos de error | ✅ | Redondeo (12 casos con la mitad exacta), tope técnico de la columna (5 casos de desborde), montos y porcentajes inválidos (20 casos), fechas inválidas (24 casos), porcentaje 0 y 100, fecha inválida en `refreshLateFees()`, filtros inválidos (9 casos), ids no numéricos o desbordados |
| DoD | Permisos de acceso | ✅ | `testOwnersAndTenantsCannotReachTheCollectionsNorChangeAnyFee` (además comprueba que una petición prohibida no recalcula nada), `testVisitorsAreRedirectedToTheLogin`, `testTheSidebarOffersTheCollectionsToOperatorsAndMarksTheActiveSection` |

**Fuera de alcance / diferido:**
- Los botones «Exportar listado», «Notificar deudores», «Enviar aviso», «Reclamar», «Recibo PDF», «Ver comprobante» y «Configurar parámetros de mora» del prototipo `ADM-10`: no figuran en los criterios del issue; las notificaciones son de HU-22 y los comprobantes de HU-16 a HU-18.
- Estado de cuenta del inquilino (INQ-01): es HU-15. Tiene que llamar a `InstallmentModel::refreshLateFees()` (ver la sección 7); la ruta `/inquilino/estado-cuenta` sigue siendo el placeholder de HU-01.
- Una tarea programada que recalcule sin que nadie consulte: el issue lo deja «por definir» y se resolvió con recálculo en cada consulta (ver la sección 4). Basta llamar al mismo método desde un comando de `spark` si el equipo la quiere.
- El prototipo muestra el cálculo «al 06/11/2026» con un banner de «próximo corte automático»: como no hay corte programado, el banner dice el día para el que se calculó.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Library | `app/Libraries/LateFeeCalculator.php` | Nuevo | Funciones puras: `daysLate()`, `daysUntilDue()`, `lateFee()`, `forInstallment()` y `assertValidDate()`. Aritmética entera, redondeo de la mitad hacia arriba, validación estricta de fechas, montos y porcentajes y límite técnico de la columna (`MAX_LATE_FEE`) |
| Model | `app/Models/InstallmentModel.php` | Modificado | `today()` (único origen de «hoy», congelable con `Time::setTestNow()`), `refreshLateFees()` (estado y recargo guardados al día), `paginateCollections()`, `summarizeCollections()`, `periodsWithInstallments()` y `findCollectionDetail()` para las pantallas |
| Controller | `app/Controllers/InstallmentController.php` | Modificado | `index` (cobranzas: recalcula, filtra, pagina y totaliza) y `show` (detalle, recalcula solo esa cuota); validación de filtros y de página |
| Routes | `app/Config/Routes.php` | Modificado | `GET /admin/cuotas` y `GET /admin/cuotas/(:num)` dentro del grupo `auth` + `role:admin,employee` |
| View | `app/Views/admin/installments/index.php` | Nuevo | Pantalla `ADM-10`: aviso del cálculo, tres tarjetas (a cobrar, cobrado, en mora), filtros, tabla con base, mora y total por cuota, fila de totales y paginación |
| View | `app/Views/admin/installments/show.php` | Nuevo | Detalle de la cuota con el desglose (base, recargo con su fórmula y total) y los datos de la cuota |
| View | `app/Views/layouts/panel.php` | Modificado | Enlace «Cobranzas» en el menú de administrador y empleado |
| Assets | `public/css/panel.css` | Modificado | Estilos de la mora (`row-overdue`, `cell-note`, `breakdown-*`, `text-danger`, tarjeta en mora) y celdas más angostas para que la tabla de nueve columnas entre en una pantalla de 1440 px sin desplazarse |
| Test | `tests/unit/LateFeeCalculatorTest.php` | Nuevo | 101 casos / 225 aserciones: días de atraso, fórmula, redondeo, desborde, entradas inválidas |
| Test | `tests/feature/LateFeeTest.php` | Nuevo | 73 casos: los tres escenarios, límites del vencimiento, filtros, totales, paginación, escape de salida, detalle y permisos. El reloj queda fijo en 2026-11-13 |
| Docs | `.docs/historias_de_usuario/HU-14…`, `.docs/ORGANIZACION_DEL_PROYECTO.md`, `.docs/REGISTRO_DE_TIEMPOS.md` y este informe | Modificado / Nuevo | DoD de HU-14, decisiones y notas de integración, fila del tiempo |

**Cambios de esquema de BD:** Ninguno (usa `installments` y `contracts`; el índice `idx_installments_status_due_date` ya existía para esta consulta).
**Cambios de rutas / permisos:**
- `GET /admin/cuotas` → `InstallmentController::index` (acepta `?q=`, `?period=AAAA-MM`, `?status=` y `?page=`)
- `GET /admin/cuotas/(:num)` → `InstallmentController::show`
- Ambas con `auth` y `role:admin,employee`. Son de solo lectura para el usuario; el único efecto es el recálculo, que es idempotente.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| El recargo se recalcula **en cada consulta** y se guarda en `late_fee` | Tarea programada; calcular al vuelo sin guardar | El escenario 1 dice «cuando se consulta»; no hay infraestructura de tareas en el proyecto; guardar el valor deja el dato disponible para HU-19, HU-27 y los reportes. Es reproducible porque depende solo de los datos y del día |
| El cálculo vive en una clase pura (`LateFeeCalculator`) y no en SQL | Un único `UPDATE … JOIN` con `DATEDIFF` y `ROUND` | Probar valores límite sin base de datos (101 casos en 0,3 s) y una sola fórmula. La tarjeta CRC `CalculadorMora` queda cumplida por esta clase |
| Redondeo **al centavo, mitad hacia arriba**, una sola vez sobre el resultado | Redondear por día; truncar | Coincide con `ROUND` de MySQL y con la carga de demostración; el issue lo deja «por definir» |
| Aritmética entera (centavos × centésimas de porcentaje) | `float` o `bcmath` | Resultado idéntico en cualquier máquina; no depende de la extensión `bcmath` |
| **Sin tope de negocio**; solo el límite de la columna (9.999.999.999,99) | Un tope relativo al monto base | El issue no lo define. Con el límite técnico el valor nunca revienta el `INSERT/UPDATE`; el desborde se prueba |
| «Hoy» sale de `InstallmentModel::today()` (zona `appTimezone`, hoy UTC) | `CURDATE()` de MySQL | Un solo punto, congelable en las pruebas. Riesgo de la zona horaria en R-2 |
| El `UPDATE` de cada cuota repite `WHERE status IN ('pending','overdue')` | Una transacción con bloqueo de filas | Si otro proceso mueve la cuota a «En revisión» o «Pagada» a mitad del recálculo, no se pisa. Cada fila es independiente y se omite la que ya está bien (no cambia `updated_at`) |
| Se recalculan también las cuotas de contratos finalizados o rescindidos | Solo contratos vigentes | La deuda sigue existiendo aunque el contrato se haya cerrado (HU-12 conserva las cuotas emitidas) |
| La búsqueda trata `%` y `_` como texto | Dejarlos como comodines de `LIKE` | La prueba mostró que el query builder de esta versión no los escapa: `q=%` devolvía todo. Se escapan con el carácter `!` y la cláusula `ESCAPE` |
| El listado muestra la mora de una cuota en revisión como «Fijo durante la revisión» y la de una pagada como «Recargo registrado» | Mostrar los días | Los días no son coherentes con un recargo congelado |
| Rutas para administrador **y empleado** | Solo administrador | La misma decisión que HU-07, HU-10, HU-13 y HU-01 (R-7) |

**Seguridad aplicada:** autorización en el servidor (`AuthFilter` + `RoleFilter`; con prueba de que una petición prohibida no dispara el recálculo), filtros validados con listas blancas y expresiones regulares estrictas (rechaza listas, mes 13, años fuera de rango, búsquedas de más de 100 caracteres), consultas con parámetros enlazados (con prueba de que `' OR 1=1 --` no devuelve nada), `%` y `_` escapados, ids de ruta numéricos (`(:num)`, con prueba de desborde de entero), escape de salida con `esc()` (con prueba de que un `<script>` en la dirección y en la búsqueda se muestra escapado).

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Calculadora | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/unit/LateFeeCalculatorTest.php` | ✅ 101 casos / 225 aserciones |
| HU-14 integración | `… tests/feature/LateFeeTest.php` | ✅ 73 casos |
| Archivos afectados | `LateFeeTest`, `InstallmentIssuingTest`, `AuthAccessControlTest` y `tests/unit` | ✅ 320 casos / 2184 aserciones |
| Suite completa | Igual, sin archivo | **579 tests / 4484 aserciones** · ✅ 576 pasan · ❌ 3 errores previos de `main` (ver R-1), los mismos que ya daban antes de HU-14 |
| Sintaxis | `php -l` en los 5 PHP nuevos (calculadora, modelo, controlador y las 2 vistas) | ✅ |
| Revisión visual | Páginas renderizadas por la aplicación con los datos de demostración en la base de pruebas y abiertas en el navegador (1440 px): listado, detalle de una cuota vencida | ✅ Las cifras coinciden (250.000 × 0,5 % × 3 días = 3.750). La revisión mostró que la tabla se desplazaba 19 px hacia el costado; se achicó el relleno de las celdas y entra completa |

**Sin verificar:**
- Dos consultas realmente simultáneas desde dos conexiones: la protección (el `UPDATE` repite la condición de estado) se probó por diseño, no con concurrencia real.
- Recorrido en vivo contra la base de desarrollo con sesión iniciada: las páginas se renderizaron con la base de pruebas; no se tocó la base de desarrollo.
- Que los totales y el listado sigan rápidos con miles de cuotas: el recálculo lee solo `pending`/`overdue` vencidas (hay índice por estado y vencimiento) y escribe solo lo que cambió, pero no se midió con volumen.
- Comportamiento en MariaDB: las pruebas corrieron con MySQL 8.4.7.
- Validador W3C y navegadores distintos del panel integrado; la vista en teléfono.

**Entorno de las pruebas de esta sesión:** los servicios de MySQL de WAMP estaban apagados y no se pueden iniciar sin permisos de administrador (el `.env` apunta al `MySQL80` del sistema). Se corrieron con una instancia aislada de MySQL 8.4.7 en el directorio temporal de la sesión (puerto 3307, sin tocar el `.env` ni ninguna base del usuario), sobre una copia de trabajo temporal de la rama con un `.env` propio. Las variables de entorno `database.tests.*` no pisan al `.env` en este proyecto (el `.env` gana), por eso hizo falta la copia.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Falla previa | Tres pruebas de `main` fallan con el error del `DOMParser` del framework («Attempt to read property "length" on bool»): `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` (HU-02) y `ProfileManagementTest::testClientCanSeeOnlyTheirOwnProfileAndImmutableAccountDetails` con `owner` y con `tenant` (HU-06). Ya fallaban antes de HU-14 | Media | Avisar a quienes hicieron HU-02 y HU-06; ver cómo lo resuelve `InstallmentIssuingTest::seeXPath()` |
| R-2 | Zona horaria | «Hoy» es la fecha en UTC (`appTimezone`). De 21:00 a 24:00 en Argentina ya es el día siguiente en UTC: una cuota que vence hoy se marca vencida unas horas antes y su primer día de mora se adelanta | Media | Decidir con el equipo: cambiar `appTimezone` (afecta `created_at` de todo el sistema) o convertir solo en `InstallmentModel::today()` |
| R-3 | Pendiente de definir | Las decisiones de redondeo, tope, recálculo en cada consulta y de qué pasa con una revisión rechazada (se cuentan los días desde el vencimiento, también los de la revisión) son mías, no del análisis | Media | Confirmarlas con el equipo; el redondeo y el tope están en `LateFeeCalculator`, la política de revisión en HU-17 |
| R-4 | Diseño | Una petición `GET` escribe en la base (el recálculo). Es idempotente y solo la pueden hacer administrador y empleado, pero un recorrido de solo lectura que modifica filas puede sorprender | Baja | Si molesta, mover el recálculo a un comando de `spark` programado y dejar las pantallas de solo lectura |
| R-5 | Integración | HU-15 (estado de cuenta) no recalcula por sí sola: si no llama a `refreshLateFees()`, el inquilino vería una cuota «Pendiente» vencida y sin recargo hasta que alguien abra las cobranzas | Alta (para HU-15) | Llamarlo en `DashboardController::tenantAccountStatus` (ver la sección 7) |
| R-6 | Deuda | `formatMoney`, `formatDate` y `formatPercentage` están repetidos como funciones locales en las vistas (ya señalado en R-5 de HU-13) | Baja | Moverlos a un helper cuando se toquen esas vistas |
| R-7 | Deuda | `ContractModel::closeContract()` (HU-12) todavía no hace `resetTransStatus()` tras el rollback (ver R-8 de HU-13). No afecta a HU-14 | Baja | Agregarlo si se prueba un fallo de base en el cierre |
| R-8 | Entorno | El `.env` gana sobre las variables de entorno y los servicios de MySQL de WAMP no arrancan sin administrador: correr las pruebas exige iniciar el `MySQL80` del sistema desde un terminal con permisos | Baja | Anotado en la sección 5 |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisión del equipo del pull request de HU-14 y confirmar las decisiones de la sección 4 (redondeo, sin tope, recálculo en cada consulta, zona horaria R-2) | `app/Libraries/LateFeeCalculator.php`, `app/Models/InstallmentModel.php` | `ci4-data-layer` |
| 2 | HU-15 — Estado de cuenta del inquilino: llamar a `model(InstallmentModel::class)->refreshLateFees(null, null)` al abrir la pantalla (o, con `$installmentId`, por cuota) y mostrar base, recargo y total como `admin/installments/show.php` | `app/Controllers/DashboardController.php`, `app/Models/InstallmentModel.php` | `ci4-core`, `ci4-data-layer`, `frontend-html-css` |
| 3 | HU-16 y HU-17 — Pagos: al informar un pago la cuota pasa a `under_review` (el recálculo la ignora) y al aprobarlo a `paid` | `app/Models/`, `app/Controllers/` | `ci4-core`, `ci4-data-layer` |
| 4 | Comentar el tiempo en el issue #8 (se hace desde Chrome: el `gh` de esta máquina no tiene sesión) | — | — |

**Siguiente HU sugerida:** HU-15 (estado de cuenta del inquilino), que depende de esta.
**Prerrequisitos del entorno:** `.env` con `database.default.*` y `database.tests.*`; MySQL en marcha con la base `inmogestion2026_test` creada (el usuario de pruebas necesita permiso `TRIGGER`). Para correr los tests: `php phpunit-11.phar -c phpunit.dist.xml --no-coverage` (el phar de PHPUnit 11 se descarga de phar.phpunit.de, fuera del proyecto).

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-13 | Claude Code con `LauPerisse` | 50 min efectivos (68 min de reloj) | Lectura del issue #8, del handoff de HU-13, del prototipo `ADM-10` y del código de cuotas y contratos; `LateFeeCalculator`, `refreshLateFees()` y las consultas de cobranzas, controlador, rutas, vistas y estilos; 174 pruebas; base de pruebas aislada por no poder iniciar el servicio de MySQL; corrección de la búsqueda con `%` y `_`; revisión visual y ajuste del ancho de la tabla; suite completa (579 tests, 3 errores previos); documentación; issue pasado a «In review» en el tablero | Commit de esta rama · pull request contra `main` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio del issue (`Get-Date -Format "yyyy-MM-dd HH:mm"`); si se retoma el issue, anotar la hora de inicio de la nueva sesión.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8), completar la fila del issue en [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) y dejar el tiempo como comentario en el issue de GitHub.
