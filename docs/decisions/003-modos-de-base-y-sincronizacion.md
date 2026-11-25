# ADR 003: modos de base y sincronización

## Contexto

Algunos clientes usarán RecreoSuite alojado completamente en internet. Otros requerirán operar en una sola computadora local y conservar una copia o servicios en la nube.

## Decisión

Se soportarán dos modos por instalación:

- nube principal, con una única base MySQL remota;
- local principal, con MySQL local y sincronización opcional hacia una nube exclusiva del cliente.

No se conectarán dos bases mediante replicación artesanal de tablas ni escrituras simultáneas sin autoridad. La sincronización funcional usará eventos `outbox/inbox`, UUID, idempotencia, cursores, acuses y reglas de conflicto por módulo.

## Autoridad inicial

- POS, caja, cocina, inventario y correlativos: autoridad local cuando la instalación sea local-first.
- Landing y contenido público: autoridad configurable, inicialmente dashboard local con publicación cloud.
- Reservas web: origen cloud y recepción local idempotente.
- SUNAT: cola de documentos; nunca se replica un secreto ni se reemite automáticamente un comprobante aceptado.

## Entrega incremental

1. Respaldo cifrado en nube y prueba de restauración.
2. Estado de conectividad y observabilidad.
3. Sincronización unidireccional de lectura/reportes.
4. Entrada cloud controlada para reservas y contenido.
5. Sincronización bidireccional solo donde existan reglas de conflicto probadas.

## Consecuencias

La operación offline es posible, pero añade complejidad significativa. Debe construirse después de estabilizar identificadores, auditoría, estados y pruebas transaccionales del sistema principal.
