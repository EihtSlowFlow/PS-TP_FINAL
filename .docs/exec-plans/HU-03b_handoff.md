# Informe de Avance y Traspaso (Handoff) — HU-03b

## 1. Resumen Ejecutivo

| Campo                           | Valor                                                                 |
| ------------------------------- | --------------------------------------------------------------------- |
| **Proyecto**                    | InmoGestión 2026                                                      |
| **Historia de usuario / Issue** | HU-03b — Restablecer la contraseña · [#18](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/18) |
| **Rama**                        | `feature/hu-03b-restablecer-contrasena`                               |
| **Último commit**               | `244bb54` (merge de `main` `1f68513`) + commit de cierre de HU-03b    |
| **Pull Request**                | Abierto hacia `main`                                                  |
| **Fecha de cierre**             | 2026-11-10 22:58                                                     |
| **Inicio / Fin**                | Implementación inicial sin hora de inicio medida (`5b86cb4`, 17:49). Sesión de revisión, endurecimiento y prueba manual: 2026-11-10 20:37 → 22:58 (pausa 21:42 → 22:27 descontada) |
| **Tiempo invertido**            | **1 h 36 min efectivos** · 2 h 21 min de reloj · Medido en la sesión de revisión y cierre (pausa de 45 min descontada). Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente**              | Ramiro (`EihtSlowFlow`), con asistencia de IA                         |
| **Estado global**               | 🟢 Completada (verificada automática y manualmente)                   |
| **Avance estimado**             | 100 %                                                                 |

**Resumen:** Se implementó e integró la recuperación de contraseña con token de un solo uso, vigencia de 60 minutos y almacenamiento solo del hash SHA-256. **No hay canal externo (correo ni SMS)**: es una decisión del equipo acordada con el docente (ver sección 4 bis). Para compensarlo, la solicitud exige correo + DNI, rechaza en la misma pantalla cualquier combinación que no coincida con un mensaje genérico (`"Las credenciales ingresadas son incorrectas."`, sin revelar si falló el correo o el DNI) y al tercer intento fallido activa de inmediato un enfriamiento de 2 minutos. El flujo usa un controlador propio (`PasswordResetController`) para no mezclarlo con el login/registro. La tabla `password_resets` ya existía en la migración `2026-11-06-100300`; no hay cambios de esquema.

## 2. Alcance y Criterios de Aceptación

Referencia: `.docs/historias_de_usuario/HU-03b_*.md`

| #    | Criterio de aceptación                                                          | Estado | Evidencia                                                                                  |
| ---- | ------------------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------ |
| CA-1 | Usuario activo solicita restablecimiento (correo + DNI) y recibe token temporal de un solo uso | ✅     | `PasswordResetController::sendLink`; `PasswordResetTest::testScenario1*`                   |
| CA-2 | Token válido + contraseña válida: actualiza, invalida token, redirige al login  | ✅     | `PasswordResetController::updatePassword`; `PasswordResetTest::testScenario2*`             |
| CA-3 | Token inválido, usado o vencido: se rechaza y se pide nueva solicitud           | ✅     | `PasswordResetTest::testScenario3*`                                                        |
| CA-4 | Correo/DNI sin cuenta activa: se rechaza en la solicitud con mensaje genérico (`"Las credenciales ingresadas son incorrectas."`) | ✅ (ver 4 bis) | `PasswordResetTest::testScenario4*`                                                        |
| RB-1 | Token temporal, un solo uso, no predecible                                      | ✅     | `random_bytes(32)`, `used_at`, `expires_at`; `testTokensAreUnpredictable`                  |
| RB-2 | Contraseña de 8+ caracteres con mayúscula, número y símbolo (`strong_password`, igual que registro y perfil), almacenada cifrada | ✅     | `min_length[8]\|strong_password`, `PASSWORD_BCRYPT`; `testScenario2PasswordWithoutComplexity*` |
| RB-3 | Usuarios dados de baja no pueden restablecer (reciben `"Usuario dado de baja, hable con un administrador."`) | ✅     | No se emite token (`PasswordResetController::ACCOUNT_DISABLED_MESSAGE`); además `findUsableByToken` exige `users.deleted_at IS NULL` |

**Fuera de alcance / diferido:** cierre de sesiones activas del usuario tras el cambio de contraseña.

**Agregado en la revisión:** enfriamiento de 2 minutos en el tercer intento fallido (ver 4 bis).

## 3. Cambios Realizados

| Capa        | Archivo                                    | Tipo       | Descripción                                                                  |
| ----------- | ------------------------------------------ | ---------- | ---------------------------------------------------------------------------- |
| Rutas       | `app/Config/Routes.php`                    | Modificado | 4 rutas públicas en el grupo `auth`                                          |
| Controlador | `app/Controllers/PasswordResetController.php` | Nuevo   | Solicitud (correo + DNI), rechazo con mensaje genérico, enfriamiento al 3.er intento y actualización de contraseña |
| Modelo      | `app/Models/PasswordResetModel.php`        | Nuevo      | Emisión, búsqueda de token usable, consumo atómico e invalidación            |
| Vistas      | `app/Views/auth/forgot_password.php`, `reset_password.php` | Nuevo | Formularios con el estilo de `register.php`; campo DNI y ayuda de la política de contraseña |
| Pruebas     | `tests/feature/PasswordResetTest.php`      | Nuevo      | Escenarios 1 a 4, reglas de negocio y errores                                |
| Seguridad   | `app/Libraries/ResetAttemptLimiter.php` | Nuevo | Contador de intentos fallidos y enfriamiento de 2 minutos (cache del framework), por IP y por correo |
| Seguridad   | `app/Filters/SecureHeaders.php`, `app/Config/Filters.php` | Nuevo / Modificado | Conserva encabezados más restrictivos de respuestas sensibles (`no-referrer`) |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** Rutas públicas bajo CSRF global. El enlace "¿Olvidaste tu contraseña?" del login aparece solo, porque `AuthController::login` ya detecta `auth/recuperar-password`.

## 4. Decisiones Técnicas

| Decisión                                                        | Justificación                                                                                         |
| --------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Controlador propio en lugar de ampliar `AuthController`         | Evita conflictos de merge con HU-02/HU-03 y separa responsabilidades                                  |
| Solo se guarda `sha256(token)`                                  | Una filtración de la tabla no permite usar tokens pendientes                                          |
| Emisión y consumo atómicos en transacción | Una nueva solicitud invalida las anteriores y dos envíos simultáneos no pueden cambiar la contraseña dos veces |
| Cuenta activa: redirección directa al formulario | El alcance no contempla correo; el token se entrega únicamente en el flujo local de la aplicación |
| Mensaje de error genérico (`"Las credenciales ingresadas son incorrectas."`) cuando correo + DNI no coinciden con una cuenta activa | Impide avanzar al formulario sin credenciales válidas y no distingue entre correo inexistente, dado de baja o DNI erróneo (igual que el login en HU-01) |
| Enfriamiento: el tercer intento fallido bloquea de inmediato por 2 minutos (por IP y por correo) | Frena la fuerza bruta del DNI. Solo cuentan los intentos que no coinciden con una cuenta activa; los de formato inválido no cuentan. Se bloquea igual exista o no el correo, así que no revela nada |
| `strong_password` también al restablecer | Misma política que el registro, el alta de usuarios y el perfil (regla definida en `app/Validation/AccountRules.php`) |
| `Cache-Control: no-store` y `Referrer-Policy: no-referrer` en el formulario | El token viaja en la URL                                                                |

## 4 bis. Decisiones aceptadas y riesgos conocidos

> **Constancia:** el equipo es consciente de lo que se implementó y de sus límites. Esta sección existe para que nadie lo descubra por sorpresa en la defensa.

**Contexto.** La HU original asumía que el sistema avisaba al usuario por un canal externo (correo o SMS). Se consultó al docente si convenía usarlo y la respuesta fue que no: sería complicar demasiado un proyecto que "muere" con la entrega, y además requeriría tiempo de codificación que el equipo no tiene. El equipo estuvo de acuerdo.

**Consecuencia.** Sin canal externo, quien pide el restablecimiento recibe el token en la misma respuesta si acierta los datos, y por eso:

| Decisión / riesgo | Detalle |
| --- | --- |
| **No se envía correo ni SMS (decisión)** | El token se entrega en la misma respuesta HTTP cuando correo y DNI coinciden con una cuenta activa. |
| **Segundo dato: DNI (mitigación)** | Hace falta conocer correo **y** DNI de la cuenta. Con solo el correo ya no se puede tomar la cuenta. |
| **Error genérico sin dejar avanzar (mitigación y UX)** | Si el correo no existe, está dado de baja o el DNI no coincide, se permanece en `/auth/recuperar-password` y se muestra `"Las credenciales ingresadas son incorrectas."` sin revelar cuál de los dos datos falló. |
| **R-2 · Toma de cuenta (riesgo aceptado)** | Quien conozca correo y DNI de un usuario puede cambiarle la contraseña, incluido el administrador. Ambos datos son semi-públicos. Se acepta por el alcance de cursada. **No usar en producción sin canal externo.** |
| **Enfriamiento de intentos (mitigación)** | El tercer intento que no coincide activa de inmediato un bloqueo de 2 minutos tanto para la IP como para el correo; con el bloqueo activo ni el DNI correcto obtiene un token. El mensaje muestra los segundos que faltan y la pantalla los cuenta en vivo (`auth.js`). |
| **R-3 · Fuerza bruta sobre el DNI (riesgo reducido, no eliminado)** | Quedan 3 intentos cada 2 minutos por correo. Un atacante con muchas IP igual está limitado por el contador del correo, pero el bloqueo por correo permite que alguien deje sin poder pedir el restablecimiento a un usuario ajeno (denegación de servicio menor). El contador vive en la cache de archivos del servidor: reiniciarla (borrar `writable/cache`) lo reinicia. |
| **El Escenario 4 cambió** | De "mensaje neutro" a rechazo con mensaje de error genérico (`"Las credenciales ingresadas son incorrectas."`) sin revelar qué dato falló. Está reescrito en la HU. |

**Qué haría falta para cerrarlos:** enviar el enlace por correo (o SMS) y volver a la respuesta neutra.

## 5. Verificación y Calidad

| Verificación       | Comando / Método                                                    | Resultado                  |
| ------------------ | ------------------------------------------------------------------- | -------------------------- |
| Sintaxis y rutas   | `php -l` y `php spark routes`                                      | ✅ Sin errores; 4 rutas registradas |
| Tests HU-03b       | `vendor/bin/phpunit --no-coverage tests/feature/PasswordResetTest.php` | ✅ 28 pruebas / 179 aserciones (tras el merge de `main`, rechazo directo, usuario dado de baja y enfriamiento) |
| Merge con `main`   | `git merge origin/main` (`1f68513`, incluye HU-06 y HU-10) | ✅ Sin conflictos; commit `244bb54` |
| Suite completa     | `vendor/bin/phpunit --no-coverage` | ⚠️ 198 pruebas / 1550 aserciones: las de HU-03b pasan; quedan los 3 fallos propios de `main` (ver nota) |
| Corrección posterior de vistas | Sintaxis y carga del formulario de solicitud | ✅ Campos de correo, DNI y contraseña con el ícono habitual |
| Flujo directo de restablecimiento | Escenarios de cuenta activa, inexistente y dada de baja | ✅ 6 pruebas / 30 aserciones |
| Prueba manual      | Solicitar, validar errores, bloqueo de intentos, usuario dado de baja y cambio de clave en navegador | ✅ Verificado manualmente por el autor en `http://localhost:8080` |

**Nota de la suite completa (diagnóstico verificado):**
1. **Contaminación de rutas desde `AuthAccessControlTest` (`main`):** `AuthAccessControlTest::testLoginScreenShowsRegistrationAndRecoveryLinksOnceTheirRoutesExist` invoca `$this->withRoutes(...)` registrando rutas ficticias `GET auth/register -> AuthController::login` y `GET auth/recuperar-password -> AuthController::login` sobre el singleton compartido `service('routes')` sin restaurarlo al terminar. Como `RouteCollection::create()` no pisa rutas ya existentes cuando después corre `loadRoutes()`, durante el resto del proceso de PHPUnit ambas URLs seguían atendidas por `AuthController::login`. Eso explicaba por qué, solo al correr la suite completa, `PasswordResetTest::testRequestFormIsPublicAndLoginLinksToIt` no encontraba `'Restablecer contraseña'` y `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` no encontraba `'Crear cuenta'`. Se agregó `service('routes')->resetRoutes()` en `PasswordResetTest::setUp()`, con lo que `PasswordResetTest` queda inmune (verificado corriendo ambos tests seguidos: 2/2 OK).
2. **Selectores incompatibles con `DOMParser` en HU-02 y HU-06 (`main`):** `PublicRegistrationTest` (línea 43) y `ProfileManagementTest` (línea 93, datasets `owner` y `tenant`) usan selectores como `input[name="role"][value="owner"]` y `input[name="document_number"]`. `DOMParser::parseSelector()` (`system/Test/DOMParser.php`) no quita las comillas y solo admite un atributo; genera un XPath inválido (`//input[@name=""role""]`), `DOMXPath::query()` devuelve `false` en PHP 8.5 / libxml 2.15 y falla al leer `->length`.

**Sin verificar:** Ninguno dentro del alcance de HU-03b.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID  | Tipo   | Descripción                                                                                   | Severidad | Acción sugerida                                  |
| --- | ------ | --------------------------------------------------------------------------------------------- | --------- | ------------------------------------------------ |
| D-1 | Deuda  | El enfriamiento solo cubre la solicitud del token, no el envío de la nueva contraseña ni el inicio de sesión | Baja | Evaluar si hace falta en HU-01 |
| D-2 | Deuda  | Las sesiones abiertas no se cierran al cambiar la contraseña                                  | Baja      | Decidir con el equipo                            |
| R-1 | Riesgo | `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` y `ProfileManagementTest::testClientCanSeeOnlyTheirOwnProfile…` fallan en `main` | Baja   | Ajeno a HU-03b; avisar al equipo                 |
| R-2 a R-4 | Riesgo aceptado | Toma de cuenta con correo + DNI, fuerza bruta del DNI y canal lateral de tiempo | Alta | Aceptados por decisión del equipo; detalle en la sección 4 bis |

## 7. Próximos Pasos

| Prioridad | Tarea                                                        |
| --------- | ------------------------------------------------------------ |
| 1         | Revisar y mergear el Pull Request hacia `main`               |

## 8. Historial de Sesiones

| Fecha      | Autor  | Duración | Resumen                                           | Commit / PR |
| ---------- | ------ | -------- | ------------------------------------------------- | ----------- |
| 2026-11-10 | Ramiro | s/d      | Implementación inicial de HU-03b                  | `5b86cb4`   |
| 2026-11-10 | Ramiro + IA | 1 h 36 min | Revisión de la HU, merge con `main` (`244bb54`), DNI como segundo dato, `strong_password`, enfriamiento de 2 min al 3.er intento fallido, mensajes de credenciales incorrectas y usuario dado de baja, prueba manual y PR | Cierre de HU-03b |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y esta nota.
2. Revisar la HU-03b y el estado de la rama.
3. Ejecutar primero `PasswordResetTest` contra la base de pruebas.
4. No apuntar las pruebas a una base de desarrollo o producción.
