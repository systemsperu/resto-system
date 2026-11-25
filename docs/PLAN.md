# Plan maestro de RecreoSuite

## Visión del producto

RecreoSuite será un producto de marca blanca para restaurantes, recreos turísticos y negocios con alojamiento. Cada cliente tendrá una instalación, dominio, base de datos, archivos y claves independientes. No es multitenant.

La interfaz será mobile first y conservará una sola base funcional para navegador, PWA y una futura aplicación Android con Capacitor. El escritorio ampliará la información disponible, pero no tendrá flujos incompatibles con móvil.

## Reglas de ejecución

- Cada fase termina con pruebas y una demostración funcional, no solo con pantallas terminadas.
- Seguridad, accesibilidad, responsive, auditoría y pruebas son tareas transversales.
- No se inicia una integración externa real sin interruptor, ambiente de pruebas y autorización documentada.
- No se promete operación offline transaccional hasta implementar idempotencia, autoridad y resolución de conflictos.
- Los módulos nuevos reutilizan contratos del dominio; no duplican clientes, productos, pagos ni disponibilidad.
- Los cambios se integran en `develop`; las versiones demostrables se etiquetan y pasan a una rama de publicación.

## Estado de las fases

| Fase | Estado | Resultado principal |
|---|---|---|
| 0. Fundación local | Completada | Entorno aislado y reproducible |
| 1. Estabilización y seguridad | Completada | Base heredada confiable |
| 2. Marca blanca y sistema visual | Cerrada con deuda aceptada | Producto configurable y mobile first; remediación WCAG pendiente |
| 3. Operación restaurante MVP | Completada | Venta completa de mesa a cierre |
| 4. Web, carta y QR | Completada | Presencia pública, carta por sede, PDF y solicitudes QR |
| 5. Datos local/nube | Implementada; validación cloud pendiente | Respaldo cifrado y sincronización controlada |
| 6. PWA y capacidades móviles | Completada; validación física pendiente | PWA segura, instalable y preparada para capacidades móviles |
| 7. SUNAT | Implementada; aceptación beta externa pendiente | Facturación segura, en cola e idempotente |
| 8. Clientes y crecimiento | Completada; conectores externos pendientes | CRM, consentimiento, campañas simuladas, cupones y fidelización |
| 9. Alojamiento | Completada; integraciones externas pendientes | Disponibilidad, reservas y operación de búngalos |
| 10. Capacitor y producto comercial | Pendiente | APK/AAB y despliegue repetible |

## Fase 0 — Fundación local

### Alcance

- Separar código, base de datos, dominio, `APP_KEY` y almacenamiento del sistema original.
- Bloquear correo, mensajería, pagos y SUNAT reales.
- Preparar Git, documentación, roles de especialistas y configuración reproducible.
- Confirmar migraciones, seeders, compilación frontend y pruebas iniciales.

### Estado y salida

Completada. RecreoSuite funciona de forma aislada en `recreosuite.test`, usa `recreosuite_dev` y mantiene integraciones externas deshabilitadas.

## Fase 1 — Estabilización, seguridad y cobertura base

### Objetivo

Convertir el sistema heredado en una base segura para desarrollar sin propagar defectos estructurales.

### Entregables

- Inventario definitivo de rutas, controladores, modelos, tablas, permisos y flujos realmente operativos.
- Corrección de problemas de codificación UTF-8 y rutas o estados inconsistentes.
- Extracción y cifrado de secretos; las contraseñas nunca vuelven completas al navegador.
- Endurecimiento de backup, restauración, mantenimiento, subida de archivos y sesiones.
- Autorización mediante policies/permisos para acciones críticas.
- Auditoría de caja, stock, usuarios, configuración y documentos fiscales.
- Pruebas de regresión para autenticación, mesa, pedido, cocina, cobro y reporte.
- Dependencias estabilizadas, incluyendo la eliminación de modificaciones directas sobre `vendor`.

### Puerta de salida

- Cero vulnerabilidades críticas conocidas en los flujos revisados.
- Suite automatizada estable y recorrido manual documentado.
- Ninguna integración externa se activa durante las pruebas.
- Backups y restauración cuentan con un procedimiento seguro probado sobre una base desechable.

## Fase 2 — Marca blanca, configuración y sistema visual mobile first

### Objetivo

Eliminar marcas codificadas y establecer los componentes compartidos antes de ampliar los módulos.

### Entregables

- Asistente inicial para empresa, sede, datos fiscales, contacto, ubicación, horarios y zona horaria.
- Gestión de logotipo, favicon, iconos, colores, tipografía e imágenes por instalación.
- Configuración separada por negocio, marca, sitio público, operación, tributación e integraciones.
- Módulos habilitables por instalación sin borrar información.
- Sistema visual con tokens y componentes accesibles: botones de 48 px, formularios, tarjetas, tablas adaptativas, modales y estados.
- Layout y navegación por rol para teléfono, tableta y escritorio.
- Corrección prioritaria de zoom, safe areas, teclado virtual, recursos CDN y textos dañados.

### Puerta de salida

Una instalación nueva puede adoptar otra identidad sin editar código. Los componentes base pasan pruebas a 320, 360, 768, 1366 y 1920 px y cumplen el nivel acordado de WCAG 2.2 AA.

## Fase 3 — Operación restaurante MVP

**Estado actual:** cerrada con regresión integral automatizada. Las mejoras avanzadas y la deuda transversal se conservan documentadas en `docs/PHASE_3_CLOSURE.md`.

### Objetivo

Completar y asegurar el recorrido diario del restaurante antes de sumar funciones comerciales.

### Orden interno

1. Usuarios, roles, permisos, personal y turnos.
2. Áreas, mesas y asignación de mozos.
3. Carta operativa, modificadores y disponibilidad.
4. Pedido, confirmación, partidas y estados de cocina.
5. Caja, métodos de pago, división de cuenta y cierre.
6. Inventario, recetas, unidades y movimientos.
7. Delivery y reservas de restaurante.
8. Reportes operativos y auditoría.

### Puerta de salida

Un usuario de prueba puede abrir caja, asignar mesa, tomar un pedido, enviarlo a cocina, marcarlo listo, dividir o cobrar la cuenta, cerrar caja y consultar el reporte sin inconsistencias. El mismo recorrido funciona en móvil, tableta y escritorio.

## Fase 4 — Landing, carta digital y QR

### Objetivo

Permitir que cada negocio administre su presencia pública sin intervención técnica.

### Entregables

- Constructor controlado de páginas, secciones, navegación, galerías, contacto, horarios, SEO y redes.
- Páginas por sede dentro de una misma empresa instalada.
- Carta, precios, disponibilidad y promociones por sede.
- Carta digital rápida, accesible y compartible.
- Exportación PDF A4 multipágina.
- QR general y QR firmado/revocable por mesa.
- Solicitud de pedido desde mesa, confirmación del mozo y envío posterior a cocina.
- Analítica de navegación con consentimiento y minimización de datos.

### Puerta de salida

El administrador publica y actualiza la landing y la carta sin editar código. El QR no permite saltarse al mozo ni crear pedidos duplicados.

## Fase 5 — Operación local, respaldo y sincronización cloud

### Objetivo

Soportar instalación en hosting o en una PC local y construir resiliencia sin usar doble escritura improvisada.

### Incrementos

1. Backup local verificado y restauración sobre entorno desechable.
2. Backup cloud cifrado, retención y monitoreo.
3. UUID e identificación de instalación/dispositivo.
4. Outbox/inbox, cursores, acuses, reintentos e idempotencia.
5. Réplica local hacia nube para consulta y reportes.
6. Comandos cloud hacia local para casos aprobados, como reservas web.
7. Reglas explícitas de conflicto por ventas, caja, stock, contenido y reservas.

### Puerta de salida

Una interrupción y reconexión controlada no pierde ni duplica movimientos. El panel muestra última sincronización, pendientes, errores y conflictos. Secretos, sesiones y certificados quedan fuera de la réplica ordinaria.

## Fase 6 — PWA y capacidades del dispositivo

### Objetivo

Convertir la interfaz estable en una aplicación web instalable, segura y preparada para capacidades móviles.

### Entregables

- Manifest e iconos generados por marca.
- Service worker y caché limitada a recursos permitidos.
- Instalación, actualización controlada, pantalla offline y estado de red/LAN/nube.
- Adaptadores web para cámara, QR/código de barras, imágenes, impresión y notificaciones.
- Cola offline únicamente para operaciones cuyo contrato de sincronización haya sido aprobado.
- Pruebas Playwright/Lighthouse y validación en teléfonos Android de gama baja y tabletas.

### Puerta de salida

La PWA se instala, actualiza y cierra sesión sin filtrar datos autenticados. Los cortes de red no duplican operaciones y las funciones denegadas por permisos conservan alternativas manuales.

## Fase 7 — Facturación electrónica SUNAT

### Objetivo

Certificar la integración heredada antes de permitir su uso productivo.

### Entregables

- Configuración tributaria y secretos cifrados.
- Series y correlativos con control de concurrencia.
- XML, firma y validaciones cubiertos sin red.
- Envío asíncrono e idempotente en ambiente beta.
- CDR, estados, reintentos, notas de crédito, resúmenes y contingencia.
- Monitoreo y procedimiento operativo de errores.

### Puerta de salida

Existe evidencia de aceptación en beta para los documentos soportados, revisión de seguridad aprobada y activación productiva deliberada por instalación. Compilar o generar XML no equivale a certificación.

## Fase 8 — Clientes, campañas y fidelización

### Entregables

- Perfil único, historial y preferencias del cliente.
- Consentimientos, segmentos, campañas y bajas.
- Cupones, promociones, puntos o beneficios configurables.
- Métricas de adquisición, conversión y recurrencia.
- Integraciones de correo o mensajería mediante adaptadores de prueba/producción.

### Puerta de salida

Las campañas respetan consentimiento, permisos y aislamiento de datos. Cupones y beneficios son idempotentes y auditables.

## Fase 9 — Alojamiento y búngalos

### Objetivo

Agregar alojamiento como dominio propio, compartiendo clientes y pagos sin reutilizar mesas o pedidos.

### Entregables

- Tipos de unidad, búngalos, capacidad, servicios, medios y estados.
- Tarifas, temporadas, restricciones y disponibilidad.
- Reservas, huéspedes, anticipos, check-in y check-out.
- Limpieza, mantenimiento y bloqueo de unidades.
- Motor de disponibilidad consumido por dashboard, landing y futuras integraciones.
- Reportes de ocupación, ingresos y operación.

### Puerta de salida

No se producen dobles reservas ante concurrencia. Recepción y limpieza pueden completar sus recorridos desde teléfono o tableta.

## Fase 10 — Capacitor, APK/AAB y producto comercial

### Objetivo

Empaquetar el producto web probado y establecer un despliegue repetible por cliente.

### Entregables

- Proyecto Capacitor con configuración por ambiente y marca.
- Secure storage, deep links, navegación Atrás, cámara, notificaciones e impresión mediante adaptadores.
- APK interna para piloto y AAB firmado para distribución.
- Keystore y credenciales fuera del repositorio.
- Instalador web, migraciones, backups, observabilidad y actualizaciones con rollback.
- Manuales de administración, operación, soporte y recuperación.
- Pruebas de rendimiento, seguridad y dispositivos reales.

### Puerta de salida

Una nueva instalación puede desplegarse siguiendo documentación, actualizarse sin perder información y recuperar una copia verificada. APK/PWA/web comparten reglas y pasan los flujos críticos.

## Versiones demostrables

### Entrega A — Base confiable

Fases 0 y 1. Sistema heredado estabilizado, seguro y cubierto por pruebas básicas.

### Entrega B — Restaurante marca blanca MVP

Fases 2 y 3. Producto vendible para operación interna de restaurante, todavía sin prometer offline ni SUNAT productivo.

### Entrega C — Presencia digital y PWA

Fases 4, 5 y 6 en incrementos aprobados. Landing, carta/QR, respaldo/sincronización y aplicación instalable.

### Entrega D — Fiscal y crecimiento

Fases 7 y 8. SUNAT certificado y herramientas de clientes/fidelización.

### Entrega E — Recreo y alojamiento completo

Fases 9 y 10. Búngalos, APK/AAB y operación comercial reproducible.

## Iteración inmediata — Fase 10

El siguiente bloque de trabajo es preparar Capacitor, generar APK/AAB y documentar un despliegue comercial repetible por instalación.

La activación externa SUNAT beta continúa separada: requiere credenciales beta válidas y una ejecución controlada autorizada. Producción permanece bloqueada.
