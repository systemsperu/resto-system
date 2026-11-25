# Estrategia mobile-first, PWA y Android

## Propósito

RecreoSuite debe ofrecer una experiencia operativa excelente desde teléfonos Android, tabletas y equipos de escritorio. La meta no es encerrar una interfaz de escritorio en una APK: cada flujo debe diseñarse primero para tacto, pantallas pequeñas, conectividad inestable y trabajo bajo presión, y ampliarse progresivamente para escritorio.

El orden previsto es:

1. aplicación web responsive mobile-first;
2. PWA instalable y segura;
3. integración progresiva con capacidades del dispositivo;
4. contenedor Capacitor y APK/AAB Android.

La aplicación seguirá teniendo una sola base funcional. Las diferencias web/PWA/Android se resolverán mediante adaptación progresiva y una capa de servicios para capacidades nativas, no duplicando las reglas de negocio.

## Estado y evidencias actuales

La preparación actual es **inicial**:

- `resources/views/layouts/app.blade.php` declara `viewport`, ofrece sidebar deslizable bajo `991px` y overlay móvil. Esto permite navegación básica, pero solo existe un breakpoint global y no una estrategia mobile-first completa.
- El mismo layout carga Google Fonts, Bootstrap Icons y Bootstrap desde CDN. La interfaz instalada u offline perdería recursos esenciales si no se empaquetan localmente.
- `resources/css/app.scss` usa Bootstrap 5.3, pero contiene principalmente estilos de escritorio y no define tokens táctiles, safe areas ni breakpoints de producto.
- `resources/js/app.js` solo inicializa Bootstrap. No registra service worker, no detecta instalación, conexión, actualización ni capacidades nativas.
- `package.json` no contiene Workbox, plugin PWA de Vite, Capacitor ni plugins Android.
- No existen manifest web, service worker, iconos PWA completos, `offline.html`, configuración Capacitor ni proyecto Android.
- `resources/views/menu/index.blade.php` bloquea zoom con `maximum-scale=1.0, user-scalable=no`; esto perjudica accesibilidad y debe retirarse.
- `resources/views/pos/order.blade.php` usa una columna fija de categorías de `155px`, paneles horizontales, altura de viewport y bastante estilo/script embebido. No hay una variante compacta por tarea.
- `resources/views/delivery/create.blade.php` fuerza dos columnas al 50 % (`w-50`) dentro de una fila sin una adaptación móvil específica.
- Los mapas de mesas en `resources/views/pos/index.blade.php` y `resources/views/tables/index.blade.php` usan lienzos fijos de 650 y 600 píxeles. Requieren zoom, paneo y alternativa accesible en lista.
- Hay tablas envueltas en `table-responsive`, pero muchas operaciones siguen dependiendo de tablas anchas; en móvil deben convertirse en tarjetas/listas de acciones cuando el desplazamiento horizontal impida comparar o actuar.
- Los botones globales usan relleno visual, pero no garantizan un área táctil mínima. Existen botones pequeños e iconos sin etiqueta accesible.
- El código de barras actual espera un lector que escribe en un campo (`pos/order.blade.php`); no usa cámara, `BarcodeDetector` ni un plugin nativo.
- La impresión usa `window.print()` en tickets e informes. No existe abstracción para impresora térmica Bluetooth/USB/red ni comportamiento Capacitor.
- Las cargas de imágenes usan `input type=file`, pero no hay captura, compresión, orientación EXIF ni permisos/cámara nativos.
- No hay caché local transaccional, cola offline, indicador de sincronización ni resolución de conflictos. No debe prometerse operación offline hasta implementar el contrato de sincronización.

## Principios de experiencia

- Mobile-first desde 320 px; el escritorio amplía información, no cambia las reglas del flujo.
- Una acción primaria visible por pantalla y confirmación clara en acciones irreversibles.
- Flujos frecuentes de mozo, cocina, caja y recepción con el menor número posible de pasos.
- Nunca depender solo de color, hover o iconos.
- WCAG 2.2 AA, zoom habilitado, foco visible, navegación por teclado y compatibilidad con lector de pantalla.
- Respetar `prefers-reduced-motion`, orientación, teclado virtual y áreas seguras del dispositivo.
- Estado explícito: conectado, sin conexión, guardando, pendiente de sincronización, sincronizado o con conflicto.
- Ninguna venta, pago, correlativo o documento SUNAT se considera enviado por haber sido guardado solo en caché.

## Breakpoints y patrones responsive

Se conservarán los breakpoints de Bootstrap para evitar dos sistemas incompatibles, usando estilos base sin media query:

| Rango | Uso esperado | Patrón |
|---|---|---|
| 320–575 px | teléfono | una columna, navegación inferior por rol, acciones fijas seguras |
| 576–767 px (`sm`) | teléfono grande | una o dos columnas simples |
| 768–991 px (`md`) | tableta vertical | panel maestro/detalle opcional |
| 992–1199 px (`lg`) | tableta horizontal/portátil | sidebar y paneles simultáneos |
| 1200 px o más (`xl`) | escritorio | densidad mayor y vistas comparativas |

Reglas:

- Usar CSS Grid/Flex fluido y `minmax()`; evitar anchos y alturas rígidos salvo impresión.
- `100dvh` con fallback, no solo `100vh`; contemplar barras del navegador y teclado virtual.
- Formularios a una columna en teléfono; etiquetas siempre visibles y tipos de entrada correctos (`tel`, `email`, `decimal`, fecha/hora).
- Tablas operativas pasan a lista/tarjeta en teléfono. El desplazamiento horizontal se reserva para reportes comparativos.
- Modales complejos se convierten en pantalla o bottom sheet en móvil.
- Mapas de mesas ofrecen vista gráfica con paneo/zoom y vista lista filtrable equivalente.
- Mantener contenido crítico dentro de `env(safe-area-inset-*)`.
- No bloquear orientación globalmente; POS y cocina deben funcionar en vertical y horizontal.

## Tamaños táctiles y densidad

- Objetivo mínimo de controles: **48 × 48 CSS px**; mínimo absoluto WCAG: 24 × 24 px con separación suficiente.
- Separación recomendada entre acciones destructivas y primarias: 8 px o más.
- Texto base de operación: 16 px; evitar que iOS/Android amplíen campos por fuentes menores.
- Acciones críticas deben incluir texto; los botones solo con icono requieren `aria-label`, tooltip y área de 48 px.
- Botones primarios de POS, cocina, cobro y check-in: alto mínimo 52–56 px.
- Estados de pedido/cocina deben combinar etiqueta, forma/icono y color con contraste AA.
- No usar gestos ocultos como único mecanismo; deslizar puede ser un atajo, nunca la única vía.

## Navegación por rol

En teléfono se reemplaza el sidebar completo por 3–5 destinos frecuentes en navegación inferior y un menú “Más”. En tableta/escritorio puede mantenerse sidebar adaptativo.

| Rol | Navegación primaria móvil propuesta |
|---|---|
| Mozo | Mesas, Pedidos, Solicitudes QR, Cuenta, Más |
| Cocina | Cola, En preparación, Listos, Historial |
| Cajero | Caja, Cobrar, Ventas, Comprobantes, Más |
| Recepción | Reservas, Huéspedes, Check-in/out, Limpieza, Más |
| Repartidor | Entregas, Ruta, Historial, Perfil |
| Administrador | Inicio, Operación, Alertas, Reportes, Más |

Los permisos del servidor siguen siendo la autoridad. Ocultar una opción en el cliente no sustituye policies/middleware. Las rutas profundas deben preservar retorno, estado y filtros; el botón Atrás de Android no debe cerrar accidentalmente una venta en curso.

## PWA, manifest e instalación

El manifest deberá generarse desde la configuración de marca blanca o desde un artefacto versionado por despliegue e incluir:

- `name`, `short_name`, `description`, `lang`, `start_url`, `scope` e `id` propios de la instalación;
- `display: standalone`, colores configurables y orientación no bloqueada;
- iconos maskable y estándar en 192 y 512 px, favicon y apple-touch-icon;
- accesos rápidos solo a rutas autorizadas y seguras;
- capturas para enriquecer el diálogo de instalación cuando corresponda.

La invitación a instalar será contextual, nunca intrusiva, y explicará beneficios reales. Debe existir pantalla de actualización: descargar una versión nueva no puede recargar automáticamente durante un cobro o pedido sin guardar.

## Service worker y caché segura

Preferir Workbox integrado con Vite y versionar explícitamente cada caché.

Estrategias:

- **Cache first:** assets compilados con hash, iconos y fuentes locales.
- **Stale while revalidate:** imágenes públicas de carta, con límites de edad/tamaño.
- **Network first con fallback:** landing y carta pública no sensible.
- **Network only:** login/logout, CSRF, panel autenticado, pagos, caja, SUNAT, respaldos y cualquier respuesta con datos personales u operativos.

No almacenar en Cache Storage páginas HTML autenticadas ni respuestas que contengan clientes, ventas, credenciales o comprobantes. Respetar `Cache-Control: no-store` en rutas sensibles. Al cerrar sesión se borran cachés y almacenamiento pertenecientes al usuario. El service worker debe validar origen, método y tipo de respuesta; nunca cachear POST por defecto.

La pantalla offline debe ser local, neutra y mostrar qué operaciones están disponibles. “Sin internet” no significa “servidor local inaccesible”: se debe distinguir red del dispositivo, acceso al servidor LAN y acceso a nube.

## Operación offline y sincronización

PWA/APK no debe introducir una segunda sincronización distinta de la arquitectura local/nube. Ambos usarán el mismo contrato de eventos, UUID, idempotencia y outbox/inbox.

- Primera etapa: interfaz/cache de recursos y lectura pública limitada.
- Operación transaccional offline solo después de definir autoridad, versión, secuencia y conflicto por agregado.
- IndexedDB, no `localStorage`, para una cola cifrada cuando sea viable; almacenar el mínimo dato necesario.
- Cada comando local lleva UUID, instalación, usuario, dispositivo, fecha local y versión del agregado.
- El servidor confirma de forma idempotente. La UI nunca interpreta un timeout como fracaso definitivo ni reenvía pagos/SUNAT sin consultar estado.
- POS, caja, stock, correlativos y SUNAT requieren reglas específicas y pruebas de doble envío, pérdida de conexión y reloj incorrecto.
- Mostrar cantidad de pendientes, último intento, último éxito y conflictos que requieren intervención.
- Background Sync puede ayudar en web compatible, pero no es garantía; Capacitor requiere una cola persistente y tareas nativas controladas.

## Capacitor y Android

Capacitor se incorporará solo cuando la PWA y sus contratos sean estables. El proyecto Android será un cliente versionado del mismo producto.

- Definir `appId` neutral por producto/despliegue, nombre y deep links por marca.
- Decidir explícitamente si el contenedor sirve assets locales con API remota o abre una URL. Para resiliencia se prefiere shell local más API HTTPS; no usar WebView remota como única estrategia sin evaluar indisponibilidad y políticas de seguridad.
- Crear una interfaz de capacidades (`CameraService`, `ScannerService`, `PrintService`, `NotificationService`, `StorageService`) con implementación web y nativa.
- Configurar navegación, status bar, splash, teclado, safe areas y retorno Android.
- Firmar APK/AAB fuera del repositorio; keystore y contraseñas nunca entran en Git.
- Publicar builds separados de desarrollo, pruebas y producción; sin hosts permisivos, cleartext ni depuración en producción.

### Permisos Android

Solicitar permisos en el momento de uso y explicar el motivo. Inicialmente:

- Cámara: escaneo QR/código de barras y captura de imágenes.
- Notificaciones (`POST_NOTIFICATIONS` en Android moderno): avisos operativos configurables.
- Bluetooth cercano, solo si se integra una impresora compatible y según versión Android.
- Ubicación, solo si una función de delivery/ruta aprobada realmente la requiere; nunca por defecto.
- Archivos/medios: usar selectores del sistema y almacenamiento específico de la app; evitar permisos amplios.

No solicitar contactos, teléfono, SMS, micrófono ni ubicación en segundo plano sin un caso aprobado. Debe existir alternativa manual si se rechaza cámara, notificaciones o Bluetooth.

## Almacenamiento, sesión y secretos

- No guardar contraseña, clave SOL, certificado, token permanente, `APP_KEY` ni secreto de pago en JavaScript, manifest, APK o preferencias simples.
- Sesiones web con cookies `Secure`, `HttpOnly`, `SameSite` y protección CSRF. Evaluar tokens de corta duración y almacenamiento seguro nativo solo si la arquitectura API lo exige.
- En Capacitor, los secretos de sesión imprescindibles se guardan mediante Android Keystore/almacenamiento seguro; se borran al cerrar sesión o revocar dispositivo.
- Datos offline sensibles se minimizan, cifran y expiran; la base local no es respaldo.
- Registrar dispositivos y permitir revocarlos. Bloqueo biométrico/PIN puede proteger reingreso, pero no sustituye autenticación del servidor.
- Certificados SUNAT y secretos empresariales permanecen en servidor, no en el dispositivo.

## Cámara, QR, código de barras e imágenes

- Web: detección progresiva con cámara y fallback a lector HID/campo manual.
- Android: plugin mantenido de escaneo sobre cámara con marco, linterna, cancelación y permisos.
- Validar formato y contenido en servidor; un QR es entrada no confiable.
- QR de mesa debe contener identificador opaco/firmado o token revocable, no IDs predecibles ni datos personales.
- Evitar escaneo duplicado con debounce/idempotencia y feedback háptico/sonoro opcional.
- Imágenes de platos/logos: captura o galería, recorte, orientación correcta, compresión antes de subir, límite de tamaño y eliminación de metadatos sensibles.

## Impresión

`window.print()` seguirá como fallback web para A4 y navegador. Para tickets se necesita una capa de impresión independiente:

- navegador/escritorio: diálogo del sistema o servicio de impresión local;
- Android: diálogo de impresión para PDF y, si se aprueba hardware, adaptador ESC/POS Bluetooth/USB/red;
- cola con estado `pendiente/impreso/error`, reintento manual e idempotencia;
- prueba de 58/80 mm, caracteres españoles, corte, logo, QR y pérdida de conexión;
- imprimir no cambia por sí solo el estado fiscal ni reemite el comprobante.

No elegir un plugin térmico hasta conocer modelos de impresora y versiones Android objetivo.

## Notificaciones

- PWA: Web Push solo con consentimiento y soporte comprobado.
- APK: Firebase Cloud Messaging o proveedor equivalente desacoplado mediante backend.
- Eventos candidatos: solicitud QR, pedido nuevo, plato listo, delivery asignado, reserva próxima y conflicto de sincronización.
- Suscripción por usuario/rol/dispositivo; no incluir datos personales, importes sensibles ni credenciales en el payload.
- Deep link a una ruta autorizada; al abrir se vuelve a consultar el estado del servidor.
- Horarios, preferencias, agrupación y deduplicación obligatorios. Cocina requiere señales visuales/sonoras dentro de la aplicación aun si push falla.

## Pruebas obligatorias

Matriz mínima:

- anchos 320, 360, 390, 412, 768, 1024, 1366 y 1920 px;
- Android vigente y dos versiones anteriores, teléfono de gama baja y tableta;
- Chrome Android, Chrome/Edge escritorio y WebView de la versión mínima soportada;
- vertical/horizontal, teclado abierto, texto al 200 %, zoom, modo oscuro del sistema y reducción de movimiento;
- red rápida, lenta, intermitente, sin internet, servidor LAN accesible y servidor LAN caído;
- reinicio de app durante pedido/cobro, sesión vencida, actualización disponible y almacenamiento lleno;
- TalkBack, teclado externo, contraste, foco y áreas táctiles;
- cámara denegada, notificaciones denegadas, código inválido y escaneo repetido;
- impresora desconectada, papel agotado, duplicado y caracteres/QR;
- actualización de APK/PWA sin perder operaciones pendientes.

Automatización recomendada: pruebas de componentes y contratos; Playwright para recorridos responsive/PWA; Lighthouse CI para instalación, rendimiento y accesibilidad; pruebas instrumentadas Android para plugins; pruebas reales de hardware antes de certificar impresión/cámara.

## Fases y criterios de aceptación

### M0 — Contratos y sistema visual móvil

- Inventariar flujos por rol y dispositivos reales.
- Definir tokens, componentes, estados y navegación adaptativa.
- Aceptación: prototipos y especificaciones de POS, cocina, caja y recepción validados a 360 y 768 px; objetivos táctiles y WCAG documentados.

### M1 — Responsive operativo

- Refactorizar layout, POS, cocina, delivery, mesas, cobro y tablas prioritarias.
- Localizar fuentes/iconos y retirar bloqueo de zoom.
- Aceptación: sin overflow accidental a 320 px; tareas principales completas con tacto, teclado y TalkBack; escritorio conserva funcionalidad.

### M2 — PWA instalable segura

- Manifest por marca, iconos, service worker, pantalla offline, actualización controlada y telemetría técnica.
- Aceptación: instalación en Android/escritorio, auditoría Lighthouse acordada, ninguna respuesta autenticada en caché y logout limpia datos locales.

### M3 — Estado de red y cola segura

- Indicadores, IndexedDB, comandos idempotentes y contrato con outbox/inbox.
- Empezar con operaciones de bajo riesgo; excluir pagos/SUNAT hasta pruebas específicas.
- Aceptación: cortes y reintentos no duplican comandos; pendientes sobreviven reinicio; conflictos son visibles y auditables.

### M4 — Capacidades del dispositivo

- Cámara/escáner, imágenes, notificaciones y abstracción de impresión.
- Aceptación: fallback manual, permisos bajo demanda, pruebas en hardware objetivo y cero secretos en almacenamiento inseguro.

### M5 — Capacitor Android

- Proyecto Android, entornos, deep links, secure storage, actualización, firma y pipeline reproducible.
- Aceptación: APK interna instalada en teléfono/tableta, navegación Atrás correcta, plugins estables y revisión de seguridad aprobada.

### M6 — Piloto y publicación

- Piloto en un local con métricas de tiempos, fallos y aprendizaje de usuarios.
- Aceptación: flujos críticos aprobados, recuperación probada, política de privacidad/permisos lista, AAB firmado y plan de rollback documentado.

## Condiciones para afirmar “lista para Android”

No se considerará lista por compilar una APK. Deben cumplirse simultáneamente: experiencia mobile-first validada por rol, PWA instalable, seguridad de caché/sesión, sincronización idempotente, permisos mínimos, pruebas en dispositivos de baja gama, manejo correcto de actualizaciones y evidencia de que pedidos, caja, impresión y SUNAT no se duplican ante interrupciones.
