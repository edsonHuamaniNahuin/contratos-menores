export default async function run(page, ui) {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.reload({ waitUntil: 'networkidle', timeout: 60000 });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(1200);

  const res = { url: page.url(), checks: [] };
  const check = (ok, label, detail = '') => res.checks.push({ ok: !!ok, label, detail });

  // 1) Render
  const chars = await page.evaluate(() => (document.body.innerText || '').length);
  check(chars > 500, 'Página renderiza contenido', chars + ' chars');

  // 2) Estilos computados de inputs (el bug del form E1)
  const estilos = await page.evaluate(() => {
    const i = document.querySelector('input[name="nombre"], input[name="email"]');
    if (!i) return null;
    const cs = getComputedStyle(i);
    return { padding: cs.padding, radius: cs.borderRadius };
  });
  check(estilos && estilos.padding !== '0px' && estilos.radius !== '0px',
        'Inputs del formulario CON estilos', JSON.stringify(estilos));

  // 3) Botón submit con estilos
  const boton = await page.evaluate(() => {
    const b = document.querySelector('button[type="submit"]');
    if (!b) return null;
    const cs = getComputedStyle(b);
    return { padding: cs.padding, bg: cs.backgroundColor };
  });
  check(boton && boton.padding !== '0px' && boton.bg !== 'rgba(0, 0, 0, 0)',
        'Botón submit CON estilos', JSON.stringify(boton));

  // 4) Beacons de tracking desde navegador real (sin 419)
  const heat = await page.evaluate(async () => {
    try {
      const r = await fetch('/api/page-event', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ landing: 'audit', tipo: 'scroll', scroll_pct: 25, url: location.href + '?utm_content=AUDIT' }),
      });
      return r.status;
    } catch (e) { return 'ERR ' + e; }
  });
  check(heat === 204, 'Beacon heatmap responde 204 desde navegador', 'status ' + heat);

  const wat = await page.evaluate(async () => {
    try {
      const r = await fetch('/api/wa-click', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ landing: 'audit', url: location.href }),
      });
      return r.status;
    } catch (e) { return 'ERR ' + e; }
  });
  check(wat === 204, 'Beacon WhatsApp responde 204 desde navegador', 'status ' + wat);

  // 5) Tracking de terceros y utilidades
  const tags = await page.evaluate(() => ({
    fb: document.documentElement.innerHTML.includes('fbq('),
    ga: !!document.querySelector('script[src*="googletagmanager"]'),
    wa: !!document.querySelector('a[href*="wa.me/"]'),
    captcha: /Cu[aá]nto es/.test(document.body.innerText),
  }));
  check(tags.fb, 'Pixel Meta presente');
  check(tags.ga, 'GA4 presente');
  check(tags.wa, 'Enlace WhatsApp presente');
  check(tags.captcha, 'Captcha del formulario renderiza');

  // 6) Peso de recursos
  const peso = await page.evaluate(() =>
    Math.round(performance.getEntriesByType('resource').reduce((a, r) => a + (r.transferSize || 0), 0) / 1024));
  check(peso < 1500, 'Peso de recursos < 1.5 MB', peso + ' KB');

  // 7) Screenshot móvil del formulario (evidencia visual)
  if (process.env.AUDIT_SHOT) {
    await page.evaluate(() => {
      const el = document.getElementById('form-correo');
      if (el) el.scrollIntoView({ block: 'center' });
    });
    await page.waitForTimeout(800);
    await page.screenshot({ path: process.env.AUDIT_SHOT });
  }

  const fallos = res.checks.filter((c) => !c.ok);
  return {
    url: res.url,
    resumen: `${res.checks.length - fallos.length}/${res.checks.length} OK`,
    fallos: fallos.map((f) => `${f.label} (${f.detail})`),
    checks: res.checks,
  };
}
