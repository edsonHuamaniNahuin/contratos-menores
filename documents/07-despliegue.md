# Despliegue (Deploy)

## Pipeline Automático

```bash
./deploy/orchestrate.sh <accion> [opciones]
```

## Acciones

| Acción | Descripción |
|---|---|
| `deploy` | Ciclo completo: pull → deps → migrate → cache → restart |
| `stop` | Detener todos los servicios |
| `start` | Iniciar todos los servicios |
| `restart` | Stop + Start |
| `status` | Estado de todos los servicios |
| `health` | Verificación de salud + auto-recuperación |
| `sync` | Sincronizar service files + daemon-reload |
| `logs` | Últimas líneas de logs de cada servicio |

## Opciones

| Opción | Descripción |
|---|---|
| `--skip-deps` | Omitir composer/pip install |
| `--skip-migrate` | Omitir migraciones |
| `--skip-pull` | Omitir git pull (CD ya hizo pull) |
| `--verbose` | Salida detallada |

## Flujo del Deploy

1. **Git pull** — `main` (prod) o `develop` (QA)
2. **Composer install** — `--no-dev --optimize-autoloader`
3. **Migraciones** — `php artisan migrate --force`
4. **Dependencias Python** — `pip install -r requirements.txt`
5. **Sync service files** — copia a `/etc/systemd/system/`
6. **Cachés Laravel** — config, route, view, event
7. **Permisos** — `storage/`, `tmp/`, directorio de backups
8. **Web server** — restart PHP-FPM + reload Apache
9. **Smart restart** — reinicio uno por uno sin downtime
10. **Health check** — verificación + auto-recuperación

## Entornos

| Entorno | Directorio | Rama | Services suffix |
|---|---|---|---|
| Producción | `/var/www/vigilante-seace` | `main` | (sin sufijo) |
| QA | `/var/www/vigilante-seace-qa` | `develop` | `-qa` |

## Services systemd

| Service | Comando |
|---|---|
| `analizador-tdr` | Python FastAPI puerto 8001 |
| `vigilante-queue` | `php artisan queue:work` |
| `vigilante-scheduler` | `php artisan schedule:work` |
| `telegram-bot` | `php artisan telegram:listen` |
| `whatsapp-bot` | `php artisan whatsapp:listen` |

## Tareas Programadas (Scheduler)

| Tarea | Frecuencia |
|---|---|
| `ImportarTdrNotificarJob` | Cada 2h (06-20) |
| `ImportarContratosDiarioJob` | Diario 02:00 |
| `ImportarContratosMayoresJob` | Cada 90min |
| `NotificarEmailSuscriptoresJob` | Cada 2h (06-20) |
| `subscriptions:expire` | Cada hora |
| `subscriptions:renew` | Cada 6h |
| `subscriptions:alerts` | Diario 08:00 |
| `contratos-mayores:limpiar-cache` | Diario 03:30 |
| `db:backup` | Diario 03:00 (solo QA/local) |
