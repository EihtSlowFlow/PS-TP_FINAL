# Informe de Avance y Traspaso (Handoff) — UI-33

## 1. Resumen Ejecutivo

| Campo | Valor |
|---|---|
| **Proyecto** | InmoGestión 2026 |
| **Historia de usuario / Issue** | Refactor de las pantallas: identidad visual propia (madera y melamina) · [#33](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/33) (mejora transversal `ui-ux`, P2, 5 pts; no es una HU) |
| **Sprint / Hito** | Sprint 2 |
| **Rama** | `feature/ui-33-identidad-visual`, creada desde `origin/Sprint-2` (`6885f8d`, la rama más actualizada: trae HU-14, HU-15, HU-16, HU-17 y HU-18) |
| **Último commit** | `6a6c9e4` — feat: identidad visual madera y melamina en todas las pantallas (#33) |
| **Pull Request** | [#43](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/43) → `Sprint-2` (abierto con `gh`; lleva `Closes #33` pero GitHub no lo toma como cierre porque la base no es `main`, ver R-7) |
| **Fecha de cierre** | 2026-11-17 10:23 (implementación 09:06; commit, push y PR 10:23) |
| **Inicio / Fin** | 2026-11-17 08:32 → 2026-11-17 10:23 (tres bloques: 08:32–09:06, 10:08–10:15 y 10:19–10:23) |
| **Tiempo invertido** | 44 min efectivos · 45 min de reloj · Medido (implementación, 34 min) y reconstruido por el registro de la sesión (levantar el localhost para revisar las pantallas, 7 min; commit, push y PR, 3 min) · sin pausas de más de 10 min dentro de cada bloque. Ver [`../REGISTRO_DE_TIEMPOS.md`](../REGISTRO_DE_TIEMPOS.md) |
| **Autor / Agente** | Claude Code con `LauPerisse` |
| **Estado global** | 🟡 Implementado, verificado y con pull request abierto. Falta la validación de la paleta con el equipo, las capturas antes y después, la revisión del PR y el comentario del tiempo en el issue |
| **Avance estimado** | 90 % (queda lo que depende del equipo) |

**Resumen:** Se aplicó la identidad «madera y melamina» a **todas** las pantallas del proyecto (login, registro y recuperación, panel, usuarios, inmuebles, contratos, cuotas, cobranzas, pagos, estado de cuenta, perfil, catálogos y páginas 404/400), no solo a las cuatro que nombra el issue. Todos los colores, fuentes, radios, sombras y la textura viven una sola vez en `public/css/tokens.css`; `panel.css` y `auth.css` solo usan variables. Quedó además una guía de estilos viva y el vocabulario de clases documentado para que los próximos issues partan de esta base.

---

## 2. Alcance y Criterios de Aceptación

Referencia: issue [#33](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/33).

| # | Criterio de aceptación (Dado / Cuando / Entonces) | Estado | Evidencia |
|---|---|---|---|
| CA-1 | Escenario 1: todas las pantallas usan la misma paleta, tipografía y componentes y no queda rastro del índigo | ✅ | Búsqueda de `4F46E5`, `4338CA`, `E0E7FF`, `C7D2FE`, `F0F4F8`, `D9E2EC` e «índigo» en `public/` y `app/Views` (sin las páginas de depuración de CodeIgniter): sin resultados. Capturas del login, el panel y la guía en el navegador |
| CA-2 | Escenario 2: contraste ≥ 4,5:1 (3:1 en controles), foco visible y estados con ícono + texto + color | ✅ | 41 pares verificados con un script sobre los valores reales de `tokens.css` (todos pasan, entre ellos los 9 del issue con las mismas cifras). Foco global de 3 px. Íconos de estado generados por CSS |
| CA-3 | Escenario 3: PHPUnit en verde y sin cambios de comportamiento | ✅ | Suite completa: 624 pruebas y 4.984 aserciones; fallan las mismas 6 que ya fallaban en `origin/Sprint-2` sin estos cambios (ver sección 5). No cambió ninguna ruta, formulario ni texto funcional |
| CA-4 | Escenario 4: 1440 px y 390 px; el sidebar pasa a drawer; impresión sin texturas | 🟡 | A 375 px: capturas del login, registro, catálogo público, contratos y panel con el drawer abierto; el drawer abre, enfoca y cierra con Escape; sin desplazamiento horizontal en las pantallas medidas (11 del panel a 1024 px y 6 a 375 px). Impresión: bloque `@media print` y tokens sin textura; todavía no existe ninguna vista imprimible para comprobarlo con un PDF |
| DoD | Paleta, tipografía y textura en un único archivo de variables | ✅ | `public/css/tokens.css` |
| DoD | Sin hex sueltos en `public/css/` | ✅ | `panel.css` y `auth.css` sin ningún literal de color (ni `rgb`); `styles.css` ya no está en `public/css/` |
| DoD | `DESIGN.md` alineado con el CSS real | ✅ | Los dos `DESIGN.md` reescritos con los valores de `tokens.css` |
| DoD | Pull request con capturas antes y después, revisado por el equipo | 🟡 | PR [#43](https://github.com/EihtSlowFlow/PS-TP_FINAL/pull/43) abierto; faltan las capturas y la revisión |

**Fuera de alcance / diferido:** el modo oscuro (el issue lo excluye); regenerar los prototipos de Stitch (quedan como referencia de estructura, como propone el issue); convertir las tablas en tarjetas apiladas en celular (ver R-1).

---

## 3. Cambios Realizados

| Capa | Archivo | Tipo | Descripción |
|---|---|---|---|
| CSS | `public/css/tokens.css` | Nuevo | Única fuente de colores, fuentes (`--font-body`, `--font-display`), radios, sombras, foco y textura de veta; anula textura y sombras en `@media print` |
| CSS | `public/css/panel.css` | Modificado | Sin `:root`; solo variables. `.table-responsive` pasa a `position: relative` (los rótulos `.sr-only` de las tablas ensanchaban toda la página y causaban desplazamiento horizontal). Sidebar café con veta e ítem activo miel; topbar y tarjetas con «canto» inferior de 2 px; botones sólidos con contraste; foco global; íconos de estado por CSS; badges de pago; drawer móvil; impresión; `empty-state`; helpers compartidos. Renombres genéricos. Se borró `.breadcrumb-link` (R-6 del review) |
| CSS | `public/css/auth.css` | Modificado | Sin `:root`; panel de marca café con veta, logo miel, enlaces subrayados, foco visible; 0 hex |
| CSS | `public/css/styles.css` | Movido | A `.sprint2_referencia/css/styles.css` (su único consumidor es `.sprint2_referencia/index.php`, que la enlaza como `css/styles.css`, así que sigue funcionando) |
| JS | `public/js/layout.js` | Nuevo | Drawer del sidebar a 900 px o menos: abre, cierra (overlay, Escape), atrapa el foco, `aria-expanded`. Se carga en `<head>` para no parpadear |
| JS | `public/js/users.js` | Modificado | Un selector: `.user-main-form` pasa a `.form-main` |
| Vista | `app/Views/layouts/panel.php` | Modificado | Enlaza `tokens.css` y la fuente Fraunces, botón hamburguesa, `id` del sidebar y overlay |
| Vista | `app/Views/auth/{login,register,forgot_password,reset_password}.php`, `properties/public_catalog.php` | Modificado | Enlazan `tokens.css` y Fraunces; en `login.php` los trazos `#059669` y `#4F46E5` pasan a `currentColor` |
| Vista | 19 vistas del panel (usuarios, inmuebles, contratos, cuotas, pagos, estado de cuenta, catálogos) | Modificado | Solo atributos `class`: `users-table`→`data-table`, `users-kpi-grid`→`kpi-grid`, `users-page-header`→`page-header`, `users-heading-row`→`page-heading-row`, `users-filter-form`→`filter-form`, `user-form-layout`→`form-layout`, `user-main-form`→`form-main`, `user-audit-sidebar` y `user-side-panel`→`form-aside`, `user-cell*`→`cell-*` |
| Vista | `admin/payments/{index,history,show}.php`, `tenant/payments/history.php`, `tenant/payments/form.php`, `admin/contracts/show.php` | Modificado | Usaban clases sin estilo (`status-inline`, `btn-small`, `form-group`, `responsive-table-wrap`, `form-actions` con `style=`): pasan al vocabulario común (`badge-payment-*`, `btn-sm`, `form-field`, `table-responsive`, `inline-actions-row`) |
| Vista | `app/Views/errors/html/error_404.php`, `error_400.php` | Modificado | Usan los tokens (antes, el naranja de CodeIgniter). `production.php` y la vista de depuración no se tocan: son del framework |
| Docs | `.docs/prototipos_pantallas/DESIGN.md` y `inmogesti_n_modern_admin/DESIGN.md` | Modificado | Reescritos con la nueva identidad; el primero suma la fuente de verdad, el vocabulario de clases y las reglas de textura e impresión |
| Docs | `.docs/prototipos_pantallas/guia_de_estilos.html` | Nuevo | Guía viva con los CSS reales: paleta, tipografía, botones, estados, mensajes, formularios, tablas, KPI, vacío, textura, reglas y esqueleto de una vista |
| Docs | `PROMPTS_STITCH_PROTOTIPOS.md`, `PROMPTS_STITCH_PANTALLAS_FALTANTES.md` | Modificado | Bloque ESTILO y LAYOUT de cada prompt y la lista de verificación con el estilo nuevo; ningún prompt supera los 2.000 caracteres (el más largo: 1.997) |
| Docs | `.agents/AGENTS.md`, `.docs/ORGANIZACION_DEL_PROYECTO.md` | Modificado | Regla de estilos para los agentes y ubicación de la guía y de `styles.css` archivada |

**Cambios de esquema de BD:** Ninguno.
**Cambios de rutas / permisos:** Ninguno. No se tocó ningún controlador, modelo, ruta ni texto funcional.

---

## 4. Decisiones Técnicas

Los puntos «Por definir» del issue no estaban cerrados por el equipo: se tomaron estos valores por defecto, todos reversibles editando solo `tokens.css`. **Conviene validarlos con el equipo.**

| Decisión | Alternativas consideradas | Justificación |
|---|---|---|
| Primario **nogal oscuro** `#7A4B2A`; secundario neutro (botón blanco con borde) en vez de grafito o beige | Roble claro como primario; grafito o beige como secundario | Es la paleta propuesta en el issue, con 7,32:1 en texto blanco. El roble claro queda como relleno suave (chips, rellenos) |
| Tipografía: Inter en datos y formularios y **Fraunces** (serif de Google Fonts) solo en títulos de página, marca y estados vacíos | Solo Inter; Lora; Playfair | Da carácter propio sin tocar la legibilidad de tablas. Si no carga, cae a Georgia; pasar los títulos a Inter es una línea (`--font-display`) |
| Textura: veta sutil en sidebar y panel de marca del login, con un SVG en línea (`feTurbulence`) dentro de `tokens.css` | Solo colores planos; imagen; degradados repetidos | Es lo que pide el issue (CSS o SVG liviano, sin imágenes ni librerías). Opacidad 22 %; se anula en impresión |
| Botones sólidos de peligro y éxito con el tono `-text`; el de advertencia con texto oscuro | Mantener `#EF4444` y `#10B981` con texto blanco | Blanco sobre `#EF4444` da 3,76:1 y sobre `#10B981` 2,54:1: no cumplían 4,5:1. Los semánticos de badges y mensajes no cambian |
| Ícono de estado generado por CSS (✓ ! ✕ i –) | Cambiar el HTML de cada badge; íconos SVG | Cumple «ícono + texto + color» sin tocar las vistas ni los textos (las pruebas no cambian) |
| Foco: contorno global de 3 px (nogal; miel sobre el sidebar) | Solo borde y halo al 15 % | El halo al 15 % no se ve; el contorno sí |
| Nombres genéricos de clases (`data-table`, `kpi-grid`, `page-header`…) | Dejar los `users-*` | Las vistas de pagos de Sprint 2 ya usaban `data-table`; el issue lo pide para tablas y KPI y se extendió al resto de los nombres atados a Usuarios |
| `styles.css` archivada en `.sprint2_referencia/css/` | Borrarla | No pierde el historial y deja funcionando la página de referencia; ya no está en `public/` |
| Páginas 404 y 400 con los tokens | Dejarlas con el estilo de CodeIgniter | Las ve cualquier usuario ante una URL equivocada |

**Seguridad aplicada:** no hay cambios de lógica ni de entradas. Las vistas siguen escapando con `esc()`. `layout.js` no lee ni envía datos.

---

## 5. Verificación y Calidad

| Verificación | Comando / Método | Resultado |
|---|---|---|
| Tests automatizados | `php phpunit-11.phar -c phpunit.dist.xml --no-coverage` en una copia del proyecto con una instancia MySQL aislada (≈ 13 min) | ✅ 624 pruebas, 4.984 aserciones, **5 errores y 1 falla, todos previos**: `PublicRegistrationTest` (HU-02) y `ProfileManagementTest` con `owner` y `tenant` (HU-06), errores del `DOMParser` ya conocidos en `main`, y 3 de `PaymentReviewTest` (HU-17: dos por CSRF «The action you requested is not allowed» y una por «TRF-777 is not seen»). Se comprobó en un checkout limpio de `origin/Sprint-2` que esos mismos 6 fallan igual sin mis cambios |
| Contraste | Script que lee `tokens.css` y calcula la razón WCAG de 41 pares de texto, controles, foco y botones | ✅ Todos pasan; los 9 pares que cita el issue dan las mismas cifras |
| Sintaxis PHP | `php -l` de las 26 vistas modificadas | ✅ Sin errores |
| CSS | Llaves balanceadas; toda `var(--x)` está declarada en `tokens.css` y todo token declarado se usa | ✅ |
| Prueba manual | App real con datos de demostración (base aislada), como administrador: panel, usuarios y alta de usuario, inmuebles y alta, contratos y alta, emitir cuotas, cobranzas, pagos e historial de pagos; y públicas: login, registro, recuperar contraseña, catálogo y 404. Se midió cada una (título, errores PHP, desplazamiento horizontal) y se miraron capturas de varias | ✅ Sin errores; la identidad nueva se ve en todas |
| Responsive | 375 px sobre la guía y sobre la app real: sin desplazamiento horizontal, botón de 44 px, el drawer abre, enfoca y cierra con Escape; capturas de login, registro, catálogo, contratos y drawer | ✅ |
| Contraste de la veta | Mezcla del color café con la veta en su punto más claro (opacidad 22 %, caso teórico máximo) | ✅ Texto principal 7,31:1 y secundario 5,00:1 sobre la veta |
| Regresión | Prueba de `aside.panel-sidebar` y `main.panel-content` (`AuthAccessControlTest`): 24 pruebas y 146 aserciones | ✅ |

**Sin verificar:**
- Las pantallas de los roles Propietario, Inquilino y Empleado y las fichas (contrato, cuota, pago) y formularios de edición: usan los mismos componentes, pero no se recorrieron una por una. El flujo de «Informar pago» del inquilino y la revisión de un pago tampoco se abrieron en el navegador (sí se corrió su suite de pruebas).
- Una vista imprimible real (todavía no existe ninguna).
- Capturas antes y después para el pull request: las toma quien abra el PR.

---

## 6. Riesgos, Bloqueos y Deuda Técnica

| ID | Tipo | Descripción | Severidad | Acción sugerida |
|---|---|---|---|---|
| R-1 | Deuda | En celular las tablas se desplazan en horizontal dentro de su tarjeta; no se convirtieron en tarjetas apiladas como describe `DESIGN.md` (requeriría `data-label` en cada celda de 8 vistas) | Baja | Decidirlo con el equipo; si se hace, agregar `data-label` a las tablas y una regla `@media` |
| R-2 | Riesgo | La paleta, la fuente Fraunces y la veta son valores por defecto pendientes de validación del equipo | Media | Mostrar `guia_de_estilos.html` y cerrar los «Por definir» del issue; cualquier ajuste es en `tokens.css` |
| R-3 | Riesgo | Fraunces se pide a Google Fonts; sin conexión cae a Georgia | Baja | Aceptable (Inter ya dependía de lo mismo) |
| R-4 | Deuda | Siguen con el estilo del framework `errors/html/production.php`, la vista de depuración y `welcome_message.php` | Baja | Dejarlos: no los ve el usuario final |
| R-5 | Deuda | Los prototipos de Stitch ya generados conservan el índigo | Baja | Por decisión del issue quedan como referencia de estructura |
| R-7 | Riesgo | El PR apunta a `Sprint-2`, que no es la rama por defecto: `Closes #33` no cierra el issue al mergear (GitHub solo lo hace al llegar a `main`), así que el issue y su tarjeta no pasan solos a «Done» | Media | Cerrar el issue y pasar la tarjeta a mano al mergear, o automatizarlo con una GitHub Action que cierre los issues del PR al mergear a `Sprint-2` |
| R-6 | Deuda ajena | Las 3 pruebas de `PaymentReviewTest` (HU-17) ya fallaban en `origin/Sprint-2`: dos mandan un POST sin token CSRF y una espera el texto «TRF-777» que la pantalla no muestra | Media | Avisar a quien hizo HU-17 (joaquincorbalan); no es de este issue |

---

## 7. Próximos Pasos

| Prioridad | Tarea | Archivos previstos | Skills a cargar |
|---|---|---|---|
| 1 | Mostrar la guía al equipo y cerrar los «Por definir» (paleta, fuente, textura) | `.docs/prototipos_pantallas/guia_de_estilos.html`, `public/css/tokens.css` | `frontend-html-css` |
| 2 | Revisar a mano cada pantalla con cada rol a 1440 px y a 390 px; sacar las capturas antes y después | — | `qa-regression` |
| 3 | Confirmar los cambios, abrir el pull request con las capturas y comentar el tiempo en el issue | — | — |
| 4 | Los próximos issues con pantallas (HU-02, HU-06, HU-08, HU-19 en adelante) parten de `layouts/panel` y de la guía; no agregan colores fuera de `tokens.css` | `app/Views/...` | `frontend-html-css` |

**Siguiente HU sugerida:** la que toque el equipo; las vistas nuevas ya salen con la identidad.
**Prerrequisitos del entorno:** ninguno nuevo. Para ver la guía basta abrir `.docs/prototipos_pantallas/guia_de_estilos.html` desde un servidor que sirva la carpeta del proyecto (por ejemplo `php -S 127.0.0.1:8099 -t .`), porque enlaza los CSS por ruta relativa.

---

## 8. Historial de Sesiones

| Fecha | Autor | Duración | Resumen | Commit / PR |
|---|---|---|---|---|
| 2026-11-17 | Claude Code con `LauPerisse` | 44 min | Tokens, tema aplicado a todas las pantallas, drawer, guía de estilos y documentación; revisión local; commit, push y PR | `6a6c9e4` / #43 |

---

## 9. Protocolo de Inicio para el Nuevo Agente

1. Leer `.agents/AGENTS.md` y `.docs/prototipos_pantallas/DESIGN.md`.
2. Abrir la guía de estilos y leer `public/css/tokens.css`.
3. Anotar la hora de inicio (`Get-Date -Format "yyyy-MM-dd HH:mm"`).
4. Verificar con `git status` y `git log -n 5` que el estado coincide con lo documentado.
5. Para una pantalla nueva: extender `layouts/panel`, reutilizar las clases de la guía y no escribir ningún color fuera de `tokens.css`.
