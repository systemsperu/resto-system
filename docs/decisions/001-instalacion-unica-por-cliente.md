# ADR 001: instalación única por cliente

## Decisión

RecreoSuite no será multitenant. Cada cliente tendrá despliegue, dominio, base, storage, claves e integraciones independientes.

## Motivo

Reduce riesgo de fuga de datos, simplifica operación local y permite que cada negocio habilite módulos y marca propios.

## Consecuencia

El código permanece común y versionado; no se crean forks por cliente. Las diferencias se expresan mediante configuración y módulos.

