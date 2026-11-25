# Auditoría UX/UI y dirección de producto

## Alcance

Auditoría de solo lectura realizada sobre las vistas Blade, rutas y estilos existentes de RecreoSuite. No se modificaron vistas, CSS, JavaScript ni comportamiento. El análisis describe el estado actual y propone una dirección UX/UI para una aplicación de marca blanca, con una instalación independiente por negocio.

La evaluación del funcionamiento visual debe complementarse más adelante con pruebas en navegador sobre escritorio, tableta, teléfono y dispositivos táctiles usados en caja y cocina. Los hallazgos aquí registrados se sustentan en la implementación disponible.

## Resumen ejecutivo

El sistema cubre una operación de restaurante considerable: acceso, panel, salón, POS, cocina, caja, delivery, reservas, clientes, inventario, reportes y facturación electrónica. La interfaz es usable como punto de partida, pero todavía no forma un producto coherente y escalable.

Los principales problemas son:

1. La navegación lateral mezcla tareas frecuentes, administración, facturación y acciones peligrosas en un listado extenso.
2. Cada vista resuelve su presentación de manera local. Se encontraron 14 bloques `<style>` y aproximadamente 191 atributos `style`, además de estilos globales duplicados entre el layout y `resources/css/app.scss`.
3. La marca está parcialmente parametrizada, pero colores, tipografías, textos comerciales, imágenes externas y varios nombres siguen escritos directamente en las vistas.
4. La revisión binaria confirmó que los archivos están codificados en UTF-8; la aparente corrupción observada provenía de la consola de Windows al leerlos con otra página de códigos.
5. La accesibilidad es insuficiente: faltan textos alternativos en imágenes, asociaciones explícitas entre algunas etiquetas y campos, estados de foco sistemáticos, semántica consistente y mecanismos accesibles para varias interacciones.
6. El responsive se atiende principalmente en el layout y en la carta pública; las tablas, modales, POS, mapa de mesas y pantallas densas requieren validación y patrones específicos.
7. No existe todavía la experiencia de landing administrable ni el módulo de alojamiento.

La recomendación es no hacer un rediseño cosmético aislado. Primero debe consolidarse un sistema de diseño marca blanca y una arquitectura de información por tareas y roles; luego migrar por flujos, empezando por navegación, POS/caja/cocina y configuración de negocio.

## Inventario de experiencias y pantallas

### Acceso y estructura global

- Inicio de sesión con panel gráfico, identidad básica y mensajes de error.
- Layout autenticado con barra lateral fija, barra superior, perfil y cierre de sesión.
- Perfil del usuario editado mediante modal global.
- Dashboard con indicadores, gráficos y productos destacados.
- Vista `welcome` heredada de Laravel, aparentemente ajena al flujo principal y candidata a retirarse o reutilizarse conscientemente.

### Operación de restaurante

- Punto de venta: selección de área y mesa.
- Toma de pedido por mesa: categorías, productos, cantidades, notas de cocina y carrito.
- Acciones de pedido: precuenta, comanda, descuento, cambio de mesa, división de cuenta y cobro.
- Monitor de cocina (KDS) con estados de preparación.
- Diseño de salón: zonas, mesas y posiciones.
- Reservas: alta, estados y eliminación.
- Carta digital pública con categorías, promociones, recomendaciones, populares y productos nuevos.

### Caja y ventas

- Apertura de caja.
- Cierre de caja.
- Historial de turnos.
- Ventas y movimientos, registro de egresos e indicadores de caja.
- Historial de ventas y tickets.
- Reporte diario imprimible.

### Delivery

- Bandeja/listado de pedidos delivery.
- Creación de delivery.
- Detalle, asignación de repartidor, cambios de estado, cobro y cancelación.
- Administración de repartidores.

### Clientes y catálogo

- Cartera de clientes con creación y edición en modales.
- Perfil del cliente con gasto y visitas.
- Categorías.
- Productos: listado, creación y edición.
- Ajuste de inventario.
- Kardex de movimientos.

### Facturación electrónica

- Bandeja y detalle de comprobantes.
- Descarga de XML, CDR, PDF A4 y ticket.
- Reintento de comprobantes.
- Creación, listado y detalle de notas de crédito.
- Resúmenes diarios y consulta de estado.
- Configuración de emisor, ambiente, credenciales y certificado dentro de la configuración general.

### Administración y soporte

- Personal/usuarios.
- Reportes gerenciales.
- Configuración básica de empresa, región, logo y SUNAT.
- Mantenimiento: reinicio, respaldo y restauración.

### Ausencias relevantes para el producto objetivo

- Landing pública administrable.
- Editor de contenido por bloques, navegación pública, SEO, analítica y formularios.
- Alojamiento: tipos, unidades, disponibilidad, tarifas, calendario, reservas, huéspedes, check-in, check-out, limpieza y mantenimiento.
- Centro de marca completo: variantes de logo, favicon, paleta, tipografías, fotografía y tono.
- Configuración y dependencias de módulos habilitables.
- Onboarding inicial y lista de preparación para publicar.

## Hallazgos UX/UI

### 1. Arquitectura de información y navegación

**Alta prioridad**

- La barra lateral presenta demasiadas opciones simultáneas y mezcla dominios distintos. Operaciones de alta frecuencia como POS y cocina compiten visualmente con configuración, reportes, SUNAT y mantenimiento.
- `Historial de Ventas`, caja, comprobantes y reportes aparecen como destinos separados sin una jerarquía financiera clara.
- Categorías, productos e inventario se encuentran bajo una etiqueta genérica de gestión, aunque para el usuario forman el dominio `Carta e inventario`.
- El enlace a la carta digital abre una pestaña nueva, pero no diferencia con suficiente claridad entre previsualizar, administrar y publicar.
- Mantenimiento aparece en la navegación primaria y con énfasis visual. Las acciones destructivas o excepcionales deben residir en un área secundaria, restringida y con confirmaciones reforzadas.
- La selección de opciones depende de roles fijos. La navegación futura debe derivarse de capacidades, módulo habilitado y contexto de trabajo.

**Recomendación**

Usar navegación por espacios de trabajo: `Inicio`, `Restaurante`, `Carta e inventario`, `Clientes y reservas`, `Alojamiento`, `Finanzas`, `Sitio web` y `Administración`. Mostrar accesos directos por rol y conservar el mapa completo dentro de cada espacio.

### 2. Consistencia visual y mantenibilidad

**Alta prioridad**

- El layout define un tema púrpura/naranja, mientras que vistas internas usan libremente colores semánticos de Bootstrap. Esto produce jerarquías y significados variables.
- Hay reglas globales en `resources/css/app.scss`, pero el layout y múltiples páginas incluyen CSS propio. Los componentes no comparten una fuente única de verdad.
- Botones, encabezados, tarjetas, filtros, estados, vacíos, modales y tablas cambian de composición entre módulos.
- Se combinan `Inter` para administración, `Outfit` para carta y estilos particulares para documentos. La diferenciación puede ser válida, pero no está definida como un sistema tipográfico.
- Se usan tamaños, radios, sombras y espaciados locales sin una escala común.
- Los iconos se emplean con frecuencia como decoración y a veces como portadores principales del significado, sin reglas consistentes.

**Recomendación**

Crear tokens y componentes reutilizables antes de migrar pantallas: color, tipografía, espaciado, elevación, radio, estado, foco, botón, campo, selector, tarjeta, tabla, indicador, diálogo, notificación, vacío y esqueleto de carga.

### 3. Marca blanca

**Alta prioridad**

- El nombre y logo se consultan desde configuración en algunas zonas, pero permanecen textos genéricos como `Mi Restaurante`, `Sistema de Restaurante` y mensajes comerciales fijos.
- El naranja y púrpura están escritos directamente en CSS, por lo que el cambio de marca no puede aplicarse de forma completa.
- El inicio de sesión consume una fotografía externa fija de Unsplash; esto limita control de marca, rendimiento, privacidad y funcionamiento sin conexión.
- La carta pública solo hereda nombre y logo. No recibe todavía identidad visual, datos de contacto, horarios, medios sociales, llamada a la acción o contenido editorial.
- Un único logo no cubre los contextos necesarios: horizontal, isotipo, claro, oscuro, favicon, redes, documentos y carga fallida.

**Recomendación**

Mantener `RecreoSuite` como nombre técnico no visible para el visitante y renderizar la identidad del negocio desde configuración. La aplicación debe funcionar correctamente incluso con una marca mínima y ofrecer previsualización antes de publicar cambios.

### 4. Formularios y configuración

**Alta prioridad**

- Configuración reúne identidad, región y SUNAT en un formulario largo con un único guardado. Esto aumenta el riesgo de pérdida, errores y cambios accidentales.
- Varias etiquetas no usan `for`/`id`, reduciendo navegación asistida y área clicable.
- Los mensajes de ayuda, obligatoriedad, validación y formato no siguen un patrón único.
- El cambio de ambiente SUNAT se ofrece junto a campos habituales; por su impacto necesita un flujo separado, estado destacado y confirmación explícita.
- Los campos sensibles no deberían reaparecer con valores existentes ni confundirse con campos de edición normal. En UX deben mostrarse como `Configurado`, permitir reemplazo deliberado y registrar el resultado sin revelar el secreto.
- Falta estado de guardado por sección, indicador de cambios sin guardar y confirmación de salida.

**Recomendación**

Dividir configuración en: `Negocio`, `Marca`, `Contacto y ubicación`, `Operación`, `Sitio web`, `Facturación electrónica`, `Integraciones`, `Módulos`, `Usuarios y permisos`, `Seguridad` y `Sistema`.

### 5. Accesibilidad

**Alta prioridad**

- Se detectaron imágenes funcionales o informativas sin atributo `alt` en dashboard, categorías, POS, productos, tickets y configuración.
- Algunos controles visuales carecen de nombre accesible o dependen de iconos.
- No se aprecia un enlace `Saltar al contenido`, estructura de landmarks ni gestión sistemática del foco al abrir/cerrar menú y modales.
- El estado activo y varias señales dependen del color. Deben acompañarse de texto, forma o icono con etiqueta.
- El contraste de textos secundarios y elementos con opacidades bajas debe verificarse contra WCAG 2.2 AA.
- Acciones por arrastre en el salón necesitan una alternativa mediante teclado o formulario.
- Las actualizaciones del KDS, carro, alertas y estados deberían anunciarse con regiones `aria-live` apropiadas.
- `maximum-scale=1.0, user-scalable=no` en la carta pública impide ampliar y debe eliminarse.
- Los documentos impresos requieren una estructura visual clara, pero también nombres y textos comprensibles al descargarlos.

**Criterio objetivo**

Apuntar a WCAG 2.2 nivel AA: teclado completo, foco visible, contraste suficiente, zoom al 200 %, objetivos táctiles adecuados, errores identificables y compatibilidad razonable con lector de pantalla.

### 6. Responsive y contextos de uso

**Alta prioridad**

- El layout oculta/desplaza la barra lateral bajo 991 px, pero eso no garantiza que cada flujo sea usable en teléfono o tableta.
- Hay 31 tablas en las vistas; no todas evidencian una estrategia consistente para columnas prioritarias, scroll, tarjetas móviles o acciones agrupadas.
- El POS y el KDS son aplicaciones de alta interacción que necesitan diseños específicos para tableta, pantallas táctiles y monitor de cocina, no solo una reducción del escritorio.
- Los modales con formularios amplios pueden superar la altura disponible y ocultar acciones en móviles.
- El mapa de mesas requiere escalado, paneo y objetivos táctiles amplios, además de prevención de movimientos accidentales.
- La carta es la experiencia pública más orientada a móvil, pero la navegación horizontal no informa categorías fuera de pantalla y el estado activo solo cambia al tocar, no al desplazarse por secciones.
- Los tickets, PDF y reportes deben validarse separadamente para impresión A4 y térmica; no deben depender de estilos de pantalla.

**Matriz mínima de validación**

| Contexto | Resolución de referencia | Flujos críticos |
|---|---:|---|
| Teléfono cliente | 360–430 px | Landing, carta, contacto, reserva |
| Teléfono personal | 360–430 px | Estado de pedido, reservas, delivery |
| Tableta táctil | 768–1024 px | POS, mesas, toma de pedido |
| Monitor cocina | 1280 px o más | KDS a distancia y actualización rápida |
| Escritorio administrativo | 1366 px o más | Configuración, inventario, reportes, SUNAT |
| Impresión | 80 mm / A4 | Comanda, ticket, comprobante, reporte |

### 7. Retroalimentación, estados y prevención de errores

**Prioridad media/alta**

- Debe normalizarse el lenguaje de estados: pendiente, confirmado, en preparación, listo, entregado, cobrado, anulado y error.
- Cada listado necesita estados de carga, vacío, error, sin resultados y permisos insuficientes.
- Las operaciones críticas requieren confirmación con consecuencias explícitas: cobro, cancelación, nota de crédito, cambio a producción, cierre de caja, restauración y reinicio.
- Los procesos externos o demorados, como SUNAT, no deben bloquear la pantalla ni comunicar éxito antes de obtener resultado. Se necesita una línea de estado y reintento seguro.
- En POS debe quedar siempre visible la mesa/contexto, el responsable, el total, el estado del pedido y la caja activa.
- Deben evitarse confirmaciones nativas del navegador en favor de diálogos consistentes, accesibles y específicos.

### 8. Lenguaje y contenido

**Alta prioridad**

- Los textos deben validarse en navegador y conservar UTF-8 de extremo a extremo, especialmente en configuración y facturación.
- Se mezclan términos técnicos (`KDS`, `POS`, `CDR`) con lenguaje operativo sin ayuda contextual.
- Algunos títulos describen implementación y no la tarea del usuario, por ejemplo `Centro de Mantenimiento` o `Resumen Diario` sin contexto.
- La marca pública necesitará tono configurable o plantillas de contenido; no deben insertarse mensajes publicitarios genéricos como contenido final.

**Recomendación**

Crear un glosario de producto y una guía breve de microcopy. Mostrar términos técnicos solo donde el rol los necesite y ofrecer explicación contextual.

## Sistema visual marca blanca propuesto

### Principios

1. **La marca cambia; la usabilidad no.** Los colores configurables no deben alterar jerarquía, contraste ni significado semántico.
2. **Operación rápida.** POS, cocina, caja y recepción priorizan legibilidad, objetivos táctiles y mínima cantidad de pasos.
3. **Contenido público expresivo.** Landing, carta y alojamiento pueden reflejar más personalidad sin romper componentes base.
4. **Semántica estable.** Éxito, advertencia, error, información y estados operativos usan colores reservados no reemplazables por la marca.
5. **Configuración con vista previa.** Cambios de identidad y contenido se previsualizan en escritorio y móvil antes de publicar.

### Tokens

- Marca: `brand-primary`, `brand-secondary`, `brand-accent` y variantes calculadas con contraste validado.
- Superficies: fondo, panel, tarjeta, superposición y superficie elevada.
- Texto: principal, secundario, tenue, inverso y enlace.
- Semánticos: éxito, advertencia, peligro, información y neutro.
- Estados operativos: libre, ocupada, reservada, pendiente, preparando, listo, entregado, cobrado y anulado.
- Tipografía: familia de interfaz estable; familia de títulos pública opcional y segura.
- Espaciado: escala de 4/8 px.
- Radios: pequeño, medio, grande y circular.
- Sombras: tarjeta, flotante y diálogo.
- Movimiento: rápido para respuesta, moderado para transición y opción de movimiento reducido.

### Activos de marca

- Logo horizontal claro y oscuro.
- Isotipo cuadrado.
- Favicon.
- Imagen para compartir en redes.
- Imagen de portada y fondos opcionales.
- Paleta con comprobación automática de contraste.
- Tipografía seleccionada desde un catálogo controlado o fuente local.
- Placeholder neutral cuando falten imágenes.

### Biblioteca mínima de componentes

- App shell, encabezado de página, breadcrumb y navegación contextual.
- Botones con jerarquías primaria, secundaria, silenciosa, peligrosa y enlace.
- Campos, búsqueda, selector, fecha/hora, carga de archivos y selector de imágenes.
- Tarjeta, KPI, lista, tabla adaptable y filtros.
- Badge/estado, alerta, toast y progreso.
- Modal, panel lateral y confirmación crítica.
- Vacío, error, carga/esqueleto y sin conexión.
- Tarjeta de producto, selector de cantidad y resumen de pedido.
- Tarjeta de mesa y tarjeta KDS.
- Calendario y matriz de disponibilidad para alojamiento.
- Bloques públicos para landing con variantes limitadas y coherentes.

## Especificación F2.1 — Asistente e identidad marca blanca

### Objetivo y alcance

El asistente configura una instalación nueva para un solo negocio. Debe reemplazar la identidad técnica de RecreoSuite en todos los puntos visibles sin mezclar todavía contenido de landing, configuración SUNAT ni activación avanzada de módulos.

El mismo modelo sirve después en `Administración > Negocio y marca`. El asistente es una presentación guiada de esa configuración, no una segunda fuente de datos.

**Resultado mínimo:** al finalizar, login, layout autenticado, títulos del navegador, carta, tickets y comunicaciones internas muestran el nombre, logo, colores, ubicación regional y contacto configurados.

### Cuándo aparece y cómo se retoma

- Solo para un administrador autenticado cuando `setup_status != completed`.
- Una instalación nueva redirige al asistente después del primer acceso.
- El progreso se guarda al completar cada paso; cerrar el navegador no pierde pasos confirmados.
- Se permite volver a pasos anteriores sin borrar valores posteriores.
- `Guardar y salir` devuelve a una pantalla segura con una tarea pendiente visible.
- No se debe mostrar el dashboard operativo como si estuviera listo mientras falten los campos mínimos.
- El asistente puede omitirse temporalmente solo en entorno local/demo y con aviso persistente; producción exige completarlo.
- Al terminar se registra versión del asistente, fecha y usuario, y se habilita `Editar configuración` sin volver a activar el onboarding.

### Estructura mobile-first

En teléfono se usa una sola columna, encabezado compacto, contenido desplazable y barra de acciones fija inferior. En escritorio el formulario conserva un ancho legible de 640–760 px y puede mostrar una vista previa lateral sin cambiar el orden del DOM.

```text
Encabezado: logo neutral + "Configura tu negocio"
Progreso: "Paso 2 de 5" + nombre del paso
Contenido: título, ayuda breve, campos
Resumen de errores del paso
Acciones fijas: Atrás | Guardar y continuar
```

Reglas:

- Ancho mínimo soportado: 320 px; referencia principal: 360–430 px.
- Padding lateral: 16 px en móvil, 24 px desde 768 px.
- Un solo campo por fila hasta 768 px; no colocar teléfono y correo juntos en móvil.
- Objetivos táctiles de al menos 44 × 44 px y separación mínima de 8 px.
- La barra inferior respeta `safe-area-inset-bottom`.
- El teclado virtual no debe ocultar el campo enfocado ni la explicación de error.
- En móvil la vista previa se abre en pantalla completa; en escritorio puede permanecer en un panel lateral.
- El indicador de progreso usa texto y número, no solo puntos o color.

### Pasos y campos

#### Paso 1 — Identidad del negocio

| Campo | Clave propuesta | Requisito y validación | Uso visible |
|---|---|---|---|
| Nombre comercial | `business_name` | Obligatorio, 2–120 caracteres | Login, layout, landing, carta, tickets |
| Razón social | `legal_name` | Opcional en onboarding, 2–200 | Documentos y pie legal |
| RUC | `tax_id` | Opcional en onboarding; si se informa, 11 dígitos | Documentos; no activa SUNAT |
| Descripción breve | `business_tagline` | Opcional, máximo 120 | Login y metadata pública |
| Tipo de negocio | `business_type` | Obligatorio: restaurante, recreo turístico, restaurante y alojamiento, alojamiento u otro | Valores iniciales y módulos sugeridos |

Compatibilidad: durante la transición, `business_name` debe alimentar o sustituir de forma controlada al actual `company_name`; no deben coexistir dos nombres editables sin una regla clara.

#### Paso 2 — Marca

| Campo | Clave propuesta | Requisito y validación | Comportamiento |
|---|---|---|---|
| Logo principal | `brand_logo` | PNG, WebP o SVG saneado; máximo 2 MB | Vista previa, recorte no destructivo |
| Isotipo | `brand_icon` | Opcional, cuadrado recomendado | Sidebar compacto, avatar de marca |
| Favicon | `brand_favicon` | Opcional; PNG/ICO/SVG saneado | Navegador |
| Color principal | `brand_primary` | Obligatorio, hexadecimal válido | CTA y elementos de marca |
| Color secundario | `brand_secondary` | Opcional | Superficies/acentos controlados |
| Color de acento | `brand_accent` | Opcional | Destacados no semánticos |
| Estilo visual | `brand_preset` | Natural, cálido, elegante, tropical o neutro | Define valores iniciales; no bloquea edición |

Comportamiento requerido:

- Si no hay logo, generar un monograma temporal con el nombre comercial; nunca mostrar `Mi Restaurante`.
- Mostrar fondo claro y oscuro para validar el logo.
- El recorte conserva el archivo original y genera variantes; no sobrescribe el recurso fuente.
- Calcular contraste del color principal con texto blanco y oscuro. Si ninguno cumple 4.5:1, bloquear continuación y sugerir un tono accesible.
- Colores de éxito, advertencia, peligro, información y estados operativos no son editables por marca.
- La tipografía no se configura en la primera versión del asistente. Se usa la familia de interfaz del sistema; una selección controlada puede incorporarse después.

#### Paso 3 — Contacto y ubicación

| Campo | Clave propuesta | Requisito y validación |
|---|---|---|
| Teléfono | `contact_phone` | Opcional, formato internacional flexible, máximo 30 |
| WhatsApp | `contact_whatsapp` | Opcional, código de país normalizado |
| Correo público | `contact_email` | Opcional, email válido |
| Dirección | `address_line` | Obligatoria para negocio físico, máximo 250 |
| Departamento | `address_department` | Obligatorio para Perú |
| Provincia | `address_province` | Obligatorio para Perú |
| Distrito | `address_district` | Obligatorio para Perú |
| Ubigeo | `address_ubigeo` | Derivado de selección; editable solo en modo avanzado |
| Referencia | `address_reference` | Opcional, máximo 180 |
| Coordenadas | `latitude`, `longitude` | Opcionales; selector de mapa futuro, entrada manual validada |

- No obligar a duplicar estos datos en SUNAT. Facturación puede ofrecer `Copiar datos del negocio` y mantener su dirección fiscal independiente.
- Los enlaces de teléfono, correo y WhatsApp de la futura landing se generan a partir de estos valores normalizados.

#### Paso 4 — Región y operación básica

| Campo | Clave propuesta | Valor inicial | Requisito |
|---|---|---|---|
| País | `country_code` | `PE` | Obligatorio, ISO 3166-1 alfa-2 |
| Idioma | `locale` | `es-PE` | Obligatorio |
| Zona horaria | `timezone` | `America/Lima` | Obligatorio, zona IANA |
| Moneda | `currency_code` | `PEN` | Obligatorio, ISO 4217 |
| Símbolo mostrado | derivado | `S/` | No almacenar como fuente primaria |
| Formato de fecha | `date_format` | `dd/MM/yyyy` | Selección controlada |
| Inicio de semana | `week_starts_on` | lunes | Selección controlada |
| Mensaje de ticket | `ticket_footer` | Texto neutral | Opcional, máximo 280 |

Debe mostrarse una vista previa de fecha, hora y monto. El sistema actual almacena `currency_symbol`; la evolución debe guardar `currency_code` y derivar símbolo/formato según locale para evitar ambigüedad.

#### Paso 5 — Revisión y aplicación

- Resumen de identidad, marca, contacto y región con enlaces `Editar` por sección.
- Previsualizaciones: login móvil, encabezado del dashboard, carta/ticket simplificado.
- Lista explícita de valores pendientes opcionales.
- Casilla obligatoria: `Confirmo que estos datos identifican al negocio`.
- Acción primaria: `Aplicar identidad`.
- Al aplicar, mostrar progreso breve y resultado. No usar una pantalla en blanco ni recargar sin retroalimentación.
- Si falla una parte, conservar todos los pasos y explicar qué no pudo aplicarse.
- Éxito: mostrar nombre y logo ya aplicados, con acciones `Ir al panel` y `Seguir configurando`.

### Elementos fuera del asistente inicial

Para limitar riesgo y carga cognitiva, estos elementos se configuran después:

- Credenciales, certificado, ambiente, series y datos fiscales SUNAT detallados.
- Redes sociales, horarios complejos y enlaces legales.
- SEO, landing, galerías y contenido editorial.
- Activación definitiva de pagos, mensajería u otras integraciones.
- Roles, permisos y alta de personal.
- Configuración detallada de restaurante y alojamiento.

El tipo de negocio solo propone módulos; no debe activar integraciones externas ni emitir documentos.

### Configuración posterior en dashboard

La ruta conceptual `Administración > Negocio y marca` se divide en pestañas o subrutas reales, no en un formulario único:

1. **Identidad:** nombre comercial, razón social, RUC, tipo y descripción.
2. **Marca:** activos, colores y previsualización.
3. **Contacto y ubicación:** datos públicos y dirección.
4. **Región y formatos:** país, idioma, zona horaria, moneda, fecha y semana.
5. **Documentos:** pie de ticket y previsualización; sin credenciales SUNAT.

Cada sección guarda independientemente, informa cambios sin guardar y muestra fecha/usuario de la última modificación. Facturación electrónica permanece como destino independiente en `Finanzas` o `Administración > Integraciones`.

### Estados y validación

- Validar al salir del campo solo cuando haya interacción previa; validar todo el paso al continuar.
- Mostrar un resumen de errores al inicio del contenido, mover foco al resumen y enlazar cada error al campo.
- Conservar el valor inválido para que pueda corregirse.
- Marcar obligatoriedad en la etiqueta y explicar `Opcional` donde aplique.
- No depender únicamente de asteriscos.
- Indicar carga, procesamiento, éxito y error de archivos.
- Detectar cambios sin guardar y confirmar antes de abandonar.
- Evitar dobles envíos deshabilitando temporalmente la acción y mostrando progreso, sin quitar su etiqueta.
- Errores del servidor deben expresarse en lenguaje accionable y contener un identificador técnico copiable solo cuando ayude a soporte.

### Accesibilidad específica

- Estructura con `header`, `main`, `nav` para progreso y `footer` para acciones.
- Un `h1` por paso; pasos y secciones mantienen jerarquía de encabezados.
- Todos los campos tienen `label` asociado mediante `for`/`id`; ayudas y errores se conectan con `aria-describedby`.
- `aria-current="step"` identifica el paso actual.
- Cambios de paso enfocan el `h1`; errores enfocan primero el resumen.
- Cargas y guardados se anuncian en una región `aria-live="polite"`; fallos críticos usan `role="alert"`.
- Selector de color incluye entrada textual hexadecimal y no exige precisión con puntero.
- Carga/recorte de imagen tiene alternativa sin arrastre y permite eliminar/reemplazar.
- Vista previa no entra repetidamente en el orden de tabulación si es meramente ilustrativa.
- Contraste mínimo 4.5:1 para texto normal, 3:1 para texto grande y componentes; foco visible con relación mínima 3:1.
- Respeta `prefers-reduced-motion`; ninguna transición es necesaria para comprender el cambio.
- No bloquear zoom ni orientación de pantalla.

### Tokens de interfaz para F2.1

Los tokens de aplicación son estables; los de marca aceptan valores configurados dentro de límites de contraste.

```css
:root {
  /* Marca configurable */
  --brand-primary: #B45309;
  --brand-on-primary: #FFFFFF;
  --brand-secondary: #3F3F46;
  --brand-accent: #15803D;

  /* Semánticos no configurables */
  --color-success: #15803D;
  --color-warning: #A16207;
  --color-danger: #B91C1C;
  --color-info: #0369A1;

  /* Superficie y texto */
  --surface-page: #F8FAFC;
  --surface-card: #FFFFFF;
  --surface-muted: #F1F5F9;
  --text-primary: #0F172A;
  --text-secondary: #475569;
  --border-default: #CBD5E1;
  --focus-ring: #2563EB;

  /* Escala */
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  --radius-sm: 0.5rem;
  --radius-md: 0.75rem;
  --radius-lg: 1rem;
  --control-height: 2.75rem;
  --content-form: 47.5rem;
}
```

Reglas de aplicación:

- `brand-primary` no sustituye enlaces, foco o semánticos si pierde claridad.
- `brand-on-primary` se calcula entre blanco/oscuro y se almacena como resultado derivado.
- Los presets solo escriben tokens de marca; no agregan CSS específico por cliente.
- Login y sitio público pueden usar una composición más expresiva, pero consumen los mismos activos y tokens.
- Layout administrativo limita la marca a logo, nombre y acentos; conserva superficies neutras para legibilidad prolongada.

### Relación con login y layout actuales

- Sustituir las consultas directas a `Setting` en Blade por un objeto de identidad compartido y cacheable.
- El `<title>` debe seguir el patrón `{Página} — {Nombre comercial}`; nunca mostrar `Laravel` o `Mi Restaurante` después del onboarding.
- El login usa logo/monograma, nombre y descripción configurados. Su imagen hero debe ser un activo local configurable con fallback neutral, no una URL externa fija.
- En móvil, el login prioriza formulario y marca; la imagen hero se reduce o elimina sin perder información.
- El sidebar muestra logo/isotipo y nombre con truncamiento accesible (`title` o nombre completo disponible para tecnología asistiva).
- El tema de administración no debe convertirse enteramente al color de marca: navegación activa y CTA sí; estados, fondos y texto conservan tokens del sistema.

### Criterios de aceptación F2.1

1. Una instalación vacía completa el asistente a 320 px sin desplazamiento horizontal.
2. El progreso sobrevive a cierre de sesión y reapertura sin duplicar datos.
3. Se puede completar todo con teclado y lector de pantalla, con foco predecible.
4. Colores de marca con contraste insuficiente no pueden publicarse sin corrección.
5. Un logo ausente produce un monograma neutral; nunca aparece una marca de demostración.
6. Nombre, logo y color se reflejan en login, layout, título, carta y documento de prueba tras aplicar.
7. Zona horaria, moneda y formatos muestran una vista previa antes de guardar.
8. Cambios parciales fallidos no borran pasos ya guardados.
9. Datos SUNAT y secretos no aparecen ni se solicitan en el asistente.
10. La edición posterior guarda por sección y advierte cambios sin guardar.
11. No se carga ninguna imagen, fuente o recurso de marca desde un tercero por defecto.
12. Las pruebas cubren al menos 360, 768 y 1366 px, zoom 200 %, teclado y contraste WCAG 2.2 AA.

## Auditoría F2.5 — Accesibilidad WCAG 2.2 AA y responsive

### Método y alcance

Revisión estática de los componentes base y vistas Blade actuales, con énfasis en layout, login, formularios, tablas, modales, POS y navegación móvil. Esta auditoría identifica evidencia en código; no equivale todavía a una conformidad WCAG certificada. El cierre exige pruebas en navegador con teclado, zoom, lector de pantalla, contraste calculado y tamaños reales de viewport.

Fecha de corte: 16 de octubre de 2026.

### Resultado ejecutivo

**Estado F2.5: no cumple todavía WCAG 2.2 AA.** Hay avances puntuales —campos del login asociados correctamente, `table-responsive` en varias bandejas, modales Bootstrap y algunos componentes recientes con objetivos de 48 px—, pero los componentes base no garantizan foco, nombre accesible, tamaño táctil, contraste ni comportamiento móvil de forma transversal.

La deuda es sistémica, no solo de páginas aisladas:

- Se detectaron aproximadamente 110 líneas con elementos `label` sin atributo `for` en las vistas de aplicación.
- Se detectaron 22 botones de cierre de alerta/modal sin `aria-label`.
- Hay 31 apariciones de tablas y solo 16 envoltorios `table-responsive`; la relación no es exacta uno a uno, pero evidencia cobertura incompleta.
- Existen imágenes de contenido sin alternativa textual en dashboard, categorías, POS, productos y documentos.
- Los tamaños base actuales producen controles frecuentes menores al objetivo táctil definido de 48 × 48 px.
- La navegación móvil es un panel visual, pero no implementa todavía el patrón accesible de diálogo/navegación con control de foco.

### Matriz WCAG 2.2 AA

| Área / criterio | Estado | Evidencia actual | Brecha para cierre |
|---|---|---|---|
| 1.1.1 Contenido no textual | No cumple | Algunas imágenes de producto ya usan `alt` con el nombre; otras imágenes en categorías, inventario, dashboard y tickets no presentan `alt` en la etiqueta | Toda imagen debe tener alternativa útil o `alt=""` si es decorativa; validar PDF/ticket por separado |
| 1.3.1 Información y relaciones | No cumple | Muchos formularios usan `<label>` visual sin `for`; tablas dependen de apariencia y no muestran sistemáticamente `caption`, alcance o encabezados asociados | Asociar etiquetas/campos, agregar semántica de tabla y relaciones de ayuda/error |
| 1.3.2 Secuencia significativa | Parcial | El layout mantiene sidebar antes del contenido y los modales Bootstrap conservan DOM razonable | Verificar orden al reordenar visualmente POS, tablas móviles y previews; añadir salto al contenido |
| 1.4.3 Contraste mínimo | Sin verificar / riesgo alto | Sidebar usa texto blanco con opacidad `0.70` y categorías `0.35` sobre color de marca configurable; textos secundarios y botones outline usan tonos fijos | Medir todas las combinaciones; impedir publicar temas que fallen 4.5:1 y 3:1 según el caso |
| 1.4.10 Reflow | No cumple | Layout general cambia a sidebar oculto bajo 991 px, pero POS fija altura/overflow, mapa y tablas densas dependen de geometría de escritorio | Probar a 320 CSS px y zoom 400 %; sin scroll bidimensional salvo contenido que lo requiera |
| 1.4.11 Contraste no textual | Sin verificar | Bordes de campos `#e8e4f4`, estados y controles outline son tenues sobre blanco; foco usa mezcla naranja translúcida | Garantizar 3:1 en foco, bordes necesarios, iconos y estados |
| 1.4.13 Contenido al hover/foco | Parcial | Dropdown Bootstrap gestiona interacción básica; no hay inventario de tooltips/elementos solo hover | Contenido emergente debe ser descartable, persistente y alcanzable por hover/foco |
| 2.1.1 Teclado | No cumple | Overlay y apertura/cierre del sidebar usan `onclick`; acciones POS usan handlers inline; mapa por arrastre necesita alternativa | Toda acción debe operar por teclado y con elemento interactivo nativo |
| 2.1.2 Sin trampa de teclado | Parcial | Bootstrap aporta control de foco en modales | Probar todos los modales y el sidebar; el sidebar móvil hoy no confina ni restaura foco |
| 2.4.1 Evitar bloques | No cumple | No se encontró enlace `Saltar al contenido`; sidebar extensa precede al contenido | Añadir skip link visible al foco hacia `<main id="main-content">` |
| 2.4.3 Orden del foco | No cumple | Apertura/cierre del sidebar no mueve ni restaura foco; eliminación automática de alertas puede alterar contexto | Definir ciclo y retorno de foco en sidebar, modales, errores y contenido dinámico |
| 2.4.7 Foco visible | Parcial / no transversal | Inputs tienen una sombra de foco; búsqueda elimina `outline`; no hay regla global `:focus-visible` para enlaces, botones e iconos | Crear anillo global con 3:1, offset y prueba en todos los temas |
| 2.4.11 Foco no oculto (mínimo) | Sin verificar | Barra/viewport de POS y futuros footers fijos pueden cubrir controles | Probar tabulación con barras fijas, teclado móvil y modales |
| 2.5.3 Etiqueta en el nombre | Riesgo | Varios controles solo icono dependen de `title` o carecen de nombre; otros sí tienen texto visible | El nombre accesible debe incluir la etiqueta visible y existir en controles solo icono |
| 2.5.7 Movimientos de arrastre | No cumple | El diseño de salón contempla posiciones mediante arrastre | Ofrecer mover mesa mediante teclado/formulario o controles direccionales equivalentes |
| 2.5.8 Tamaño del objetivo (mínimo) | No cumple | `.btn` usa padding vertical `0.55rem`; navegación `11px 14px`; cerrar sidebar usa `p-0`; controles +/- del carrito usan `py-0 px-1` | Mínimo WCAG 24 × 24; criterio interno de RecreoSuite: 48 × 48 en acciones táctiles y 44 × 44 como mínimo excepcional |
| 3.2.1 Al recibir foco | Parcial | No se observaron cambios generales por foco | Verificar selects, búsqueda, barcode/autofocus y componentes dinámicos |
| 3.3.1 Identificación de errores | Parcial | Laravel muestra errores en ciertos formularios; patrón no es uniforme | Resumen enlazado, error junto al campo y conservación del valor en todos los formularios |
| 3.3.2 Etiquetas o instrucciones | No cumple | Login sí asocia `for/id`; gran cantidad de formularios internos solo tiene label visual | Normalizar labels, obligatoriedad, ayuda y formatos esperados |
| 3.3.7 Entrada redundante | Sin verificar | Contacto, dirección y datos fiscales se solicitan en áreas diferentes | Permitir copiar/reutilizar datos ya capturados y no pedirlos de nuevo innecesariamente |
| 3.3.8 Autenticación accesible | Parcial | Login permite pegar credenciales y no evidencia CAPTCHA/prueba cognitiva | Mantener pegado/autocompletado, añadir `autocomplete` apropiado y probar gestores de contraseña |
| 4.1.2 Nombre, función y valor | No cumple | Sidebar usa `div`; control de usuario es `div` interactivo; botones de menú/cierre sin nombre; modales carecen a menudo de `aria-labelledby` | Usar elementos nativos y estados `aria-expanded`, `aria-controls`, nombres y relaciones |
| 4.1.3 Mensajes de estado | No cumple | Alertas se eliminan automáticamente; no todas usan `role=status/alert` o región viva | Anunciar éxito/error sin mover foco y permitir tiempo suficiente o cierre manual |

### Evidencia por componente

#### Foco

- `resources/views/layouts/app.blade.php` define foco solo para `.form-control` y la búsqueda. No existe una política global `:focus-visible` para enlaces, botones, dropdowns, tarjetas interactivas y controles de icono.
- La búsqueda declara `outline: none`; la sombra alternativa debe medirse y mantenerse en alto contraste.
- `openMenu()` y `closeMenu()` solo cambian clases y `overflow`; no enfocan el sidebar, no confinan el foco y no lo devuelven al botón disparador.
- Los modales se benefician del JavaScript de Bootstrap, pero requieren prueba individual, título asociado y restauración de foco.
- El POS fuerza foco al código de barras y a notas en varios momentos. Debe comprobarse que no robe foco mientras el usuario interactúa con otro control.

**Cierre:** existe un anillo `:focus-visible` común, todas las acciones son visibles al tabular, el foco nunca queda detrás de overlays y abrir/cerrar superficies restaura el contexto.

#### Labels, nombres y mensajes

- Login es el mejor patrón actual: `label for="email/password"` coincide con `id`.
- En filtros de facturación, caja, clientes, configuración, reservas y perfil se usan labels visuales sin asociación programática.
- El botón de usuario de la barra superior es un `div` con comportamiento de botón; no expone nombre/rol/estado completo.
- Botones de hamburguesa, cerrar sidebar y múltiples `btn-close` carecen de nombre accesible.
- Iconos decorativos no siempre tienen `aria-hidden="true"`.
- Alertas globales desaparecen automáticamente a los cinco segundos, incluso si el usuario necesita más tiempo para leerlas.

**Cierre:** 100 % de controles tienen nombre accesible; labels y errores están asociados; iconos decorativos se ocultan; mensajes de estado son anunciados y no desaparecen sin mecanismo razonable.

#### Targets táctiles

- El padding base de `.btn` no asegura 48 px de altura.
- Los enlaces de sidebar rondan aproximadamente 42 px según fuente y line-height.
- El botón de cerrar sidebar usa `p-0` y los botones +/- del carro `py-0 px-1`, claramente por debajo del objetivo interno.
- Botones de acción en tablas con solo icono tienden a ser compactos y aparecen agrupados.
- El módulo de módulos ya adopta `min-height: 48px` en acciones; debe convertirse en patrón común.

**Cierre:** acciones primarias, navegación, controles de cantidad, cerrar, menú y acciones de tabla alcanzan 48 × 48 px en contextos táctiles; excepciones nunca bajan de WCAG 24 × 24 y mantienen separación suficiente.

#### Contraste y uso del color

- Los colores de marca alimentan sidebar y CTA. La legibilidad cambia con valores configurables, por lo que una paleta válida por defecto no garantiza una instalación válida.
- Texto del sidebar con opacidad 0.70 y encabezados con 0.35 son riesgos claros, en especial sobre tonos claros.
- `text-muted`, placeholders, bordes de campos, botones outline y badges necesitan medición.
- Los estados de mesa, cocina, comprobante y delivery no deben comunicarse solo por color.

**Cierre:** matriz automatizada/manual de contraste para cada token y combinación; texto normal ≥ 4.5:1, texto grande y componentes ≥ 3:1; todos los estados incorporan texto/icono/forma además de color.

#### Tablas

- `table-responsive` existe en varias bandejas, pero no constituye por sí solo una experiencia móvil ni accesible.
- Acciones con iconos y títulos abreviados complican nombre accesible y uso táctil.
- Faltan consistentemente `caption` o nombre accesible, alcance de encabezados y estrategia para tablas largas.
- El scroll horizontal puede ocultar identificación de fila y columna; las acciones pueden quedar fuera de viewport.

**Cierre:** cada tabla tiene nombre/contexto, encabezados programáticos y acciones nombradas. A 320–430 px adopta una de tres estrategias documentadas: columnas prioritarias con scroll, tarjetas semánticas o vista de detalle; nunca se reduce texto hasta hacerlo ilegible.

#### Modales

- Se localizaron 18 declaraciones de modal. Bootstrap resuelve parte del foco, Escape y backdrop, pero 22 cierres de modal/alerta no tienen etiqueta accesible.
- Varios modales no declaran `aria-labelledby` enlazado al título.
- Formularios largos de cobro/edición pueden superar la altura móvil; las acciones inferiores pueden quedar fuera de vista.
- No hay política uniforme de foco inicial, error dentro de modal o confirmación antes de cerrar con cambios.

**Cierre:** título asociado, descripción cuando sea necesaria, cierre etiquetado, foco inicial lógico, scroll interno, footer visible sin tapar contenido, retorno al disparador y prueba a 320 px/200 % zoom.

#### Navegación móvil

- A menos de 991 px el sidebar se desplaza fuera de pantalla y se abre con overlay; es un buen punto de partida visual.
- El contenedor es `div`, no `nav` ni diálogo, y carece de etiqueta accesible.
- El botón de apertura no expone `aria-expanded`/`aria-controls` y solo contiene icono.
- El overlay clicable es un `div` inaccesible por teclado.
- No se cierra explícitamente con Escape, no confina foco y no restaura el foco al disparador.
- La topbar combina título, búsqueda y usuario; a 320 px puede envolver y consumir una fracción grande del primer viewport.

**Cierre:** patrón off-canvas accesible con botón nativo nombrado, `aria-expanded`, `aria-controls`, Escape, foco inicial/retorno, fondo inerte y landmark `nav`. A 320 px no existe solapamiento ni scroll horizontal.

### Criterios responsive de cierre

Probar cada componente base, no solo páginas completas:

| Viewport / modo | Criterio obligatorio |
|---|---|
| 320 × 568 | Sin scroll horizontal de página; títulos, formularios, modales y navegación utilizables |
| 360 × 800 | Targets táctiles, teclado virtual y acciones fijas no ocultan campos/errores |
| 768 × 1024 | POS, mesas y formularios aprovechan tableta sin depender de hover |
| 1024 × 768 | Orientación horizontal mantiene navegación, modales y acciones críticas visibles |
| 1366 × 768 | Tablas y panel administrativo conservan jerarquía y densidad legible |
| Zoom 200 % | Contenido y acciones siguen disponibles sin superposición |
| Reflow equivalente a 320 CSS px | Cumple 1.4.10; excepción solo para mapas/tablas justificadas con alternativa |
| Texto espaciado WCAG | No hay recorte con line-height 1.5, párrafos +2, letras +0.12 y palabras +0.16 |
| Movimiento reducido | Transiciones no son necesarias y respetan `prefers-reduced-motion` |

### Puerta de calidad F2.5

F2.5 puede marcarse cerrada únicamente cuando:

1. No existen controles sin nombre accesible en una exploración automatizada y manual.
2. No existen labels visibles sin asociación programática salvo etiquetas de grupo correctamente implementadas con `fieldset/legend` o equivalente.
3. Todos los flujos base se completan solo con teclado: login, menú, perfil, configuración, tabla, modal, POS y cierre de sesión.
4. Hay foco visible consistente y ningún foco queda oculto por sidebar, modal o barra fija.
5. Navegación móvil implementa estado, Escape, confinamiento/fondo inerte y retorno de foco.
6. Acciones táctiles críticas cumplen 48 × 48 px; ninguna acción baja del mínimo WCAG 24 × 24.
7. Todas las combinaciones de tokens pasan contraste AA y el selector de marca impide combinaciones inválidas.
8. Tablas y modales documentan y pasan su estrategia responsive a 320 px y zoom 200 %.
9. Mensajes dinámicos se anuncian; alertas importantes no desaparecen automáticamente antes de poder leerse.
10. Las pruebas manuales incluyen Chrome/Edge con teclado, Android táctil, iOS/Safari o equivalente, NVDA + Firefox/Chrome y VoiceOver + Safari.
11. Una herramienta automática (axe, Lighthouse o equivalente) reporta cero violaciones críticas/serias en los componentes base; esto complementa, no sustituye, la revisión manual.
12. Se conserva evidencia: capturas por viewport, reporte de contraste, recorrido de teclado y lista de incidencias resueltas o aceptadas con justificación.

### Orden recomendado de corrección

1. Semántica del app shell: `main`, `nav`, skip link, botones reales y nombres accesibles.
2. Regla global de foco y targets; aplicar primero a navegación, cierres y acciones POS.
3. Patrón compartido de campo con label, ayuda, error y obligatoriedad.
4. Off-canvas móvil accesible y modales normalizados.
5. Tabla responsive/accesible compartida y migración por bandejas.
6. Validación de contraste para tokens de marca y estados operativos.
7. Pruebas de reflow/zoom, lector de pantalla y flujos completos antes de declarar conformidad.

## Especificación F3.2 — Áreas, mesas y asignación de mozos

### Alcance auditado

La implementación actual ofrece dos experiencias relacionadas pero separadas:

- `resources/views/tables/index.blade.php`: administración visual del salón, creación/eliminación de zonas y mesas, arrastre y guardado de posiciones.
- `resources/views/pos/index.blade.php`: selección operativa de mesa con consumo y reservas del día.

La lógica reside principalmente en `app/Http/Controllers/TableController.php`, `app/Models/Area.php`, `app/Models/Table.php` y sus rutas en `routes/web.php`. El personal se gestiona mediante `app/Models/Employee.php`, `app/Models/User.php`, `app/Http/Controllers/EmployeeController.php` y `resources/views/employees/index.blade.php`.

No existe actualmente una relación persistente mesa–mozo. El pedido registra al usuario que lo creó, pero eso no representa una asignación operativa previa o vigente de mesas.

### Hallazgos verificables del estado actual

1. **El editor depende exclusivamente de ratón.** Solo registra `mousedown`, `mousemove` y `mouseup`; no admite touch/pointer events, teclado ni alternativa mediante formulario. Incumple el criterio WCAG 2.2 sobre movimientos de arrastre.
2. **El lienzo tiene geometría fija.** Usa 600 px de alto y mesas de 100 × 100 px con coordenadas absolutas. En POS el lienzo sube a 650 px y las tarjetas a 110 × 110 px. No existe adaptación por viewport, zoom del plano ni transformación coherente de coordenadas.
3. **El límite de arrastre es incompleto.** Solo impide valores negativos; no limita borde derecho/inferior, solapamiento entre mesas ni pérdida visual al cambiar de resolución.
4. **Se guardan todas las mesas de todas las áreas.** `savePositions()` recopila cada `.draggable-table`, no solo el área activa, y no comunica cambios pendientes por área.
5. **No hay gestión completa de mesa.** El alta solicita solo nombre y zona aunque la base contiene `seats`; no hay edición de nombre, capacidad, forma, tamaño, estado administrativo o cambio de área.
6. **La capacidad se pierde en creación masiva normal.** `seats` existe en la migración, pero no está en `$fillable` de `Table` ni en el formulario/controlador actual; los seeders que lo envían mediante `create()` no lo persisten por asignación masiva.
7. **El modelo declara `width` y `height` como fillable sin columnas visibles en las migraciones revisadas.** La UI tampoco usa dimensiones persistidas.
8. **Eliminar un área borra sus mesas directamente.** La confirmación nativa menciona la consecuencia, pero no presenta cantidad de mesas, pedidos/reservas afectados, alternativa de traslado ni bloqueo seguro.
9. **Estado inconsistente entre administración y operación.** El editor usa `table.status`; POS deriva ocupado de pedidos y reservado de reservas. Esto puede mostrar señales distintas para la misma mesa.
10. **Los estados dependen en parte del color.** Verde/rojo/amarillo se acompañan de algunos textos en POS, pero el editor usa principalmente icono coloreado y no muestra leyenda ni estado textual.
11. **Pestañas incompletas semánticamente.** Tienen `role=tab`, pero faltan relaciones completas `aria-controls`/`aria-labelledby`; en móvil pueden desbordarse sin patrón claro.
12. **Controles sin nombre/tamaño suficiente.** Cerrar modales y borrar mesa carecen de etiqueta accesible; el borrado es pequeño, de baja opacidad y difícil de tocar.
13. **Retroalimentación bloqueante y poco accesible.** Guardar usa `alert()` y errores técnicos; no existe región de estado, confirmación persistente ni recuperación parcial.
14. **La selección POS replica el plano.** Un plano grande con coordenadas absolutas puede ser útil en tableta/escritorio, pero no ofrece lista o búsqueda alternativa para teléfono.
15. **No hay asignación de mozos.** No se pueden asignar por mesa, área, turno ni franja; tampoco se visualiza cobertura o mesas sin responsable.

### Modelo de interacción propuesto

Separar explícitamente dos modos para reducir errores:

1. **Operar salón:** vista diaria para mozos/caja; abrir mesa, ver estado, reserva y responsable. No permite mover o borrar elementos.
2. **Diseñar salón:** vista administrativa; crear, editar, ordenar, mover y retirar áreas/mesas. Requiere entrar deliberadamente y muestra cambios sin guardar.

La asignación de mozos pertenece a la operación/planificación del turno, no al diseño físico permanente. El diseño puede guardar una asignación predeterminada opcional, pero la cobertura vigente debe tener fecha/turno y auditoría.

### Arquitectura de información

```text
Restaurante > Salón y mesas
├── Operación de hoy
│   ├── Todas las áreas
│   ├── Vista mapa / vista lista
│   ├── Filtros: estado, mozo, capacidad
│   └── Cobertura de mozos
├── Diseño del salón
│   ├── Áreas
│   ├── Mesas
│   ├── Modo ordenar
│   └── Cambios pendientes / publicar diseño
└── Asignaciones
    ├── Por turno
    ├── Por área o mesas específicas
    ├── Mesas sin cubrir
    └── Historial
```

### Experiencia mobile-first

#### Pantalla inicial: operación de hoy

En 320–767 px la vista predeterminada es una lista de tarjetas, no el plano absoluto:

- Selector de área horizontal con scroll y nombre accesible.
- Resumen: `12 mesas · 3 ocupadas · 1 reservada · 2 sin mozo`.
- Filtros en panel inferior: estado, capacidad y mozo.
- Tarjeta de mesa de ancho completo con nombre, área, capacidad, estado textual, consumo/reserva y mozo asignado.
- Acción primaria contextual: `Abrir`, `Ver pedido` o `Ver reserva`.
- Alternador `Lista / Mapa`; recordar preferencia por dispositivo.

En tableta/escritorio el mapa puede ser predeterminado. Debe incluir zoom, ajuste a pantalla, paneo, leyenda y panel de detalle. La lista permanece siempre disponible como alternativa accesible.

#### Administración de áreas

- Listado reordenable con nombre, número de mesas, estado y acción `Editar`.
- Crear/editar en panel o modal con campos asociados:
  - Nombre, obligatorio, 2–80 caracteres y único sin distinguir mayúsculas.
  - Descripción/ubicación, opcional, máximo 160.
  - Estado: activa/inactiva.
  - Orden de presentación, preferentemente mediante botones `Subir/Bajar` además de drag.
- Área vacía ofrece `Crear primera mesa`.
- Desactivar conserva historial y oculta el área de la operación nueva.
- Eliminar solo si está vacía y sin dependencias; si contiene mesas, ofrecer `Mover mesas` o `Desactivar`.

#### Administración de mesas

- Crear/editar con:
  - Nombre/código visible, obligatorio, 1–40 y único dentro del área.
  - Área, obligatoria.
  - Capacidad, obligatoria, entero 1–99.
  - Forma: cuadrada, redonda, rectangular u otra.
  - Tamaño visual: pequeño, mediano, grande; no píxeles libres para el usuario.
  - Estado administrativo: activa, inactiva o fuera de servicio.
  - Identificador QR, solo lectura cuando exista.
  - Asignación predeterminada opcional, separada de la asignación del turno.
- Editar desde tarjeta/panel; no ocultar acciones detrás de hover.
- Duplicar mesa permite crear series (`Mesa 1` a `Mesa 10`) con vista previa y validación de duplicados.
- Mover de área conserva historial y valida pedidos/reservas activas.
- Eliminar se sustituye preferentemente por retirar/desactivar; eliminación física solo sin historial y con permiso elevado.

#### Modo ordenar el plano

- Entrada explícita mediante `Editar distribución`; el resto de acciones queda deshabilitado mientras se mueve mobiliario.
- Implementar Pointer Events para ratón/touch/lápiz, pero mantener alternativa de teclado/formulario.
- Mesa seleccionada muestra controles `↑ ↓ ← →`, incremento configurable y campos X/Y avanzados.
- Flechas de teclado mueven; Shift + flecha usa incremento mayor. Anunciar posición mediante región viva sin saturar.
- Límites completos, cuadrícula, ajuste opcional, detección visual de solapamientos y acción `Deshacer`.
- Guardar solo el área activa y mostrar `3 cambios sin guardar`.
- Acciones persistentes: `Cancelar cambios` y `Guardar distribución`.
- Confirmar navegación si hay cambios; no usar `alert()` nativo.
- Coordenadas deben almacenarse normalizadas respecto al lienzo o migrarse de forma segura para responder a tamaños distintos.

### Asignación de mozos

#### Regla de datos y negocio

La asignación debe relacionar una mesa con un empleado activo cuyo cargo sea `waiter`, y opcionalmente con su cuenta `User`. No debe asignarse directamente por rol de usuario porque un empleado puede existir sin acceso y la ficha laboral ya es el origen para personal/turnos.

Entidad conceptual propuesta:

```text
table_assignments
- id
- table_id
- employee_id
- work_shift_id nullable
- starts_at
- ends_at nullable
- status: scheduled | active | ended | cancelled
- assigned_by
- notes nullable
- timestamps
```

Reglas:

- Solo personal activo con cargo mozo aparece como elegible.
- Advertir si el mozo no tiene turno activo; permitir únicamente a un administrador con motivo.
- Una mesa tiene como máximo una asignación activa principal.
- Un mozo puede cubrir varias mesas/áreas.
- Asignar un área crea asignaciones por mesa y muestra conflictos antes de confirmar.
- Cambiar responsable no cambia la autoría histórica de pedidos ya creados.
- Si un pedido requiere que el mozo asignado sea su propietario, esa transición debe ser una acción explícita y auditada.
- Finalizar turno cierra o solicita reasignar cobertura activa; nunca deja asignaciones silenciosamente vigentes.

#### Flujo mobile-first de asignación

1. Entrar en `Cobertura de mozos` desde operación de hoy.
2. Ver chips/resumen: mozos en turno, mesas cubiertas y mesas sin asignar.
3. Elegir `Asignar por área` o seleccionar mesas mediante casillas de 48 px.
4. Abrir panel inferior con lista de mozos: nombre, carga actual, horario de turno y disponibilidad.
5. Seleccionar mozo; mostrar resumen `Juan cubrirá 6 mesas en Terraza` y conflictos.
6. Confirmar; anunciar éxito y actualizar responsable sin perder filtros/scroll.

La tarjeta de mesa muestra avatar/inicial y nombre corto del responsable. Estado `Sin mozo` usa texto e icono, no solo color. Un filtro permite ver únicamente cobertura incompleta.

### Accesibilidad y responsive específicos

- Áreas usan tabs completas con flechas de teclado y relaciones ARIA, o un `<select>`/lista equivalente en móvil.
- Mesa operativa es un enlace o botón con nombre completo: `Mesa 4, Terraza, ocupada, atendida por Ana`.
- Todos los botones solo icono incluyen nombre accesible y tooltip no esencial.
- Targets táctiles: 48 × 48 px para mesas, seleccionar, mover, editar y retirar.
- Estados libre/ocupada/reservada/fuera de servicio/sin mozo combinan texto, icono y color con contraste AA.
- El mapa tiene una alternativa de lista funcionalmente equivalente.
- Arrastre tiene alternativa sin drag conforme a WCAG 2.5.7.
- Zoom del navegador al 200 % y reflow a 320 CSS px no ocultan acciones.
- Paneles/modales enfocan título al abrir, confinan foco y lo devuelven al disparador.
- Guardado y errores usan `role=status`/`alert` según corresponda y no desaparecen automáticamente.
- `prefers-reduced-motion` elimina animaciones de escala/desplazamiento no esenciales.

### Estados vacíos, errores y concurrencia

- Sin áreas: explicar y ofrecer `Crear área`.
- Área sin mesas: ofrecer creación simple o por serie.
- Sin mozos activos/en turno: enlazar a personal/turnos según permiso, sin inventar una asignación.
- Guardado de mapa fallido: conservar posiciones locales, permitir reintentar o descartar y no recargar silenciosamente.
- Conflicto por edición concurrente: informar que otro usuario actualizó el diseño y permitir revisar antes de sobrescribir.
- Mesa con pedido/reserva: bloquear retirada/movimiento incompatible y explicar la dependencia.
- Cambio de estado operativo: definir una única fuente derivada; no permitir contradicción entre `table.status`, pedido y reserva.

### Archivos involucrados en una implementación futura

**Modificar:**

- `resources/views/tables/index.blade.php`: separar lista/mapa, formularios accesibles, modo ordenar y asignaciones.
- `resources/views/pos/index.blade.php`: lista móvil, responsable y estados unificados.
- `app/Http/Controllers/TableController.php`: CRUD completo, validación, guardado por área, autorización y respuestas seguras.
- `app/Models/Table.php`: capacidad, relaciones y atributos coherentes.
- `app/Models/Area.php`: orden/estado y relaciones.
- `app/Models/Employee.php`: relación con asignaciones.
- `routes/web.php`: rutas explícitas para edición, orden y asignaciones con permisos.
- `resources/css/app.scss` o componentes específicos: tokens/estilos responsive compartidos, evitando estilos inline.
- `resources/js/app.js` o módulo dedicado: Pointer Events, teclado, cambios pendientes y estado accesible.

**Crear previsiblemente:**

- Migración para estado/orden de áreas y atributos faltantes de mesa.
- Migración `table_assignments` con restricciones e índices.
- Modelo `TableAssignment`.
- Form Requests para área, mesa, posiciones y asignaciones.
- Policy o permisos `floorplan.manage` y `table_assignments.manage`.
- Pruebas feature/unitarias y pruebas de interacción del editor.

**Revisar dependencias:**

- `app/Http/Controllers/PosController.php`, reservas y políticas de pedido para definir efecto de la asignación.
- `resources/views/employees/index.blade.php` y turnos para mostrar cobertura vigente.
- Reportes por mozo, preservando diferencia entre responsable asignado y autor real del pedido.

### Criterios verificables de cierre F3.2

1. A 320 px se crean, editan, desactivan y consultan áreas/mesas sin scroll horizontal de página.
2. A 360 px la vista lista permite abrir cualquier mesa y conocer estado, reserva, consumo y mozo sin entrar al mapa.
3. Todo el editor funciona con teclado; ninguna tarea exige arrastrar.
4. Touch/ratón/lápiz pueden mover mesas mediante Pointer Events y nunca sacarlas fuera del lienzo.
5. Cambios del plano se limitan al área activa, muestran contador, permiten deshacer/cancelar y advierten al salir.
6. Nombre de mesa es único por área y capacidad 1–99 se persiste correctamente.
7. No se elimina un área con mesas/pedidos/reservas; se ofrece traslado o desactivación.
8. Estado mostrado es consistente en editor, POS, reservas y dashboard y no depende solo del color.
9. Solo empleados activos con cargo mozo pueden recibir asignaciones normales.
10. Una mesa no admite dos asignaciones activas principales; conflictos se muestran antes de confirmar.
11. Asignación por área informa cuántas mesas, carga actual del mozo y efecto del cambio.
12. Finalizar/cancelar un turno gestiona explícitamente sus asignaciones vigentes.
13. Cambiar mozo no reescribe la autoría histórica de pedidos y queda auditado.
14. Mesas sin responsable son identificables por texto/icono y filtro.
15. Tabs, modales, mapa y tarjetas cumplen teclado, foco, nombres accesibles, 48 px y contraste WCAG 2.2 AA.
16. Guardados fallidos conservan cambios locales y ofrecen reintento sin usar `alert()` nativo.
17. Pruebas cubren autorización, validación, conflicto, asignación por turno y flujo responsive 320/768/1366.
18. La alternativa de lista ofrece funcionalidad equivalente al mapa para WCAG y dispositivos pequeños.

## Arquitectura de información objetivo

### Dashboard autenticado

```text
Inicio
├── Resumen según rol
├── Alertas y tareas pendientes
└── Accesos rápidos

Restaurante
├── Salón y mesas
├── Punto de venta
├── Cocina
├── Delivery
└── Reservas de mesa

Carta e inventario
├── Productos y categorías
├── Carta digital
├── Promociones y disponibilidad
├── Stock
└── Kardex

Clientes
├── Directorio
├── Historial
├── Cupones y fidelización (futuro)
└── Campañas (futuro)

Alojamiento (módulo habilitable)
├── Calendario y disponibilidad
├── Reservas
├── Búngalos/unidades y tarifas
├── Huéspedes
├── Entradas y salidas
└── Limpieza y mantenimiento

Finanzas
├── Caja y turnos
├── Ventas y movimientos
├── Facturación electrónica
├── Notas de crédito
├── Resúmenes SUNAT
└── Reportes

Sitio web
├── Resumen de publicación
├── Páginas y secciones
├── Navegación
├── Multimedia
├── SEO y redes
├── Formularios/contactos
└── Vista previa

Administración
├── Negocio y marca
├── Personal, roles y permisos
├── Módulos
├── Integraciones
├── Auditoría
├── Seguridad
└── Sistema y respaldos
```

La navegación debe adaptarse al rol. Un mozo no necesita ver administración; cocina entra directamente al KDS; caja prioriza apertura/cobro/cierre; recepción prioriza alojamiento y reservas; marketing prioriza sitio, carta y campañas.

### Landing pública

```text
Encabezado
├── Marca
├── Navegación
└── Acciones: reservar, ver carta, WhatsApp

Portada
├── Propuesta de valor
├── Imagen/video optimizado
└── Llamadas a la acción

Contenido configurable por bloques
├── Presentación
├── Experiencias/servicios
├── Platos destacados y carta
├── Promociones
├── Alojamiento y disponibilidad
├── Galería
├── Testimonios
├── Eventos
├── Ubicación y horarios
├── Preguntas frecuentes
└── Contacto/reserva

Pie
├── Datos legales y contacto
├── Redes sociales
├── Políticas
└── Libro de reclamaciones
```

No se recomienda un constructor HTML libre. El administrador debería activar, ordenar y editar bloques validados, con borrador, vista previa y publicación.

### Restaurante

El flujo principal debe ser contextual y continuo:

```text
Mesa o canal → pedido → confirmación → cocina → listo/entrega → cobro → comprobante
```

- Mantener visibles mesa/canal, cliente opcional, mozo, tiempo, estado y total.
- Reducir navegación de ida y vuelta entre POS, cocina, ventas y facturación.
- Separar acciones frecuentes de acciones excepcionales.
- Permitir recuperación clara ante producto agotado, cambio de mesa, error de cobro o falla SUNAT.
- El QR de mesa debe crear una solicitud pendiente para el mozo; solo la confirmación autorizada la envía a cocina.

### Alojamiento

El módulo debe diseñarse como dominio propio y compartir clientes, pagos, marca y reportes sin reutilizar indebidamente conceptos de mesas/pedidos.

```text
Consulta → disponibilidad → cotización → reserva → pago/garantía
→ preingreso → check-in → estancia/servicios → check-out
→ limpieza → disponible
```

Vistas clave:

- Calendario operativo por unidad y rango de fechas.
- Buscador público de disponibilidad.
- Ficha de reserva y huésped.
- Estado de cada búngalo: disponible, reservado, ocupado, limpieza, mantenimiento o bloqueado.
- Tablero de llegadas, salidas y limpieza del día.
- Tarifas por temporada, capacidad, restricciones y extras.
- Cuenta integrada de alojamiento, restaurante y servicios cuando corresponda.

## Fases UX recomendadas

### Fase UX 0 — Línea base y evidencia

- Verificar codificación UTF-8 en navegador antes de evaluar microcopy.
- Ejecutar inventario navegable con capturas por rol y resolución.
- Medir tareas críticas: abrir caja, tomar pedido, enviar a cocina, cobrar, emitir comprobante y cerrar caja.
- Entrevistar al menos a administrador, cajero, mozo, cocina y recepción/alojamiento.
- Definir métricas: tiempo de tarea, errores, retrocesos, abandonos y solicitudes de ayuda.

**Salida:** mapa de flujos actuales, problemas validados y línea base medible.

### Fase UX 1 — Fundamentos y accesibilidad

- Consolidar tokens y componentes.
- Definir tipografía, color, espaciado, foco y estados.
- Crear app shell responsive y patrones para tablas/formularios/modales.
- Establecer WCAG 2.2 AA como criterio de aceptación.
- Crear guía de lenguaje y estados.

**Salida:** biblioteca mínima reutilizable y documentación de uso.

### Fase UX 2 — Navegación y configuración marca blanca

- Reorganizar navegación por espacios de trabajo y rol.
- Diseñar onboarding inicial.
- Dividir configuración en secciones guardables.
- Incorporar centro de marca con vista previa y validación de contraste.
- Separar SUNAT, seguridad y mantenimiento de la configuración cotidiana.

**Salida:** instalación reconocible como el negocio del cliente sin cambios de código.

### Fase UX 3 — Operación crítica

- Rediseñar salón, POS, carro, cocina, caja y cobro como un flujo continuo.
- Optimizar para tacto, velocidad, estados en tiempo real y recuperación de errores.
- Validar con personal en tableta, escritorio y monitor de cocina.
- Incorporar pruebas de usabilidad y accesibilidad automatizadas/manuales.

**Salida:** operación diaria rápida, consistente y resistente a errores.

### Fase UX 4 — Carta y landing administrable

- Diseñar sistema de bloques, multimedia, navegación, SEO, borrador y publicación.
- Diseñar carta móvil, filtros, disponibilidad, promociones y QR.
- Integrar formularios de contacto y reserva con estados claros.
- Optimizar rendimiento, imágenes y accesibilidad pública.

**Salida:** sitio de marca completo administrable sin editar código.

### Fase UX 5 — Alojamiento

- Investigar operación real de búngalos.
- Prototipar disponibilidad, reserva, recepción, estancia, salida y limpieza.
- Validar calendario y estados con recepción y limpieza.
- Integrar experiencia pública con landing y cuenta operativa con restaurante.

**Salida:** módulo de alojamiento coherente, no una extensión improvisada de reservas de mesa.

### Fase UX 6 — Finanzas, SUNAT y optimización

- Diseñar estados asíncronos de SUNAT, reintentos, errores y trazabilidad.
- Simplificar reportes por rol y decisiones.
- Validar documentos A4 y 80 mm.
- Medir métricas posteriores, corregir fricción y completar auditoría WCAG.

**Salida:** experiencia financiera confiable y preparada para producción.

## Criterios de aceptación UX transversales

- Ninguna marca del cliente está escrita directamente en las vistas.
- La identidad configurada se refleja coherentemente en acceso, dashboard, landing, carta, documentos y comunicaciones.
- Toda tarea crítica funciona con teclado y con objetivos táctiles adecuados.
- Contraste, foco, zoom y errores cumplen WCAG 2.2 AA.
- Toda vista contempla carga, vacío, error, éxito y permisos insuficientes.
- Las acciones peligrosas explican consecuencias y exigen confirmación proporcional.
- Teléfono, tableta, escritorio, KDS e impresión tienen validación específica.
- Un usuario ve solo módulos y acciones pertinentes a su rol.
- La configuración pública admite borrador, vista previa y publicación.
- Los cambios visuales se implementan mediante componentes/tokens, no mediante estilos locales nuevos.

## Prioridades inmediatas

1. Mantener una verificación automatizada de UTF-8 y retirar la vista Laravel residual del flujo público.
2. Consolidar estilos embebidos en un sistema de diseño, sin intentar migrar todas las pantallas a la vez.
3. Reestructurar navegación y configuración por dominios.
4. Diseñar centro de marca y onboarding antes de construir la landing.
5. Prototipar y probar POS/cocina/caja con usuarios reales antes de modificar lógica.
6. Resolver accesibilidad crítica: zoom, foco, etiquetas, nombres accesibles, alternativas al arrastre y anuncios dinámicos.
7. Diseñar landing y alojamiento sobre modelos de contenido y flujos aprobados, no solo sobre maquetas visuales.

## Notas de implementación futura

- Conservar Bootstrap inicialmente es viable si se encapsula detrás de componentes Blade y tokens; no hace falta sustituir el framework para obtener coherencia.
- Evitar consultas directas a configuración desde cada vista. La identidad y capacidades deberían llegar desde un proveedor/capa común para producir estados previsibles y fáciles de probar.
- Separar estilos de aplicación, sitio público y documentos impresos, compartiendo únicamente tokens adecuados.
- Alojar localmente los activos críticos y definir optimización de imágenes, variantes y texto alternativo desde el gestor multimedia.
- Introducir cambios por recorrido completo y con pruebas de regresión visual/funcional; una migración masiva de CSS sería difícil de verificar.

