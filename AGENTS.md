# Reglas de trabajo de RecreoSuite

## Alcance

RecreoSuite es una aplicación de marca blanca con una instalación y una base de datos independientes por cliente. No es multitenant. PONOA, El Águila u otra marca son configuraciones de despliegue, nunca nombres codificados en el producto.

## Reglas obligatorias

- Trabajar únicamente sobre este repositorio salvo autorización expresa.
- No reutilizar bases, claves, certificados, storage ni `.env` de otros proyectos.
- Mantener `SUNAT_SEND_ENABLED=false` en desarrollo y pruebas automatizadas.
- No ejecutar emisiones, pagos, correos o mensajes externos sin autorización explícita y un ambiente de prueba verificado.
- No guardar contraseñas, claves SOL, certificados, respaldos con datos ni credenciales en Git.
- Escribir comentarios nuevos en español y conservar nombres técnicos necesarios para compatibilidad.
- Toda función nueva debe incluir autorización, validación, migración reversible y pruebas.
- Evitar nombres de clientes en código, migraciones, seeders y recursos visuales.
- No rediseñar pantallas sin revisar `docs/UX_UI.md`.
- Diseñar toda interfaz nueva con enfoque mobile first y validar después tableta y escritorio.
- Mantener acciones táctiles frecuentes con objetivos de al menos 48 x 48 px, separación suficiente y estados visibles.
- No depender de `hover`, clic derecho, teclado físico ni tablas anchas para completar un flujo operativo.
- La capa web debe poder ejecutarse como navegador, PWA instalable y contenedor Capacitor sin duplicar reglas de negocio.
- Registrar decisiones estructurales en `docs/decisions/`.

## Agentes de trabajo

### Arquitectura

Custodia límites entre módulos, decisiones técnicas y compatibilidad entre instalaciones. Revisa `docs/ARCHITECTURE.md` y `docs/MODULES.md`.

### Backend

Implementa dominio, casos de uso, servicios, políticas, colas, integraciones y APIs. No coloca reglas de negocio en vistas o controladores extensos. Es responsable de idempotencia, concurrencia, sincronización y rendimiento del servidor.

### Datos y sincronización

Custodia migraciones, integridad, índices, respaldos y contratos de intercambio. Diseña el registro de cambios, resolución de conflictos y recuperación entre instalación local y nube.

### Frontend

Implementa Blade, JavaScript, componentes, estados de interfaz y consumo de APIs. Mantiene responsive, rendimiento y separación entre presentación y dominio. Trabaja junto al especialista UX/UI, pero no sustituye investigación, diseño ni validación de accesibilidad.

### Móvil, PWA y Android

Custodia la experiencia mobile first, navegación táctil, manifest, service worker, instalación PWA y futura integración con Capacitor/Android. Define límites seguros de funcionamiento sin conexión, permisos nativos, almacenamiento local y pruebas en dispositivos reales. Sigue `docs/MOBILE_PWA.md` y no replica lógica de negocio dentro de la aplicación Android.

### UX/UI

Custodia arquitectura de información, accesibilidad, responsive y sistema visual marca blanca. Sigue `docs/UX_UI.md` y valida WCAG 2.2 AA.

### QA y seguridad

Define regresión, amenazas, aislamiento de integraciones y criterios de salida. Sigue `docs/TESTING.md` y `docs/SECURITY_SUNAT.md`.

### SUNAT

Trabaja detrás del interruptor de envíos. No habilita producción hasta completar el control documentado, obtener aceptación en beta y verificar XML, firma y CDR.

## Flujo mínimo

1. Crear rama desde `develop`.
2. Confirmar alcance y criterio de aceptación.
3. Implementar el cambio mínimo coherente.
4. Ejecutar pruebas del módulo y regresión.
5. Actualizar documentación si cambia una decisión o contrato.
6. Revisar que no haya secretos ni conexiones reales habilitadas.

