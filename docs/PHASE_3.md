# Fase 3 — Operación restaurante MVP

## Objetivo

Completar el recorrido diario del restaurante sobre dominios explícitos, trazabilidad y una interfaz operable desde móvil, tableta y escritorio.

## Bloques

### F3.1 — Personal, cuentas y turnos

- Separar ficha laboral de cuenta de acceso.
- Vinculación opcional uno a uno con usuarios existentes.
- Personal activo/inactivo sin borrar historial.
- Turnos laborales con validación de rango y superposición.
- Auditoría de altas, cambios de estado y turnos.
- Evolución posterior de roles y permisos para almacén, recepción, limpieza y marketing.

**Estado:** base funcional implementada. Falta ampliar la matriz de roles y completar edición mobile first de fichas.

### F3.2 — Áreas, mesas y asignación de mozos

- Aforo, estados y asignación operativa.
- QR revocable por mesa se diseñará en Fase 4.

**Estado:** base funcional implementada. Las mesas administran aforo, disponibilidad y un mozo activo vinculado; los cambios quedan auditados y el historial impide borrados destructivos. La responsabilidad histórica del pedido y la máquina de estados transaccional se completarán en F3.3.

### F3.3 — Pedido, cocina y estados

- Máquina de estados explícita, estaciones, tiempos y trazabilidad.

**Estado:** base compatible implementada. El pedido separa al usuario creador del mozo responsable; cocina avanza partidas con control de estado esperado, los traslados sincronizan ambas mesas y retirar la última partida cancela sin borrar historial. La confirmación explícita, el estado `ready` y las estaciones especializadas quedan como evolución posterior.

### F3.4 — Caja, venta y pagos

- Separar pedido, venta y pagos; soportar pagos múltiples y cierre consistente.

**Estado:** base funcional implementada. Los pagos son registros independientes, admiten combinación de métodos y conservan los campos legacy del pedido durante la transición. Apertura, cobro y cierre bloquean la caja; el arqueo usa únicamente efectivo confirmado. Reversiones y libro contable completo quedan para una fase financiera posterior.

### F3.5 — Catálogo, modificadores e inventario operativo

- Variantes, modificadores, recetas, unidades, mermas y disponibilidad.

**Estado:** base funcional implementada. El catálogo admite unidades y stock decimal, mínimos, productos sin control de existencias, recetas, mermas justificadas y grupos de modificadores vinculados a productos. El cobro bloquea existencias y revierte toda la venta si falta stock. La selección de modificadores dentro del pedido se integrará con la evolución mobile-first del POS.

### F3.6 — Cierre operativo y regresión

- Recorrido completo mesa → pedido → cocina → cobro → cierre → reporte.

**Estado:** completado. La prueba integral cubre personal, asignación de mesa, pedido, cocina, pago combinado, inventario, cierre de caja, ventas, reportes y auditoría. El detalle de cobertura y límites está en `docs/PHASE_3_CLOSURE.md`.

## Deuda aceptada al entrar

Por indicación del propietario se inició Fase 3 con la puerta de accesibilidad de Fase 2 aún abierta. La deuda está cuantificada en `docs/UX_UI.md` y continúa siendo obligatoria antes de una publicación comercial.
