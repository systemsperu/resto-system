# Arquitectura de RecreoSuite

## Propósito

RecreoSuite es una aplicación de marca blanca para restaurantes, recreos turísticos y negocios con alojamiento. El producto mantiene una sola base de código, pero cada cliente recibe una instalación independiente con su propio dominio, base de datos, archivos, claves y configuración comercial.

Este diseño **no es multitenant**. Una instalación representa un solo negocio. Si una empresa requiere varias sedes en el futuro, estas pertenecerán al mismo negocio instalado y deberán modelarse explícitamente; no se usará una columna `tenant_id` ni se compartirán datos entre clientes.

## Modelo de despliegue

```text
Código versionado de RecreoSuite
├── Instalación PONOA
│   ├── dominio y servidor propios
│   ├── base de datos propia
│   ├── APP_KEY propia
│   └── almacenamiento propio
└── Instalación El Águila
    ├── dominio y servidor propios
    ├── base de datos propia
    ├── APP_KEY propia
    └── almacenamiento propio
```

Reglas de aislamiento:

- Nunca compartir `.env`, `APP_KEY`, sesiones, caché, colas ni directorio `storage` entre instalaciones.
- Nunca incluir credenciales, certificados o datos de clientes en Git.
- Las actualizaciones se distribuyen desde la base de código común mediante migraciones y un procedimiento de despliegue repetible.

## Canales de experiencia

El frontend será una base web responsive compartida, diseñada mobile first, que se entregará por tres canales:

1. navegador web en teléfonos, tabletas y computadoras;
2. PWA instalable con manifest y service worker;
3. aplicación Android empaquetada con Capacitor.

Capacitor funcionará como contenedor y puente hacia capacidades del dispositivo; no tendrá una segunda implementación del dominio. Blade, componentes web, contratos HTTP y validaciones del servidor continuarán siendo la fuente común. Las funciones nativas —cámara y QR, impresión, archivos, notificaciones o almacenamiento seguro— se consumirán mediante adaptadores que permitan una alternativa web cuando corresponda.

La interfaz no dependerá de `hover`, clic derecho, teclado físico ni anchuras de escritorio. Los flujos de mozo, cocina, caja y reservas priorizarán objetivos táctiles de al menos 48 x 48 px, jerarquía clara, pocas pulsaciones y estados de conexión visibles. Las tablas extensas deberán transformarse en tarjetas, vistas resumidas o desplazamiento controlado en pantallas pequeñas.

PWA no significa que toda operación sea automáticamente offline. El service worker almacenará únicamente recursos y respuestas expresamente clasificadas. Pedidos, pagos, caja, inventario y comprobantes requieren reglas de idempotencia, autoridad y sincronización antes de admitir escrituras sin conexión. Credenciales, secretos SUNAT, sesiones completas y datos personales sensibles no se conservarán en cachés públicas del dispositivo.

## Modos de datos y operación sin conexión

RecreoSuite soportará dos modos de instalación seleccionados durante el despliegue:

1. **Nube principal:** aplicación y MySQL viven en el hosting. Los dispositivos acceden por HTTPS y la nube es la única fuente de escritura.
2. **Local principal con sincronización:** aplicación y MySQL funcionan en la computadora del negocio aunque no haya internet. Un servicio de sincronización replica cambios autorizados hacia una nube exclusiva del cliente.

No se implementará escritura libre y simultánea entre dos bases equivalentes. La sincronización utilizará un patrón `outbox/inbox`:

- identificadores UUID globales y un `installation_id` estable;
- eventos versionados por entidad y operación;
- cola local persistente con reintentos e idempotencia;
- receptor cloud que rechaza duplicados;
- cursor de sincronización, acuses y auditoría;
- reglas explícitas de conflicto por agregado;
- archivos sincronizados por almacenamiento de objetos, no dentro de filas SQL;
- secretos, certificados SUNAT y sesiones excluidos de la replicación ordinaria.

Las ventas, caja, stock, series y correlativos necesitan autoridad definida. En modo local, la instalación local es autoridad operativa y la nube recibe una réplica; no se editarán esas mismas operaciones desde ambos lados. Contenido público, reservas web y otros datos originados en nube entrarán mediante comandos identificados e idempotentes.

Una copia de seguridad en la nube no equivale a sincronización bidireccional. El primer hito será respaldo cifrado y monitoreo; la sincronización operativa se construirá después de estabilizar el dominio y sus pruebas.
- La identidad pública se obtiene de la configuración del negocio, no de textos escritos directamente en las vistas.
- Las integraciones externas deben poder habilitarse por instalación y distinguir claramente prueba de producción.

## Estado tecnológico observado

- Backend: PHP 8.2 o superior y Laravel 12.
- Persistencia: Eloquent y migraciones Laravel; el código actual está orientado a MySQL.
- Presentación: Blade, Bootstrap 5, Sass, JavaScript y Vite 7.
- Autenticación: sesión Laravel con inicio de sesión propio.
- Autorización: middleware por roles fijos (`admin`, `cashier`, `waiter`, `kitchen`).
- Facturación electrónica: Greenter para XML, firma y comunicación con SUNAT.
- Archivos: discos `local` y `public` de Laravel.
- Procesos asíncronos: Laravel incluye infraestructura de colas, pero el flujo operativo actual ejecuta tareas críticas principalmente dentro de la petición web.

## Arquitectura actual

La aplicación es un monolito Laravel organizado principalmente por tipo técnico:

```text
Petición HTTP
  -> middleware de autenticación, rol o caja abierta
  -> controlador
  -> modelos Eloquent / transacción de base de datos
  -> servicio SUNAT cuando corresponde
  -> vista Blade o descarga de archivo
```

Los límites de dominio todavía son informales. `Order` funciona como agregado central para pedidos de mesa, ventas, delivery y comprobantes electrónicos. `Setting` funciona como almacén genérico clave-valor para identidad, operación y secretos de SUNAT. Esta estructura permitió avanzar rápido, pero no debe extenderse indiscriminadamente.

## Arquitectura objetivo

Se recomienda conservar un monolito modular. Para el tamaño esperado, separar servicios desplegables añadiría complejidad sin aportar aislamiento útil. Los módulos deben tener límites claros dentro de Laravel:

```text
Canales de entrada
├── Panel administrativo
├── POS y cocina
├── Landing y carta pública
└── Portal de reservas
          │
          v
Módulos de aplicación
├── Identidad y configuración
├── Acceso y personal
├── Catálogo e inventario
├── Restaurante
├── Caja y pagos
├── Facturación electrónica
├── Clientes y marketing
├── Delivery
├── Sitio público
├── Alojamiento
└── Reportes y auditoría
          │
          v
Infraestructura
├── MySQL
├── almacenamiento de medios y documentos
├── caché, sesiones y colas
├── correo y mensajería
└── SUNAT y futuras pasarelas
```

Cada módulo debe exponer casos de uso y no depender de vistas o controladores de otro módulo. Los controladores deben coordinar la petición; las reglas de negocio, transacciones y efectos externos deben residir en servicios o acciones probables de probar.

## Límites y dependencias

### Núcleo compartido

Contiene identidad del negocio, preferencias regionales, medios, módulos habilitados, usuarios, permisos y auditoría. Todos los demás módulos pueden leer esta configuración; ninguno debe escribir claves arbitrarias de otro módulo.

### Restaurante

Es dueño de áreas, mesas, pedidos, partidas y estados de cocina. Depende de catálogo, personal y, al cerrar una venta, de caja/pagos. No debe conocer detalles SOAP ni credenciales SUNAT.

### Catálogo e inventario

Es dueño de categorías, productos, recetas, costos, existencias y movimientos. La landing y el POS consumen el catálogo. Restaurante registra consumos mediante una interfaz de inventario, no mediante actualizaciones dispersas de `stock`.

### Caja y pagos

Es dueño de aperturas, cierres, movimientos, cobros, devoluciones y conciliación. Un pedido puede originar un cobro; el comprobante fiscal se genera a partir de una venta confirmada, no debe ser el mecanismo que confirma el pago.

### Facturación electrónica

Es dueño de series, correlativos, documentos fiscales, XML, CDR, reintentos y estados SUNAT. Depende de una venta cerrada, del cliente fiscal y de la configuración tributaria. La comunicación externa debe ejecutarse mediante trabajos en cola idempotentes y auditables.

### Sitio público

Es dueño de páginas, secciones, navegación, SEO, galerías y formularios. Consume identidad, catálogo, promociones y disponibilidad de alojamiento. No debe duplicar productos, precios ni unidades de alojamiento dentro de bloques de contenido.

### Alojamiento

Será dueño de tipos de unidad, búngalos, tarifas, disponibilidad, reservas, huéspedes, estadías, limpieza y mantenimiento. Puede compartir clientes y pagos, pero no debe reutilizar mesas ni pedidos para representar habitaciones o reservas.

### Clientes y marketing

Es dueño del perfil, consentimientos, segmentos, campañas, cupones y fidelización. Restaurante, delivery y alojamiento referencian al cliente sin apropiarse de su historial completo.

## Datos maestros y marca blanca

La configuración debe dividirse por responsabilidad en lugar de crecer como una bolsa de claves:

- Negocio: nombre comercial, razón social, RUC, contacto, ubicación, horario, idioma, moneda y zona horaria.
- Marca: logos, favicon, colores, tipografías e imágenes predeterminadas.
- Sitio: dominio canónico, navegación, secciones, SEO y redes sociales.
- Operación: módulos habilitados, opciones de POS, caja, reservas y delivery.
- Tributación: parámetros fiscales, series y ambiente SUNAT.
- Secretos: credenciales y contraseñas cifradas, nunca devueltas completas al navegador.

Los archivos deben registrarse como medios administrados con propósito, disco, ruta, tipo y metadatos. Productos y búngalos conservarán sus propias relaciones con medios.

## Integraciones y efectos externos

- Correo, mensajería, pagos y SUNAT deben tener adaptadores explícitos y modos deshabilitado, prueba y producción cuando corresponda.
- Ninguna instalación nueva debe emitir o enviar información externa hasta completar su asistente y una activación administrativa deliberada.
- Los reintentos deben ser idempotentes y conservar intentos, respuesta resumida y marcas de tiempo.
- Los certificados y documentos fiscales se guardan en almacenamiento privado.
- Los errores mostrados al usuario no deben revelar rutas, claves, respuestas sensibles ni trazas.

## Seguridad y autorización

La autorización actual por cadenas de rol es insuficiente para los módulos previstos. La evolución debe introducir permisos granulares y políticas Laravel, manteniendo roles como agrupaciones configurables. Deben auditarse al menos:

- cambios de identidad, tributación e integraciones;
- apertura, cierre y ajustes de caja;
- cambios de stock y costos;
- anulaciones, notas de crédito y reintentos SUNAT;
- restauración, borrado o exportación de datos;
- cambios de permisos y usuarios.

Las operaciones destructivas de mantenimiento no deben depender únicamente de una confirmación en HTML. Requieren autorización reforzada, validación del archivo, respaldo previo, registro de auditoría y procedimiento operacional fuera de la petición web cuando sea posible.

## Estrategia de evolución

1. Estabilizar el comportamiento heredado con pruebas de regresión.
2. Extraer configuración de negocio y secretos del almacén genérico actual.
3. Introducir permisos, auditoría y servicios de aplicación.
4. Definir contratos entre restaurante, caja, inventario y facturación.
5. Construir landing y carta pública sobre datos maestros existentes.
6. Añadir alojamiento como módulo separado.
7. Incorporar trabajos en cola, observabilidad y despliegue repetible.

Las migraciones deben ser aditivas y reversibles cuando sea razonable. No se eliminarán campos heredados hasta migrar datos, actualizar consumidores y cubrir el flujo con pruebas.

## Deuda técnica prioritaria

- `processSplit()` no implementa la división de cuenta.
- Los roles y menús no representan permisos granulares; además, el controlador de usuarios no admite de forma consistente todos los roles existentes en la base.
- `Setting` mezcla datos públicos, preferencias y secretos; las contraseñas SUNAT se guardan y se reinyectan en el formulario sin protección suficiente.
- El envío SUNAT ocurre acoplado al cierre del pedido y puede prolongar o fragilizar la petición.
- La dependencia `greenter/lite` usa una versión de desarrollo y un script posterior modifica `vendor`; debe reemplazarse por una dependencia estable o una integración mantenible.
- El modelo `Order` concentra responsabilidades de pedido, venta, pago y documento tributario.
- El stock combina producto vendible e ingrediente en una misma entidad sin unidades de medida ni lotes claramente modelados.
- Existen problemas de codificación de texto visibles en fuentes y comentarios.
- Backup, restauración y reinicio están expuestos desde el panel y requieren endurecimiento.
- Las pruebas existentes son las plantillas mínimas de Laravel; no protegen los flujos críticos.
- No hay módulo de landing administrable, medios, módulos habilitables, fidelización ni alojamiento.
- No existe aislamiento por sede. Si se requiere multisede dentro de una instalación, debe diseñarse antes de agregar datos operativos por sede.

## Criterios arquitectónicos de aceptación

Una función nueva se considera integrada cuando:

- pertenece a un módulo y tiene un dueño de datos identificable;
- respeta instalación única por cliente y no introduce conceptos de tenant;
- obtiene identidad y opciones desde configuración, sin marcas codificadas;
- aplica autorización del lado servidor;
- protege secretos y desactiva efectos externos en pruebas;
- incluye migración, validación, auditoría cuando corresponda y pruebas del caso feliz y errores principales;
- no obliga a duplicar información entre el dashboard, landing y operación.

