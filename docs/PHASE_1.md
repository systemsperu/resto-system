# Fase 1 — Registro de ejecución

## Estado

En curso. Inicio: 13 de octubre de 2026.

## Avance F1.1

- Incorporado un contrato central de capacidades por rol en `config/access.php`.
- Incorporado middleware `permission` y método `User::hasPermission()`.
- Agregado el rol operativo `kitchen` a usuarios, acceso y redirección de inicio.
- Caja restringida a administrador/cajero; el mozo conserva toma de pedidos sin caja.
- Cocina separa visualización de cambio de estado.
- Eliminación de reservas y administración de repartidores quedan reservadas al administrador.
- Checkout exige caja abierta para impedir ventas con `cash_register_id` nulo.
- Navegación oculta enlaces que el usuario no puede abrir.
- Carta digital vuelve a permitir zoom del navegador.

## Avance F1.2

- Agregar el primer producto marca la mesa como ocupada.
- El checkout requiere método de pago y tipo de documento válidos.
- Un cobro en efectivo se rechaza cuando el monto recibido es insuficiente.
- No se permite cerrar la venta mientras existan partidas pendientes o en preparación.
- Una venta completada queda asociada a la caja abierta, descuenta inventario y libera la mesa.
- Los tickets internos usan `NOT_APPLICABLE` en lugar de un estado SUNAT nulo incompatible con el esquema.
- Cocina ya no muestra partidas pendientes pertenecientes a órdenes cerradas.
- El flujo está cubierto por pruebas con base de datos aislada; SUNAT continúa deshabilitado.
- Una policy de dominio impide que un mozo consulte o modifique pedidos asignados a otro mozo.
- La creación del primer pedido bloquea la fila de la mesa para reducir órdenes paralelas sobre la misma mesa.
- El cobro bloquea la orden y vuelve a comprobar estado y cocina dentro de la transacción.
- Un segundo intento de cobro no descuenta nuevamente el inventario.
- Cocina solo admite `pending → cooking → served` en órdenes activas.
- Delivery solo admite transiciones progresivas; el estado `delivered` se obtiene mediante checkout y no desde el cambio genérico de estado.

## Avance F1.3

- Usuario SOL, clave SOL y contraseña del certificado se almacenan cifrados con la `APP_KEY` de la instalación.
- La migración convirtió los valores locales existentes sin mostrarlos ni sustituirlos.
- Los secretos quedan excluidos de las colecciones utilizadas por formularios, tickets y reportes.
- El formulario nunca devuelve una credencial guardada; un campo vacío conserva el valor anterior.
- La actualización de configuración usa una lista blanca y validación por campo, en lugar de guardar cualquier dato recibido.
- La ruta interna del certificado ya no se muestra en el dashboard y el archivo continúa en almacenamiento privado.
- No puede seleccionarse ambiente SUNAT productivo mientras `SUNAT_SEND_ENABLED=false`.
- El correo local utiliza `log`; no se detectaron pasarelas de pago, mensajería ni otros transportes externos implementados.
- La `APP_KEY` pasa a ser parte indispensable del respaldo: perderla impide descifrar las credenciales de esa instalación.

## Avance F1.4

- Backup y restore ya no construyen comandos de shell ni incluyen la contraseña en los argumentos del proceso.
- Cada instalación debe habilitar mantenimiento y declarar una única base autorizada; cualquier diferencia bloquea la operación.
- Los respaldos se generan con nombre aleatorio dentro de almacenamiento privado.
- Backup, restore y reset requieren contraseña actual, frase exacta con el nombre de la base y límite de intentos.
- Restore limita tamaño, exige extensión SQL, rechaza binarios y valida una cabecera SQL reconocible.
- Antes de restore o reset se genera automáticamente un respaldo privado de seguridad.
- Reset usa eliminaciones ordenadas dentro de una transacción en lugar de desactivar claves foráneas globalmente.
- Los resultados quedan registrados en `system_audits` sin contraseñas ni contenido SQL.
- Se generó correctamente un respaldo real de `recreosuite_dev` en almacenamiento privado.
- La restauración se verificó sobre una base temporal aislada; se comprobaron tablas esenciales y la base temporal se eliminó al finalizar.

## Línea base verificada

- Laravel expone 112 rutas.
- La suite inicial pasa: 3 pruebas y 5 aserciones.
- La autorización usa una columna `role` y el middleware `role`; todavía no existen permisos granulares ni policies de dominio.
- La línea base reconocía `admin`, `cashier` y `waiter`; durante F1.1 se agregó el rol `kitchen` de forma coherente en usuarios, acceso e inicio de sesión.
- El POS exige caja abierta únicamente a `admin` y `cashier`; el rol `waiter` puede ingresar sin caja.
- SUNAT permanece bloqueado mediante `SUNAT_SEND_ENABLED=false`.

## Matriz actual de acceso

| Área | Acceso actual | Observación |
|---|---|---|
| Dashboard, configuración, usuarios, productos y mesas | Admin | Protección global por rol |
| Facturación, notas de crédito y resúmenes | Admin | El envío además debe permanecer bloqueado por configuración |
| Ventas, cobro, gastos y delivery | Admin, cashier | Agrupación por rol |
| POS: visualización | Admin, cashier, waiter | Caja obligatoria solo para admin/cashier |
| POS: modificar pedido, traslado, división e ítems | Admin, cashier, waiter | Falta incorporar propiedad/asignación del pedido |
| POS: descuento | Admin, cashier | Protegido por capacidad específica |
| Cocina: lectura | Admin, cashier, waiter, kitchen | Lectura separada de la transición de estado |
| Cocina: cambio de estado | Admin, kitchen | Falta formalizar la máquina de estados |
| Reservas: crear y cambiar estado | Admin, cashier, waiter | Eliminar queda reservado al administrador |
| Apertura y cierre de caja | Admin, cashier | Checkout exige además una caja abierta |
| Carta `/menu` | Público | Correcto como intención; falta endurecer caché y privacidad |
| Archivos `/storage/{path}` | Público | Revisar que solo exponga medios públicos y nunca documentos fiscales o respaldos |

## Riesgos iniciales priorizados

1. La primera capa por capacidad ya existe; todavía faltan policies de propiedad/asignación sobre pedidos y detalles.
2. Cocina necesita una máquina de estados explícita y pruebas de transición.
3. Traslado, división y modificación de pedidos requieren validación de estado además del permiso de rol.
4. Apertura/cierre de caja y reservas todavía necesitan auditoría.
5. `greenter/lite` usa una versión de desarrollo y Composer ejecuta `scripts/patch-greenter.php` sobre `vendor`.
6. Los seeders contienen credenciales de demostración conocidas; deben limitarse a desarrollo y permitir configuración segura.
7. Los archivos revisados son UTF-8; la representación dañada detectada inicialmente correspondía a la página de códigos de PowerShell, no al contenido almacenado.

## Bloques de trabajo

### F1.1 — Contrato de acceso

- Definir permisos por capacidad, sin ligar reglas a elementos visibles del menú.
- Crear matriz objetivo por rol.
- Cubrir rutas públicas, autenticadas, financieras y administrativas con pruebas.

### F1.2 — Recorrido operativo protegido

- Preparar fixtures de caja, mesas, productos y pedidos.
- Probar mesa → pedido → cocina → cobro → reporte.
- Añadir pruebas de doble envío, estados inválidos y usuarios sin permiso.

### F1.3 — Secretos e integraciones

- Separar configuración pública, privada y secretos.
- Cifrar secretos en reposo y enmascararlos en formularios.
- Mantener pruebas sin transporte SUNAT, correo, pagos ni mensajes reales.

### F1.4 — Mantenimiento y archivos

- Validar backup/restore en base desechable.
- Restringir archivos, extensiones, tamaño y ubicación.
- Incorporar confirmación reforzada y auditoría para reset/restore.

### F1.5 — Dependencias y calidad

- Sustituir el parche de Greenter por una solución versionada mantenible.
- Corregir UTF-8 de manera controlada.
- Ejecutar análisis, formato, pruebas y compilación antes de cerrar la fase.

## Avance F1.5

- Bootstrap, Bootstrap Icons y Chart.js se empaquetan con Vite; se retiraron las cargas duplicadas desde CDN y la imagen remota de la pantalla de acceso.
- `npm audit` queda sin vulnerabilidades conocidas después de actualizar el árbol compatible.
- La compilación de producción es reproducible sin descargar recursos de presentación durante el uso.
- El cambio de `@import` a `@use` eliminó la deprecación propia; permanecen avisos originados dentro de Bootstrap 5 y no bloquean la compilación.
- La codificación real de código y vistas se verificó como UTF-8; no se aplicaron reemplazos masivos que pudieran dañar contenido válido.
- Greenter permanece fijado a una rama de desarrollo antigua y requiere el parche heredado. Se intentó migrar a parches administrados por Composer, pero el equipo no pudo descargar el plugin y PHP CLI no tiene `ext-soap`. SUNAT continúa deshabilitado; este punto queda bloqueado hasta habilitar SOAP y disponer de acceso al repositorio del plugin o actualizar Greenter.
- La suite completa pasa con 24 pruebas y 72 aserciones; la compilación Vite de producción también pasa.
- Pint identifica deuda de formato heredada en 83 de 130 archivos. No se ejecutó una reescritura masiva para evitar mezclar cambios cosméticos con la estabilización; será reducida incrementalmente al tocar cada módulo.

## Cierre de fase

La base operativa queda estabilizada para iniciar el sistema de marca blanca. No quedan integraciones externas activas ni vulnerabilidades conocidas en el árbol npm. La certificación SUNAT, la actualización de Greenter y `ext-soap` se mantienen explícitamente fuera de operación y se resolverán antes de la Fase 7.

## Evidencia de salida requerida

- Matriz de permisos objetivo aprobada e implementada.
- Pruebas de autorización y recorrido operativo en verde.
- Reporte de secretos y mantenimiento sin riesgos críticos abiertos.
- Compilación frontend y suite completa reproducibles.
- Demostración manual documentada con datos de prueba.
