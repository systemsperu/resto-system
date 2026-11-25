# Despliegue

## Mantenimiento de base de datos

Las operaciones web de backup, restauración y reinicio permanecen bloqueadas hasta configurar simultáneamente:

```dotenv
SYSTEM_MAINTENANCE_ENABLED=true
SYSTEM_MAINTENANCE_DATABASE=nombre_exacto_de_la_base
MYSQLDUMP_BINARY=/ruta/segura/mysqldump
MYSQL_BINARY=/ruta/segura/mysql
```

`SYSTEM_MAINTENANCE_DATABASE` debe coincidir exactamente con la base activa. Las herramientas se ejecutan con argumentos separados y la contraseña se transmite mediante el entorno privado del proceso, nunca concatenada a una línea de shell.

Para comprobar una instalación sin sobrescribir la base activa:

```bash
php artisan maintenance:verify-restore --confirm=VALIDAR
```

El comando crea una base con prefijo restringido `recreosuite_restore_check_`, restaura el respaldo, comprueba tablas esenciales y elimina esa base temporal. Requiere permisos MySQL para crear y eliminar bases y debe usarse solo durante instalación o mantenimiento controlado.

## Modelo

Una instalación independiente por cliente:

- Código versionado de RecreoSuite.
- `.env` y `APP_KEY` exclusivos.
- Base de datos exclusiva.
- Storage y backups exclusivos.
- Dominio, SSL y cron exclusivos.
- Identidad y módulos definidos desde configuración.

## Desarrollo local

1. Copiar `.env.example` a `.env`.
2. Definir credenciales locales sin versionarlas.
3. Crear una base vacía exclusiva.
4. Ejecutar `composer install`, `npm install`, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link` y `npm run build`.
5. Mantener `SUNAT_SEND_ENABLED=false`.

## Producción

No reutilizar el `.env`, base, storage ni certificado de otro cliente. La activación SUNAT se realiza después del despliegue general y exige completar `docs/SECURITY_SUNAT.md`.

