# Informe de Avance y Traspaso (Handoff) — HU-06

## 1. Resumen Ejecutivo

| Campo                           | Valor                                                                               |
| ------------------------------- | ----------------------------------------------------------------------------------- |
| **Proyecto**                    | InmoGestión 2026                                                                    |
| **Historia de usuario / Issue** | HU-06 — Gestionar mi perfil · [#11](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/11) |
| **Sprint / Hito**               | Sprint 1 · Gestión de usuarios                                                      |
| **Rama**                        | `feature/hu-06-perfil-propio`                                                       |
| **Commit de implementación**    | `0f6fb4b` — `feat(profile): add profile management features for owners and tenants` |
| **Pull Request**                | #34 — mergeado el 2026-11-10 20:44                                                  |
| **Fecha de actualización**      | 2026-11-10                                                                          |
| **Inicio / Fin**                | 2026-11-10 16:42 → 2026-11-10 17:16 · último commit verificable: 20:04             |
| **Tiempo invertido**            | 34 min efectivos · 34 min de reloj · medido según el horario registrado              |
| **Autor / Agente**              | Copilot SDK en VS Code                                                              |
| **Estado global**               | 🟡 Implementación realizada; ejecución de pruebas pendiente                         |
| **Avance estimado**             | 100 %                                                                               |

**Resumen:** Se agregó el perfil propio para propietarios e inquilinos, siguiendo la referencia visual `mi_perfil_inmogesti_n_2026`: datos identificatorios protegidos, contacto editable y cambio de contraseña. Se amplió la validación de teléfonos y contraseñas en perfil, registro público y gestión administrativa. La implementación y las pruebas están en la rama indicada; PHP lint, las reglas aisladas y las rutas se verificaron. El PHPUnit global disponible es incompatible con PHP actual, por lo que las pruebas feature no llegaron a ejecutar sus aserciones.

## 2. Alcance y Criterios de Aceptación

Referencia: [`HU-06_gestion_perfil_propio.md`](../historias_de_usuario/HU-06_gestion_perfil_propio.md)

| #    | Criterio de aceptación                                                                          | Estado | Evidencia                                                                                                              |
| ---- | ----------------------------------------------------------------------------------------------- | ------ | ---------------------------------------------------------------------------------------------------------------------- |
| CA-1 | Consultar perfil y actualizar teléfono/dirección propios                                        | 🟡     | `ProfileController::show`, `updateContact`; pruebas en `ProfileManagementTest`, aún no ejecutadas                      |
| CA-2 | Cambiar contraseña verificando la actual y almacenar el hash                                    | 🟡     | `ProfileController::changePassword`, `password_hash(..., PASSWORD_BCRYPT)`; prueba automatizada pendiente de ejecución |
| CA-3 | Rechazar contraseña actual incorrecta sin alterar la vigente                                    | 🟡     | Error de validación explícito y prueba automatizada pendiente de ejecución                                             |
| RN-1 | Cada usuario accede únicamente a su propio perfil                                               | 🟡     | El ID se obtiene de la sesión; rutas limitadas por `auth` y `role:owner,tenant`; prueba automatizada pendiente         |
| RN-2 | Rol y DNI no se modifican desde el perfil propio                                                | 🟡     | Se presentan como datos de solo lectura y no se aceptan en el payload de contacto                                      |
| RN-3 | La contraseña nueva no coincide con la actual; tiene 8+ caracteres, mayúscula, número y símbolo | 🟡     | Regla compartida de validación y mensajes visibles; pruebas añadidas, ejecución bloqueada por PHPUnit global           |
| RN-4 | Validación de teléfonos en perfil, registro y alta/edición administrativa                       | 🟡     | Regla compartida: 6–15 dígitos y formato permitido; pruebas añadidas, ejecución bloqueada por PHPUnit global           |

**Fuera de alcance / diferido:** Cambio de nombre, email, DNI o rol; recuperación de contraseña; acceso al perfil para admin/employee.

## 3. Cambios Realizados

| Capa       | Archivo                                                                                                                       | Tipo               | Descripción                                                                                                             |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------- | ------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| Controller | `app/Controllers/ProfileController.php`                                                                                       | Nuevo              | Consulta de perfil, edición de contacto con whitelist y cambio de contraseña autenticado                                |
| Validation | `app/Validation/AccountRules.php`, `app/Config/Validation.php`                                                                | Nuevo / Modificado | Reglas compartidas de formato de teléfono y complejidad de contraseña                                                   |
| Routes     | `app/Config/Routes.php`                                                                                                       | Modificado         | `GET /perfil`, `POST /perfil/contacto` y `POST /perfil/contrasena`, detrás de `auth` y `role:owner,tenant`              |
| View       | `app/Views/profile/show.php`                                                                                                  | Nuevo              | Diseño alineado a la captura de referencia, datos protegidos, contacto editable y cambio de contraseña con confirmación |
| Layout     | `app/Views/layouts/panel.php`                                                                                                 | Modificado         | Acceso “Mi perfil” en los menús de propietario e inquilino                                                              |
| CSS        | `public/css/panel.css`                                                                                                        | Modificado         | Distribución responsiva para los bloques de perfil                                                                      |
| Assets     | `public/js/auth.js`, `public/js/users.js`                                                                                     | Modificado         | Confirmación/cambio de clave en perfil y sugerencia administrativa con requisitos garantizados                          |
| Views      | `app/Views/auth/register.php`, `app/Views/admin/users/form.php`                                                               | Modificado         | Ayuda y validación visible de teléfono y política de contraseña                                                         |
| Tests      | `tests/feature/ProfileManagementTest.php`, `tests/feature/PublicRegistrationTest.php`, `tests/feature/UserManagementTest.php` | Modificado         | Errores de política de contraseña, confirmación, igualdad con clave actual y formato telefónico                         |
| Historia   | `.docs/historias_de_usuario/HU-06_gestion_perfil_propio.md`                                                                   | Modificado         | DoD actualizado; ejecución de pruebas queda pendiente                                                                   |
| Handoff    | `.docs/exec-plans/HU-06_handoff.md`                                                                                           | Nuevo              | Este traspaso                                                                                                           |

**Cambios de esquema de BD:** Ninguno. Se reutilizan `users.phone`, `users.address` y `users.password_hash`.
**Cambios de rutas / permisos:** Las rutas de perfil son explícitas, solo `GET` para consulta y `POST` para cambios; `auth` más `role:owner,tenant`; CSRF global vigente.

## 4. Decisiones Técnicas

| Decisión                                                                                                    | Alternativas consideradas                                     | Justificación                                                                       |
| ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Obtener el usuario objetivo desde `session.user_id`                                                         | Recibir un ID del cliente                                     | Impide seleccionar o modificar el perfil de otra cuenta                             |
| Permitir cambiar solo `phone` y `address` en la acción de contacto                                          | Aceptar datos de cuenta desde el formulario                   | Mantiene inmutables rol, DNI, correo y demás campos                                 |
| Separar los formularios de contacto y contraseña                                                            | Enviar todos los cambios juntos                               | Evita mezclar validaciones y no modifica contacto si falla el cambio de contraseña  |
| Pedir contraseña actual, nueva y repetición; validar complejidad y rechazar la contraseña actual como nueva | Permitir cambio directo o mantener reglas distintas por flujo | Confirma control de cuenta y aplica una política consistente                        |
| Compartir validadores de teléfono y contraseña con registro y administración                                | Validar cada formulario de forma independiente                | Mantiene los requisitos iguales en todos los flujos que crean o actualizan usuarios |
| Verificar la clave actual con `password_verify()` y persistir BCRYPT                                        | Reemplazar directamente el hash                               | Cumple la regla de negocio y conserva el almacenamiento cifrado                     |
| Regenerar la sesión tras cambiar la contraseña                                                              | Mantener el ID de sesión                                      | Reduce el riesgo asociado a una sesión previamente fijada                           |

**Seguridad aplicada:** filtros de autenticación y rol; CSRF global; identidad desde sesión; validación server-side; whitelist para los campos de contacto; verificación de contraseña actual; rechazo de reutilización de contraseña; contraseñas nuevas y confirmación fuera de old-input; hash BCRYPT; salida contextual con `esc()`; el hash no se envía a la vista.

## 5. Verificación y Calidad

| Verificación     | Comando / Método                                                                 | Resultado                                                                    |
| ---------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Sintaxis PHP     | `php -l` sobre los controladores, regla compartida, vistas y pruebas modificadas | ✅ Sin errores de sintaxis                                                   |
| Rutas            | `php spark routes`                                                               | ✅ Aparecen las tres rutas de HU-06 con filtros `auth` y `role:owner,tenant` |
| Reglas de cuenta | Smoke checks CI4 con teléfonos y contraseñas válidos e inválidos                 | ✅ Pasa los casos probados                                                   |
| Higiene del diff | `git diff --check`                                                               | ✅ Sin errores                                                               |
| Pruebas HU-06    | `composer test -- --filter ProfileManagementTest`                                |
| Migraciones      | Revisión de esquema existente                                                    | ✅ No se requieren cambios                                                   |

**Sin verificar:** Aserciones feature contra MySQL/MariaDB, comportamiento visual en navegador y suite completa. No hay `vendor/` ni `composer.lock`; `composer test` invoca un PHPUnit global antiguo incompatible con la versión de PHP disponible.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID  | Tipo                    | Descripción                                                                                                                   | Severidad | Acción sugerida                                                                                   |
| --- | ----------------------- | ----------------------------------------------------------------------------------------------------------------------------- | --------- | ------------------------------------------------------------------------------------------------- |
| B-1 | Bloqueo de verificación | El PHPUnit global falla antes de correr tests (`Call to undefined function each()`); faltan dependencias locales del proyecto | Media     | Instalar dependencias declaradas y ejecutar con PHPUnit compatible                                |
| B-2 | Bloqueo de entorno      | No se ejecutó el test feature con una conexión de base descartable confirmada                                                 | Media     | Configurar el grupo de pruebas de `.env` solo con una BD desechable y ejecutar la prueba dirigida |

## 7. Próximos Pasos

| Prioridad | Tarea                                                                                                | Archivos previstos                                      | Skills a cargar                 |
| --------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------- | ------------------------------- |
| 1         | Preparar PHPUnit compatible y base de pruebas desechable; ejecutar `ProfileManagementTest`           | `tests/feature/ProfileManagementTest.php`, `.env` local | `ci4-testing`, `ci4-data-layer` |
| 2         | Ejecutar las pruebas relevantes de registro, gestión de usuarios y perfil, y luego la suite completa | `tests/feature/`                                        | `ci4-testing`, `qa-regression`  |
| 3         | Revisar visualmente las pantallas de perfil para propietario e inquilino                             | `app/Views/profile/show.php`, `public/css/panel.css`    | `frontend-html-css`             |

**Siguiente HU sugerida:** Confirmar el orden del equipo; HU-06 no tiene dependencia pendiente de HU-02.
**Prerrequisitos del entorno:** PHP 8.2+, PHPUnit 10.5+/11 compatible con `composer.json`, dependencias del proyecto y una base MySQL/MariaDB exclusiva para pruebas.

## 8. Historial de Sesiones

| Fecha      | Autor                  | Duración                        | Resumen                                                                                                                     | Commit / PR                      |
| ---------- | ---------------------- | ------------------------------- | --------------------------------------------------------------------------------------------------------------------------- | -------------------------------- |
| 2026-11-10 | Copilot SDK en VS Code | 34 min efectivos / 34 min de reloj | HU-06 y validaciones comunes de teléfono/contraseña; lint, reglas aisladas y rutas verificadas; PHPUnit global incompatible | `0f6fb4b` / PR #34 (merge 20:44) |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md`, este handoff y la historia HU-06.
2. Verificar con `git status`, `git branch --show-current` y `git log -n 5` el estado de la rama.
3. Configurar una base exclusiva para pruebas; no usar la base de desarrollo ni producción.
4. Ejecutar primero `ProfileManagementTest`; luego la suite relevante y actualizar este handoff con resultados comprobados.
5. No incluir cambios locales de `.env`, dependencias descargadas ni otros archivos ajenos al alcance en un commit.
