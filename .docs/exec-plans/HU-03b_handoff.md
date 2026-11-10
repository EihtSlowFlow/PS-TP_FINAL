# Informe de Avance y Traspaso (Handoff) — HU-03b

## 1. Resumen Ejecutivo

| Campo                           | Valor                                                                 |
| ------------------------------- | --------------------------------------------------------------------- |
| **Proyecto**                    | InmoGestión 2026                                                      |
| **Historia de usuario / Issue** | HU-03b — Restablecer la contraseña · `[#N° issue]`                    |
| **Rama**                        | `feature/hu-03b-restablecer-contrasena`                               |
| **Último commit**               | Pendiente de commit                                                   |
| **Pull Request**                | Sin PR                                                                |
| **Fecha de cierre**             | 2026-11-10                                                           |
| **Autor / Agente**              | Ramiro, con asistencia de Claude                                      |
| **Estado global**               | 🟢 Implementado y verificado automáticamente                          |
| **Avance estimado**             | 100 %                                                                 |

**Resumen:** Se implementó e integró la recuperación de contraseña con token de un solo uso, vigencia de 60 minutos y almacenamiento solo del hash SHA-256. El flujo usa un controlador propio (`PasswordResetController`) para no mezclarlo con el login/registro. La tabla `password_resets` ya existía en la migración `2026-11-06-100300`; no hay cambios de esquema.

## 2. Alcance y Criterios de Aceptación

Referencia: `.docs/historias_de_usuario/HU-03b_*.md`

| #    | Criterio de aceptación                                                          | Estado | Evidencia                                                                                  |
| ---- | ------------------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------------------ |
| CA-1 | Usuario activo solicita restablecimiento y recibe token temporal de un solo uso | ✅     | `PasswordResetController::sendLink`; `PasswordResetTest::testScenario1*`                   |
| CA-2 | Token válido + contraseña válida: actualiza, invalida token, redirige al login  | ✅     | `PasswordResetController::updatePassword`; `PasswordResetTest::testScenario2*`             |
| CA-3 | Token inválido, usado o vencido: se rechaza y se pide nueva solicitud           | ✅     | `PasswordResetTest::testScenario3*`                                                        |
| CA-4 | Correo no registrado: respuesta neutra que no revela si existe                  | ✅     | `PasswordResetTest::testScenario4*` (mismo mensaje para inexistente y dado de baja)        |
| RB-1 | Token temporal, un solo uso, no predecible                                      | ✅     | `random_bytes(32)`, `used_at`, `expires_at`; `testTokensAreUnpredictable`                  |
| RB-2 | Contraseña mínimo 8 caracteres, almacenada cifrada                              | ✅     | `min_length[8]`, `PASSWORD_BCRYPT`                                                         |
| RB-3 | Usuarios dados de baja no pueden restablecer                                    | ✅     | No se emite token; además `findUsableByToken` exige `users.deleted_at IS NULL`             |

**Fuera de alcance / diferido:** cierre de sesiones activas del usuario tras el cambio de contraseña; límite de intentos de solicitud por IP/correo (rate limiting).

## 3. Cambios Realizados

| Capa        | Archivo                                    | Tipo       | Descripción                                                                  |
| ----------- | ------------------------------------------ | ---------- | ---------------------------------------------------------------------------- |
| Rutas       | `app/Config/Routes.php`                    | Modificado | 4 rutas públicas en el grupo `auth`                                          |
| Controlador | `app/Controllers/PasswordResetController.php` | Nuevo   | Solicitud, redirección directa al formulario y actualización                  |
| Modelo      | `app/Models/PasswordResetModel.php`        | Nuevo      | Emisión, búsqueda de token usable, consumo atómico e invalidación            |
| Vistas      | `app/Views/auth/forgot_password.php`, `reset_password.php` | Nuevo | Formularios con el estilo de `register.php`                          |
| Pruebas     | `tests/feature/PasswordResetTest.php`      | Nuevo      | Escenarios 1 a 4, reglas de negocio y errores                                |
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
| Mensaje neutro para correo inexistente o dado de baja | No se emite token para una cuenta que no puede restablecer la contraseña |
| `Cache-Control: no-store` y `Referrer-Policy: no-referrer` en el formulario | El token viaja en la URL                                                                |

## 5. Verificación y Calidad

| Verificación       | Comando / Método                                                    | Resultado                  |
| ------------------ | ------------------------------------------------------------------- | -------------------------- |
| Sintaxis y rutas   | `php -l` y `php spark routes`                                      | ✅ Sin errores; 4 rutas registradas |
| Tests HU-03b       | `vendor/bin/phpunit --no-coverage tests/feature/PasswordResetTest.php` | ✅ 18 pruebas / 110 aserciones |
| Corrección posterior de vistas | Sintaxis y carga del formulario de solicitud | ✅ Campos de correo y contraseña con el ícono habitual |
| Flujo directo de restablecimiento | Escenarios de cuenta activa, inexistente y dada de baja | ✅ 4 pruebas / 22 aserciones |
| Prueba manual      | Solicitar, abrir enlace, cambiar clave, ingresar                    | ⏳ Pendiente                |

**Sin verificar:** recorrido visual completo en navegador.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID  | Tipo   | Descripción                                                                                   | Severidad | Acción sugerida                                  |
| --- | ------ | --------------------------------------------------------------------------------------------- | --------- | ------------------------------------------------ |
| D-1 | Deuda  | Sin rate limiting en la solicitud de restablecimiento                                         | Media     | Evaluar filtro de throttling                     |
| D-2 | Deuda  | Las sesiones abiertas no se cierran al cambiar la contraseña                                  | Baja      | Decidir con el equipo                            |
| R-1 | Riesgo | `PublicRegistrationTest::testRegistrationPageRendersOnlyPublicRolesAndCsrfField` falla en `main` | Baja   | Ajeno a HU-03b; avisar al equipo                 |

## 7. Próximos Pasos

| Prioridad | Tarea                                                        |
| --------- | ------------------------------------------------------------ |
| 1         | Probar el flujo completo en el navegador                     |
| 2         | Revisar los cambios y crear el commit / PR                   |

## 8. Historial de Sesiones

| Fecha      | Autor  | Resumen                                           | Commit / PR |
| ---------- | ------ | ------------------------------------------------- | ----------- |
| 2026-11-10 | Ramiro | Implementación inicial de HU-03b | `[hash]` |
| 2026-11-10 | Codex | Integración en la estructura CI4, endurecimiento de token y verificación automática | Pendiente de commit |
| 2026-11-10 | Codex | Corrección de enlace local codificado como ruta relativa y consistencia visual de los campos de contraseña | Pendiente de commit |
| 2026-11-10 | Codex | Auditoría de URL/escape y prueba de regresión para evitar enlaces absolutos codificados | Pendiente de commit |
| 2026-11-10 | Codex | Se eliminó el correo fuera de alcance; una cuenta activa pasa directamente al formulario de nueva contraseña | Pendiente de commit |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y esta nota.
2. Revisar la HU-03b y el estado de la rama.
3. Ejecutar primero `PasswordResetTest` contra la base de pruebas.
4. No apuntar las pruebas a una base de desarrollo o producción.
