# Fase 2 — Marca blanca y sistema visual mobile first

## Objetivo

Permitir que cada instalación adopte la identidad y configuración de su negocio sin editar código, manteniendo una experiencia usable en teléfono, tableta y escritorio.

## Bloques de trabajo

### F2.1 — Perfil de empresa y contrato de configuración

- Perfil tipado para identidad, contacto, ubicación y zona horaria.
- Lista cerrada de claves editables, normalización y validación del lado servidor.
- Compatibilidad con `company_name` y `company_logo` para no romper login, carta ni tickets.
- Autorización defensiva mediante `settings.manage`.
- Panel con campos de contacto y ubicación, resumen accesible de errores y controles táctiles.
- Especificación del asistente inicial documentada en `docs/UX_UI.md`.

**Estado:** completado. La suite pasa con 27 pruebas y 90 aserciones.

### F2.2 — Identidad visual y recursos

- Contexto de marca compartido y cacheable, sin consultas directas desde Blade.
- Logo principal, icono cuadrado y favicon con reemplazo y retiro seguros.
- Colores primario y secundario con validación hexadecimal y contraste WCAG 2.2 AA.
- Tokens centrales aplicados primero a layout, login y carta pública.
- Vista previa de marca antes de guardar.

**Estado:** completado. El contexto de marca descarta colores o rutas manipulados incluso si no ingresaron por el formulario. La configuración permite previsualizar, reemplazar y retirar recursos; layout, login y carta consumen los mismos tokens y fallbacks. La matriz residual de seguridad está en `docs/F2_2_QA_SECURITY.md`.

### F2.3 — Asistente de configuración inicial

- Flujo reanudable de cinco pasos definido en UX/UI.
- Detección segura de instalación incompleta.
- Separación entre datos públicos, operación y tributación; SUNAT queda fuera del asistente.
- Acceso posterior a las mismas secciones desde configuración.

**Estado:** completado. El asistente guarda cada paso, permite volver a pasos confirmados, registra versión, usuario y fecha al finalizar, y no solicita ni modifica datos SUNAT. Un estado explícito `pending` o `in_progress` bloquea la operación: el administrador es dirigido al asistente y los demás roles esperan su finalización. Las instalaciones existentes con nombre configurado y sin estado conservan su acceso. `Guardar y salir` solo permite entrar al panel en local o testing.

### F2.4 — Módulos habilitables

- Registro de módulos y dependencias.
- Rutas, navegación, tareas e integraciones condicionadas del lado servidor.
- Desactivar nunca elimina información.
- Pruebas para módulos habilitados y deshabilitados.

**Estado:** completado. El registro central distingue el núcleo inmutable de nueve módulos opcionales, valida dependencias y ciclos, persiste únicamente el estado y responde 404 en rutas deshabilitadas. El panel administrativo y la navegación reflejan el mismo contrato, pero la protección permanece en el servidor. Cada cambio efectivo queda auditado y nunca elimina datos del módulo.

### F2.5 — Sistema visual y validación responsive

- Componentes base accesibles con objetivos táctiles mínimos de 48 por 48 px.
- Navegación y layouts por rol para móvil, tableta y escritorio.
- Validación a 320, 360, 768, 1366 y 1920 px.
- Eliminación incremental de estilos embebidos y valores de marca codificados.

**Estado:** base transversal implementada; puerta de accesibilidad pendiente. Se añadieron tokens, foco visible, controles táctiles de 48 px, safe areas, reducción de movimiento, contenedores y tablas desplazables. Login y navegación móvil fueron corregidos para 320 px, alturas reducidas y zoom, con estado ARIA, Escape y retorno de foco. La auditoría estática aún identifica deuda heredada fuera de las pantallas creadas en esta fase: aproximadamente 110 etiquetas sin asociación, 22 cierres sin nombre accesible y 15 tablas sin contenedor responsive. La Fase 2 no se cerrará hasta resolver y verificar esa puerta en navegador autenticado.

## Decisiones

- Una instalación representa una empresa; no se introduce multitenencia.
- Los datos fiscales y secretos siguen separados del perfil público.
- La marca actúa como acento en administración; las superficies operativas conservan contraste neutral.
- La moneda evolucionará de símbolo heredado a código ISO sin romper los consumidores actuales.
