# ADR 005 — Alojamiento como dominio independiente

## Estado

Aceptada.

## Decisión

Alojamiento tendrá unidades, tarifas, reservas, huéspedes, bloqueos y limpieza propios. Compartirá clientes, cuentas, empleados, auditoría y el registro de pagos del núcleo.

Las reservas de alojamiento no se representarán como pedidos, mesas ni reservas de restaurante. El motor de disponibilidad será un servicio de dominio reutilizable y la reserva bloqueará la unidad durante la validación transaccional.

## Consecuencias

- restaurante y alojamiento pueden activarse de forma independiente;
- un cliente conserva un único perfil e historial transversal;
- los pagos indican su origen y pueden vincularse a un pedido o a una reserva;
- landing y futuras integraciones consultarán disponibilidad sin replicar reglas;
- una integración externa deberá respetar el mismo servicio o un contrato idempotente equivalente.
