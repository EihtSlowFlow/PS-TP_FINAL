# Reunión del equipo del 29/10/2026 — Estimación y presupuesto

> Reunión de Discord en la que el equipo preparó la estimación de esfuerzo y el presupuesto del proyecto. Las conclusiones que quedaron en el repositorio están en [`PRESUPUESTO_Y_PLANIFICACION.md`](../PRESUPUESTO_Y_PLANIFICACION.md).

## 1. Método de estimación

- Cada objetivo del proyecto se describió con **valor, esfuerzo y riesgo**, y las historias se estimaron en **puntos de historia** con la sucesión de Fibonacci (1, 2, 3, 5, 8, 13). Se comprobó que los valores respetaran esa escala y se notó que entre un número y el siguiente no hay puntos medios.
- Como referencia se siguió la tabla de la cátedra que relaciona los puntos con la complejidad (por ejemplo, 3 puntos equivalen a complejidad mínima y riesgo bajo).
- **Riesgo:** lo estimó el equipo en tres niveles (alto, medio y bajo).
- **Valor:** se evaluó consultarlo a la cátedra, que hace de cliente, y se resolvió completarlo con criterio propio.
- **Esfuerzo:** estaba escrito en texto y se analizó pasarlo a números. Los puntos de historia miden el tamaño relativo de cada historia; el esfuerzo real cambia según la persona (lo que para uno es un 3, para otro es un 5).

## 2. Contenido del documento de presupuesto

Se revisó que el documento incluyera: requerimientos funcionales y no funcionales, historias de usuario con criterios de aceptación, análisis de riesgos y contingencias, Planning Poker, horas-hombre, estimación de esfuerzo, puntos de historia y un presupuesto financiero aproximado.

También se pidió dejar explícito qué funcionalidades entran en el desarrollo y cuáles quedan fuera. Como el proyecto es grande y el tiempo es justo, no se dejó nada fuera.

## 3. Horas

- Los borradores iniciales de la estimación dieron totales muy distintos entre sí (desde unas 33 horas hasta cientos de horas) y no siempre se podía rastrear el cálculo. Un borrador agregaba 20 % de riesgo y contingencia: 27 horas base pasaban a 32,5 y se redondeaban a 33.
- Aplicando la conversión teórica de horas por punto, el total superaba las 160 horas, y algunos intentos las 200. Con muchas historias de 5 puntos el total se disparaba (se habló de unas 130 horas).
- El equipo propuso equivalencias propias: 2 puntos ≈ 1 hora, 3 puntos ≈ 2 a 3 horas y 5 puntos ≈ 7 horas.
- Se calculó cuántas horas por día debía dedicar cada integrante hasta la entrega. Las restricciones son que la entrega es del 17 al 24 de noviembre, que son tres integrantes y que cada uno cursa unas cuatro materias, con parciales y finales en noviembre.
- **Conclusión:** se fijó un máximo práctico de **50 horas**, ajustado al tiempo disponible. Se justificó porque los casos de uso y la documentación ya estaban definidos, se contaba con las clases grabadas, el enunciado y la guía del trabajo final, y se iba a reutilizar código previo.
- Un participante sugirió el método para armar el presupuesto: ver cuántas horas dedica el equipo por día, calcular la semana y después el costo total.

## 4. Tarifa

- Como referencia se tomó lo visto en la clase del 17/9: entre $15.000 y $30.000 por hora según el perfil.
- El equipo acordó ubicarse **entre $15.000 y $20.000 por hora**.
- Se mencionó como referencia externa la página oficial de honorarios profesionales (la de Córdoba). **No quedó guardado el enlace.**

## 5. Alcance: cursada y final

- Se discutió si las 50 horas y los $900.000 representaban todo el desarrollo o solo la cursada. La revisión del documento concluyó que es válido como **estimación integral** si se aclara; si fuera solo la cursada, la tabla no coincidiría porque incluye funcionalidades del final (liquidación y dashboard).
- Lo que el enunciado pide solo para el final: gestión completa e historial de usuarios, notificaciones, liquidación financiera a propietarios y dashboard con reportes de gestión.
- La revisión señaló además dos inconsistencias: el registro de pagos (5 puntos) estaba calculado en 5,5 horas mientras que otras tareas de 5 puntos llevaban 6; y la Fase 2 no mostraba de forma explícita las notificaciones ni la gestión completa de usuarios.
- **Decisiones:** conservar la tabla completa, dejar el documento original como estaba y trabajar los cambios en un archivo aparte, para fusionarlos después.

## 6. Organización del trabajo

- El equipo trabaja con issues y un tablero de GitHub Projects. Se dudó de si eso es Scrum, que implica una reunión diaria de 15 minutos, y se propuso ordenar el tablero con el backlog a la izquierda y el sprint actual a continuación.

## 7. Notas técnicas

- En CodeIgniter el borrado lógico se habilita con una opción de configuración del modelo, lo que facilita las bajas lógicas de usuarios e inmuebles.
- Si la base de datos queda bien hecha se evita buena parte de los errores posteriores, por eso conviene dedicarle tiempo al principio.

## Pendientes al cierre de la reunión

- Confirmar con la cátedra qué se exige en la entrega del 17 al 24 de noviembre y si terminar todo antes de tiempo cambia la condición de promoción.
- Guardar el enlace de la fuente de honorarios si se la va a citar en el informe.
- Unificar la cifra de referencia (subtotal de la cursada o estimación integral) y la equivalencia de puntos a horas.
