@echo off
REM Ritual diario del agente — programado por Windows Task Scheduler (09:00)
cd /d D:\xampp\htdocs\vigilante-seace
if not exist logs mkdir logs
"D:\Program Files\nodejs\node_modules\opencode-ai\bin\opencode.exe" run --file .agents\DIARIO_AGENTE.md --title "Ritual diario" "Ejecuta el RITUAL DIARIO siguiendo al pie de la letra el runbook adjunto. Al terminar resume en 3 lineas." >> logs\agente-diario.log 2>&1
