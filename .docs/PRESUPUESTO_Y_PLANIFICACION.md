# Presupuesto y planificación — InmoGestión 2026

> Última actualización: 06/11/2026. Este documento complementa a [`ANALISIS_DE_REQUERIMIENTOS.md`](ANALISIS_DE_REQUERIMIENTOS.md), que sigue siendo la fuente de las cifras (sección 8). Acá se deja asentado **por qué** salieron esas cifras, qué restricciones las condicionan y qué inconsistencias quedan por corregir.
>
> **Fuentes:** la charla del equipo del 29/10/2026 ([`documentacion/discord proyecto 29-10-26.md`](documentacion/discord%20proyecto%2029-10-26.md)), contrastada con las clases del 17/9 y del 20/10 (`.context/`), el enunciado ([`2026 - inmogestion.md`](2026%20-%20inmogestion.md)) y los demás documentos del repositorio. La transcripción es un dictado automático con mucho ruido: solo se tomó lo que se entiende con claridad y coincide con otras fuentes. Lo dudoso se marca como tal.

## Índice

1. [Cifras vigentes](#1-cifras-vigentes)
2. [Cómo se llegó a esas cifras](#2-cómo-se-llegó-a-esas-cifras)
3. [Alcance de la estimación](#3-alcance-de-la-estimación)
4. [Contenido que debe tener el documento de presupuesto](#4-contenido-que-debe-tener-el-documento-de-presupuesto)
5. [Inconsistencias detectadas](#5-inconsistencias-detectadas)
6. [Decisiones pendientes](#6-decisiones-pendientes)

---

## 1. Cifras vigentes

Tomadas de la sección 8 de [`ANALISIS_DE_REQUERIMIENTOS.md`](ANALISIS_DE_REQUERIMIENTOS.md). Si cambian allá, hay que actualizarlas acá.

| Fase | Contenido | SP | HH | Subtotal |
|---|---|---|---|---|
| **1. Cursada (regularización)** | Seguridad, usuarios, inmuebles, contratos, cuotas y mora, portal del inquilino y registro de pagos | 33 | 50,0 | $900.000 |
| **2. Examen final** | Liquidación a propietarios, dashboard directivo, consulta de liquidaciones | 9 | 11,0 | $198.000 |
| **3. Post-entrega** | Garantía de 60 días, capacitación, mantenimiento LAMP, backups | — | 22,33 | $402.000 |
| **Total** | | **42** | **83,33** | **$1.500.000** (tope) |

- **Tarifa:** $18.000 ARS por hora. 83,33 HH × $18.000 = $1.499.940, que se redondea al tope.
- **Facturación escalonada:** 20 % al inicio, 40 % con la cursada regularizada, 20 % con el final y 20 % al cerrar (detalle en la sección 8 del análisis).
- Las **50 horas y los $900.000** coinciden con el subtotal de la Fase 1 (cursada). Ojo: en la charla se los trató como el costo de **todo** el desarrollo. Ver [alcance](#3-alcance-de-la-estimación) e [inconsistencias](#5-inconsistencias-detectadas).

---

## 2. Cómo se llegó a esas cifras

### 2.1 Método de estimación

El análisis usa la escala **V.E.R.** (Valor, Esfuerzo, Riesgo) con puntos de historia de la sucesión de Fibonacci (1, 2, 3, 5, 8, 13). Los criterios que dio la cátedra en la clase del 20/10 son:

| Factor | Criterio de la cátedra |
|---|---|
| **Valor** | Cuántos problemas le saca de encima al cliente el requerimiento. Se deduce del negocio; solo se le pregunta directamente al cliente si es experto. Para una inmobiliaria, uno de los más valorados es poder ver el estado de los contratos de alquiler |
| **Riesgo** | Un solo puntaje, pensado desde dos lados: el riesgo interno del requerimiento (conocimiento que falta, dependencias externas, depende de una sola persona) y el impacto sobre el flujo del proyecto si se posterga (login y roles condicionan toda la arquitectura) |
| **Esfuerzo** | Dificultad y tiempo no son lo mismo: algo puede ser largo pero simple, o corto pero complejo. Se mide con Planning Poker |

Cómo lo aplicó el equipo (charla del 29/10):

- Se siguió una tabla de equivalencias entre puntos y complejidad como referencia (por ejemplo, 3 SP = complejidad mínima y riesgo bajo).
- **Riesgo:** lo dedujo el equipo "a ojo", en tres niveles (alto, medio, bajo).
- **Valor:** se consideró consultarlo a la cátedra, que hace de cliente, pero se resolvió completarlo con criterio propio.
- **Esfuerzo:** se había escrito en texto y se evaluó pasarlo a número. Los puntos de historia son una medida relativa de la historia; el esfuerzo real cambia según la persona (lo que para uno es un 3 para otro es un 5).
- Se usaron borradores generados con IA a partir de los documentos del equipo. Dieron totales muy dispares (desde unas 33 horas hasta cientos) y no siempre se podía rastrear el cálculo, por lo que las horas se terminaron ajustando a mano.

### 2.2 Por qué unas 50 horas

La conversión teórica (cantidad fija de horas por punto) daba **160 horas y pico**, y algunos intentos superaban las 200. Con muchos 5 en las historias el total se disparaba (en la charla se habló de unas 130 horas). No era viable por estas restricciones:

- **Plazo:** la entrega es en la segunda semana de noviembre (**17 al 24 de noviembre de 2026**, según el enunciado). La charla fue el 29/10: quedaban entre 19 y 26 días hasta esa ventana (en la charla hablaban de "un mes"), y el equipo contaba con unas dos semanas de trabajo efectivo, o menos.
- **Capacidad:** son tres integrantes y cada uno cursa unas cuatro materias a la vez, con parciales y finales en noviembre.
- **Decisión:** se fijó un tope práctico de **unas 50 horas** ("máximo 50"), que a $18.000 por hora da $900.000. Las horas se ajustaron al tiempo disponible ("nos adaptamos al tiempo límite"); no resultan de sumar las historias una por una. Un indicio en la tabla de la Fase 1: 33 SP × 1,5 HH = 49,5 HH, y los 0,5 HH extra del registro de pagos completan las 50.

Qué hace creíble ese tope (dicho en la charla):

- Los casos de uso y la documentación ya están definidos, así que no hay que relevar ni inventar requisitos desde cero.
- Se cuenta con las clases grabadas, el enunciado y la guía del trabajo final.
- Se reutiliza código previo (`.sprint2_referencia/`) y se usan herramientas de asistencia para acortar tiempos.
- CodeIgniter 4 resuelve la baja lógica con una opción del modelo (`$useSoftDeletes`), lo que facilita HU-05 y HU-09.
- Un riesgo ya identificado: si la base de datos no queda bien hecha se multiplican los errores ("si está bien hecha, nos ahorramos la mitad de los errores"). Coincide con que el issue #22 sea P0 y se haga entre los tres.

Costo de este enfoque: según el **triángulo de proyecto** de la cátedra (costo, tiempo y alcance fijan la calidad), con costo y plazo ajustados la única forma de no perder calidad es no agregar alcance.

### 2.3 Tarifa

- Se usan **$18.000 por hora**.
- En la clase del 17/9 se habló de una hora de desarrollo de $20.000 a $30.000 y de $15.000 a $20.000 para perfil junior (la transcripción no deja claro quién lo dijo); en la charla se citó un rango de $15.000 a $30.000. El equipo se ubicó en **$15.000 a $20.000**, y $18.000 queda dentro de ese rango.
- Se mencionó como referencia externa la página oficial de honorarios profesionales (se citó la de Córdoba). **No hay enlace guardado**; conviene agregarlo si se va a citar en el informe.

---

## 3. Alcance de la estimación

Lo que surgió en la charla:

- Los $900.000 y las 50 horas se discutieron como el costo de **todo el desarrollo**, y la tabla del borrador incluía también liquidación y dashboard, que son del final.
- Una revisión hecha con un asistente de IA señaló que eso es válido **solo si se aclara que la estimación es integral** (cursada y final). Si esas cifras representaran solo la cursada, la tabla no coincidiría, porque incluye funcionalidades del final.
- Se concluyó que no hay problema siempre que se aclare el alcance; ante la duda se optó por conservar la tabla completa.
- El análisis vigente ya separa por fases: **50 HH y $900.000 son el subtotal de la cursada**, y **83,33 HH y $1.500.000 el total** con final y post-entrega. Falta unificar cuál es la cifra de referencia (ver [inconsistencias](#5-inconsistencias-detectadas)).
- Según el enunciado, lo que **no** entra en la cursada es lo siguiente:

| Requerido solo para el final | Historias | Reflejo en la tabla de Fase 2 del análisis |
|---|---|---|
| ABMC de usuarios: gestión completa e historial | Sin historia propia en el backlog (HU-05 solo conserva el historial en la baja) | No figura |
| Gestión de notificaciones | HU-22, HU-23, HU-24 (11 SP) | No figura en la tabla ni en las horas; sí aparece en la descripción del sprint |
| Liquidación financiera a propietarios (con su consulta) | HU-19, HU-20, HU-21 | Sí: "Liquidación Financiera" y "Consulta y Recibos de Liquidación" |
| Dashboard y reportes de gestión | HU-25, HU-26, HU-27 | Parcial: "Dashboard y Panel de Control Directivo"; no se nombran los reportes (HU-27) |

---

## 4. Contenido que debe tener el documento de presupuesto

En la charla se revisó una lista de contenidos del documento. Hoy están cubiertos así:

| Contenido | Dónde está |
|---|---|
| Requerimientos funcionales y no funcionales | Análisis, sección 5 |
| Historias de usuario y criterios de aceptación | Análisis, sección 4 (condiciones de satisfacción) y [`historias_de_usuario/`](historias_de_usuario/) |
| Análisis de riesgos y contingencias | Análisis, sección 6 |
| Planning Poker y horas-hombre | Análisis, secciones 1 y 8 |
| Estimación de esfuerzo y puntos de historia | Análisis, sección 8 y backlog de [`historias_de_usuario/README.md`](historias_de_usuario/README.md) |
| Presupuesto financiero aproximado | Análisis, sección 8 |
| Alcance explícito (qué entra y qué queda fuera) | Análisis, sección 2; falta aclarar el carácter integral de la estimación (ver [sección 3](#3-alcance-de-la-estimación)) |

---

## 5. Inconsistencias detectadas

Para corregir en [`ANALISIS_DE_REQUERIMIENTOS.md`](ANALISIS_DE_REQUERIMIENTOS.md) cuando el equipo decida tocarlo (en la charla se acordó dejarlo como estaba y trabajar los cambios aparte).

| # | Inconsistencia | Detalle |
|---|---|---|
| 1 | **Cifra de referencia ambigua** | En la charla, 50 h y $900.000 eran el total del desarrollo. En el análisis vigente son solo la cursada (el total es 83,33 h y $1.500.000, con 22,33 h de post-entrega). El `README.md` usa 83,33 HH y $1.500.000 |
| 2 | **Fase 2 incompleta** | Faltan en la tabla las notificaciones y la gestión completa e historial de usuarios, pedidas para el final. En la charla se marcó que esto no estaba explícito |
| 3 | **Equivalencia puntos → horas no uniforme** | En la Fase 1 casi todo se calcula a 1,5 HH por punto (2 SP = 3,0 HH; 3 SP = 4,5 HH; 5 SP = 7,5 HH), pero el registro de pagos (5 SP) lleva 8,0 HH. En la Fase 2, 2 SP son 2,5 HH y 5 SP son 6,0 HH. Es el mismo tipo de desvío que marcó la revisión con IA en la charla (5 SP equivalían a 5,5 h en una tarea y a 6 h en otras) |
| 4 | **Dos escalas de puntos** | El análisis estima por módulo (33 + 9 = 42 SP); el backlog estima por historia. Sumando las historias, la cursada da 70 SP (73 con HU-03b) y el final 41 SP, no los 66 y 38 del resumen. Si las 73 SP de historias se convirtieran a 1,5 HH por punto darían unas 110 HH, más del doble de las 50 presupuestadas. **No convertir los puntos de historia a horas con la escala del análisis** |
| 5 | **Enlaces rotos en el `README.md`** | Apunta a `PRESUPUESTO_Y_PLANIFICACION.md` (este documento, que no existía) y a `[BORRADOR]ANALISIS_DE_REQUERIMIENTOS.md` (hoy es `ANALISIS_DE_REQUERIMIENTOS.md`). El primero queda resuelto con este documento |
| 6 | **"Todas las funcionalidades" en el enunciado** | La sección de entrega dice que en la fecha de la entrega final "se debe cumplir con todas las funcionalidades para aprobar la cursada", mientras que la matriz de requisitos separa cursada y final. En la charla también quedó la duda de qué implica terminar todo antes de tiempo para la promoción |

**Actualización del 06/11/2026:** los puntos 1, 2, 4 y 5 quedaron resueltos al alinear la documentación con el análisis. La sección 9.1 del análisis fija 83,33 h y $1.500.000 como estimación vigente (punto 1) y explica la relación entre las dos escalas de puntos, que también está en el README de historias (punto 4). La sección 9.2 explica que las notificaciones forman parte del esfuerzo de la Fase 2, y HU-04 incluye para el final la consulta de usuarios dados de baja (punto 2). El `README.md` ya no tiene enlaces rotos (punto 5). **Siguen abiertos** el punto 3 (equivalencia de puntos a horas) y el 6 (qué se exige en la entrega).

---

## 6. Decisiones pendientes

1. **Confirmar con la cátedra qué se exige en la entrega del 17 al 24 de noviembre** (punto 6 de la sección anterior): solo la cursada o todo, y si terminar todo cambia la condición de promoción.
2. **Fijar la cifra de referencia:** aclarar en el análisis que 50 h y $900.000 son el subtotal de la cursada y que la estimación es integral (83,33 h y $1.500.000), o unificar en un único total.
3. **Elegir una equivalencia única de puntos a horas** (por ejemplo 1,5 HH por punto) o justificar las excepciones, y aplicarla igual en las Fases 1 y 2.
4. **Completar la Fase 2** con notificaciones y gestión completa e historial de usuarios, o declararlas expresamente fuera del presupuesto.
5. **Definir si se agrega un margen de contingencia explícito** sobre las horas. Un borrador agregaba 20 % de riesgo y contingencia (27 h base → 32,5 h, redondeadas a 33 h), pero el análisis actual no lo tiene. No quedó claro a qué alcance correspondía ese borrador.
6. **Guardar el enlace de la fuente de honorarios** si se la va a citar.

**Actualización del 06/11/2026:** las decisiones 2 y 4 quedaron resueltas por lo explicado en la sección anterior. Siguen abiertas la 1, la 3, la 5 y la 6.
