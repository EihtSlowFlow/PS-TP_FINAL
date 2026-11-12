# Informe de Avance y Traspaso (Handoff) — HU-12

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | HU-12 — Finalizar o rescindir un contrato de alquiler · Sin issue registrado |
| **Sprint / Hito** | Sprint 1 (Inmuebles y contratos) |
| **Rama** | `feature/hu-12-cierre-contratos` |
| **Último commit** | Sin commit: cambios en el árbol de trabajo |
| **Pull Request** | Sin PR |
| **Fecha de cierre** | 2026-11-11 19:39 |
| **Inicio / Fin** | 2026-11-11 18:56 → 2026-11-11 19:39 |
| **Tiempo invertido** | 43 min efectivos · 43 min de reloj · Medido · sin pausas de más de 10 minutos. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | joaquincorbalan (Copilot SDK en VS Code) |
| **Estado global** | 🟢 Completada |
| **Avance estimado** | 100 % |

**Resumen (2–4 líneas):** Se implementó el cierre de contratos vigentes con opción de finalización o rescisión, motivo opcional y confirmación en servidor. La operación actualiza el contrato, registra fecha y motivo, y devuelve el inmueble a «Disponible» en la misma transacción. También se agregaron pruebas automatizadas para el escenario feliz y el caso de reintento sobre un contrato ya cerrado.

---

## 2. Alcance y Criterios de Aceptación

Referencia: [`.docs/historias_de_usuario/HU-12_rescision_finalizacion_contrato.md`](../historias_de_usuario/HU-12_rescision_finalizacion_contrato.md)

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Finalizar o rescindir un contrato vigente con motivo y confirmación actualiza el estado y vuelve el inmueble a Disponible | ✅ | `ContractCreationTest::testClosingAContractClosesTheLeaseAndReturnsThePropertyToAvailable` |
| CA-2 | El historial financiero se conserva intacto y sigue siendo consultable | ✅ | `ContractModel::closeContract()` actualiza solo `status`, `closed_at` y `closing_reason`; no elimina cuotas, pagos ni comprobantes |
| CA-3 | Un contrato ya finalizado o rescindido no puede cerrarse nuevamente | ✅ | `ContractCreationTest::testClosingAnAlreadyClosedContractIsRejected` |

**Fuera de alcance / diferido:** No se agregaron nuevas pantallas para reportes ni liquidaciones; la implementación quedó acotada al cierre del contrato y a la confirmación del admin.

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| Model | `app/Models/ContractModel.php` | Modificado | Agregado `closeContract()` con transacción, bloqueo de la fila del inmueble, cierre atómico y comprobación de contrato vigente |
| Controller | `app/Controllers/ContractController.php` | Modificado | Nuevos métodos `close()`, `finalize()` y `rescind()`, mensajes y confirmación server-side |
| Routes | `app/Config/Routes.php` | Modificado | Rutas `POST /admin/contratos/{id}/finalizar`, `/rescindir` y `/cerrar` |
| View | `app/Views/admin/contracts/show.php` | Modificado | Botones y estado de cierre para contratos activos |
| View | `app/Views/admin/contracts/close_confirm.php` | Nuevo | Pantalla de confirmación con motivo del cierre |
| Test | `tests/feature/ContractCreationTest.php` | Modificado | Casos de cierre feliz y de rechazo por contrato ya cerrado |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** Las rutas nuevas quedan bajo `admin` con `auth` + `role:admin,employee`, coherentes con el resto de gestión de contratos.

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Cerrar la operación en una misma transacción con `FOR UPDATE` | Actualizar sin bloqueo o en dos pasos separados | Evita race conditions y garantiza que el inmueble no quede en dos estados contradictorios |
| Guardar `closed_at` y `closing_reason` solo cuando el contrato sale de `in_force` | Dejar esos campos con valor en contratos vigentes | Respeta la regla `CHECK` de la migration y la semántica del esquema |
| Regresar el inmueble a `available` aunque haya pasado por mantenimiento | Mantener el estado previo | La HU-12 establece que el inmueble vuelve a disponibilidad al cerrarse el contrato |

**Seguridad aplicada:** validación del estado solicitado, cierre confirmado por POST, uso del CSRF del framework y autorización por rol del grupo `admin`/`employee`.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests automatizados | `vendor\bin\phpunit --filter ContractCreationTest tests\feature\ContractCreationTest.php` | ✅ 1 suite ejecutada con éxito |
| Migraciones | `php spark migrate` | No se ejecutó; no hubo cambios de esquema |
| Prueba manual | Flujo de cierre desde la ficha del contrato | No ejecutado en navegador |
| Regresión | Suite del módulo de contratos | ✅ Ejecutada con éxito |

**Sin verificar:** la prueba visual del flujo desde un navegador real y la revisión del CSS/UX de los nuevos botones en el panel.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Deuda | El motivo del cierre se guarda como texto libre y se trunca a 255 caracteres. | Media | Si en el futuro se requiere un catálogo de motivos, convertirlo en tabla o enumeración. |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Revisar y cerrar la historia de HU-12 en el tablero/issue cuando exista el número | `.docs/exec-plans/HU-12_handoff.md` | `ci4-core`, `ci4-data-layer` |
| 2 | Si se desea UI más elaborada, incluir confirmación con modal y selector de motivo | `app/Views/admin/contracts/show.php` | `frontend-javascript` |

**Siguiente HU sugerida:** HU-13 — Emisión masiva de cuotas
**Prerrequisitos del entorno:** `php spark migrate` y seeders activos para probar en entorno local.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-11 | joaquincorbalan (Copilot SDK en VS Code) | 43 min | Implementación del cierre de contratos vigentes, validación de estado y pruebas de regresión | Sin commit |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Anotar la hora de inicio del issue (`Get-Date -Format "yyyy-MM-dd HH:mm"`); si se retoma el issue, anotar la hora de inicio de la nueva sesión.
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. No releer código ajeno al alcance salvo necesidad demostrada.
6. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8), completar la fila del issue en [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) y dejar el tiempo como comentario en el issue de GitHub.
