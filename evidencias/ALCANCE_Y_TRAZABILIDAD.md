# Alcance y trazabilidad

La matriz resume la evidencia incluida. Las rutas corresponden al código privado inspeccionado; los archivos originales no se publican en este repositorio.

| Función | Estado observado | Rol principal | Ruta o pantalla | Evidencia fuente | Comprobación pendiente |
|---|---|---|---|---|---|
| Iniciar sesión y crear sesión | Implementado | Usuario registrado | `GET/POST /login` | `LoginController.php:20-35` | Ejecución de casos válidos e inválidos en ambiente aislado |
| Cerrar sesión | Implementado | Usuario autenticado | `POST /logout` | `LoginController.php:38-45` | Verificar invalidación mediante prueba HTTP |
| Aplicar roles y permisos | Implementado | Admin, cajero o mozo según permiso | Rutas protegidas | `CheckRole.php`, `CheckPermission.php`, `config/access.php` | Matriz completa de denegaciones ejecutada |
| Visualizar salón y mesas | Implementado | Usuario con acceso POS | `/pos` | `PosController.php`, `pos/index.blade.php` | Recorrido manual responsive |
| Asignar o retirar mozo | Implementado | Administrador con `dining-room.manage` | `PATCH /tables/table/{table}/waiter` | `AssignTableWaiterRequest.php`, `TableController.php` | Confirmar mensajes y actualización en UI |
| Abrir una mesa | Implementado | Admin, cajero o mozo autorizado | `/pos/table/{table}` | `PosController.php`, `OrderPolicy.php` | Probar mesa ajena y mesa fuera de servicio |
| Seleccionar producto por clic | Parcial | Operador autorizado | `POST /pos/order/{table}/add` | `PosController.php:71-78` | Falta validar explícitamente `product_id` y revalidar producto activo/vendible |
| Agregar por código de barras | Implementado en código | Operador autorizado | Ruta POS de código de barras | `PosController.php:81-99` | Ejecución con código válido, inexistente e inactivo |
| Crear pedido y detalle | Implementado | Actor autorizado por política | Servicio interno | `OrderWorkflowService.php:17-47` | Prueba transaccional aislada sobre MySQL o SQLite de pruebas |
| Calcular total | Implementado | Servicio de pedidos | Servicio interno | `OrderWorkflowService::recalculate()` | Casos de cantidades, precios y concurrencia |

## Reglas reales destacadas

- Administrador y cajero pueden operar pedidos pendientes; un mozo solo puede operar el pedido asignado a su ficha activa.
- La asignación de mesa acepta una ficha de empleado existente y el controlador exige que sea un mozo activo asociado a un usuario con rol `waiter`.
- La creación de pedidos y detalles se ejecuta dentro de una transacción, bloquea mesa y pedido, marca la mesa como ocupada y recalcula el total.
- Los importes del detalle conservan el precio del producto al momento de agregarlo. El total se obtiene de la suma de `quantity × price`.
- Las marcas de fecha y hora provienen de los `timestamps` de Eloquent; su valor efectivo depende de la zona horaria de la aplicación y del momento de persistencia.

Los archivos Markdown y los diagramas del repositorio constituyen la evidencia académica publicada.

