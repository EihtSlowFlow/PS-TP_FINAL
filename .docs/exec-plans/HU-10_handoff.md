# Informe de Avance y Traspaso (Handoff) — HU-10

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-10 — Alta y vinculación de contratos · [#15](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/15) |
| **Sprint / Hito** | Sprint 1 (Inmuebles y contratos) |
| **Rama** | `feature/hu-10-alta-contratos` (creada desde `docs/registro-de-tiempos`, cuyo commit ya está en `origin/main` por el PR #35) |
| **Último commit** | Sin commit: los cambios están en el árbol de trabajo, sin confirmar |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-10 21:07 |
| **Inicio / Fin** | 2026-11-10 20:47 → 2026-11-10 21:07 (el «fin» es el cierre de la implementación; todavía no hay commit) |
| **Tiempo invertido** | 20 min efectivos · 20 min de reloj · Medido · sin pausas de más de 10 minutos. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟢 Completada (falta commit, pull request y el comentario del tiempo en el issue) |
| **Avance estimado** | 100 % del alcance del issue |

**Resumen:** Se implementó el alta de contratos de alquiler para administradores y empleados: el formulario `ADM-07` (`/admin/contratos/nuevo`), el guardado atómico y la ficha del contrato `ADM-08` (`/admin/contratos/{id}`). El contrato nace «Vigente», toma como propietario al del inmueble y deja el inmueble «Alquilado» en la misma transacción, con la fila del inmueble bloqueada para impedir dos contratos simultáneos. Se agregaron 85 pruebas (suite completa: **241 tests / 2006 aserciones, 1 falla que ya existía en HU-02**).

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-10_alta_contrato_alquiler.md`](../historias_de_usuario/HU-10_alta_contrato_alquiler.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: con un inmueble disponible y un inquilino activo se registra el contrato «Vigente», el inmueble pasa a «Alquilado», el propietario es el del inmueble y se muestra la ficha con mensaje de confirmación | ✅ | `ContractCreationTest::testScenario1OperatorCreatesAContractInForceAndThePropertyBecomesRented` (administrador y empleado), `testScenario1TheContractFileShowsTheDataOfTheNewContract`, `testTheContractOwnerIsAlwaysTheOwnerOfThePropertyWhateverTheFormSends` |
| CA-2 | Escenario 2: un inmueble con contrato vigente, en mantenimiento, dado de baja o inexistente se rechaza con el aviso «no está disponible» y no cambia nada | ✅ | `testScenario2AnUnavailablePropertyIsRejectedAndNothingChanges` (5 casos), `testAPropertyWithAContractInForceIsRejectedEvenIfItsStatusSaysAvailable`, `testSubmittingTheSameContractTwiceCreatesOnlyOne` |
| CA-3 | Escenario 3: una fecha de finalización igual o anterior a la de inicio (o una fecha inválida) se rechaza con el error | ✅ | `testScenario3InvalidDatesAreRejectedAndNothingIsStored` (8 casos) |
| CA-4 | Escenario 4: si falla el guardado del contrato o la actualización del inmueble, no se persiste ningún cambio | ✅ | `testScenario4AFailureWhileStoringTheContractLeavesThePropertyUntouched`, `testScenario4AFailureWhileUpdatingThePropertyDiscardsTheContract` (con un disparador de MySQL que hace fallar la actualización del inmueble), `testTheTypedValuesSurviveAFailedSave` |
| RN-1 | Un inmueble solo tiene un contrato vigente a la vez | ✅ | Bloqueo de la fila + chequeo en el modelo (los tests anteriores) + índice único `uq_contracts_active_property_id` (ya cubierto por `SchemaIntegrityTest`) |
| RN-2 | Solo inmuebles activos y disponibles; inquilinos y propietarios activos | ✅ | `testTheFormOffersOnlyAvailablePropertiesAndActiveTenants`, `testADeactivatedTenantIsNotOfferedAndAPostWithTheirIdIsRejected`, `testOnlyUsersWithTheTenantRoleCanBeTheTenantOfAContract`, `testAPropertyWhoseOwnerWasDeactivatedCannotBeContracted` |
| RN-3 | Monto > 0; porcentajes entre 0 y 100; día de vencimiento entre 1 y 28 | ✅ | `testInvalidFieldsAreRejectedWithTheirOwnErrorAndNothingIsStored` (29 casos), `testValuesAtTheLimitsOfTheRulesAreAccepted` (límites 0, 100, 1, 28) |
| RN-4 | Creación del contrato y cambio de estado del inmueble atómicos | ✅ | Los tests del escenario 4 |
| DoD | Permisos de acceso | ✅ | `testAdministratorsAndEmployeesCanUseTheContractScreens`, `testOwnersAndTenantsCannotReachAnyContractRoute`, `testVisitorsAreRedirectedToTheLogin`, `testThePostWithoutACsrfTokenIsRejected` |

**Fuera de alcance / diferido:**
- Listado, búsqueda y filtros de contratos, y acceso del propietario a sus contratos: son de HU-11 (notas de integración agregadas en esa historia).
- Cierre de contratos (finalizar o rescindir): es de HU-12 (notas agregadas en esa historia).
- Emisión del cronograma de cuotas, «garantía», cláusulas, depósito, firma digital y el «Registrar nuevo inquilino» que aparecen en el prototipo `ADM-07`: no figuran en los criterios del issue y no hay tablas que los guarden. La emisión de cuotas es de HU-13.
- Las secciones de cuotas, observaciones y estado de cuenta del propietario del prototipo `ADM-08`: dependen de otras historias. La ficha muestra las partes, el plazo y las condiciones.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/ContractModel.php` | Nuevo | Estados y etiquetas, `createAndRentProperty()` (transacción con la fila del inmueble bloqueada con `FOR UPDATE`; el propietario sale del inmueble), `findWithDetails()` (une inmueble, inquilino y propietario sin filtrar por baja) y `describeDuration()` |
| Model | `app/Models/PropertyModel.php` | Modificado | `findAvailableForContract()`: inmuebles disponibles, sin baja y con propietario activo |
| Controller | `app/Controllers/ContractController.php` | Nuevo | `create`, `store` y `show`, con la validación en el servidor y los mensajes |
| Routes | `app/Config/Routes.php` | Modificado | `GET /admin/contratos/nuevo`, `POST /admin/contratos`, `GET /admin/contratos/(:num)` dentro del grupo `auth` + `role:admin,employee` |
| View | `app/Views/admin/contracts/form.php` | Nuevo | Formulario en tres secciones y resumen en vivo (pantalla `ADM-07`) |
| View | `app/Views/admin/contracts/show.php` | Nuevo | Ficha del contrato (pantalla `ADM-08`, reducida) |
| View | `app/Views/layouts/panel.php` | Modificado | Enlace «Contratos» en el menú de administrador y empleado |
| View | `app/Views/admin/properties/index.php` | Modificado | Acción «Crear contrato» en los inmuebles disponibles |
| Assets | `public/js/contracts.js`, `public/css/panel.css` | Nuevo / Modificado | Propietario, monto sugerido, duración y resumen en vivo; estilos de estado del contrato y de la caja del propietario |
| Test | `tests/feature/ContractCreationTest.php` | Nuevo | 75 casos de prueba (30 métodos con proveedores de datos) |
| Test | `tests/unit/ContractDurationTest.php` | Nuevo | 10 casos: texto de la duración y etiquetas de estado |
| Test | `tests/feature/AuthAccessControlTest.php` | Modificado | La prueba del menú por rol incluye «Contratos» |
| Docs | `.docs/historias_de_usuario/HU-10…`, `HU-11…`, `HU-12…`, `.docs/REGISTRO_DE_TIEMPOS.md` y este informe | Modificado / Nuevo | DoD de HU-10, notas de integración para HU-11 y HU-12, fila del tiempo |

**Cambios de esquema de BD:** Ninguno (usa `contracts`, `properties` y `users`, creadas en el issue #22; la tabla ya tiene el índice único y las restricciones `CHECK`).
**Cambios de rutas / permisos:**
- `GET /admin/contratos/nuevo` → `ContractController::create` (acepta `?property_id=` para preseleccionar)
- `POST /admin/contratos` → `ContractController::store` (CSRF)
- `GET /admin/contratos/(:num)` → `ContractController::show/$1`
- Todas con `auth` y `role:admin,employee`.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Rutas para administrador **y empleado** | Solo administrador | Es la misma decisión de HU-07 y de HU-01 (R-7): el empleado tiene las rutas operativas y su descripción en el sistema incluye contratos. El análisis aún tiene «por definir» qué funciones del administrador usa el empleado; si se decide otra cosa, se cambia el filtro de rol de las tres rutas |
| Bloquear la fila del inmueble (`SELECT … FOR UPDATE`) dentro de la transacción y volver a comprobar el estado y los contratos vigentes | Confiar solo en la validación del controlador o solo en el índice único | La validación previa da buenos mensajes pero no impide la carrera entre dos solicitudes; con el bloqueo la segunda espera y encuentra el inmueble ya «Alquilado». El índice único queda como última defensa |
| El propietario del contrato se toma de la fila bloqueada del inmueble; el formulario no lo envía (se muestra a modo de vista previa) | Aceptar un `owner_id` del formulario y compararlo | Lo exige la regla de negocio y elimina la manipulación del formulario (hay prueba que envía otro `owner_id`) |
| `update_percentage` obligatorio en el formulario, sin valor inicial (el campo tiene la ayuda «usá 0 si no se actualiza») | Dejarlo opcional con 0 por defecto | El criterio dice que el administrador lo informa; no se precargan comisión ni mora para que no se guarden valores que nadie eligió |
| Propietario inactivo del inmueble: el inmueble no se ofrece y, si se envía igual, se rechaza con un mensaje que manda a asignar un propietario activo desde la edición | Permitir el contrato con un propietario dado de baja | Es la regla «propietarios activos» y la nota de integración de HU-05 |
| El monto se sugiere con el precio base del inmueble y se reemplaza al cambiar de inmueble solo mientras el usuario no escribió uno propio | Dejar siempre vacío | HU-07 documenta que el precio base «se toma de base al confeccionar un contrato» |
| El selector de inquilinos usa `UserModel::findActiveTenantsForContract()` y el servidor valida con `findActiveById()` + rol `tenant` | Consultar `users` directamente | Es la nota de integración de HU-05 |
| La ficha es mínima y el menú «Contratos» abre el formulario hasta que exista el listado (HU-11) | Crear un listado provisorio | Evita adelantar HU-11; las notas de integración dicen qué reapuntar |
| `normalizeDecimal()` se copió en `ContractController` (igual que en `PropertyController`) | Moverlo a `BaseController` y tocar `PropertyController` | `PropertyController` lo están extendiendo HU-08 y HU-09: no se toca para evitar conflictos. Queda como deuda (R-4) |
| Sin duración máxima ni restricción sobre fechas pasadas | Limitar a N años o a fechas futuras | El issue solo exige fin posterior al inicio; registrar un contrato con inicio en el pasado puede ser legítimo |

**Seguridad aplicada:** autorización en el servidor (`AuthFilter` + `RoleFilter`), CSRF en el `POST` (con prueba), validación completa en el servidor, consultas con el Query Builder y una única consulta cruda con parámetros enlazados (`FOR UPDATE`), escape de salida con `esc()` (con prueba de que un `<script>` en la dirección se muestra escapado), propietario y estado no se confían al cliente, y la revisión del inquilino y del propietario activos en el servidor.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests de HU-10 | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage tests/feature/ContractCreationTest.php` | ✅ 75 casos (74 a la primera; el que falló era un error de la propia prueba, que se corrigió y pasó) |
| Tests unitarios | `… tests/unit/ContractDurationTest.php` | ✅ 10 casos / 13 aserciones |
| Suite completa | Igual, sin archivo | ✅ 240 de 241 · **241 tests / 2006 aserciones** (antes 156) · ❌ 1 falla previa, ver R-1 |
| Sintaxis | `php -l` en los 10 PHP nuevos o modificados | ✅ |
| Revisión visual | Páginas renderizadas por la aplicación y abiertas en el navegador: formulario con inmueble preseleccionado, formulario con errores y ficha | ✅ Se ven como el prototipo (adaptado al estilo del panel); los errores salen por campo y se conservan los datos |
| JavaScript | Probado en el navegador | ✅ Propietario al elegir el inmueble, monto sugerido que no pisa lo escrito, resumen (comisión, neto, mora) y duración («24 meses»); el propietario no viaja en el formulario |
| Atomicidad | Disparador de MySQL que bloquea cualquier `UPDATE` de `properties` | ✅ Sin contrato y con el inmueble «Disponible»; al quitar el disparador, el mismo pedido funciona |
| Fusión con `origin/main` | `git merge-tree` con una copia de los cambios | ✅ Sin conflictos (`origin/main` ya incluye HU-06 y el PR #35) |

**Sin verificar:**
- Dos solicitudes realmente simultáneas desde dos conexiones: la protección (bloqueo + índice único) se probó con el segundo envío en secuencia y con el diseño, no con concurrencia real.
- Recorrido en vivo contra la base de desarrollo con sesión iniciada: las páginas se renderizaron con la base de pruebas; no se creó ningún contrato en la base de desarrollo (para no modificar los datos de demo).
- Comportamiento de las restricciones `CHECK` en MariaDB o en MySQL anterior a 8.0.16: las pruebas corrieron con el MySQL local de WAMP.
- Validador W3C de HTML5/CSS3 y navegadores distintos del de la aplicación.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Falla previa | `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` (HU-02) falla en la suite completa («Crear cuenta» no aparece) y, ejecutada sola, falla distinto (`DOMParser`: «Attempt to read property "length" on bool»). Falló igual antes de tocar código de esta historia. `origin/main` modificó ese archivo de prueba después de esta rama | Media | Volver a correrla tras fusionar `origin/main`; si sigue fallando, avisar a quien hizo HU-02 |
| R-2 | Coordinación | HU-08 y HU-09 extienden la vista y el modelo de inmuebles; esta historia agrega una acción en `properties/index.php`, `PropertyModel::findAvailableForContract()` y el enlace del menú en `panel.php` | Baja | Los cambios son chicos y no chocaron en la fusión de prueba |
| R-3 | Pendiente de definir | Permisos del empleado sobre contratos (el análisis: «por definir») | Baja | Confirmarlo con el equipo; es un cambio de filtro en las rutas |
| R-4 | Deuda | `normalizeDecimal()` está duplicado en `PropertyController` y `ContractController` | Baja | Moverlo a `BaseController` cuando se toque `PropertyController` |
| R-5 | Dependencia | El enlace «Contratos» del menú y los «Volver al listado de inmuebles» apuntan a provisorios hasta que exista el listado de HU-11 | Baja | Ver las notas de integración de HU-11 |
| R-6 | Caso borde | Un inmueble cuyo propietario fue dado de baja no se ofrece para contratos: el usuario no ve un aviso en el selector | Baja | Si molesta, mostrarlo deshabilitado con la leyenda «propietario dado de baja» |
| R-7 | Datos | Al registrar el contrato no se genera ninguna cuota; el prototipo habla de «emitir cronograma» | Baja | Es de HU-13 (emisión de cuotas) |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Confirmar el commit, abrir el pull request desde `feature/hu-10-alta-contratos` y comentar el tiempo en el issue #15 (el `gh` de esta máquina no tiene sesión: `gh auth login`) | — | — |
| 2 | Fusionar `origin/main` en la rama antes del PR (trae HU-06) y repetir la suite | — | — |
| 3 | HU-11 — Consulta de contratos (listado, filtros y acceso del propietario) | `app/Controllers/ContractController.php`, `app/Models/ContractModel.php`, `app/Views/admin/contracts/` | `ci4-core`, `ci4-data-layer`, `ci4-http-validation` |
| 4 | HU-12 — Rescisión o finalización (transacción espejo de la de esta historia) | `app/Controllers/ContractController.php`, `app/Models/ContractModel.php` | `ci4-core`, `ci4-data-layer`, `php-security` |

**Siguiente HU sugerida:** HU-11 (consulta de contratos).
**Prerrequisitos del entorno:** `.env` con `database.default.*` y `database.tests.*`; base `inmogestion2026_test` creada; el usuario de MySQL de pruebas necesita permiso `TRIGGER` (la prueba de atomicidad crea y borra un disparador temporal). Para correr los tests: `php phpunit-11.phar -c phpunit.dist.xml --no-coverage` (el phar de PHPUnit 11 se descarga de phar.phpunit.de, fuera del proyecto).

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-10 | Claude Code con `LauPerisse` | 20 min | Lectura del issue #15 y del código de inmuebles y usuarios, modelo con transacción y bloqueo, controlador, rutas, formulario y ficha, JavaScript, 85 pruebas, revisión visual y notas de integración para HU-11 y HU-12. Suite completa: 241 tests / 2006 aserciones, 1 falla previa de HU-02 | Rama `feature/hu-10-alta-contratos`, sin commit |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio del issue (`Get-Date -Format "yyyy-MM-dd HH:mm"`); si se retoma el issue, anotar la hora de inicio de la nueva sesión.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8), completar la fila del issue en [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) y dejar el tiempo como comentario en el issue de GitHub.
