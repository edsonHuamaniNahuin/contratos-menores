# Backup Automático de Base de Datos

## Comandos

```bash
php artisan db:backup           # Crear backup (omite si ya existe hoy)
php artisan db:restore          # Listar backups (interactivo)
php artisan db:restore archivo.sql  # Restaurar backup específico
```

## Configuración

```env
BACKUP_DIRECTORY=D:/xampp/backups/vigilante-seace
BACKUP_SCHEDULE_AT=03:00
BACKUP_MAX_BACKUPS=7
BACKUP_COMPRESS=false
BACKUP_TIMEOUT=300
BACKUP_MYSQLDUMP_PATH=D:/xampp/mysql/bin/mysqldump.exe
BACKUP_MYSQL_PATH=D:/xampp/mysql/bin/mysql.exe
```

## Comportamiento

| Regla | Descripción |
|---|---|
| 1 backup por día | Si ya existe uno hoy, saltea |
| Máximo 7 backups | El 8vo más antiguo se elimina |
| Solo local/QA | `!app()->environment('production')` |
| Diario 03:00 AM | Configurable con `BACKUP_SCHEDULE_AT` |
| Compresión opcional | vía gzip (Linux) o desactivado (Windows) |

## Schedule

```php
// routes/console.php
if (!app()->environment('production')) {
    Schedule::command('db:backup')
        ->dailyAt(env('BACKUP_SCHEDULE_AT', '03:00'))
        ->timezone('America/Lima');
}
```

## Restauración

```
php artisan db:restore
+---+--------------------------------+----------+------------+
| # | Archivo                        | Tamaño   | Fecha      |
+---+--------------------------------+----------+------------+
| 1 | vigilante_seace_2026-07-17.sql | 36.86 MB | 2026-07-17 |
+---+--------------------------------+----------+------------+
Ingresa el número del backup a restaurar (o 0 para cancelar):
>
```

También se puede restaurar directamente:
```bash
php artisan db:restore "dump-vigilante_seace-202607171113.sql"
```

## Servicio

- **Service:** `app/Services/DatabaseBackupService.php`
- **Comandos:** `app/Console/Commands/DatabaseBackupCommand.php`, `DatabaseRestoreCommand.php`
- **Config:** `config/backup.php`

## Despliegue

El directorio de backups se crea automáticamente durante el deploy con permisos `www-data`. El scheduler service tiene `ReadWritePaths` configurado para escribir en el directorio de backups.
