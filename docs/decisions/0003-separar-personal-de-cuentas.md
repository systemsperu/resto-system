# ADR 0003 — Separar personal de cuentas de acceso

## Estado

Aceptada.

## Decisión

`employees` representa a la persona y su relación laboral. `users` representa únicamente una cuenta autenticable. La relación es opcional y uno a uno: puede existir personal sin acceso y una cuenta administrativa sin ficha laboral durante la transición.

Los turnos laborales viven en `work_shifts`; no se reutilizan aperturas de caja porque una sesión de caja es un hecho financiero y un turno es planificación de personal. Las fichas se desactivan en lugar de eliminarse, preservando turnos y trazabilidad.

## Consecuencias

- Los pedidos y auditorías pueden seguir vinculados a la cuenta que ejecutó una acción.
- La planificación puede incluir limpieza, recepción o almacén sin conceder acceso al sistema.
- La suspensión de acceso y el cese laboral serán operaciones relacionadas, pero no equivalentes.
