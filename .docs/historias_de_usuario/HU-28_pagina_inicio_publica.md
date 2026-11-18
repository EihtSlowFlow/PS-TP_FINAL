# HU-28 — Página de inicio pública

> **Rol:** Público (visitante) · **Alcance:** Cursada (ampliación detectada el 17/11/2026) · **Prioridad:** P1 · **Story Points:** 3 · **Riesgo:** Bajo · **Dependencias:** HU-08 (#3)

## Estado actual

Hoy `/` redirige al login si no hay sesión o al panel según el rol. El catálogo público ya existe, pero no tiene una página de inicio compartida ni llamados claros para buscar alquiler, registrarse o publicar un inmueble.

## Historia de usuario

Como visitante,

quiero llegar a una página de inicio que explique InmoGestión y me permita buscar un alquiler, registrarme o publicar un inmueble,

para entender la plataforma y comenzar a usarla sin conocer sus rutas internas.

## Criterios de aceptación

### Escenario 1: Visitante sin sesión

**Dado** que no tengo sesión iniciada,

**cuando** entro a `/`,

**entonces** el sistema muestra Inicio, Propiedades, Ingresar y Registrarse, una propuesta de valor, un buscador rápido por tipo y precio máximo, las últimas propiedades disponibles, una explicación para inquilinos y propietarios y los datos de contacto.

### Escenario 2: Llamados a la acción

**Dado** que estoy en la página de inicio,

**cuando** elijo «Buscar alquiler», «Publicar mi inmueble» o «Registrarme»,

**entonces** el sistema me lleva al catálogo, al registro con el rol Propietario preseleccionado o al registro, respectivamente.

### Escenario 3: Usuario con sesión

**Dado** que tengo una sesión iniciada,

**cuando** entro a `/`,

**entonces** el sistema me redirige a mi panel correspondiente.

### Escenario 4: Sin propiedades disponibles

**Dado** que no hay inmuebles disponibles,

**cuando** se carga la página,

**entonces** se muestra un mensaje informativo sin dejar la sección vacía.

## Reglas de negocio

- Solo se muestran inmuebles activos y disponibles; una vez implementada HU-32, también deben estar aprobados.
- El registro acepta los parámetros `owner` o `tenant` para preseleccionar el rol.
- El encabezado y el pie serán un layout público compartido por inicio, catálogo y detalle.
- HTML5 semántico y responsive, sin librerías nuevas.

## Por definir

- Los textos de servicios y los datos reales de contacto. Propuesta: mantenerlos en configuración y reutilizarlos en la ficha pública.
- La landing no tiene un prototipo previo. Se puede seguir como referencia el encabezado y el hero del catálogo público.

## Definition of Done

- [x] `/` muestra la página de inicio a los visitantes y redirige a los usuarios autenticados.
- [x] Inicio, catálogo y detalle reutilizan el layout público.
- [x] Los llamados a la acción llevan a las rutas correspondientes.
- [x] El registro permite preseleccionar solo Propietario o Inquilino.
- [x] Se reemplaza la prueba actual que exige redirigir a login desde `/` y se prueban los escenarios.
- [x] Handoff en `.docs/exec-plans/` y tiempo registrado en `.docs/REGISTRO_DE_TIEMPOS.md` y en un comentario del issue.

## Dependencias

- HU-08 (#3): catálogo público.
- HU-02 (#20): registro de usuarios.
- Relacionada con HU-32 (#57) y HU-29 (#54).

## Referencias

- Código de referencia: `app/Controllers/Home.php`, `app/Views/properties/public_catalog.php`, `app/Config/Routes.php`.
- Issue: [#53](https://github.com/EihtSlowFlow/PS-TP_FINAL/issues/53).
