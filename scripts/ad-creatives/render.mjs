export default async function run(page) {
  const url = process.env.RENDER_URL;
  const out = process.env.RENDER_OUT;
  const w = parseInt(process.env.RENDER_W || '1080', 10);
  const h = parseInt(process.env.RENDER_H || '1080', 10);

  await page.setViewportSize({ width: w, height: h });
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(700);
  await page.screenshot({ path: out, clip: { x: 0, y: 0, width: w, height: h } });

  return { url, out, w, h };
}
