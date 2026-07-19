# Setup QA en Elastika VPS

## Pre-requisitos
1. Crear 2 bots nuevos en @BotFather:
   - **QA Bot** (`@LicitacionesMYPeQABot` o similar) → obtener token
   - **Dev Bot** (`@LicitacionesMYPeDevBot` o similar) → obtener token para local

2. Crear subdominio `qa.licitacionesmype.pe` apuntando al mismo VPS

---

## 1. Crear directorio y clonar repo

```bash
sudo mkdir -p /var/www/vigilante-seace-qa
sudo chown www-data:www-data /var/www/vigilante-seace-qa
cd /var/www/vigilante-seace-qa

# Clonar repo
git clone https://edsonHuamaniNahuin@github.com/edsonHuamaniNahuin/contratos-menores.git .
git checkout develop
```

## 2. Crear base de datos QA

```sql
-- Conectarse a MySQL
mysql -u root -p

CREATE DATABASE vigilante_seace_qa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON vigilante_seace_qa.* TO 'vigilante'@'127.0.0.1';
FLUSH PRIVILEGES;
```

## 3. Crear `.env` para QA

```bash
cp .env.example .env
```

Editar con los valores de QA:

```env
APP_ENV=qa
APP_URL=https://qa.licitacionesmype.pe
APP_DEBUG=true

DB_DATABASE=vigilante_seace_qa
DB_USERNAME=vigilante
DB_PASSWORD=V1g1l4nt3S34c32026

# Correo (MailerSend SMTP — mismas credenciales que producción)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailersend.net
MAIL_PORT=587
MAIL_USERNAME=MS_756fgs@licitacionesmype.pe
MAIL_PASSWORD=<TU_MAILERSEND_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@licitacionesmype.pe
MAIL_FROM_NAME="${APP_NAME}"

# Token del BOT de QA (diferente al de producción)
TELEGRAM_BOT_TOKEN=<TOKEN_DEL_BOT_QA>
TELEGRAM_CHAT_ID=<TU_CHAT_ID>
TELEGRAM_DEBUG_LOGS=true

# Admin bot QA (puede ser el mismo admin o uno separado)
TELEGRAM_ADMIN_BOT_TOKEN=<TOKEN_ADMIN_QA>
TELEGRAM_ADMIN_CHAT_ID=<TU_CHAT_ID>

# Analizador en puerto 8002 (producción usa 8001)
ANALIZADOR_TDR_URL=http://127.0.0.1:8002
ANALIZADOR_TDR_ENABLED=true
ANALIZADOR_TDR_TIMEOUT=120

# En QA/Producción: 0 = SIEMPRE usar Job async (vigilante-queue siempre activo)
TDR_ASYNC_MIN_SIZE_BYTES=0
```

Generar key:
```bash
php artisan key:generate
```

## 4. Instalar dependencias y migrar

```bash
export PATH="/usr/local/php82/bin:/usr/local/python311/bin:/usr/local/bin:$PATH"

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Python (analizador-tdr)
cd analizador-tdr
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
deactivate
cd ..
```

## 5. Crear directorio de logs QA

```bash
sudo mkdir -p /var/log/vigilante-seace-qa
sudo chown www-data:www-data /var/log/vigilante-seace-qa
```

## 6. Sincronizar service files QA

```bash
chmod +x deploy/orchestrate.sh
bash deploy/orchestrate.sh sync
bash deploy/orchestrate.sh start
bash deploy/orchestrate.sh status
```

Esto instalará automáticamente los servicios con sufijo `-qa`:
- `analizador-tdr-qa.service` (puerto 8002)
- `vigilante-queue-qa.service`
- `vigilante-scheduler-qa.service`
- `telegram-bot-qa.service`
- `whatsapp-bot-qa.service`

## 7. Configurar Apache (VirtualHost QA)

```apache
<VirtualHost *:443>
    ServerName qa.licitacionesmype.pe
    DocumentRoot /var/www/vigilante-seace-qa/public

    <Directory /var/www/vigilante-seace-qa/public>
        AllowOverride All
        Require all granted
    </Directory>

    # SSL (ajustar según tu certificado)
    SSLEngine on
    SSLCertificateFile /path/to/qa-cert.pem
    SSLCertificateKeyFile /path/to/qa-key.pem
</VirtualHost>
```

## 8. Configurar GitHub Secrets para QA

En GitHub → Settings → Environments → crear `qa`:

| Secret | Valor |
|---|---|
| `VPS_HOST` | (mismo que producción) |
| `VPS_USER` | (mismo que producción) |
| `VPS_SSH_KEY` | (misma key) |
| `VPS_APP_DIR_QA` | `/var/www/vigilante-seace-qa` |

## 9. Verificar

```bash
bash deploy/orchestrate.sh health
curl -s https://qa.licitacionesmype.pe | head -5
```

---

## Flujo de branches

```
develop  ──push──→  CI (ci.yml) ──→  CD-QA (cd-qa.yml) ──→  qa.licitacionesmype.pe
   │
   └──merge──→  main  ──push──→  CI ──→  CD-Prod (cd.yml) ──→  licitacionesmype.pe
```
