# Arquitectura - Vigilante SEACE

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 + PHP 8.2 |
| Frontend | Livewire 4 + Tailwind CSS + Alpine.js |
| Base de Datos | MySQL 8 |
| Colas | Laravel Queue (database driver) |
| Cache | Laravel Cache (file/redis) |
| IA | Python FastAPI + Gemini 2.5 Flash |
| Notificaciones | Telegram Bot API + WhatsApp Cloud API |
| Pagos | MercadoPago |
| Despliegue | systemd + deploy/orchestrate.sh |

## Servicios systemd

| Servicio | Comando | Puerto |
|---|---|---|
| `vigilante-scheduler` | `php artisan schedule:work` | — |
| `vigilante-queue` | `php artisan queue:work` | — |
| `analizador-tdr` | `python main.py` (FastAPI) | 8001 |
| `telegram-bot` | `php artisan telegram:listen` | — |
| `whatsapp-bot` | `php artisan whatsapp:listen` | — |

## Bases de Datos

### Tablas principales
- `contratos` — Contratos Menores (SEACE API pública)
- `contratos_mayores` — Contratos Mayores (OCDS API)
- `users` — Usuarios con sistema de roles/permisos
- `subscriptions` — Suscripciones (MercadoPago)
- `tdr_analisis` — Análisis IA de TDRs (Menores)
- `tdr_analisis_mayores` — Análisis IA de TDRs (Mayores)
- `contrato_seguimientos` — Seguimientos de contratos (Menores)
- `contrato_seguimientos_mayores` — Seguimientos de contratos (Mayores)
- `notified_processes` + `notification_sends` — Dedup de notificaciones
- `whatsapp_subscriptions` — Suscriptores WhatsApp
- `telegram_subscriptions` — Suscriptores Telegram

## Flujo de Datos

```
SEACE API Pública (gob.pe)
  └─ SeaceBuscadorPublicoService
      ├─ Buscador Publico (Livewire)
      ├─ ImportarTdrNotificarJob (notificaciones)
      └─ ImportarContratosDiarioJob (analytics)

OCDS API (oece.gob.pe)
  └─ SeaceMayoresService
      ├─ Buscador Mayores (Livewire)
      └─ ImportarContratosMayoresJob (ingesta)

Python µservice (analizador-tdr)
  └─ AnalizadorTDRService / MayoresTdrService
      └─ TDR Analysis → Gemini IA
```

## Estructura de Directorios

```
app/
├── Livewire/          # Componentes Livewire (UI reactiva)
├── Services/          # Lógica de negocio
│   ├── Tdr/           # Servicios de análisis TDR
│   └── Contratos/     # Analytics contratos
├── Jobs/              # Jobs programados (queue)
├── Models/            # Modelos Eloquent
├── Console/Commands/  # Comandos artisan
└── Contracts/         # Interfaces (DIP)

database/migrations/   # Migraciones
deploy/                # Scripts de despliegue + systemd units
documents/             # Documentación
routes/
├── web.php            # Rutas web
├── api.php            # API (webhooks)
└── console.php        # Tareas programadas (scheduler)
```
