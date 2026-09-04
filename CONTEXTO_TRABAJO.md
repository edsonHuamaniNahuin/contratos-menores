# 🧭 CONTEXTO DE TRABAJO — Vigilante SEACE
## Archivo vivo para retomar sesión tras /compact

---

## 1. Proyecto y entorno
- **App:** Vigilante SEACE — monitoreo automatizado de licitaciones SEACE (Perú). Laravel 12 + Livewire + MySQL + bots Telegram/WhatsApp + microservicio Python (analizador IA).
- **Producción:** https://licitacionesmype.pe · Servidor: licitacionesmype.pe (161.132.4.111), Ubuntu 20.04, Apache, PHP 8.2 (/usr/local/php82), Node 22 (instalado 19/08 en /usr/local/node-v22), MySQL local. **QA:** /var/www/vigilante-seace-qa.
- **Local (dev):** D:\xampp\htdocs\vigilante-seace (Windows). MySQL local: D:\xampp\mysql\bin\mysqld.exe (se cae seguido; levantarlo con --defaults-file=D:\xampp\mysql\bin\my.ini).
- **Herramientas MCP:** ELASTIKA_SUNQUPACHA (SSH producción), DEV_SISNNA/QA_SISNNA (Oracle de otro proyecto, NO tocar), DatosForSEO/KeywordsEverywhere/SerpApi (SEO/keywords), Google Keyword Planner.

## 2. Git flow (IMPORTANTE)
- Rama **develop** para trabajar → **main** para producción.
- Push SIEMPRE con token inline (GCM tiene credenciales viejas de edsonJordan → 403):
  `git push https://oauth2:TOKEN@github.com/edsonHuamaniNahuin/contratos-menores.git develop|main`
  Token: [PEDIR AL USUARIO — guardado en el historial de la sesión, NO versionar en el repo]
- Flujo deploy: commit develop → push develop → checkout main → pull → merge develop → push main → checkout develop.
- **NO commit/push sin autorización explícita del usuario** (regla establecida varias veces). El usuario avisa cuándo autoriza.
- Refs locales pueden quedar viejas: refrescar con `git fetch origin develop main` si "ahead" es falso.

## 3. Deploy producción (SSH ELASTIKA_SUNQUPACHA)
```
cd /var/www/vigilante-seace && git pull origin main
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan migrate --force   (si hay migraciones)
sudo systemctl restart vigilante-queue
npm run build (como ROOT, no www-data — node_modules es root; node 22 en /usr/local/bin)
```
- Servicios systemd: vigilante-queue, vigilante-scheduler, whatsapp-bot, telegram-bot, analizador-tdr (uvicorn 127.0.0.1:8001), apache2. `telegram-admin-bot.service` NO existe en el server (solo en repo deploy/).
- Tinker en producción: `sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan tinker --execute='...'` (cuidado con comillas en PowerShell; usar archivos PHP base64 si es largo).

## 4. Estado de lo construido (últimos commits significativos)
- (pendiente de commit) Landings E1 `/alertas-licitaciones`, E2 `/software-licitaciones`, E3 `/licitaciones-vigentes` + captura leads por correo (`demo_leads`, captcha dinámico) + fotos en public/images/landings/.
- `0d35d2ca` docs reorganizados por objetivo (documents/) + README índice.
- `341d3358` Skills S1-S3 creados en `.opencode/skills/` (mercado-vigilante, landing-vigilante, funnel-paid-vigilante).
- `c7022a57` .md movidos de la raíz + candidatos a entrevista.
- `11a85e26` Fix duplicidad: import OCDS migra OCIDs sintéticos del scraper → OCID real.
- `8bd6cd77` Fix 500 detalle contrato (proveedores doble-codificación; 501 registros reparados).
- `a83f5537` Tratamiento de datos del Excel scraper + división por mitades (límite 500 filas).
- `244894be/84a1c5c1/03211557` Scraper de Procedimientos SEACE (script .cjs + job 12:00/21:00) — cubre gap de latencia del OECE (12 días).
- `0184a433` WhatsApp 131056: throttle 3s compartido por cache + backoff 90s por par.
- `3ac12c6e` Template WhatsApp `nuevo_contrato` idioma **es_PE** (config `WHATSAPP_TEMPLATE_LANGUAGE`).
- `e0cbb8ea` Compresión PDF en microservicio (413) + retry SEACE 5xx + cache template 12h.
- `19681048/cf4ecedc` Direccionamiento mayores async (job + polling) — fix 504.
- `e891ac40` Fix 500 /admin/analytics (rows[0] vs todas las filas).
- `0afe60a8` Permiso view-analytics + alias /analytics.
- `e615794f/91a2efd9/690f613f/77ba1871...` Permisos directos por usuario (permission_user) + gestor en roles-permisos.
- WhatsApp/email/vigilancia: tracking wamid, cola reenvío, solo-WhatsApp buena pro, cache template.
- Scraper entorno: /opt/scraper-seace (node_modules puppeteer-core+xlsx + chrome-headless-shell).
- `11a85e26` Fix duplicidad: import OCDS migra OCIDs sintéticos del scraper → OCID real.
- `8bd6cd77` Fix 500 detalle contrato (proveedores doble-codificación; 501 registros reparados).
- `a83f5537` Tratamiento de datos del Excel scraper + división por mitades (límite 500 filas).
- `244894be/84a1c5c1/03211557` Scraper de Procedimientos SEACE (script .cjs + job 12:00/21:00) — cubre gap de latencia del OECE (12 días).
- `0184a433` WhatsApp 131056: throttle 3s compartido por cache + backoff 90s por par.
- `3ac12c6e` Template WhatsApp `nuevo_contrato` idioma **es_PE** (config `WHATSAPP_TEMPLATE_LANGUAGE`).
- `e0cbb8ea` Compresión PDF en microservicio (413) + retry SEACE 5xx + cache template 12h.
- `19681048/cf4ecedc` Direccionamiento mayores async (job + polling) — fix 504.
- `e891ac40` Fix 500 /admin/analytics (rows[0] vs todas las filas).
- `0afe60a8` Permiso view-analytics + alias /analytics.
- `e615794f/91a2efd9/690f613f/77ba1871...` Permisos directos por usuario (permission_user) + gestor en roles-permisos.
- WhatsApp/email/vigilancia: tracking wamid, cola reenvío, solo-WhatsApp buena pro, cache template.
- Scraper entorno: /opt/scraper-seace (node_modules puppeteer-core+xlsx + chrome-headless-shell).

## 5. Arquitectura clave a recordar
- **2 fuentes de datos mayores:** (a) scraper Excel SEACE 2x/día = disponibilidad el MISMO día (OCID sintético `ocds-scraped-md5`), (b) API OCDS OECE = enriquece después (migra sintético→real por nomenclatura). Excel NO trae OCID/estado/documentos.
- **WhatsApp:** template es_PE (4 vars) para ventana 24h cerrada; cola de reenvío cuando el usuario interactúa; throttle cache 3s; backoff 131056 90s.
- **RBAC:** permisos = rol ∪ directos (permission_user); directos se limpian al expirar suscripción.
- **GA4:** G-4PRW1QCW48 (property 404642926), script scripts/ga4-mcp-server.py (GA4_CREDENTIALS_JSON en .env), dashboard /admin/analytics.
- **Monitoreo:** /admin/monitoreo lee laravel.log (errores últimos 15).

## 6. TAREAS ACTIVAS (checklist)
- [ ] **3 EMBUDOS + 3 CAMPAÑAS** (documents/02-marketing-adquisicion/EMBUDOS_3_CAMPANAS.md): E1=/alertas-licitaciones ✅, E2=/software-licitaciones ✅, E3=/licitaciones-vigentes ✅ (construidas 02/09). Objetivos: E1 VENTA / E2 REUNIÓN / E3 LEAD — reunión WhatsApp +51 918 874 873, SIN trial gratis. Leads por correo → BD demo_leads + email, con antibot (honeypot, captcha dinámico por sesión, rate-limit IP, blacklist desechables).
- [ ] Siguiente: activar las 3 campañas Google Ads (keywords Nivel 1, negativos) y primera medición CPR/CAC/ROAS con datos de demo_leads.
- [ ] **S1 mercado-vigilante:** guía de entrevista imprimible (7 preguntas); entrevistas (Elsa/Corporación Famod, Rodrigo/Zavatec, Boris cancelado, Lisette/Honda). Candidatos en documents/03-clientes-investigacion/candidatos-entrevistas.md.
- [ ] **P0 landings:** ✅ /alertas-licitaciones, ✅ /software-licitaciones, ✅ /licitaciones-vigentes (feed real). Pendiente: lead magnet /plantillas-tdr.
- [ ] **P1:** internal linking, verificar tracking GA4 registro/pago, re-medir GSC.
- [ ] **P2:** verificar tráfico guía Ley 32069, Google Ads (S/500-1000), CWV.
- [ ] **P3:** Live TikTok, FAQ schema, backlinks.
- [ ] Pendiente técnico menor: `deploy/orchestrate.sh` pipefail (build roto no detectado); instalar telegram-admin-bot.service si se quiere.
- [ ] Reporte completo de objetivos: documents/04-metricas-reportes/REPORTE_OBJETIVOS_SEP2026.md.

## 7. Documentación (organizada por objetivo)
- Índice: `documents/README.md`. Carpetas: 01-estrategia-negocio, 02-marketing-adquisicion, 03-clientes-investigacion, 04-metricas-reportes, 05-tecnico-operaciones (BITACORA.md de bugs allí).
- Skills: `.opencode/skills/{mercado-vigilante,landing-vigilante,funnel-paid-vigilante}/SKILL.md`.

## 8. Reglas de estilo de trabajo
- Antes de implementar un fix de producción: investigar logs, cuantificar, proponer, y pedir autorización para commit/push si el usuario no la dio.
- Todo bug/incidencia se registra en documents/05-tecnico-operaciones/BITACORA.md (formato: fecha · categoría · commit · síntoma → causa → solución).
- Responder en español; código sin comentarios salvo que se pida; verificar con `php -l` y pruebas en producción cuando aplique.
