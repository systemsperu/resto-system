# Módulos de RecreoSuite

## Convenciones de estado

- **Operativo:** existe flujo de backend y pantalla utilizable; todavía puede requerir endurecimiento.
- **Parcial:** existe una parte sustancial, pero falta una regla o flujo importante.
- **Base técnica:** hay modelos o servicios reutilizables, sin experiencia completa o validación suficiente.
- **Nuevo:** no existe en el código revisado y debe diseñarse.

## Inventario actual

| Módulo | Estado | Evidencia reutilizable | Brechas principales |
|---|---|---|---|
| Autenticación | Operativo | Inicio/cierre de sesión y middleware `auth` | Recuperación de contraseña, controles de sesión y endurecimiento |
| Usuarios y roles | Parcial | Usuarios y roles `admin`, `cashier`, `waiter`, `kitchen` | Permisos granulares, políticas, personal completo y consistencia de roles |
| Áreas y mesas | Operativo | Mapa, posiciones, estados y relación con pedidos/reservas | Asignación de mozos, QR por mesa, aforo y posible alcance por sede |
| POS y pedidos | Operativo | Pedido por mesa, cantidades, notas, descuentos, propina, traslado y precuenta | División de cuenta, concurrencia, estados formalizados e idempotencia |
| Cocina | Operativo | Monitor y cambio de estado de partidas | Estaciones, tiempos, prioridades, impresión/avisos y trazabilidad |
| Catálogo/carta | Parcial | Categorías, productos, fotos, precios, promoción, destacados y carta pública | Horarios, variantes, modificadores, alérgenos, disponibilidad y PDF de carta |
| Inventario y recetas | Parcial | Stock, costo, ingredientes, ajustes y kardex | Unidades, lotes, compras, proveedores, mermas, almacenes y conteos |
| Caja | Operativo básico | Apertura, cierre, asociación de pedidos/gastos y balance | Arqueo robusto, movimientos, retiros, turnos, caja esperada y conciliación |
| Pagos | Parcial | Método, monto recibido y vuelto | Pago mixto, pasarelas, devoluciones, conciliación y comprobantes del proveedor |
| Clientes | Operativo ampliado | CRUD, historial, preferencias, consentimientos auditados y segmentos | Deduplicación avanzada y políticas de privacidad |
| Reservas de mesa | Operativo básico | Registro, mesa, fecha, personas y estado | Disponibilidad real, bloqueos, canales, recordatorios y anticipo |
| Delivery | Operativo básico | Pedido sin mesa, cliente, dirección, tarifa, repartidor y estados | Zonas, tarifas calculadas, tracking, asignación avanzada y pedidos públicos |
| Ventas y gastos | Operativo básico | Listado, ticket, reporte diario y gastos | Devoluciones, conciliación y separación formal pedido/venta |
| Reportes | Parcial | Indicadores y consultas de ventas por dimensiones básicas | Exportaciones verificadas, márgenes fiables, filtros y analítica transversal |
| Facturación SUNAT | Base técnica avanzada | Series, correlativos, XML UBL, firma, envío, CDR, PDF/QR, notas y resúmenes | Seguridad de secretos, pruebas, colas, aceptación beta demostrada y contingencia |
| Configuración | Parcial | Tabla clave-valor, identidad básica, logo, zona horaria y SUNAT | Modelo por secciones, validación, marca completa, módulos y asistente inicial |
| Mantenimiento | Parcial/riesgoso | Backup, restauración y reinicio | Seguridad, portabilidad, validación, auditoría y ejecución fuera de web |
| Landing administrable | Nuevo | La carta pública sirve como precedente visual y de consulta | Páginas, bloques, navegación, SEO, galerías, formularios y publicación |
| Carta QR por mesa | Nuevo | Carta pública, productos, mesas y flujo POS son reutilizables | Token QR, solicitud, asignación a mozo, confirmación y seguridad |
| Marketing/fidelización | Operativo seguro | Campañas simuladas por consentimiento, cupones, puntos, segmentos y métricas | Conectores externos y automatizaciones, desactivados por defecto |
| Alojamiento | Operativo | Unidades, tarifas, disponibilidad, estadías, huéspedes, pagos, limpieza, mantenimiento y reportes | Landing pública, OTA/channel manager y pagos externos |
| Analítica web | Nuevo | No se identificó implementación | Eventos, consentimiento, fuentes, conversiones y panel |
| Auditoría | Nuevo | Logs técnicos de Laravel no equivalen a auditoría funcional | Registro inmutable de acciones críticas y consulta autorizada |

## Dependencias permitidas

```text
Identidad/configuración <── todos los módulos (solo lectura salvo su propio panel)
Acceso/personal        <── todos los módulos protegidos
Catálogo/inventario    <── restaurante, carta pública, delivery
Clientes               <── restaurante, delivery, alojamiento, marketing
Restaurante            ──> caja/pagos ──> facturación SUNAT
Landing                ──> catálogo, promociones, alojamiento
Alojamiento            ──> clientes, caja/pagos
Reportes               ──> proyecciones de los módulos; no modifica operación
Auditoría               <── acciones críticas de todos los módulos
```

Dependencias que se deben evitar:

- Landing escribiendo directamente pedidos, stock o documentos fiscales.
- Restaurante conociendo credenciales o respuestas internas de SUNAT.
- Facturación decidiendo si un pago está confirmado.
- Alojamiento representado mediante mesas, pedidos o reservas de mesa.
- Configuración genérica como sustituto de entidades operativas.
- Reportes incorporando reglas de escritura o corrección de datos.

## Módulos objetivo

### Identidad y configuración del negocio

Será el primer módulo transversal. Debe administrar nombre comercial, datos legales, ubicación, contactos, horarios, moneda, zona horaria, marca, redes, SEO, opciones operativas y módulos habilitados. Sus datos alimentarán dashboard, landing, carta, tickets, PDFs y mensajes.

El código existente de `SettingController`, `Setting` y `settings/index.blade.php` es reutilizable solo como punto de migración. Se debe separar configuración pública, operativa, tributaria y secreta.

### Acceso, permisos y personal

Debe distinguir cuenta de acceso de ficha de personal. Los permisos se aplicarán mediante middleware/policies y el menú solo reflejará permisos ya concedidos. Roles sugeridos: administrador, gerente, cajero, mozo, cocina, almacén, recepción, limpieza y marketing.

Reutilizable: autenticación, usuarios y middleware actual. A reemplazar: comparaciones de cadenas de rol dispersas y validaciones incompletas.

### Restaurante, POS y cocina

Conserva áreas, mesas, pedidos, partidas y monitor de cocina. Debe evolucionar con estados explícitos, asignación de mozo, división de cuenta real, modificadores, control de concurrencia y eventos auditables.

Reutilizable: modelos `Area`, `Table`, `Order`, `OrderDetail`; controladores de POS y cocina; vistas operativas. Antes de ampliar, es necesario separar conceptualmente pedido, venta y pago.

### Catálogo, recetas e inventario

El catálogo será fuente única para POS, delivery, landing y carta QR. Inventario gestionará ingredientes, unidades, movimientos y costos. Las promociones se modelarán con vigencia y reglas, no únicamente como precio alternativo del producto.

Reutilizable: `Category`, `Product`, tabla pivote de ingredientes e `InventoryLog`. Deben añadirse unidades, almacenes, compras/proveedores y reglas de disponibilidad.

### Caja y pagos

Debe registrar sesiones de caja, movimientos y cobros independientemente del canal de venta. Los pagos podrán ser múltiples y asociarse a pedidos de restaurante, delivery o alojamiento mediante una abstracción común.

Reutilizable: `CashRegister`, `Expense` y controles actuales de apertura/cierre. El método de pago almacenado directamente en `orders` es una limitación que deberá migrarse.

### Facturación electrónica SUNAT

Debe recibir una venta fiscal confirmada y gestionar su propio ciclo: numeración, construcción, firma, envío, respuesta, reintento, consulta y representación. Los estados técnicos no deben mezclarse con el estado del pedido.

Reutilizable: `SunatConfig`, builders, `SunatService`, `DocumentSeries`, `CreditNote`, `DailySummary`, controladores y vistas de consulta. Antes de producción se requiere cifrado de secretos, dependencia Greenter estable, pruebas de XML, prueba beta completa, colas idempotentes y estrategia de contingencia.

### Clientes, campañas y fidelización

El perfil único del cliente debe servir a restaurante, delivery y alojamiento. Marketing añadirá consentimientos, segmentos, cupones, puntos, campañas y atribución. La activación del módulo no debe ser requisito para ventas anónimas.

Reutilizable: `Client` y su historial de pedidos. El resto es desarrollo nuevo.

### Delivery

Mantendrá pedidos, direcciones, tarifa, reparto y seguimiento. Debe consumir catálogo y pagos, y publicar estados sin exponer el panel interno.

Reutilizable: `Delivery`, `DeliveryDriver`, controlador y vistas. Faltan zonas, reglas tarifarias, recepción pública e integración opcional de mapas/mensajería.

### Landing y gestión de contenido

La landing será administrable mediante bloques controlados: portada, historia, servicios, destacados, promociones, carta, galería, alojamiento, testimonios, ubicación, contacto, preguntas y pie. El administrador podrá editar, ordenar, ocultar, previsualizar y publicar; no editar HTML arbitrario.

La landing hereda la identidad del negocio y referencia productos, promociones y alojamientos reales. Debe incluir SEO, metadatos sociales, sitemap, rendimiento, accesibilidad y diseño adaptable. La vista pública `menu` aporta una base de consulta, pero no constituye un CMS.

### Carta digital, PDF y QR por mesa

Debe ofrecer carta pública adaptable, QR general y PDF A4 multipágina. El QR de mesa identifica la mesa mediante un token revocable; el cliente prepara una solicitud, el mozo asignado la revisa y solo su confirmación la envía a cocina.

Reutilizable: catálogo, carta pública, mesas, pedidos y estados de cocina. Son nuevos el flujo de solicitud, notificaciones, tokens, asignación y trazabilidad.

### Alojamiento

Se implementará como dominio separado con tipos, unidades/búngalos, características, imágenes, tarifas, temporadas, restricciones, disponibilidad, reservas, huéspedes, check-in, check-out, limpieza y mantenimiento. Compartirá clientes y pagos mediante interfaces, no tablas del restaurante.

Debe contemplar reservas directas desde la landing, bloqueo transaccional de disponibilidad y calendario operativo. Es desarrollo nuevo.

### Reportes y analítica

Reportes operativos consumirán datos de ventas, caja, inventario, personal, delivery y alojamiento. La analítica web medirá navegación, campañas y conversiones con consentimiento cuando aplique. Se recomienda construir proyecciones o consultas dedicadas y evitar lógica extensa en vistas.

Reutilizable: dashboard y reportes actuales como referencia de indicadores. Requiere redefinir fuentes y métricas después de separar pedido, pago y venta.

## Orden recomendado de construcción

1. Pruebas de regresión y correcciones críticas del sistema heredado.
2. Identidad/configuración, secretos y asistente de instalación.
3. Permisos, personal y auditoría.
4. Separación de pedido, venta, pagos e integración fiscal.
5. Catálogo e inventario ampliados.
6. Landing administrable y gestión de medios.
7. Carta digital, PDF y QR por mesa.
8. Clientes, campañas y fidelización.
9. Delivery avanzado.
10. Alojamiento.
11. Reportes consolidados, analítica y preparación de producción.

## Definición de módulo habilitable

Un módulo puede marcarse como habilitable solo cuando:

- sus rutas y navegación desaparecen al desactivarlo;
- sus tareas programadas e integraciones dejan de ejecutarse;
- desactivarlo no elimina datos ni rompe dependencias obligatorias;
- declara qué módulos necesita;
- conserva autorización del lado servidor aunque esté visible por error;
- tiene pruebas para estado habilitado y deshabilitado.

No todo componente debe ser opcional. Identidad, acceso, configuración, auditoría y una base de clientes compartida forman parte del núcleo.

## Implementación F2.4

El contrato ejecutable vive en `config/modules.php`. Los módulos heredados empiezan activos para conservar instalaciones existentes; landing, alojamiento y marketing empiezan inactivos hasta que sus flujos estén implementados. `ModuleManager` normaliza estados persistidos, impide dependencias huérfanas y evita desactivar un módulo requerido por otro activo. El middleware `module:{clave}` protege las rutas con 404 y el panel de módulos solo modifica `enabled_modules`; no borra tablas, archivos ni registros operativos.
