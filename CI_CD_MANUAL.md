# 🚀 CI/CD - Vigilante SEACE

> **Stack:** Laravel 12 + Python FastAPI  
> **Repositorio:** GitHub  
> **VPS:** Elastika  
> **Última actualización:** 11 de febrero de 2026

---

## 📋 Índice

1. [Arquitectura del Pipeline](#-arquitectura-del-pipeline)
2. [Requisitos Previos](#-requisitos-previos)
3. [CI - Integración Continua](#-ci---integración-continua)
4. [CD - Despliegue Continuo](#-cd---despliegue-continuo)
5. [Configuración del VPS Elastika](#-configuración-del-vps-elastika)
6. [GitHub Secrets](#-github-secrets)
7. [Scheduler y Queue Workers](#-scheduler-y-queue-workers)
8. [Systemd Services](#-servicios-systemd)
9. [Rollback](#-rollback)
10. [Monitoreo y Logs](#-monitoreo-y-logs)
11. [Checklist de Deploy](#-checklist-de-deploy)

---

## 🏗 Arquitectura del Pipeline

```
┌──────────────┐     ┌──────────────┐     ┌─────────────────────┐
│  Developer   │────▶│   GitHub     │────▶│   Elastika VPS      │
│  git push    │     │   Actions    │     │   (Producción)      │
└──────────────┘     │              │     │                     │
                     │  CI: Tests   │     │  ┌───────────────┐  │
                     │  CI: Build   │     │  │  Laravel 12   │  │
                     │  CD: Deploy  │     │  │  PHP 8.2      │  │
                     │              │     │  │  MySQL 8      │  │
                     └──────────────┘     │  │  Apache/Nginx │  │
                                          │  └───────────────┘  │
                                          │  ┌───────────────┐  │
                                          │  │  Python 3.11  │  │
                                          │  │  FastAPI      │  │
                                          │  │  Uvicorn      │  │
                                          │  └───────────────┘  │
                                          └─────────────────────┘
```

### Flujo de trabajo

| Evento | Acción |
|--------|--------|
| Push a `develop` | CI: Tests + Lint (sin deploy) |
| Pull Request a `main` | CI: Tests + Lint + Code Review |
| Push/merge a `main` | CI + CD: Tests → Build → Deploy automático |

---

## 📦 Requisitos Previos

### En tu máquina local

- Git configurado con `origin` apuntando a tu repo en GitHub
- Acceso SSH al VPS de Elastika

### En el VPS Elastika

| Software | Versión mínima | Comando de verificación |
|----------|---------------|------------------------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer --version` |
| MySQL | 8.0+ | `mysql --version` |
| Node.js | 20.x | `node -v` |
| NPM | 9.x+ | `npm -v` |
| Python | 3.11+ | `python3 --version` |
| Git | 2.x+ | `git --version` |
| Apache o Nginx | - | `apache2 -v` o `nginx -v` |
| Supervisor o systemd | - | `systemctl --version` |

---

## ✅ CI - Integración Continua

**Archivo:** `.github/workflows/ci.yml`

Se ejecuta en cada push a `main`/`develop` y en Pull Requests.

### Jobs

#### 1. Laravel (PHP 8.2 / 8.3)

```
✓ Checkout código
✓ Setup PHP con extensiones (pdo_mysql, gd, mbstring, etc.)
✓ Cache de Composer
✓ Install dependencias PHP
✓ Setup Node.js 20
✓ Install + Build frontend (npm ci && npm run build)
✓ Preparar .env de test
✓ Migraciones sobre MySQL de test
✓ Ejecutar tests (php artisan test)
✓ Laravel Pint (code style check)
```

#### 2. Python Microservicio

```
✓ Checkout código
✓ Setup Python 3.11
✓ Cache de pip
✓ Install requirements.txt
✓ Validar imports (analyzer_service, config)
✓ Verificar carga de configuración
```

---

## 🚢 CD - Despliegue Continuo

**Archivo:** `.github/workflows/cd.yml`

Se ejecuta **solo** cuando hay push a `main` (después de merge de PR o push directo).

### Flujo CD

```
1. ✅ CI pasa (requerido)
2. 📦 Build assets frontend en GitHub Actions
3. 🔐 Conexión SSH al VPS
4. 🔄 git pull en el servidor
5. 📦 composer install --no-dev
6. 🗄️ php artisan migrate --force
7. ⚡ Cachear config/routes/views
8. 🐍 Actualizar venv Python + requirements
9. 🔄 Reiniciar queue workers
10. 🔄 Reiniciar microservicio Python
11. 🔄 Reload web server
12. 📤 Upload assets compilados
13. ✅ Health check
```

---

## 🖥 Configuración del VPS Elastika

### 1. Primer setup del servidor

```bash
# Conectarse al VPS
ssh usuario@tu-vps-elastika.com

# Crear directorio del proyecto
sudo mkdir -p /var/www/vigilante-seace
sudo chown $USER:www-data /var/www/vigilante-seace

# Clonar repositorio
cd /var/www
git clone git@github.com:TU_USUARIO/vigilante-seace.git
cd vigilante-seace

# Configurar Git (deploy key o token)
git remote set-url origin git@github.com:TU_USUARIO/vigilante-seace.git
```

### 2. Setup Laravel

```bash
cd /var/www/vigilante-seace

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Configurar environment
cp .env.example .env
nano .env   # Editar con datos de producción

# IMPORTANTE: Configurar en .env
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://tu-dominio.com
# DB_HOST=127.0.0.1
# DB_DATABASE=vigilante_seace
# DB_USERNAME=tu_usuario_mysql
# DB_PASSWORD=tu_contraseña_segura
# TELEGRAM_BOT_TOKEN=tu_bot_token
# TELEGRAM_CHAT_ID=tu_chat_id
# ANALIZADOR_TDR_URL=http://127.0.0.1:8001
# ANALIZADOR_TDR_ENABLED=true

# Generar key y migrar
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force

# Cachear todo
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permisos de storage
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Link storage público
php artisan storage:link
```

### 3. Setup Python Microservicio

```bash
cd /var/www/vigilante-seace/analizador-tdr

# Crear virtual environment
python3 -m venv venv
source venv/bin/activate

# Instalar dependencias
pip install --upgrade pip
pip install -r requirements.txt

# Configurar .env del microservicio
cp .env.example .env  # o crear desde cero
nano .env

# IMPORTANTE: Configurar en analizador-tdr/.env
# APP_ENV=production
# DEBUG=False
# GEMINI_API_KEY=tu_api_key_gemini
# DEFAULT_LLM_PROVIDER=gemini

deactivate
```

### 4. Configuración Apache (VirtualHost)

```apache
# /etc/apache2/sites-available/vigilante-seace.conf

<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/vigilante-seace/public

    <Directory /var/www/vigilante-seace/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/vigilante-seace-error.log
    CustomLog ${APACHE_LOG_DIR}/vigilante-seace-access.log combined
</VirtualHost>
```

```bash
# Activar sitio
sudo a2ensite vigilante-seace.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 5. Configuración Nginx (alternativa)

```nginx
# /etc/nginx/sites-available/vigilante-seace

server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/vigilante-seace/public;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/vigilante-seace /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6. SSL con Let's Encrypt

```bash
sudo apt install certbot python3-certbot-apache  # o python3-certbot-nginx
sudo certbot --apache -d tu-dominio.com          # o --nginx
```

---

## 🔐 GitHub Secrets

Configurar en: **GitHub → Repo → Settings → Secrets and variables → Actions**

| Secret | Descripción | Ejemplo |
|--------|-------------|---------|
| `VPS_HOST` | IP o dominio del VPS Elastika | `190.xxx.xxx.xxx` |
| `VPS_USER` | Usuario SSH del servidor | `deploy` |
| `VPS_SSH_KEY` | Clave privada SSH (contenido completo) | `-----BEGIN OPENSSH...` |
| `VPS_PORT` | Puerto SSH (opcional, default 22) | `22` |
| `VPS_APP_DIR` | Ruta del proyecto en el servidor | `/var/www/vigilante-seace` |

### Generar SSH Key para Deploy

```bash
# En tu máquina local
ssh-keygen -t ed25519 -C "github-deploy-vigilante" -f ~/.ssh/deploy_vigilante

# Copiar la clave PÚBLICA al servidor
ssh-copy-id -i ~/.ssh/deploy_vigilante.pub usuario@tu-vps-elastika.com

# Copiar la clave PRIVADA como GitHub Secret (VPS_SSH_KEY)
cat ~/.ssh/deploy_vigilante
# → Pegar TODO el contenido en GitHub Secrets
```

### Crear Environment en GitHub

1. Ir a **Settings → Environments → New environment**
2. Nombre: `production`
3. (Opcional) Añadir **required reviewers** para aprobar deploys manuales
4. (Opcional) Limitar a branch `main`

---

## ⏰ Scheduler y Queue Workers

### Laravel Scheduler (cron)

El scheduler ejecuta el **ImportarTdrNotificarJob** automáticamente:

| Horario | Días | Descripción |
|---------|------|-------------|
| 10:00 | Lun-Vie | 1ra ejecución del día |
| 12:00 | Lun-Vie | 2da ejecución |
| 14:00 | Lun-Vie | 3ra ejecución |
| 16:00 | Lun-Vie | 4ta ejecución |
| 18:00 | Lun-Vie | 5ta y última ejecución |

**Configurar cron en el VPS:**

```bash
# Editar crontab
crontab -e

# Agregar esta línea (OBLIGATORIO para que el scheduler funcione)
* * * * * cd /var/www/vigilante-seace && php artisan schedule:run >> /dev/null 2>&1
```

**Verificar que el schedule está registrado:**

```bash
cd /var/www/vigilante-seace
php artisan schedule:list
```

Salida esperada:

```
  10:00-18:00  App\Jobs\ImportarTdrNotificarJob  Weekdays, Every two hours ...
```

### Queue Worker

El Job se despacha a la cola. Necesitas un queue worker corriendo:

```bash
# Prueba manual
php artisan queue:work --tries=2 --timeout=300

# En producción usar Supervisor o systemd (ver sección siguiente)
```

---

## 🔧 Servicios Systemd

### 1. Queue Worker (Laravel)

```ini
# /etc/systemd/system/vigilante-queue.service

[Unit]
Description=Vigilante SEACE Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/vigilante-seace
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=2 --timeout=300 --max-jobs=100
StandardOutput=append:/var/www/vigilante-seace/storage/logs/queue-worker.log
StandardError=append:/var/www/vigilante-seace/storage/logs/queue-worker-error.log

[Install]
WantedBy=multi-user.target
```

### 2. Microservicio Python (Analizador TDR)

```ini
# /etc/systemd/system/analizador-tdr.service

[Unit]
Description=Analizador TDR SEACE - FastAPI Microservice
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/vigilante-seace/analizador-tdr
Environment="PATH=/var/www/vigilante-seace/analizador-tdr/venv/bin"
ExecStart=/var/www/vigilante-seace/analizador-tdr/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8001 --workers 2
StandardOutput=append:/var/www/vigilante-seace/storage/logs/analizador-tdr.log
StandardError=append:/var/www/vigilante-seace/storage/logs/analizador-tdr-error.log

[Install]
WantedBy=multi-user.target
```

### 3. Activar servicios

```bash
# Recargar systemd
sudo systemctl daemon-reload

# Activar al boot + iniciar
sudo systemctl enable vigilante-queue.service
sudo systemctl start vigilante-queue.service

sudo systemctl enable analizador-tdr.service
sudo systemctl start analizador-tdr.service

# Verificar estado
sudo systemctl status vigilante-queue.service
sudo systemctl status analizador-tdr.service
```

---

## ↩️ Rollback

### Rollback rápido (último commit)

```bash
ssh usuario@tu-vps-elastika.com

cd /var/www/vigilante-seace

# Ver últimos commits
git log --oneline -5

# Volver al commit anterior
git reset --hard HEAD~1

# Reinstalar y recachear
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart

# Reiniciar Python
cd analizador-tdr && source venv/bin/activate && pip install -r requirements.txt && deactivate
sudo systemctl restart analizador-tdr.service
```

### Rollback a commit específico

```bash
git reset --hard COMMIT_HASH
# Luego repetir los pasos de reinstalación
```

### Rollback de migraciones

```bash
# Revertir última migración
php artisan migrate:rollback --step=1
```

---

## 📊 Monitoreo y Logs

### Logs Laravel

```bash
# Log principal
tail -f /var/www/vigilante-seace/storage/logs/laravel.log

# Log del scheduler
tail -f /var/www/vigilante-seace/storage/logs/importador-tdr-schedule.log

# Log del queue worker
tail -f /var/www/vigilante-seace/storage/logs/queue-worker.log
```

### Logs Python

```bash
tail -f /var/www/vigilante-seace/storage/logs/analizador-tdr.log
```

### Logs Systemd

```bash
# Queue worker
journalctl -u vigilante-queue.service -f

# Microservicio Python
journalctl -u analizador-tdr.service -f
```

### Health Checks

```bash
# Laravel
php artisan --version

# Scheduler registrado
php artisan schedule:list

# Queue funcionando
php artisan queue:monitor default

# Microservicio Python
curl http://127.0.0.1:8001/health
```

---

## ✅ Checklist de Deploy

### Primera vez (setup inicial)

- [ ] VPS Elastika provisionado con PHP 8.2, MySQL 8, Python 3.11
- [ ] SSH key de deploy generada y configurada
- [ ] GitHub Secrets configurados (`VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`, `VPS_APP_DIR`)
- [ ] Environment `production` creado en GitHub
- [ ] Repositorio clonado en el VPS
- [ ] `.env` de Laravel configurado en producción
- [ ] `.env` de analizador-tdr configurado
- [ ] MySQL: base de datos y usuario creados
- [ ] `php artisan migrate --force` ejecutado
- [ ] `php artisan db:seed --force` ejecutado
- [ ] Virtual host Apache/Nginx configurado
- [ ] SSL (Let's Encrypt) configurado
- [ ] Cron del scheduler configurado (`* * * * *`)
- [ ] Systemd: `vigilante-queue.service` activo
- [ ] Systemd: `analizador-tdr.service` activo
- [ ] `php artisan schedule:list` muestra el ImportarTdrNotificarJob
- [ ] Health check: Laravel responde en el dominio
- [ ] Health check: `curl http://127.0.0.1:8001/health` responde
- [ ] Primer push a `main` ejecuta CI/CD correctamente

### Cada deploy (automático)

- [ ] CI pasa (tests PHP + validación Python)
- [ ] Assets compilados
- [ ] Código actualizado en VPS
- [ ] Migraciones aplicadas
- [ ] Cache regenerada
- [ ] Python venv actualizado
- [ ] Servicios reiniciados
- [ ] Health check pasa

---

## 🔗 Comandos Útiles

```bash
# ─── Desarrollo local ───
composer dev                              # Levanta todo (server + queue + vite + logs)
php artisan schedule:list                 # Ver jobs programados
php artisan schedule:test                 # Ejecutar scheduler manualmente
php artisan queue:work --once             # Procesar un solo job

# ─── Producción ───
php artisan config:cache                  # Cachear configuración
php artisan config:clear                  # Limpiar cache config
php artisan queue:restart                 # Reiniciar workers
php artisan down                          # Modo mantenimiento
php artisan up                            # Salir de mantenimiento

# ─── Microservicio Python ───
cd analizador-tdr
source venv/bin/activate
uvicorn main:app --host 127.0.0.1 --port 8001 --reload   # Dev
uvicorn main:app --host 127.0.0.1 --port 8001 --workers 2 # Prod
```
