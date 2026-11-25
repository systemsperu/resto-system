# Estrategia de pruebas de RecreoSuite

## Estado de línea base

Auditoría de solo lectura realizada el 13 de octubre de 2026. No se efectuaron envíos a SUNAT ni llamadas a integraciones externas.

- PHPUnit usa SQLite en memoria, correo `array`, cola síncrona y sesión en memoria.
- Solo existen dos pruebas de ejemplo.
- Resultado actual de `php artisan test --stop-on-failure`: una prueba unitaria aprobada y una prueba funcional fallida. La prueba espera `200` en `/`, pero la aplicación responde `302` hacia el inicio de sesión; debe probar explícitamente esa redirección.
- No hay pruebas automatizadas para autenticación, autorización, POS, cocina, inventario, caja, delivery, reservas, restauración, facturación electrónica, notas de crédito ni resúmenes diarios.
- La extensión PHP `soap` no está habilitada en el PHP CLI auditado. `openssl`, `pdo_mysql` y `zip` sí están disponibles.

## Principios

1. Toda prueba automatizada debe usar una base efímera y datos sintéticos.
2. SUNAT, correo, pagos y mensajería deben reemplazarse por dobles de prueba salvo en una ejecución beta deliberada.
3. Ninguna suite normal puede alcanzar endpoints externos.
4. Cada corrección de un defecto debe incorporar una prueba de regresión.
5. Los casos financieros deben verificar tanto el resultado visible como los cambios atómicos en base de datos.
6. Las pruebas nunca deben registrar contraseñas, certificados, XML con datos personales ni claves SOL.

## Matriz priorizada

| Prioridad | Área | Casos mínimos | Tipo | Criterio de aceptación |
|---|---|---|---|---|
| P0 | Autenticación | acceso válido e inválido, cierre de sesión, regeneración de sesión, limitación de intentos | Feature/seguridad | ningún acceso no autenticado; sin fijación de sesión; rate limit activo |
| P0 | Roles | administrador, cajero y personal operativo sobre cada grupo de rutas | Feature | usuarios sin permiso reciben 403 y no alteran datos |
| P0 | Caja | apertura única, cierre, arqueo, venta asociada, concurrencia | Feature/integración | una caja abierta por contexto; totales reproducibles; transacciones atómicas |
| P0 | POS/pedido | mesa, agregar/quitar cantidades, nota, descuento, traslado, cobro | Feature/integración | totales correctos; pedido y stock consistentes ante errores |
| P0 | Cocina | pendiente, preparación, servido; acceso concurrente | Feature | transiciones válidas y trazables; no se pierden detalles |
| P0 | Inventario | receta, descuento al cobrar, ajustes, stock insuficiente, reversión | Feature | kardex cuadra con stock; no hay doble descuento |
| P0 | SUNAT sin red | construcción UBL, firma, nombres de archivo, hash, estados y errores simulados | Unit/integración con fake | cero tráfico externo y XML validado contra esquemas/reglas acordadas |
| P0 | Series | reserva de correlativo concurrente por tipo/serie | Integración | sin duplicados ni reutilización silenciosa |
| P0 | Copias/restauración | autorización, archivo inválido, aislamiento y recuperación | Seguridad/integración | nunca se ejecuta contenido no confiable; restauración verificada en base desechable |
| P1 | Factura/boleta | validaciones de documento, importes, IGV, redondeo, gratuita/exonerada si aplica | Unit/feature | totales contables y tributarios deterministas |
| P1 | Nota de crédito | documento origen, motivo, importes, reintento idempotente | Feature con fake | no duplica ni altera el comprobante origen incorrectamente |
| P1 | Resumen diario | selección de boletas, ticket, consulta y CDR simulado | Feature con fake | solo documentos elegibles; estados coherentes e idempotentes |
| P1 | Archivos SUNAT | descarga XML/CDR, autorización, inexistente, traversal | Seguridad/feature | solo archivos del comprobante y directorio permitido |
| P1 | Delivery | alta, asignación, estados, cancelación, cobro | Feature | transiciones y caja consistentes |
| P1 | Reservas | alta, solapamiento, estado, eliminación y permisos | Feature | reglas de disponibilidad aplicadas |
| P1 | Clientes | CRUD, validación DNI/RUC, privacidad | Feature | validación y acceso restringido |
| P1 | Configuración | marca, moneda, zona horaria, secretos vacíos y carga de archivos | Feature/seguridad | dejar secreto vacío no lo borra; archivo validado por contenido |
| P2 | Carta pública | categorías activas, disponibilidad, precios y responsive | Feature/E2E | solo contenido publicable y consistente con catálogo |
| P2 | Reportes | ventas, caja, inventario, zona horaria y filtros | Feature | cifras reconciliables con datos fuente |
| P2 | UI crítica | escritorio, tableta y móvil; teclado, contraste, errores | E2E/accesibilidad | flujos críticos utilizables sin ambigüedad |

## Capas de prueba propuestas

### Unitarias

- Cálculo de subtotal, IGV, descuento, propina, vuelto y redondeos.
- Conversión de importes a letras.
- Reglas de estados de pedido, cocina, delivery y SUNAT.
- Construcción de comprobantes mediante datos sintéticos.
- Resolución segura de rutas de certificados, XML y CDR.

### Feature e integración local

- Rutas, validación, autorización y persistencia con `RefreshDatabase`.
- Transacciones y bloqueos para caja, stock y correlativos.
- Adaptador SUNAT falso que devuelva aceptación, observación, rechazo, timeout y respuesta corrupta.
- Filesystem falso para certificados, XML y CDR.
- Pruebas de restauración solo en una base creada y destruida para el caso.

### E2E

- Ingreso → apertura de caja → mesa → pedido → cocina → cobro → reporte.
- Delivery completo.
- Emisión simulada y descarga de representación impresa.
- Configuración de marca y reflejo en panel/carta.

### Beta controlada

La prueba contra SUNAT beta queda fuera de la suite normal. Debe ejecutarse manualmente o en un job aislado, con credenciales beta dedicadas, datos ficticios, confirmación explícita y registro de evidencia sanitizada. Nunca se ejecutará automáticamente después de instalar dependencias, migrar o desplegar.

## Datos y ambientes

- `testing`: SQLite en memoria o MySQL efímero cuando se necesiten bloqueos/concurrencia equivalentes a producción.
- `local`: base propia de RecreoSuite, correo en log y adaptador SUNAT deshabilitado por defecto.
- `beta-sunat`: instalación aislada, sin datos reales y sin posibilidad de cambiar a producción desde la interfaz.
- `staging`: copia anonimizada o datos fabricados; ningún secreto productivo.
- `production`: no se ejecutan seeders demo, pruebas destructivas ni comandos de emisión de prueba.

## Puertas de calidad

Antes de integrar cambios:

- Suite completa en verde.
- Sin acceso de red durante pruebas ordinarias.
- Análisis estático y formato aprobados.
- Migraciones comprobadas desde cero y sobre una versión anterior soportada.
- Pruebas P0 obligatorias y cobertura de cada flujo crítico, sin usar el porcentaje como único indicador.

Antes de una versión comercial:

- E2E críticos aprobados en Chrome/Edge y resolución de tableta.
- Ensayo de backup/restore en entorno aislado.
- Reconciliación de caja, ventas, inventario y comprobantes.
- Evidencia beta SUNAT aprobada según `SECURITY_SUNAT.md`.
- Revisión de seguridad sin hallazgos críticos o altos abiertos.

## Primer backlog de automatización

1. Corregir la prueba de `/` para afirmar la redirección esperada y añadir prueba de `/login`.
2. Crear fábricas/seeders sintéticos para roles, catálogo, caja, mesas y pedidos.
3. Cubrir autenticación y matriz de roles.
4. Cubrir el flujo POS-cocina-cobro-inventario-reporte.
5. Introducir una interfaz de transporte SUNAT y un fake sin red.
6. Cubrir correlativos, XML, firma, estados, reintentos e idempotencia.
7. Cubrir acciones destructivas y manejo seguro de archivos.

