# Comandos Artisan Personalizados

## Backup

```bash
php artisan db:backup
php artisan db:restore
php artisan db:restore archivo.sql
```

## Contratos Mayores

```bash
# Importar manualmente (1 página)
php artisan queue:work --once

# Limpiar cache de OCIDs antiguos
php artisan contratos-mayores:limpiar-cache
php artisan contratos-mayores:limpiar-cache --dias=5 --dry-run
```

## Suscripciones

```bash
php artisan subscriptions:expire
php artisan subscriptions:renew
php artisan subscriptions:alerts
```

## Bots

```bash
php artisan telegram:listen
php artisan whatsapp:listen
```

## Desarrollo

```bash
php artisan optimize:clear      # Limpiar todos los cachés
php artisan queue:work           # Procesar cola de jobs
php artisan schedule:work        # Ejecutar tareas programadas (daemon)
php artisan livewire:list        # Listar componentes Livewire
```

### ⚠️  PELIGRO — Solo usar con extrema precaución

```bash
# ¡¡¡ ELIMINA TODAS LAS TABLAS Y DATOS — IRREVERSIBLE !!!
# Solo usar en desarrollo local si tenés backup reciente.
# NUNCA en producción o QA sin confirmar backup previo.
php artisan migrate:fresh --seed
```

