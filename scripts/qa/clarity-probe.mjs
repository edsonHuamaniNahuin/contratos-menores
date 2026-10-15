export default async function run(page) {
  const responses = [];
  page.on('response', (r) => {
    if (r.url().includes('clarity')) responses.push([r.status(), r.url().slice(0, 90)]);
  });
  const failed = [];
  page.on('requestfailed', (r) => {
    if (r.url().includes('clarity')) failed.push([r.failure() ? r.failure().errorText : '?', r.url().slice(0, 90)]);
  });

  await page.goto('https://licitacionesmype.pe/alertas-licitaciones', { waitUntil: 'load' });
  await page.waitForTimeout(9000);

  const probe = await page.evaluate(() => ({
    clarityFn: typeof window.clarity,
    flag: window.__clarityInstalado === true,
    cookieClck: document.cookie.includes('_clck'),
    cookieClsk: document.cookie.includes('_clsk'),
    tag: Array.from(document.querySelectorAll('script[src]')).map((s) => s.src).filter((s) => s.includes('clarity')),
    ls: Object.keys(localStorage).filter((k) => /clck|clsk|clarity/i.test(k)),
  }));

  return { responses, failed, probe };
}
