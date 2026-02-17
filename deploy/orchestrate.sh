#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Vigilante SEACE — Orquestador de Servicios
# ═══════════════════════════════════════════════════════════════
#
# Uso:
#   ./deploy/orchestrate.sh <accion> [opciones]
#
# Acciones:
#   stop       Detiene todos los servicios del proyecto (con kill de zombies)
#   start      Inicia todos los servicios en orden correcto
#   restart    Stop + Start
#   deploy     Build-first: pull → deps → migrate → cache → smart-restart (sin downtime global)
#   status     Muestra el estado de todos los servicios
#   health     Verifica que los servicios estén sanos
#   sync       Solo sincroniza los .service files y recarga daemon
#   logs       Muestra las últimas líneas de todos los logs
#
# Opciones:
#   --skip-deps    Omitir instalación de dependencias (composer/pip)
#   --skip-migrate Omitir migraciones
#   --skip-pull    Omitir git pull (usado por CD que ya hizo pull antes)
#   --verbose      Salida detallada
#
# Ejemplos:
#   ./deploy/orchestrate.sh deploy
#   ./deploy/orchestrate.sh restart
#   ./deploy/orchestrate.sh status
#   ./deploy/orchestrate.sh deploy --skip-deps
#
# ═══════════════════════════════════════════════════════════════

set -euo pipefail

# ──────────────────────────────────────────────────────────────
# Configuración
# ──────────────────────────────────────────────────────────────
APP_DIR="${VPS_APP_DIR:-/var/www/vigilante-seace}"
PYTHON_DIR="$APP_DIR/analizador-tdr"
PHP_BIN="${PHP_BIN:-/usr/local/php82/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
LOG_DIR="/var/log/vigilante-seace"
DEPLOY_LOG="$LOG_DIR/deploy.log"

# Servicios del proyecto (orden de inicio importa)
SERVICES=(
    "analizador-tdr"      # 1ro: FastAPI (sin dependencias)
    "vigilante-queue"     # 2do: Queue worker (procesa jobs)
    "telegram-bot"        # 3ro: Telegram (depende de queue + analizador)
    "whatsapp-bot"        # 4to: WhatsApp (depende de queue + analizador)
)

# Procesos que necesitan force-kill (long-polling, bloqueos)
ZOMBIE_PATTERNS=(
    "artisan telegram:listen"
    "artisan whatsapp:listen"
    "artisan queue:work"
    "uvicorn main:app"
)

# Opciones
SKIP_DEPS=false
SKIP_MIGRATE=false
SKIP_PULL=false
VERBOSE=false

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ──────────────────────────────────────────────────────────────
# Funciones auxiliares
# ──────────────────────────────────────────────────────────────

log_info()  { echo -e "${BLUE}[INFO]${NC}  $(date '+%H:%M:%S') $*"; }
log_ok()    { echo -e "${GREEN}[OK]${NC}    $(date '+%H:%M:%S') $*"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC}  $(date '+%H:%M:%S') $*"; }
log_error() { echo -e "${RED}[ERROR]${NC} $(date '+%H:%M:%S') $*"; }
log_step()  { echo -e "\n${CYAN}═══ $* ═══${NC}"; }

# Escribe al log de deploy si existe el directorio
log_deploy() {
    if [ -d "$LOG_DIR" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$DEPLOY_LOG" 2>/dev/null || true
    fi
}

# Verifica si un servicio existe en systemd
service_exists() {
    systemctl list-unit-files "${1}.service" &>/dev/null
}

# Verifica si un servicio está activo
service_is_active() {
    systemctl is-active --quiet "${1}.service" 2>/dev/null
}

# Mata procesos zombies por patrón
kill_zombies() {
    local pattern="$1"
    local pids
    pids=$(pgrep -f "$pattern" 2>/dev/null || true)

    if [ -n "$pids" ]; then
        log_warn "Matando procesos zombie: $pattern (PIDs: $pids)"
        pkill -9 -f "$pattern" 2>/dev/null || true
        sleep 1
        # Verificar que murieron
        if pgrep -f "$pattern" &>/dev/null; then
            log_error "No se pudieron matar procesos: $pattern"
            return 1
        fi
        log_ok "Procesos eliminados: $pattern"
    elif [ "$VERBOSE" = true ]; then
        log_info "Sin zombies para: $pattern"
    fi
}

# Espera a que un servicio esté activo (con timeout)
wait_for_service() {
    local service="$1"
    local timeout="${2:-15}"
    local elapsed=0

    while [ $elapsed -lt $timeout ]; do
        if service_is_active "$service"; then
            return 0
        fi
        sleep 1
        elapsed=$((elapsed + 1))
    done
    return 1
}

# Limpia zombies de un servicio específico y espera si es Telegram
ensure_clean() {
    local svc="$1"
    local pattern=""
    local had_zombies=false

    # Resetear estado failed de systemd (desbloquea StartLimitBurst)
    sudo systemctl reset-failed "${svc}.service" 2>/dev/null || true

    case "$svc" in
        telegram-bot)     pattern="artisan telegram:listen" ;;
        whatsapp-bot)     pattern="artisan whatsapp:listen" ;;
        vigilante-queue)  pattern="artisan queue:work" ;;
        analizador-tdr)   pattern="uvicorn main:app" ;;
    esac

    if [ -n "$pattern" ] && pgrep -f "$pattern" &>/dev/null; then
        had_zombies=true
        kill_zombies "$pattern"
    fi

    # Telegram: limpiar lock stale de --isolated (por si se usó manualmente)
    # y esperar 5s para que la API libere la sesión de long-polling
    if [ "$svc" = "telegram-bot" ]; then
        cd "$APP_DIR"
        # Liberar lock de Isolatable sin tinker (evita problema de psysh/www-data)
        $PHP_BIN -r "
            require '$APP_DIR/vendor/autoload.php';
            \$app = require '$APP_DIR/bootstrap/app.php';
            \$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
            \Illuminate\\Support\\Facades\\Cache::lock('framework/command-telegram-bot-listener')->forceRelease();
        " 2>/dev/null || true
        log_info "Lock de --isolated liberado (si existía)"
        if [ "$had_zombies" = true ]; then
            log_info "Esperando 5s para que Telegram libere sesión de polling..."
            sleep 5
        fi
    fi

    # WhatsApp: limpiar lock de --isolated
    if [ "$svc" = "whatsapp-bot" ]; then
        cd "$APP_DIR"
        $PHP_BIN -r "
            require '$APP_DIR/vendor/autoload.php';
            \$app = require '$APP_DIR/bootstrap/app.php';
            \$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
            \Illuminate\\Support\\Facades\\Cache::lock('framework/command-whatsapp-bot-listener')->forceRelease();
        " 2>/dev/null || true
        log_info "Lock WhatsApp --isolated liberado (si existía)"
    fi
}

# ──────────────────────────────────────────────────────────────
# Acciones principales
# ──────────────────────────────────────────────────────────────

do_stop() {
    log_step "DETENIENDO SERVICIOS"

    local had_telegram=false

    # 1. Detener servicios via systemctl (orden inverso)
    for ((i=${#SERVICES[@]}-1; i>=0; i--)); do
        local svc="${SERVICES[$i]}"
        if service_is_active "$svc"; then
            [ "$svc" = "telegram-bot" ] && had_telegram=true
            log_info "Deteniendo ${svc}.service..."
            sudo systemctl stop "${svc}.service" 2>/dev/null || true
            log_ok "${svc}.service detenido"
        else
            [ "$VERBOSE" = true ] && log_info "${svc}.service ya estaba detenido"
        fi
    done

    # 2. Matar zombies (procesos que no murieron con SIGTERM)
    log_info "Limpiando procesos residuales..."
    for pattern in "${ZOMBIE_PATTERNS[@]}"; do
        kill_zombies "$pattern"
    done

    # 3. Espera para Telegram solo si estaba activo (liberar sesión de long-polling)
    if [ "$had_telegram" = true ]; then
        log_info "Esperando 5s para que Telegram libere la sesión..."
        sleep 5
    fi

    log_ok "Todos los servicios detenidos y limpios"
    log_deploy "STOP: Servicios detenidos"
}

do_start() {
    log_step "INICIANDO SERVICIOS"

    # Asegurar directorio de logs
    sudo mkdir -p "$LOG_DIR"
    sudo chown www-data:www-data "$LOG_DIR"

    for svc in "${SERVICES[@]}"; do
        if ! service_exists "$svc"; then
            log_warn "${svc}.service no existe en systemd (ejecuta 'sync' primero)"
            continue
        fi

        # Si ya está activo, no hacer nada
        if service_is_active "$svc"; then
            log_ok "${svc}.service ya activo — skip"
            continue
        fi

        # Limpiar zombies antes de iniciar
        ensure_clean "$svc"

        log_info "Iniciando ${svc}.service..."
        if sudo systemctl start "${svc}.service" 2>/dev/null; then
            if wait_for_service "$svc" 15; then
                log_ok "${svc}.service ✅ activo"
            else
                log_error "${svc}.service ❌ no arrancó en 15s"
            fi
        else
            log_error "${svc}.service ❌ fallo al ejecutar start"
        fi
    done

    log_ok "Servicios iniciados"
    log_deploy "START: Servicios iniciados"
}

do_restart() {
    do_stop
    do_start
}

# Reinicio inteligente para deploy: por servicio, sin downtime global
do_smart_restart() {
    log_step "REINICIO INTELIGENTE DE SERVICIOS"

    sudo mkdir -p "$LOG_DIR"
    sudo chown www-data:www-data "$LOG_DIR"

    local failed=()

    for svc in "${SERVICES[@]}"; do
        if ! service_exists "$svc"; then
            log_warn "${svc}.service no existe en systemd (ejecuta 'sync' primero)"
            continue
        fi

        log_info "Reiniciando ${svc}.service..."

        # 1. Detener si está activo
        if service_is_active "$svc"; then
            sudo systemctl stop "${svc}.service" 2>/dev/null || true
        fi

        # 2. Limpiar zombies + espera si aplica (ej: Telegram API)
        ensure_clean "$svc"

        # 3. Iniciar
        if sudo systemctl start "${svc}.service" 2>/dev/null; then
            if wait_for_service "$svc" 15; then
                log_ok "${svc}.service ✅ activo"
            else
                log_error "${svc}.service ❌ no arrancó en 15s"
                failed+=("$svc")
            fi
        else
            log_error "${svc}.service ❌ fallo en start"
            failed+=("$svc")
        fi
    done

    if [ ${#failed[@]} -gt 0 ]; then
        log_warn "Servicios con problemas: ${failed[*]}"
        log_deploy "SMART-RESTART: Fallos en ${failed[*]}"
    else
        log_ok "Todos los servicios reiniciados"
        log_deploy "SMART-RESTART: Todos OK"
    fi
}

do_sync() {
    log_step "SINCRONIZANDO SERVICE FILES"

    cd "$APP_DIR"

    # Copiar service files
    local files_synced=0
    for svc in "${SERVICES[@]}"; do
        local src="deploy/${svc}.service"
        local dst="/etc/systemd/system/${svc}.service"

        if [ -f "$src" ]; then
            # Solo copiar si cambió
            if ! cmp -s "$src" "$dst" 2>/dev/null; then
                sudo cp "$src" "$dst"
                log_ok "Actualizado: ${svc}.service"
                files_synced=$((files_synced + 1))
            elif [ "$VERBOSE" = true ]; then
                log_info "Sin cambios: ${svc}.service"
            fi
        else
            log_warn "No encontrado: $src"
        fi
    done

    # Logrotate
    if [ -f "deploy/logrotate-vigilante" ]; then
        if ! cmp -s "deploy/logrotate-vigilante" "/etc/logrotate.d/vigilante-seace" 2>/dev/null; then
            sudo cp deploy/logrotate-vigilante /etc/logrotate.d/vigilante-seace
            sudo chmod 644 /etc/logrotate.d/vigilante-seace
            log_ok "Actualizado: logrotate config"
            files_synced=$((files_synced + 1))
        fi
    fi

    # Recargar systemd si hubo cambios
    if [ $files_synced -gt 0 ]; then
        sudo systemctl daemon-reload
        log_ok "daemon-reload ejecutado ($files_synced archivos actualizados)"
    else
        log_info "Sin cambios en service files"
    fi

    # Habilitar servicios (idempotente)
    for svc in "${SERVICES[@]}"; do
        sudo systemctl enable "${svc}.service" 2>/dev/null || true
    done

    log_deploy "SYNC: $files_synced service files actualizados"
}

do_deploy() {
    local start_time=$(date +%s)

    log_step "DEPLOY COMPLETO - $(date '+%Y-%m-%d %H:%M:%S')"
    log_deploy "========== DEPLOY INICIADO =========="

    # ═══ BUILD PHASE (servicios siguen corriendo — sin downtime) ═══

    # ── 1. Pull cambios ──
    if [ "$SKIP_PULL" = false ]; then
        log_step "GIT PULL"
        cd "$APP_DIR"
        git fetch origin main
        git reset --hard origin/main
        log_ok "Código actualizado"
    else
        log_info "Saltando git pull (--skip-pull)"
        cd "$APP_DIR"
    fi

    # ── 2. Dependencias Laravel ──
    if [ "$SKIP_DEPS" = false ]; then
        log_step "DEPENDENCIAS PHP"
        $PHP_BIN $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
        log_ok "Composer install completado"
    else
        log_info "Saltando dependencias PHP (--skip-deps)"
    fi

    # ── 3. Migraciones ──
    if [ "$SKIP_MIGRATE" = false ]; then
        log_step "MIGRACIONES"
        $PHP_BIN artisan migrate --force 2>&1
        log_ok "Migraciones ejecutadas"
    else
        log_info "Saltando migraciones (--skip-migrate)"
    fi

    # ── 4. Dependencias Python ──
    if [ "$SKIP_DEPS" = false ]; then
        log_step "DEPENDENCIAS PYTHON"
        cd "$PYTHON_DIR"
        if [ ! -d "venv" ]; then
            /usr/local/python311/bin/python3 -m venv venv
            log_info "Virtualenv creado"
        fi
        source venv/bin/activate
        pip install --upgrade pip --quiet 2>&1 | tail -1
        pip install -r requirements.txt --quiet 2>&1 | tail -1
        deactivate
        log_ok "Dependencias Python instaladas"
    else
        log_info "Saltando dependencias Python (--skip-deps)"
    fi

    # ── 5. Sincronizar service files ──
    cd "$APP_DIR"
    do_sync

    # ── 6. Cachés Laravel ──
    log_step "CACHÉS LARAVEL"
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache
    $PHP_BIN artisan event:cache
    log_ok "Cachés generados"

    # ── 7. Permisos ──
    log_step "PERMISOS"
    sudo chown -R www-data:www-data "$APP_DIR/storage" 2>/dev/null || true
    sudo chmod -R 775 "$APP_DIR/storage" 2>/dev/null || true
    sudo chmod 1777 /tmp 2>/dev/null || true
    log_ok "Permisos configurados"

    # ═══ RESTART PHASE (downtime mínimo, por servicio) ═══

    # ── 8. Reiniciar PHP-FPM + Apache ──
    log_step "WEB SERVER"
    sudo systemctl restart php-fpm.service 2>/dev/null || true
    sudo systemctl reload apache2 2>/dev/null || true
    log_ok "PHP-FPM y Apache reiniciados"

    # ── 9. Reinicio inteligente de servicios (uno por uno) ──
    do_smart_restart

    # ── 10. Verificación de salud ──
    do_health

    # ── Resumen ──
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))

    log_step "DEPLOY COMPLETADO en ${duration}s"
    log_deploy "========== DEPLOY COMPLETADO (${duration}s) =========="
}

do_status() {
    log_step "ESTADO DE SERVICIOS"

    echo ""
    printf "%-25s %-12s %-10s %s\n" "SERVICIO" "ESTADO" "PID" "UPTIME"
    printf "%-25s %-12s %-10s %s\n" "─────────────────────────" "────────────" "──────────" "─────────────"

    for svc in "${SERVICES[@]}"; do
        local state=$(systemctl is-active "${svc}.service" 2>/dev/null || echo "not-found")
        local pid=$(systemctl show -p MainPID --value "${svc}.service" 2>/dev/null || echo "-")
        local uptime=""

        if [ "$state" = "active" ] && [ "$pid" != "0" ] && [ "$pid" != "-" ]; then
            uptime=$(ps -p "$pid" -o etime= 2>/dev/null | xargs || echo "-")
        fi

        case "$state" in
            active)   state_color="${GREEN}active${NC}" ;;
            inactive) state_color="${YELLOW}inactive${NC}" ;;
            failed)   state_color="${RED}failed${NC}" ;;
            *)        state_color="${RED}${state}${NC}" ;;
        esac

        printf "%-25s %-22b %-10s %s\n" "${svc}.service" "$state_color" "${pid:-'-'}" "${uptime:-'-'}"
    done

    # Servicios de infraestructura
    echo ""
    for infra_svc in "php-fpm" "apache2" "mysql"; do
        local state=$(systemctl is-active "${infra_svc}.service" 2>/dev/null || echo "not-found")
        case "$state" in
            active)   state_color="${GREEN}active${NC}" ;;
            *)        state_color="${RED}${state}${NC}" ;;
        esac
        printf "%-25s %-22b\n" "${infra_svc}.service" "$state_color"
    done
    echo ""
}

do_health() {
    log_step "VERIFICACIÓN DE SALUD"
    local healthy=true

    # 1. Servicios activos
    for svc in "${SERVICES[@]}"; do
        if service_is_active "$svc"; then
            log_ok "${svc}.service activo"
        else
            log_error "${svc}.service NO activo"
            healthy=false
        fi
    done

    # 2. Analizador TDR API (puerto 8001)
    if curl -sf --max-time 5 http://127.0.0.1:8001/health &>/dev/null; then
        log_ok "Analizador TDR API respondiendo en :8001"
    else
        log_warn "Analizador TDR API no responde en :8001 (puede tardar en iniciar)"
    fi

    # 3. Laravel funcional
    cd "$APP_DIR"
    if $PHP_BIN artisan --version &>/dev/null; then
        log_ok "Laravel artisan funcional"
    else
        log_error "Laravel artisan no responde"
        healthy=false
    fi

    # 4. Sin procesos duplicados (telegram bot)
    local telegram_count=$(pgrep -f "artisan telegram:listen" 2>/dev/null | wc -l)
    if [ "$telegram_count" -le 1 ]; then
        log_ok "Telegram bot: $telegram_count instancia(s) (correcto)"
    else
        log_error "Telegram bot: $telegram_count instancias (¡DUPLICADOS!)"
        healthy=false
    fi

    # 5. Sin procesos duplicados (whatsapp bot)
    local whatsapp_count=$(pgrep -f "artisan whatsapp:listen" 2>/dev/null | wc -l)
    if [ "$whatsapp_count" -le 1 ]; then
        log_ok "WhatsApp bot: $whatsapp_count instancia(s) (correcto)"
    else
        log_error "WhatsApp bot: $whatsapp_count instancias (¡DUPLICADOS!)"
        healthy=false
    fi

    if [ "$healthy" = true ]; then
        log_ok "Sistema SANO ✅"
    else
        log_error "Sistema con PROBLEMAS ❌ — revisar logs"
    fi
}

do_logs() {
    log_step "ÚLTIMAS LÍNEAS DE LOGS"

    for svc in "${SERVICES[@]}"; do
        local log_file="$LOG_DIR/${svc}.log"
        local err_file="$LOG_DIR/${svc}-error.log"

        echo -e "\n${CYAN}── ${svc} (stdout) ──${NC}"
        if [ -f "$log_file" ]; then
            tail -5 "$log_file"
        else
            echo "(sin log)"
        fi

        if [ -f "$err_file" ] && [ -s "$err_file" ]; then
            echo -e "${RED}── ${svc} (stderr) ──${NC}"
            tail -5 "$err_file"
        fi
    done

    echo -e "\n${CYAN}── Laravel (últimos errores) ──${NC}"
    if [ -f "$APP_DIR/storage/logs/laravel.log" ]; then
        grep -i "error\|exception" "$APP_DIR/storage/logs/laravel.log" 2>/dev/null | tail -5 || echo "(sin errores)"
    fi
}

# ──────────────────────────────────────────────────────────────
# Parse argumentos
# ──────────────────────────────────────────────────────────────

ACTION="${1:-}"
shift || true

for arg in "$@"; do
    case "$arg" in
        --skip-deps)    SKIP_DEPS=true ;;
        --skip-migrate) SKIP_MIGRATE=true ;;
        --skip-pull)    SKIP_PULL=true ;;
        --verbose)      VERBOSE=true ;;
        *)              log_warn "Argumento desconocido: $arg" ;;
    esac
done

# Exportar PATH para binarios del VPS
export PATH="/usr/local/php82/bin:/usr/local/python311/bin:/usr/local/bin:$PATH"

case "$ACTION" in
    stop)    do_stop ;;
    start)   do_start ;;
    restart) do_restart ;;
    deploy)  do_deploy ;;
    status)  do_status ;;
    health)  do_health ;;
    sync)    do_sync ;;
    logs)    do_logs ;;
    *)
        echo "Vigilante SEACE — Orquestador de Servicios"
        echo ""
        echo "Uso: $0 <accion> [opciones]"
        echo ""
        echo "Acciones:"
        echo "  stop       Detiene todos los servicios"
        echo "  start      Inicia todos los servicios"
        echo "  restart    Stop + Start"
        echo "  deploy     Ciclo completo de deploy"
        echo "  status     Estado de todos los servicios"
        echo "  health     Verificación de salud"
        echo "  sync       Sincronizar service files"
        echo "  logs       Ver últimas líneas de logs"
        echo ""
        echo "Opciones:"
        echo "  --skip-deps     Omitir dependencias"
        echo "  --skip-migrate  Omitir migraciones"
        echo "  --skip-pull     Omitir git pull"
        echo "  --verbose       Salida detallada"
        exit 1
        ;;
esac
