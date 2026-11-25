# ADR 004: Mobile first, PWA y Capacitor

## Estado

Aceptada.

## Contexto

RecreoSuite será usado por mozos, cocina, caja, administración y clientes desde teléfonos, tabletas y computadoras. También deberá distribuirse como PWA y, más adelante, como APK Android sin mantener aplicaciones funcionalmente distintas.

## Decisión

- Diseñar primero para pantallas pequeñas y uso táctil; ampliar progresivamente para tableta y escritorio.
- Mantener una sola base web y una sola lógica de negocio del lado servidor.
- Usar PWA como primera etapa de instalación y Capacitor como contenedor Android cuando los flujos web estén estabilizados.
- Encapsular capacidades nativas detrás de adaptadores y conservar alternativas web cuando sean viables.
- Tratar el funcionamiento offline como una capacidad de dominio controlada, no como caché indiscriminada.
- Exigir validación en dispositivos reales además de emuladores y herramientas responsive.

## Consecuencias

Las pantallas heredadas deberán auditarse y migrarse gradualmente a componentes táctiles. La adopción de Capacitor no se realizará antes de definir autenticación, actualización, almacenamiento seguro, sincronización e idempotencia. Los builds por cliente podrán cambiar marca, dominio e iconos, pero no contendrán credenciales ni bifurcaciones del producto.
