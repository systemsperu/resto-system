# Cierre técnico de la Fase 7 — SUNAT

## Resultado

La facturación heredada quedó separada del cobro y protegida contra dobles envíos. Facturas, boletas, notas de crédito y resúmenes diarios se programan mediante trabajos de la cola `sunat`; la venta no depende de la latencia del servicio externo.

Cada envío conserva una clave idempotente, estado, cantidad de intentos, último intento y resultado. Un documento aceptado no vuelve a enviarse. Los rechazados no forman parte de los reintentos automáticos; requieren revisar la causa y decidir el tratamiento tributario correcto.

## Modos

- `disabled`: valor predeterminado; no existe comunicación externa.
- `fake`: genera evidencia sintética para pruebas sin utilizar red.
- `beta`: reservado para una prueba autorizada con credenciales beta.
- `production`: requiere `SUNAT_SEND_ENABLED=true`, `SUNAT_MODE=production`, `SUNAT_PRODUCTION_ENABLED=true` y `APP_ENV=production`.

Los endpoints provienen de la lista interna de Greenter. El dashboard no puede inyectar una URL externa.

## Dependencias y entorno

- Greenter Lite, WS, XML y Core 5.3.0 estables.
- Eliminado el script que modificaba archivos de `vendor`.
- Extensiones PHP verificadas: SOAP, OpenSSL y ZIP.
- Certificados almacenados en el disco privado de Laravel.
- El comando `php artisan sunat:cert:demo` funciona en Laragon y localiza `openssl.cnf` de forma explícita.

## Operación local

El worker fiscal se inicia con:

```text
php artisan queue:work --queue=sunat,default --tries=3
```

En desarrollo debe conservarse:

```text
SUNAT_SEND_ENABLED=false
SUNAT_MODE=disabled
SUNAT_PRODUCTION_ENABLED=false
```

## Controles cubiertos

- validación de RUC para factura y DNI opcional para boleta;
- reserva transaccional de series y correlativos;
- almacenamiento privado de XML, CDR y certificados;
- descargas restringidas al prefijo `sunat/`;
- errores internos fuera de los mensajes del navegador;
- auditoría sin contraseñas ni claves privadas;
- reintentos con backoff y límite;
- transporte fake, bloqueo de producción e idempotencia probados.

## Bloqueo externo pendiente

No se realizó una emisión real. Para obtener aceptación beta se necesitan usuario y clave SOL beta, RUC de prueba y autorización expresa para comunicarse con SUNAT. Después deben ejecutarse y conservarse, sin datos personales, los CDR de una factura, boleta, nota de crédito y resumen diario.

Hasta completar esa evidencia, la fase queda cerrada técnicamente pero no certificada para producción.
