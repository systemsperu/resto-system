# Cierre de la Fase 8 — Clientes y crecimiento

## Resultado

El cliente existente se mantiene como perfil único para POS, delivery y facturación. Se agregaron fecha de nacimiento, origen, preferencias, última visita, borrado lógico, historial de compras, consentimiento por canal, segmentos y cuenta de fidelización.

## Consentimiento y campañas

Cada autorización o baja crea un registro histórico con canal, origen, fecha y operador. La campaña consulta siempre el último consentimiento del canal y omite clientes sin contacto o sin autorización vigente.

El transporte actual es `fake`: calcula audiencia, crea entregas idempotentes y métricas, pero no envía correos, SMS ni WhatsApp. La activación futura de un proveedor requiere un adaptador específico, credenciales externas y autorización separada. `MARKETING_EXTERNAL_ENABLED=false` permanece como bloqueo.

## Cupones

Los cupones soportan descuento fijo o porcentual, consumo mínimo, vigencia, límite global y límite por cliente. Cada canje requiere una clave idempotente; repetir la misma operación devuelve el canje original sin incrementar usos. El servicio bloquea concurrencia sobre el cupón.

El panel permite crear cupones y registrar un canje manual controlado. La aplicación automática del descuento dentro del cobro POS se reserva para la revisión guiada de ese módulo, para no alterar silenciosamente totales ni comprobantes.

## Fidelización

Cada cliente puede tener una cuenta de puntos con saldo, puntos históricos y nivel base, plata u oro. Ganancias, canjes y ajustes son transacciones inmutables e idempotentes. No se permiten saldos negativos. Cuando el módulo está habilitado, una venta identificada suma un punto por sol entero y actualiza la última visita.

El panel también permite otorgar, canjear o ajustar puntos manualmente, siempre con usuario responsable y auditoría.

## Métricas

El panel muestra clientes totales, recurrentes, contactos aptos simulados, canjes de cupones y adquisición por origen. No usa seguimiento publicitario externo.

## Operación local

El módulo `marketing` quedó habilitado en esta instalación local y depende de `landing`. Se accede desde **Gestión → Marketing**. Las correcciones visuales se harán posteriormente pantalla por pantalla con la guía del implementador.

## Seguridad y pruebas

- rutas de gestión limitadas al administrador;
- bajas respetadas antes de generar entregas;
- datos personales no incluidos en logs de campaña;
- campañas, cupones y puntos idempotentes;
- historial de ventas impide eliminar físicamente un cliente;
- integraciones externas deshabilitadas.
