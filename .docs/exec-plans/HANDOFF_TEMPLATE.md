# Informe de Avance y Traspaso (Handoff)

> **Propósito:** Documento de trazabilidad del avance de una historia de usuario (HU) o issue, y a la vez traspaso de contexto entre agentes/sesiones.
> **Uso:** Copiar como `.docs/exec-plans/HU-XX_handoff.md` (o `current_state.md`) al cerrar cada sesión. Puede pegarse tal cual como comentario en el issue/PR. Mantener solo hechos verificados; no completar con suposiciones.

---

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | `[Nombre del proyecto]` |
| **Historia de usuario / Issue** | `[HU-XX — Título]` · `[#N° issue / URL]` |
| **Sprint / Hito** | `[ej: Sprint 1]` |
| **Rama** | `[feature/hu-xx-descripcion]` |
| **Último commit** | `[hash corto — mensaje]` |
| **Pull Request** | `[#N° / URL / Sin PR]` |
| **Fecha de cierre** | `[YYYY-MM-DD HH:MM]` |
| **Autor / Agente** | `[nombre]` |
| **Estado global** | `[🟢 Completada · 🟡 En curso · 🔴 Bloqueada]` |
| **Avance estimado** | `[0–100 %]` |

**Resumen (2–4 líneas):** `[Qué se logró en esta sesión y cuál es el estado resultante.]`

---

## 2. Alcance y Criterios de Aceptación

Referencia: `[.docs/historias_de_usuario/HU-XX_nombre.md]`

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | `[Criterio]` | `[✅ / 🟡 / ❌ / ⏳]` | `[Test, ruta o captura]` |
| CA-2 | `[Criterio]` | `[✅ / 🟡 / ❌ / ⏳]` | `[Test, ruta o captura]` |

**Fuera de alcance / diferido:** `[Qué se decidió no hacer y por qué.]`

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| `[Controller]` | `app/Controllers/...` | `[Nuevo / Modificado / Eliminado]` | `[Cambio concreto]` |
| `[Model]` | `app/Models/...` | `[...]` | `[...]` |
| `[Migration]` | `app/Database/Migrations/...` | `[...]` | `[...]` |
| `[Filter / Config / Routes]` | `app/...` | `[...]` | `[...]` |
| `[View]` | `app/Views/...` | `[...]` | `[...]` |
| `[Test]` | `tests/...` | `[...]` | `[...]` |

**Cambios de esquema de BD:** `[Tablas/columnas/índices/FK afectados, o "Ninguno"]`
**Cambios de rutas / permisos:** `[Rutas nuevas, filtros, roles con acceso, o "Ninguno"]`

---

## 4. Decisiones Técnicas

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| `[ej: Hash de claves con password_hash() BCRYPT]` | `[ej: Argon2]` | `[Motivo]` |

**Seguridad aplicada:** `[Validación server-side, autorización por rol, escape de salida, CSRF, manejo de sesión, etc.]`

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests automatizados | `[composer test / phpunit ...]` | `[✅ N pasan · ❌ N fallan · ⏳ No ejecutado]` |
| Migraciones | `php spark migrate` | `[Resultado]` |
| Prueba manual | `[Flujo probado y roles usados]` | `[Resultado]` |
| Regresión | `[Funcionalidades adyacentes revisadas]` | `[Resultado]` |

**Sin verificar:** `[Lo que NO se pudo probar y por qué.]`

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | `[Riesgo / Bloqueo / Deuda]` | `[Descripción]` | `[Alta / Media / Baja]` | `[Qué hacer y cuándo]` |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | `[Tarea inmediata]` | `[Rutas]` | `[ej: ci4-core, ci4-data-layer]` |
| 2 | `[Tarea siguiente]` | `[Rutas]` | `[...]` |

**Siguiente HU sugerida:** `[HU-XX — Título]`
**Prerrequisitos del entorno:** `[ej: php spark migrate, seeders, variables .env]`

---

## 8. Historial de Sesiones

| Fecha | Autor | Resumen | Commit / PR |
|---|---|---|---|
| `[YYYY-MM-DD]` | `[nombre]` | `[Una línea]` | `[hash / #PR]` |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y cargar solo los skills indicados en la sección 7.
2. Leer este handoff y la HU referenciada en la sección 2.
3. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
4. No releer código ajeno al alcance salvo necesidad demostrada.
5. Al terminar, actualizar este documento (secciones 1, 2, 3, 5, 6, 7 y 8).
