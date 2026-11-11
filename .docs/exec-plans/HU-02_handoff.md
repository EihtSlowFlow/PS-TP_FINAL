# Informe de Avance y Traspaso (Handoff) — HU-02

## 1. Resumen Ejecutivo

| Campo                               | Valor                                                                                                           |
| ----------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| **Proyecto**                        | InmoGestión 2026                                                                                                |
| **Historia de usuario / Issue**     | HU-02 — Registrarse como propietario o inquilino · [#20](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/20) |
| **Sprint / Hito**                   | Sprint 1 — Registro de clientes externos                                                                        |
| **Rama**                            | `Historia-de-usuario-02`                                                                                        |
| **Último commit de implementación** | `7f86da8` — feat: implement public registration for owners and tenants (HU-02)                                  |
| **Pull Request**                    | [#32 — Historia de usuario 02](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/32), creado y fusionado el 2026-11-10 |
| **Fecha de actualización**          | 2026-11-10                                                                                                      |
| **Tiempo de trabajo**               | 9:47 a. m. a 10:36 a. m. (49 minutos)                                                                           |
| **Autor / Agente**                  | Joaquín (`joaquincorbalan`), con Copilot SDK en VS Code                                                         |
| **Estado global**                   | 🟡 Implementación realizada;                                                                                    |
| **Avance estimado**                 | 100 %                                                                                                           |

**Resumen:** Se agregó el registro público dentro del flujo de autenticación, separado del ABMC administrativo de `UserController`. El formulario permite crear cuentas de propietario o inquilino, valida los datos en el servidor, normaliza DNI/correo, verifica unicidad y guarda la contraseña con BCRYPT. La implementación está en el commit `7f86da8` de la rama indicada.

## 2. Alcance y Criterios de Aceptación

Referencia: [HU-02 — Registro de clientes externos](../historias_de_usuario/HU-02_registro_clientes_externos.md)

| #    | Criterio de aceptación                                                                               | Estado | Evidencia                                                                                                                                             |
| ---- | ---------------------------------------------------------------------------------------------------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| CA-1 | Registro exitoso como propietario o inquilino; guarda la cuenta y redirige al login con confirmación | 🟡     | Implementado en `AuthController::storeRegistration`; cubierto por `PublicRegistrationTest`, aún sin ejecución exitosa por falta de conexión de prueba |
| CA-2 | Rechazo de email/DNI duplicados; conserva otros campos y no conserva las contraseñas                 | 🟡     | Comprobación contra usuarios activos y limpieza de `password`/`password_confirm`; prueba feature bloqueada por la BD                                  |
| CA-3 | Rechazo de correo inválido, contraseña corta o confirmación distinta, con errores por campo          | 🟡     | Reglas de validación del servidor, validación nativa del navegador y comparación de contraseñas en `auth.js`; pruebas feature bloqueadas por la BD    |
| RB-1 | El registro público no permite asignar rol de administrador                                          | 🟡     | Lista blanca de roles `owner,tenant` en el servidor y solo esas opciones en la vista; prueba feature bloqueada por la BD                              |

**Fuera de alcance / diferido:**

- El alta administrativa de cualquier rol continúa en HU-04, a través de `UserController`.
- Inicio automático de sesión después del alta: la historia requiere volver al login; la implementación cumple ese flujo.

## 3. Cambios Realizados

| Capa        | Archivo                                    | Tipo       | Descripción                                                                                |
| ----------- | ------------------------------------------ | ---------- | ------------------------------------------------------------------------------------------ |
| Rutas       | `app/Config/Routes.php`                    | Modificado | Agrega `GET /auth/register` y `POST /auth/register`                                        |
| Controlador | `app/Controllers/AuthController.php`       | Modificado | Agrega formulario y creación de cuenta, validación, unicidad, hash BCRYPT y mensajes flash |
| Vista       | `app/Views/auth/register.php`              | Nuevo      | Formulario público accesible y responsivo                                                  |
| CSS         | `public/css/auth.css`                      | Modificado | Estilos responsivos del registro y selector de rol                                         |
| JavaScript  | `public/js/auth.js`                        | Modificado | Comparación de contraseñas en navegador; conserva el control mostrar/ocultar               |
| Pruebas     | `tests/feature/PublicRegistrationTest.php` | Nuevo      | Casos para roles permitidos, duplicados, validación y hash                                 |

**Cambios de esquema de BD:** Ninguno. Se reutilizan `users`, `roles` y las consultas de unicidad existentes en `UserModel`.

**Cambios de rutas / permisos:** Las dos rutas nuevas son públicas y quedan cubiertas por el filtro CSRF global existente. No se agregaron permisos de panel ni se cambió la autenticación de HU-01.

## 4. Decisiones Técnicas

| Decisión                                                                                                | Alternativas consideradas                            | Justificación                                                                                               |
| ------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Implementar el registro en `AuthController` y bajo el grupo `auth`                                      | Crear otro controlador o reutilizar `UserController` | El alta pública pertenece al flujo de cuentas; `UserController` sigue reservado para gestión administrativa |
| Permitir únicamente roles `owner` y `tenant` en servidor                                                | Confiar en las opciones del formulario               | El cliente puede manipular el POST; la autorización de roles se aplica mediante `in_list` del lado servidor |
| Normalizar email a minúsculas y quitar puntos, espacios y guiones del DNI antes de comprobar duplicados | Comparar el texto tal como se ingresó                | Reduce duplicados debidos a diferencias de mayúsculas o formato                                             |
| No iniciar sesión automáticamente al registrar                                                          | Iniciar sesión inmediatamente                        | La HU especifica redirigir a login con mensaje de éxito                                                     |

**Seguridad aplicada:** CSRF global; validación en servidor; escapado contextual en la vista; roles permitidos restringidos en backend; contraseña excluida del old-input y guardada con `password_hash(..., PASSWORD_BCRYPT)`.

## 5. Verificación y Calidad

| Verificación                      | Comando / Método                                                                                                                         | Resultado                                                                                                                                       |
| --------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Sintaxis PHP                      | `php -l` en rutas, controlador, vista y prueba                                                                                           | ✅ Sin errores de sintaxis                                                                                                                      |
| Sintaxis JavaScript               | `node --check public\js\auth.js`                                                                                                         | ✅ Sin errores                                                                                                                                  |
| Rutas registradas                 | `php spark routes`                                                                                                                       | ✅ Aparecen `GET auth/register` y `POST auth/register`                                                                                          |
| Formato del diff                  | `git diff --check`                                                                                                                       | ✅ Sin errores                                                                                                                                  |
| PHPUnit del entorno global        | `composer test -- --no-coverage tests/feature/PublicRegistrationTest.php`                                                                | ❌ El PHPUnit global antiguo llama a `each()`, eliminado en PHP 8                                                                               |
| Pruebas HU-02 con PHPUnit 11.5.57 | PHPUnit 11.5.57 PHAR descargado en el directorio de sesión; `-c phpunit.dist.xml --no-coverage tests\feature\PublicRegistrationTest.php` | ⏸️ 5 casos ejecutados, todos bloqueados antes de las aserciones: no hay credenciales de BD de pruebas (`Access denied for user ''@'localhost'`) |
| Prueba manual en navegador        | No ejecutada                                                                                                                             | ⏳ Sin evidencia de navegador en esta sesión                                                                                                    |

**Sin verificar:** Resultado funcional de las pruebas contra una base MySQL/MariaDB de pruebas descartable y flujo manual completo.

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID  | Tipo             | Descripción                                                                                                                                  | Severidad | Acción sugerida                                                                                                       |
| --- | ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | --------- | --------------------------------------------------------------------------------------------------------------------- |
| B-1 | Bloqueo          | La conexión `database.tests.*` no tiene credenciales configuradas en este entorno; las pruebas feature no llegaron a ejecutar sus aserciones | Media     | Configurar una BD descartable dedicada según `.env.example`/README y volver a ejecutar la prueba                      |
| D-1 | Deuda de entorno | `composer test` invoca un PHPUnit global incompatible con PHP 8.2; el proyecto no tiene `composer.lock` ni vendor local en este checkout     | Media     | Instalar dependencias del proyecto (`composer install`) y ejecutar el PHPUnit compatible declarado en `composer.json` |
| R-1 | Revisión         | El estado de revisión/PR de `Historia-de-usuario-02` no se confirmó                                                                          | Baja      | Verificar GitHub antes de iniciar otro cambio o preparar merge                                                        |

## 7. Próximos Pasos

| Prioridad | Tarea                                                                                                      | Archivos previstos                                                            | Skills a cargar                            |
| --------- | ---------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ------------------------------------------ |
| 1         | Configurar y ejecutar `PublicRegistrationTest` con una base descartable; luego ejecutar la suite relevante | `.env`, `app/Config/Database.php`, `tests/feature/PublicRegistrationTest.php` | `ci4-testing`, `ci4-data-layer`            |
| 2         | Revisar visualmente el registro y los estados de error/éxito en navegador                                  | `app/Views/auth/register.php`, `public/css/auth.css`, `public/js/auth.js`     | `frontend-html-css`, `frontend-javascript` |
| 3         | Confirmar estado de PR y actualizar este handoff con el resultado real de revisión/merge                   | Este handoff                                                                  | —                                          |

**Siguiente HU sugerida:** HU-03 — Cerrar sesión (Issue #19), sujeto al orden acordado por el equipo.
**Prerrequisitos del entorno:** PHP 8.2; PHPUnit 10.5+/11; base MySQL/MariaDB separada y descartable configurada como grupo `tests`. No ejecutar pruebas destructivas sobre datos de desarrollo o producción.

## 8. Historial de Sesiones

| Fecha      | Autor                                                | Resumen                                                                                                                                                   | Commit / PR                                          |
| ---------- | ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| 2026-11-10 | Joaquín (`joaquincorbalan`) y Copilot SDK en VS Code | Implementación HU-02 en `AuthController`, formulario, rutas y pruebas; PR creado y fusionado el mismo día | Commit de implementación `7f86da8`; [PR #32](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/32) |

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y esta nota.
2. Revisar HU-02 y el estado actual de `Historia-de-usuario-02`; no asumir que el commit listado sigue siendo HEAD.
3. Configurar una base exclusiva para pruebas y ejecutar primero `PublicRegistrationTest`.
4. No modificar credenciales reales ni apuntar las pruebas a una base de desarrollo/producción.
5. Si se hacen cambios posteriores, actualizar este handoff y verificar su estado en el PR #32 antes de dar HU-02 por cerrada.
