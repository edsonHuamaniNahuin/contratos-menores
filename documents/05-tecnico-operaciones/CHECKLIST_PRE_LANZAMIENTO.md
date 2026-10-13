# Checklist Pre-Lanzamiento (Campañas + Landings)

> **Regla de oro:** sin auditoría en verde, NO se activa gasto.
> Lección del día 1 (12/10): los anuncios rindieron (CTR 2.76%, CPC S/ 0.05) pero las landings tenían
> 3 defectos que nadie auditó antes de lanzar: formulario E1 sin estilos, tracking de WhatsApp roto
> (CSRF), y CSS de producción 12 días viejo.

---

## 1. Auditoría automática de funnel (por landing)

```bash
node "C:\Users\PC-01\.claude\skills\browser-automation\browser.mjs" \
  "https://licitacionesmype.pe/alertas-licitaciones" \
  --script "D:\xampp\htdocs\vigilante-seace\scripts\qa\funnel-audit.mjs"
```

Checks (10) con evidencia:
1. Renderiza contenido
2. **Inputs del formulario con estilos computados** (caza el bug del doble `class`)
3. Botón submit con estilos
4. **Beacon heatmap responde 204 desde navegador real** (caza el bug CSRF/Sanctum)
5. **Beacon WhatsApp responde 204 desde navegador real**
6. Pixel Meta presente
7. GA4 presente
8. Enlace WhatsApp presente
9. Captcha renderiza
10. Peso de recursos < 1.5 MB

Correr en las 3 landings. Revisar los screenshots móviles del formulario (ojos humanos, 30 segundos).

## 2. Validador de campaña

```bash
python3 scripts/meta-ads/verify_campaign.py --token-file /root/.meta-token-pages
```
88 checks: presupuestos, segmentación, placements (solo FB+IG), exclusiones, 9 anuncios con landing/UTM/imagen.

## 3. Prueba e2e de lead real (con limpieza)

1. Enviar el formulario en navegador real (captcha real).
2. Verificar: fila en `demo_leads` con `utm_content` + `fbclid`, email interno a services@, mensaje de éxito.
3. Borrar el lead de prueba.

## 4. Build fresco

Tras cada deploy con cambios de vistas/estilos:
```bash
ssh ... "cd /var/www/vigilante-seace && npm run build"   # desde el fix 13/10 el deploy ya lo hace SIEMPRE
```
Verificar que el HTML referencia el hash actual (`/build/assets/app-XXXX.css`).

## 5. Observación (herramientas de mejora continua)

- **Heatmaps first-party**: `/admin/heatmaps` (clics con coordenadas + scroll por landing).
- **Clics WhatsApp**: tabla `wa_clicks` (ahora sí funcionando) + métrica en el monitor.
- **Microsoft Clarity** (grabaciones de sesión, gratis): **ACTIVO** (project ID `ywwnwgfd`) en las 3 landings, plantillas-tdr, buscadores públicos y páginas del layout público. Gateado por consentimiento de cookies (solo graba si el visitante acepta analíticas). NO instalado en el panel autenticado (privacidad).
- **GA4**: tráfico, rebote, dispositivos del tráfico pagado (utm_source=meta).

## Frecuencia

| Cuándo | Qué |
|---|---|
| Antes de CADA lanzamiento o cambio de landing | Secciones 1–4 completas |
| Semanal (lunes) | Revisar heatmaps + Clarity + GA4 y proponer 1-2 cambios CRO |
| Tras cada cambio de landing | Repetir sección 1 |
