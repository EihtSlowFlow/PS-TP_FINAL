# Registro de tiempos por issue — InmoGestión 2026

> Última actualización: 10/11/2026. Acá queda asentado cuánto se tardó en hacer cada issue. Es el complemento de los handoffs ([`exec-plans/`](exec-plans/)): el handoff cuenta **qué** se hizo y este registro **cuánto tiempo** llevó.
>
> Alcance: tiempo de desarrollo de una persona trabajando con asistencia de IA, desde que se pide el issue hasta su commit. No incluye reuniones, la lectura previa de otros integrantes ni la revisión del pull request.

## Índice

1. [Registro](#1-registro)
2. [Cómo se calcula el tiempo](#2-cómo-se-calcula-el-tiempo)
3. [Cómo registrar el tiempo de cada issue de ahora en adelante](#3-cómo-registrar-el-tiempo-de-cada-issue-de-ahora-en-adelante)
4. [Cómo reconstruir un tiempo que no se midió](#4-cómo-reconstruir-un-tiempo-que-no-se-midió)

---

## 1. Registro

| Issue | HU | Historia | Responsable | Pts | Inicio | Fin | Tiempo efectivo | Tiempo de reloj | Fuente |
|---|---|---|---|---|---|---|---|---|---|
| [#2](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/2) (pull request) | — | Prototipos de pantallas | LauPerisse (prompts hechos con Claude; las pantallas se generaron en Google Stitch) | 5 | 28/10 18:50 | 28/10 20:33 | **44 min** | 44 min | Reconstruido |
| [#22](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/22) | — | Base de datos | LauPerisse (parte hecha con Claude; el issue es de los tres) | 8 | 06/11 11:03 | 06/11 19:14 | **1 h 42 min** | 2 h 08 min | Reconstruido |
| [#19](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/19) | HU-03 | Cierre de sesión | LauPerisse | 1 | 09/11 21:05 | 09/11 21:21 | **16 min** | 16 min | Reconstruido |
| [#12](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/12) | HU-07 | Alta y edición de inmuebles | LauPerisse | 5 | 09/11 21:20 | 09/11 21:48 | **28 min** | 28 min | Reconstruido |
| [#20](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/20) | HU-02 | Registro de propietarios e inquilinos | joaquincorbalan | 3 | 10/11 09:47 | 10/11 10:36 | **49 min** | 49 min | Medido (handoff) |
| [#11](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/11) | HU-06 | Gestionar mi perfil | joaquincorbalan | 2 | 10/11 16:42 | 10/11 17:16 | **34 min** | 34 min | Medido (handoff) |
| [#3](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/3) | HU-08 | Catálogo y filtros de inmuebles | joaquincorbalan (Copilot SDK en VS Code) | 3 | 10/11 21:16 | 10/11 22:26 | **56 min** | 56 min | Medido (2 sesiones; pausa descontada) |
| [#15](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/15) | HU-10 | Alta y vinculación de contratos | LauPerisse | 8 | 10/11 20:47 | 10/11 21:07 | **20 min** | 20 min | Medido |
| [#18](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/18) | HU-03b | Restablecer la contraseña | EihtSlowFlow | 3 | 10/11 20:37 | 10/11 22:58 | **1 h 36 min** | 2 h 21 min | Medido (sesión de revisión y cierre; pausa 21:42–22:27 descontada) |
| [#6](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/6) | HU-13 | Emisión masiva de cuotas | LauPerisse | 5 | 10/11 23:28 | 10/11 23:52 | **24 min** | 24 min | Medido |
| **Total registrado** | | | | **43** | | | **7 h 49 min** | 9 h 00 min | |

Sin registro de tiempo (los hizo otro integrante; que cada responsable complete su fila con el método de la [sección 3](#3-cómo-registrar-el-tiempo-de-cada-issue-de-ahora-en-adelante)):

| Issue | HU | Historia | Responsable | Pts | Estado |
|---|---|---|---|---|---|
| [#21](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/21) | HU-01 | Iniciar sesión y control de acceso | EihtSlowFlow | 5 | Mergeada (PR #27), sin tiempo registrado |
| [#17](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/17) | HU-04 | Gestión de usuarios | EihtSlowFlow | 5 | Mergeada (PR #28), sin tiempo registrado |
| [#10](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/10) | HU-05 | Baja lógica de usuarios | EihtSlowFlow | 2 | Mergeada (PR #29), sin tiempo registrado |

### Detalle de los tiempos reconstruidos

| Issue | Qué entra en el tiempo | Qué no entra |
|---|---|---|
| #2 | Dos bloques el 28/10: **18:50 a 19:27** (armado de los prompts de cada pantalla, vínculo con el repositorio y manejo del `.env`; 37 min) y **20:26 a 20:33** (`.env` al `.gitignore`, commit de los prototipos y pull request; 7 min). Del pedido (18:50) al commit `c403491` (20:33) | El lapso de las 19:27 a las 20:26 (≈ 59 min), sin registro de sesión: es cuando se generaron las pantallas en Google Stitch, fuera de Claude Code. Tampoco la revisión del pull request. Los puntos (5, tamaño L) y las fechas se cargaron en el tablero el 10/11, porque el ítem había quedado vacío |
| #22 | Dos bloques el 06/11: **11:03 a 11:53** (diseño, 11 migraciones, seeders, esquema SQL y documentación; 33 min efectivos de 50 de reloj) y **17:56 a 19:14** (base real del equipo, rol Empleado, actualización de los 19 issues, pruebas de integridad, commit y pull request; 69 min) | Los ≈ 9 min de prompts para Stitch (18:23 a 18:32), que no son parte del issue. Tampoco el trabajo de los otros dos integrantes ni la preparación del issue del 28/10 |
| #19 | Del pedido del issue (21:05) al commit `a24187f` (21:21): ruta, controlador, botón en el sidebar, 17 casos de prueba y prueba de punta a punta con servidor real | — |
| #12 | Del pedido del issue (21:20) al commit `7a17382` (21:48): modelo, controlador, rutas, vistas, JavaScript, 49 casos de prueba y revisión visual en vivo | Los ≈ 3 min de tildar los criterios y mover las tarjetas en GitHub, que cubrieron los dos issues juntos |
| #15 | Del pedido del issue (20:47) al cierre de la implementación (21:07): lectura del issue y del código, `ContractModel` con la transacción, `ContractController`, rutas, formulario y ficha, JavaScript, 85 pruebas nuevas (75 de integración y 10 unitarias), revisión visual en el navegador y la suite completa (que corrió en segundo plano mientras se escribía la documentación). Hora de inicio y de fin anotadas en el momento | El commit, el pull request y el comentario en el issue de GitHub, que quedaron pendientes (el `gh` de esta máquina no tiene sesión iniciada). Sin pausas de más de 10 minutos |
| #18 | Dos bloques en la sesión de revisión y cierre del 10/11: **20:37 a 21:42** (65 min: revisión integral de HU-03b, merge de `main`, validación de DNI como segundo dato, `strong_password`, enfriamiento de 2 min con `ResetAttemptLimiter` y cuenta regresiva en vivo, diagnóstico de la suite completa y pruebas automatizadas) y **22:27 a 22:58** (31 min: nuevo merge de `main`, rechazo directo con mensaje de credenciales incorrectas y de usuario dado de baja, prueba manual en navegador, commit y PR) | La pausa de 45 min entre las 21:42 y las 22:27; tampoco la sesión previa donde se creó el commit inicial `5b86cb4` (17:49), cuyo inicio no se había medido |
| #6 | Del pedido del issue (23:28) al cierre de la implementación (23:52): lectura del issue, de HU-10 y del código de contratos, `InstallmentModel` con la transacción y la numeración correlativa, `ContractModel` (regla de cobertura), `InstallmentController`, rutas, vista previa e informe, estilos, 110 pruebas nuevas (82 de integración y 28 unitarias), revisión visual con los datos de demostración, la suite completa (5 min 47 s, en segundo plano mientras se escribía la documentación) y la documentación. Hora de inicio y de fin anotadas en el momento | El commit, el pull request y el comentario en el issue de GitHub, que quedaron pendientes (el `gh` de esta máquina no tiene sesión iniciada). Sin pausas de más de 10 minutos |

---

## 2. Cómo se calcula el tiempo

| Medida | Definición |
|---|---|
| **Tiempo de reloj** | Del primer mensaje que pide el issue al commit que lo cierra. Si el trabajo se partió en varios bloques, se suman los bloques |
| **Tiempo efectivo** | El tiempo de reloj sin las pausas: se suman los intervalos entre eventos consecutivos de la sesión y se ignoran los huecos de más de 10 minutos |
| **Fuente: Medido** | Se anotaron la hora de inicio y la de fin en el momento (sección 3) |
| **Fuente: Reconstruido** | Se calculó después, a partir de los registros de sesión de Claude Code y de los commits (sección 4). Es una buena aproximación, no una medición |

Los puntos de historia son otra medida (ver la sección "Relación con la estimación del Análisis de Requerimientos" del [README de historias](historias_de_usuario/README.md) y el punto 4 de las inconsistencias de [`PRESUPUESTO_Y_PLANIFICACION.md`](PRESUPUESTO_Y_PLANIFICACION.md)): **no se convierten a horas con este registro**. Estos tiempos son de desarrollo asistido por IA y no equivalen a las horas-hombre del presupuesto.

---

## 3. Cómo registrar el tiempo de cada issue de ahora en adelante

1. **Al empezar el issue**, apenas llega el pedido, anotar la hora con `Get-Date -Format "yyyy-MM-dd HH:mm"` (PowerShell) o `date '+%Y-%m-%d %H:%M'` (Bash) y dejarla en el campo **Inicio** de la sección 1 del handoff.
2. **Durante el trabajo**, si hay una pausa de más de 10 minutos (esperando una respuesta, un corte, otra tarea), anotarla para descontarla.
3. **Al cerrar el issue** (commit final), anotar la hora de **Fin** y completar en la sección 1 del handoff: Inicio, Fin y Tiempo invertido (efectivo, de reloj y fuente). Si el issue se retoma en otra sesión, cada sesión lleva su duración en la columna **Duración** de la sección 8.
4. **Agregar la fila** del issue a la tabla de la sección 1 de este documento y actualizar el total.
5. **Comentar el issue en GitHub** con el tiempo, con este formato:

   ```markdown
   ## ⏱️ Tiempo invertido

   | | |
   |---|---|
   | **Inicio** | AAAA-MM-DD HH:MM |
   | **Fin** | AAAA-MM-DD HH:MM |
   | **Tiempo efectivo** | X h YY min |
   | **Tiempo de reloj** | X h YY min |
   | **Fuente** | Medido / Reconstruido |

   Descontado: <pausas o tareas ajenas al issue, si las hubo>.
   ```

El tiempo se registra también cuando el issue queda a medias: se anota lo trabajado hasta ahora y se completa al retomarlo.

---

## 4. Cómo reconstruir un tiempo que no se midió

Los registros de sesión de Claude Code están en `~/.claude/projects/<carpeta del proyecto>/*.jsonl` y tienen una marca de tiempo (en UTC) por mensaje y por herramienta. Con las horas del pedido y del commit se calcula el tiempo efectivo (PowerShell; ajustar el archivo, el rango y el huso, que acá es UTC-3):

```powershell
function Get-Active($file, $from, $to, $gapMin = 10) {
  $prev = $null; $active = [TimeSpan]::Zero
  foreach ($line in [System.IO.File]::ReadLines($file)) {
    if ($line -match '"timestamp":"([^"]+)"') {
      $t = [datetime]::Parse($Matches[1]).ToUniversalTime().AddHours(-3)
      if ($t -lt $from -or $t -gt $to) { continue }
      if ($prev -and ($t - $prev).TotalMinutes -le $gapMin) { $active += $t - $prev }
      $prev = $t
    }
  }
  "{0:N0} min efectivos" -f $active.TotalMinutes
}
Get-Active "<sesion>.jsonl" ([datetime]'2026-11-09 21:05') ([datetime]'2026-11-09 21:21')
```

Las horas de los pedidos salen de los mensajes del usuario en el mismo archivo y las de los commits de `git log --format='%h %ad %s' --date=iso-local`.
