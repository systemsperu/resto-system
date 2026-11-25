# Base de datos

## Convención

Cada instalación usa una base exclusiva. Desarrollo local utiliza `recreosuite_dev`; pruebas automatizadas deben utilizar una base efímera o transacciones aisladas.

## Fuente de verdad

Las migraciones Laravel son la fuente canónica del esquema. Los archivos SQL históricos sirven solo como evidencia y recuperación controlada; nunca deben importarse directamente sobre una base existente.

## Reglas

- Toda migración debe ser reversible y probarse sobre una base vacía.
- No insertar marcas o datos fiscales reales en migraciones.
- Los seeders deben ser repetibles y contener solo datos ficticios.
- Certificados, claves y respaldos con datos quedan fuera de Git.
- Los cambios destructivos requieren backup verificado y ventana de mantenimiento.
- Importes monetarios usan `decimal`; no usar coma flotante para persistencia.
- Series y correlativos requieren bloqueo transaccional e idempotencia.

## Bases locales conocidas

- `recreosuite_dev`: desarrollo activo de RecreoSuite.
- Las bases con prefijo `restaurante` pertenecen a proyectos de referencia y no deben modificarse desde este repositorio.

