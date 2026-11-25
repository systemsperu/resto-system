# Fase 5 — Operación local, respaldo y sincronización cloud

## Resultado implementado

- Modos `cloud` y `local_sync` definidos por instalación.
- UUID estable para instalación y dispositivo, fuera del repositorio.
- Outbox persistente con UUID de evento, versión de agregado, checksum, acuse, reintento exponencial e idempotencia.
- Inbox idempotente para comandos cloud autorizados.
- Entrada cloud inicial limitada a reservas; los conflictos de horario quedan registrados para resolución humana.
- Ventas, caja, inventario y contenido se publican como eventos desde la autoridad local. No existe doble escritura de bases.
- Contraseñas, tokens, sesiones, certificados y secretos se eliminan de los payloads ordinarios.
- Backup SQL cifrado con `APP_KEY`, verificación posterior, retención configurable y adaptador de disco Laravel (`local`, S3 u otro disco configurado).
- Panel administrativo de estado, pendientes, fallos, conflictos y respaldos.
- Comandos `sync:push` y `backup:cloud`, programados sin solapamiento.
- Transporte HTTP autenticado por token y receptor limitado por frecuencia.

## Autoridad y conflictos

- En `local_sync`, restaurante, caja, pagos e inventario tienen autoridad local; la nube recibe una réplica.
- Las reservas originadas en web llegan como comandos identificados. El mismo evento no se aplica dos veces.
- Una reserva sobre una mesa ocupada en una ventana cercana genera un conflicto y no sobrescribe información local.
- Secretos SUNAT, sesiones, archivos temporales y credenciales no forman parte del contrato.

## Operación

El scheduler debe ejecutarse cada minuto mediante `php artisan schedule:run`. `SYNC_ENABLED` permanece en `false` hasta configurar una URL HTTPS y un token distintos por instalación. El disco de backup puede probarse localmente; producción debe usar un bucket exclusivo y privado.

## Validación externa pendiente

No se configuraron credenciales ni un endpoint cloud real. Para cerrar la validación productiva se requieren `SYNC_ENDPOINT`, `SYNC_TOKEN` y un disco privado de almacenamiento del cliente. Esto no bloquea la operación local ni habilita envíos por accidente.
