# Seguridad y habilitación de SUNAT

## Estado posterior a la Fase 7

La implementación técnica fue endurecida el 20 de octubre de 2026. Los secretos están cifrados, no regresan al navegador, el transporte está deshabilitado por defecto, producción exige doble habilitación y entorno productivo, y los comprobantes usan una cola dedicada con clave idempotente, reintentos limitados y auditoría. PHP CLI dispone de SOAP, OpenSSL y ZIP. Greenter fue actualizado de la rama inestable 2.0 de 2020 a la versión estable 5.3.0 y se eliminó el parche directo sobre `vendor`.

La suite normal utiliza transporte deshabilitado; las pruebas fiscales usan `SUNAT_MODE=fake` sin red. La aceptación real en SUNAT beta sigue pendiente porque no se han proporcionado credenciales beta ni se ha autorizado un envío externo. Ningún punto de este documento autoriza producción.

## Protección local de credenciales

Las claves `sunat_sol_user`, `sunat_sol_pass` y `sunat_cert_password` se guardan cifradas con la `APP_KEY` propia de la instalación. No deben consultarse mediante `pluck()` genérico ni enviarse a vistas. `Setting::decryptedValues()` queda reservado a servicios internos que necesitan consumirlas y `Setting::publicValues()` se usa en presentación, tickets y reportes.

Los formularios siempre dejan vacíos los campos secretos. Vacío significa “conservar”; para cambiar una credencial se escribe un valor nuevo. La aplicación no ofrece recuperación del secreto en texto plano.

La `APP_KEY` debe respaldarse por separado y con control de acceso. Rotarla exige un procedimiento de recifrado; reemplazarla directamente vuelve ilegibles las credenciales existentes.

## Mitigación inicial aplicada

La fundación de RecreoSuite incorporó `SUNAT_SEND_ENABLED=false` y un bloqueo central en `SunatService` para facturas, boletas, notas de crédito, resúmenes y consultas de ticket. Existe una prueba automatizada que verifica el bloqueo. Este control reduce la posibilidad de envíos accidentales, pero no reemplaza los demás requisitos P0 de este documento.

## Alcance y conclusión

Auditoría estática de la implementación local al 13 de octubre de 2026. No se leyeron ni imprimieron secretos, no se envió ningún comprobante y no se realizaron conexiones a SUNAT.

La implementación contiene construcción y firma de documentos, transporte Greenter, persistencia de XML/CDR, notas de crédito, resúmenes y reintentos. Aun así, **SUNAT no debe habilitarse todavía**, ni en beta compartida ni en producción, hasta cerrar las barreras P0 de este documento.

## Riesgos priorizados

| Severidad | Hallazgo | Evidencia técnica | Impacto | Acción requerida |
|---|---|---|---|---|
| Crítica | Secretos SUNAT almacenados en texto plano | `Setting` persiste `value` sin cifrado; `SunatConfig` lee contraseña SOL y contraseña PFX directamente | exposición de credenciales y firma | mover secretos a almacenamiento cifrado; rotar cualquier secreto previamente persistido |
| Crítica | Secretos se devuelven al navegador | formulario de configuración rellena `value` de contraseña SOL y PFX | filtración por HTML, navegador, extensiones o capturas | nunca hidratar secretos; campo vacío significa “conservar”; acción separada para reemplazar |
| Crítica | Emisión externa sin interruptor central seguro | controladores y comandos llaman directamente a `SunatService`; el comando de prueba solo pide confirmación si detecta producción | emisión accidental, correlativos consumidos y efectos tributarios | añadir `SUNAT_TRANSPORT_ENABLED=false`, allowlist por ambiente y bloqueo duro de producción |
| Alta | Restauración SQL mediante shell | `SystemController` construye comandos `mysql`/`mysqldump` con valores de entorno y ejecuta SQL subido | inyección de comandos/SQL, destrucción total, exposición de clave en lista de procesos | retirar del panel o ejecutar mediante servicio restringido, argumentos sin shell, validación y entorno de restauración separado |
| Alta | Archivo de credenciales versionado | Git rastrea un archivo denominado `Credenciales de acceso al Sistema.txt` | divulgación de accesos al distribuir/clonar | eliminarlo del historial, rotar credenciales y sustituir por instrucciones sin secretos |
| Alta | Dependencia no estable y parche directo a `vendor` | `greenter/lite` usa `2.0.x-dev`; scripts Composer modifican código instalado | builds no reproducibles, cambios tributarios inesperados | fijar versiones/commit revisado o mantener fork; verificar hashes y pruebas de contrato |
| Alta | Reintentos incluyen rechazados por defecto | comando reintenta `PENDING`, `ERROR` y `REJECTED` | reenvíos inválidos o repetidos sin corregir causa | política por código/estado; idempotencia; aprobación explícita para rechazados |
| Alta | Sin pruebas tributarias ni de autorización | solo hay pruebas de ejemplo y la suite actual falla | regresiones silenciosas y acceso incorrecto | implementar matriz P0 de `TESTING.md` antes de habilitar transporte |
| Alta | Certificado validado solo por extensión | carga acepta `.pfx`/`.p12` por nombre y tamaño | archivo inválido/malicioso y fallos posteriores | verificar MIME/contenido PKCS#12, clave privada, vigencia, RUC y permisos de archivo |
| Alta | Extensión SOAP ausente en CLI | auditoría de módulos PHP no encontró `soap` | envío fallará en runtime/worker | habilitar y verificar SOAP en cada runtime PHP usado por web, CLI y colas |
| Media | Descarga no restringe explícitamente el prefijo | se comprueba existencia de la ruta persistida, no que pertenezca a `sunat/` | lectura de otro archivo local si la ruta almacenada se altera | normalizar y exigir prefijo/directorio permitido; asociar archivo al comprobante |
| Media | Mensajes internos llegan a la UI | excepciones de reintento/restauración se muestran al usuario | fuga de rutas y detalles operativos | mensaje genérico al usuario y detalle sanitizado en log restringido |
| Media | Sin rate limit visible en login/reintentos | rutas no declaran throttling | fuerza bruta o abuso de acciones costosas | rate limiting, bloqueo progresivo y auditoría |
| Media | Configuración demo tiene valores por defecto | RUC/usuario/clave demo se resuelven desde código | falsa sensación de configuración válida | estado “no configurado”; validación explícita por ambiente |
| Media | `APP_DEBUG=true` en ejemplo | `.env.example` habilita depuración | despliegues pueden exponer trazas y configuración | ejemplo seguro con debug falso y checklist de producción |
| Media | Sin auditoría inmutable suficiente | acciones SUNAT/configuración no muestran un registro dedicado de quién/cuándo | baja trazabilidad | registrar actor, IP, cambio de ambiente, hash, intento, respuesta y correlación sin secretos |

## Controles de diseño obligatorios

### Secretos y certificados

- Cifrar valores sensibles con una clave separada y gestionada fuera de la base; no limitarse a ocultarlos visualmente.
- No incluir secretos en vistas, logs, excepciones, backups descargables ni comandos visibles en procesos.
- Guardar certificados fuera del directorio público con permisos mínimos.
- Validar PKCS#12, vigencia, titular/RUC y presencia de clave privada al cargarlo.
- Permitir reemplazo y rotación, nunca recuperación del secreto en claro desde el dashboard.
- Cifrar backups y excluir/gestionar por separado los secretos.

### Separación de ambientes

- Beta y producción deben tener variables, credenciales, certificados, bases y colas independientes.
- El ambiente efectivo debe mostrarse claramente en el dashboard, pero no debe poder cambiarse con un simple campo en producción.
- Producción exige dos condiciones: configuración de despliegue autorizada y habilitación operativa registrada.
- El endpoint debe provenir de una allowlist interna; nunca aceptar una URL arbitraria desde formulario/base.
- Los comandos `sunat:test` y emisión demo deben estar imposibilitados en producción, no solo sujetos a confirmación interactiva.

### Transporte e idempotencia

- Encapsular Greenter detrás de una interfaz con implementaciones `fake`, beta y producción.
- Registrar un identificador idempotente por RUC-tipo-serie-correlativo y bloquear envíos simultáneos.
- No reenviar un documento aceptado. Un rechazo solo se reintenta si el código admite reenvío sin emitir un documento sustituto.
- Aplicar timeouts, backoff, límite de intentos y cola dedicada.
- Ante timeout, consultar estado antes de reenviar para evitar duplicidades.
- Conservar XML firmado y CDR de forma íntegra, con hash y política de retención/backup.

### Autorización y auditoría

- Separar permisos para configurar SUNAT, cargar certificado, emitir, reintentar, crear nota de crédito, generar resumen y descargar archivos.
- Exigir reautenticación y segundo factor para cambiar ambiente, certificado o credenciales.
- Registrar actor, fecha/hora Lima, IP, comprobante, transición, código y hash; nunca contraseña ni clave privada.
- Alertar por rechazos repetidos, saltos de correlativo, uso de producción y cambios de certificado.

## Criterios para habilitar SUNAT beta

Todos son obligatorios:

- [ ] Transporte deshabilitado por defecto y fake usado por la suite normal.
- [ ] Ambiente beta fijado fuera del dashboard y endpoint validado por allowlist.
- [ ] Credenciales beta dedicadas y certificado de prueba almacenados de forma cifrada.
- [ ] Ningún secreto aparece en HTML, logs, Git, backups ni salida de comandos.
- [ ] SOAP, OpenSSL y ZIP verificados en web, CLI y worker.
- [ ] Dependencias Greenter fijadas en versiones/commit auditados; build reproducible sin parche opaco de `vendor`.
- [ ] Pruebas P0 de XML, firma, correlativos, estados, autorización e idempotencia en verde.
- [ ] Validación de RUC, serie, documentos de cliente, IGV, redondeo y zona horaria.
- [ ] Base y datos exclusivamente sintéticos.
- [ ] Operador autorizado ejecuta un caso controlado de boleta, factura, nota de crédito y resumen cuando corresponda.
- [ ] Se obtiene y valida CDR; XML/CDR quedan ligados al comprobante con hash.
- [ ] Se documentan códigos, observaciones y rechazos sin datos personales.
- [ ] Existe procedimiento probado para timeout, consulta, reintento y contingencia.

## Criterios para habilitar producción

Además de todos los criterios beta:

- [ ] Revisión funcional/tributaria por especialista responsable y reglas vigentes confirmadas.
- [ ] Cero hallazgos críticos o altos abiertos.
- [ ] Credenciales SOL secundarias con mínimo privilegio; secretos beta no reutilizados.
- [ ] Certificado productivo vigente, validado contra el RUC y con alertas de expiración.
- [ ] Series/correlativos aprobados, únicos, respaldados y sometidos a prueba de concurrencia.
- [ ] Lista explícita de usuarios autorizados, MFA y reautenticación para operaciones sensibles.
- [ ] Backups cifrados, restauración ensayada y retención de XML/CDR definida.
- [ ] Monitoreo, alertas, cola fallida y procedimiento de conciliación diaria activos.
- [ ] Despliegue con `APP_ENV=production`, `APP_DEBUG=false`, HTTPS y cookies seguras.
- [ ] Prueba de humo sin emisión seguida de una primera emisión supervisada y conciliada en SUNAT.
- [ ] Plan documentado de rollback que no reutilice correlativos ni elimine comprobantes ya emitidos.

## Protocolo de primera activación

1. Congelar versión y respaldar base/configuración cifrada.
2. Verificar reloj, zona horaria, RUC, razón social, dirección, series y certificado sin imprimir secretos.
3. Mantener workers detenidos y confirmar que no existan pendientes heredados.
4. Habilitar transporte para un único operador y ventana controlada.
5. Emitir un comprobante de importe pequeño y datos legítimos autorizados.
6. Confirmar estado en SUNAT, CDR, hash, PDF y conciliación contable.
7. Si falla, deshabilitar transporte; consultar antes de reenviar.
8. Solo después de evidencia aceptada, habilitar la cola con límites y alertas.

## Evidencia mínima por liberación

- Versión exacta del código y dependencias.
- Resultado de pruebas y análisis de seguridad.
- Ambiente/endpoints efectivos sin secretos.
- Casos beta y CDR sanitizados.
- Validación de certificado y fecha de vencimiento.
- Conciliación de series/correlativos.
- Aprobador técnico y responsable tributario.
- Fecha, decisión y plan de reversión.

