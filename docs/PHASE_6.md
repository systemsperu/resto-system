# Cierre de la Fase 6 — PWA y capacidades móviles

## Resultado

RecreoSuite puede instalarse como PWA desde `https://recreosuite.test`. El manifiesto, los iconos y los colores se generan desde la configuración de marca de cada instalación.

El service worker aplica una política conservadora:

- conserva únicamente la carcasa offline, recursos compilados y contenido público permitido;
- no intercepta ni almacena solicitudes `POST`;
- no guarda páginas autenticadas, caja, pedidos, pagos ni información privada;
- muestra una pantalla neutral cuando el servidor no está disponible;
- elimina caché y almacenamiento del sitio al cerrar sesión.

No se implementó una cola transaccional offline. Las ventas, pedidos, caja, inventario y pagos requieren conexión con el servidor autoritativo, evitando duplicados o conflictos no resueltos.

## Capacidades web

`window.RecreoDevice` ofrece adaptadores desacoplados para:

- lectura de QR y códigos de barras con cámara, con ingreso manual como alternativa;
- preparación local de imágenes, reducción de tamaño y eliminación de metadatos al recodificar;
- impresión mediante el diálogo del navegador;
- solicitud explícita de permiso para notificaciones.

Estos contratos podrán recibir implementaciones nativas en Capacitor durante la Fase 10 sin duplicar reglas de negocio.

## Instalación y actualización

1. Abrir `https://recreosuite.test` en Chrome o Edge.
2. Iniciar sesión y usar el botón **Instalar aplicación** cuando el navegador lo habilite.
3. Cuando exista una nueva versión, el sistema muestra **Actualización disponible**. La recarga ocurre solo después de que el usuario pulse **Actualizar**, evitando interrumpir una operación activa.

El certificado local de Laragon incluye `recreosuite.test`. Apache acepta TLS 1.2 y TLS 1.3 mediante `C:/laragon/etc/apache2/httpd-ssl.conf`.

## Validación automatizada

Las pruebas verifican manifiesto e iconos, pantalla offline, prohibición de caché en respuestas autenticadas y limpieza solicitada al cerrar sesión. La compilación de Vite comprueba la integración JavaScript y CSS.

Lighthouse 12.8.2 sobre `/login` por HTTPS obtuvo: accesibilidad 100, buenas prácticas 100, SEO 91 y rendimiento 67. El rendimiento y la deuda visual se atenderán en la revisión de interfaz acordada; no se alteraron los flujos operativos en esta fase.

## Validación pendiente fuera del código

La instalación y los permisos deben recorrerse físicamente en al menos un Android de gama baja y una tableta. También deben comprobarse cámara, impresión disponible en el negocio y comportamiento del sistema al cambiar entre Wi-Fi y datos móviles. Esta validación no bloquea el código de la fase, pero sí una publicación comercial.

Los defectos visuales heredados observados en el dashboard y el POS no forman parte de este cierre; se corregirán pantalla por pantalla en la revisión visual acordada.
