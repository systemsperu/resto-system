# Cierre de Fase 3

## Recorrido validado

La regresión integral ejecuta el recorrido operativo con datos sintéticos:

1. registra una ficha de mozo y la vincula con su cuenta;
2. crea un área y una mesa, asignando al mozo responsable;
3. abre el pedido y ocupa la mesa;
4. cocina avanza la partida de pendiente a preparación y servida;
5. caja cobra con efectivo y tarjeta dentro de una sola transacción;
6. el inventario se descuenta una vez y la mesa vuelve a estar disponible;
7. el arqueo incluye únicamente el tramo pagado en efectivo;
8. ventas y reportes reflejan la operación completada;
9. los cambios críticos quedan en la auditoría.

## Límites conservados

- SUNAT permanece con transporte real deshabilitado en pruebas.
- Los modificadores ya se administran, pero todavía no se seleccionan dentro de la partida POS.
- La confirmación explícita previa a cocina y el estado `ready` son evoluciones pendientes.
- La regresión automatizada cubre reglas y transacciones; las carreras reales requieren pruebas adicionales sobre MySQL con conexiones simultáneas.
- La deuda de accesibilidad heredada de Fase 2 continúa abierta antes de una publicación comercial.
