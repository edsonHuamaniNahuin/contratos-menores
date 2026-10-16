@echo off
REM Refactor semanal del agente (checkpoint de bloques) — programado lunes 09:15
cd /d D:\xampp\htdocs\vigilante-seace
if not exist logs mkdir logs
"D:\Program Files\nodejs\node_modules\opencode-ai\bin\opencode.exe" run --file .agents\DIARIO_AGENTE.md --title "Refactor semanal" "Es LUNES: ademas del ritual diario, ejecuta la revision SEMANAL: llamada por bloque (PROTOCOLO_OPERATIVO §4), rotacion de creativos si corresponde, reasignacion de presupuesto entre brazos (dentro del tope), decision de retargeting si las audiencias alcanzan, y propuesta de escala si ROAS>=5x. Actualiza el panel/registry A-B." >> logs\agente-semanal.log 2>&1
