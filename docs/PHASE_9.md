# Cierre de la Fase 9 — Alojamiento y búngalos

## Resultado

El alojamiento funciona como módulo independiente del restaurante. Comparte clientes, usuarios, auditoría y pagos, pero no reutiliza mesas, pedidos ni reservas de mesa.

## Capacidades

- tipos y unidades de alojamiento con capacidad, servicios, ubicación y medios;
- tarifas por temporada, noches mínimas y días habilitados;
- consulta pública de disponibilidad y cotización sin datos personales;
- reservas con bloqueo transaccional de la unidad para evitar solapamientos;
- huéspedes, anticipos, entrada, salida y cancelación;
- bloqueos por mantenimiento, uso interno u otros motivos;
- tareas de limpieza generadas al registrar la salida;
- perfiles separados de recepción y limpieza;
- indicadores operativos y reporte CSV por periodo.

## Pagos compartidos

La tabla `payments` admite ahora un origen `restaurant` o `lodging`. Un pago pertenece a un pedido o a una reserva de alojamiento. El módulo impide registrar anticipos superiores al saldo.

## Disponibilidad

`AvailabilityService` es el contrato reutilizable para dashboard, landing y futuras integraciones. Excluye reservas confirmadas o alojadas y bloqueos vigentes, valida capacidad, noches mínimas, temporada y días habilitados. La creación de la reserva bloquea la unidad dentro de una transacción y vuelve a consultar disponibilidad.

## Operación local

El módulo `lodging` está preparado para habilitarse desde **Configuración → Módulos**. El panel se encuentra en `/lodging` y la consulta pública en `/alojamiento/disponibilidad`.

No se cargaron tarifas ni huéspedes ficticios en la base local: el administrador debe registrar los datos reales del establecimiento.

## Límites deliberados

- no hay conexión con OTA, channel manager ni pasarela externa;
- los pagos se registran, no se procesan externamente;
- la publicación visual de unidades en la landing se conectará durante la revisión guiada del sitio;
- el diseño visual detallado queda para la revisión por módulos solicitada por el implementador.
