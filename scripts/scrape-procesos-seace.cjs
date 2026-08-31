/**
 * Scraper de Procedimientos de Selección del SEACE (buscador público prod2).
 *
 * Descarga el Excel "Lista-Procesos.xls" del día indicado y lo convierte a
 * JSON para que Laravel lo importe. Cubre el gap de latencia de la API OCDS
 * del OECE (que puede publicar releases con días/semanas de retraso).
 *
 * Uso:
 *   node scrape-procesos-seace.js [desde dd/mm/yyyy] [hasta dd/mm/yyyy] [salida.json]
 *
 * Env:
 *   SCRAPE_CHROME_BIN      Ruta del binario de chrome/chrome-headless-shell
 *   SCRAPE_NODE_MODULES    Carpeta con node_modules (puppeteer-core, xlsx)
 *
 * Salida: { success, count, rows: [{entidad, fecha, nomenclatura, reiniciado,
 *           objeto, descripcion, vr, moneda, version}] }
 */
const path = require('path');
const fs = require('fs');
const os = require('os');
const { createRequire } = require('module');

const NODE_MODULES = process.env.SCRAPE_NODE_MODULES || '/opt/scraper-seace/node_modules';
const req = createRequire(path.join(NODE_MODULES, 'module.js'));
const puppeteer = req('puppeteer-core');
const XLSX = req('xlsx');

const URL = 'https://prod2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml';

function detectChrome() {
  if (process.env.SCRAPE_CHROME_BIN && fs.existsSync(process.env.SCRAPE_CHROME_BIN)) {
    return process.env.SCRAPE_CHROME_BIN;
  }
  const candidates = [
    '/opt/scraper-seace/browsers/chrome-headless-shell/linux-152.0.7977.64/chrome-headless-shell-linux64/chrome-headless-shell',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium-browser',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  ];
  for (const c of candidates) {
    if (fs.existsSync(c)) return c;
  }
  return null;
}

async function run() {
  const desde = process.argv[2] || (() => {
    const d = new Date();
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
  })();
  const hasta = process.argv[3] || desde;
  const salida = process.argv[4] || '/tmp/scrape-procesos-seace.json';

  const chromeBin = detectChrome();
  if (!chromeBin) {
    console.error('No se encontro chrome/chrome-headless-shell. Define SCRAPE_CHROME_BIN');
    process.exit(1);
  }

  const dlDir = fs.mkdtempSync(path.join(os.tmpdir(), 'seace-excel-'));
  const browser = await puppeteer.launch({
    executablePath: chromeBin,
    headless: true,
    args: ['--no-sandbox', '--disable-blink-features=AutomationControlled', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');
    await page.setViewport({ width: 1366, height: 900 });

    const client = await page.createCDPSession();
    await client.send('Page.setDownloadBehavior', { behavior: 'allow', downloadPath: dlDir });

    await page.goto(URL, { waitUntil: 'networkidle2', timeout: 90000 });
    await new Promise(r => setTimeout(r, 4000));

    await page.evaluate(() => {
      const t = Array.from(document.querySelectorAll('a')).find(l => (l.textContent || '').includes('Procedimientos de Selecci'));
      if (t) t.click();
    });
    await new Promise(r => setTimeout(r, 3000));

    const rango = await page.evaluate(({ d, h }) => {
      const setVal = (id, val) => {
        const el = document.querySelector('#' + id.replace(/:/g, '\\:'));
        if (!el) return false;
        el.value = val;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
      };
      return {
        d1: setVal('tbBuscador:idFormBuscarProceso:dfechaInicio_input', d + ' 00:00:00'),
        d2: setVal('tbBuscador:idFormBuscarProceso:dfechaFin_input', h + ' 23:59:59'),
      };
    }, { d: desde, h: hasta });

    if (!rango.d1 || !rango.d2) {
      throw new Error('No se pudieron setear las fechas del formulario');
    }

    await page.evaluate(() => {
      const b = document.querySelector('#tbBuscador\\:idFormBuscarProceso\\:btnBuscarSelToken');
      if (b) b.click();
    });
    await new Promise(r => setTimeout(r, 25000));

    await page.evaluate(() => {
      const b = document.querySelector('#tbBuscador\\:idFormBuscarProceso\\:btnExportar');
      if (b) b.click();
    });

    // Esperar a que aparezca el archivo descargado (max 60s)
    let archivo = null;
    for (let i = 0; i < 30; i++) {
      await new Promise(r => setTimeout(r, 2000));
      const files = fs.readdirSync(dlDir).filter(f => f.endsWith('.xls'));
      if (files.length > 0) { archivo = files[0]; break; }
    }

    if (!archivo) {
      throw new Error('No se descargo el Excel de procedimientos');
    }

    const wb = XLSX.readFile(path.join(dlDir, archivo));
    const sh = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sh, { header: 1 });

    // ── Tratamiento de los datos del Excel ──
    // El SEACE exporta celdas "sucias": entidades con "-" final, descripciones
    // con saltos de línea, VR en formato texto con comas de miles o "---".
    const limpiarEntidad = s => String(s || '')
      .replace(/[\r\n]+/g, ' ')
      .replace(/\s+$/g, '')
      .replace(/-+\s*$/g, '')   // quita el "-" final que agrega el SEACE
      .trim();

    const limpiarTexto = s => String(s || '')
      .replace(/[\r\n\u2028\u2029\x0b\x0c]+/g, ' ') // saltos de línea/controles → espacio
      .replace(/\s{2,}/g, ' ')                       // espacios múltiples → uno
      .trim();

    const parsearVR = raw => {
      const s = String(raw || '').trim();
      if (!s || s === '---' || s === '-') return null;

      let t = s.replace(/S\//g, '').replace(/\s/g, '');

      const tieneComa = t.includes(',');
      const tienePunto = t.includes('.');

      if (tieneComa && tienePunto) {
        // 1.234.567,89 (miles con punto, decimal con coma) o 1,234,567.89
        t = t.lastIndexOf(',') > t.lastIndexOf('.')
          ? t.replace(/\./g, '').replace(',', '.')
          : t.replace(/,/g, '');
      } else if (tieneComa) {
        const partes = t.split(',');
        // Una sola coma con 1-2 decimales → decimal; si no → miles
        t = (partes.length === 2 && partes[1].length <= 2)
          ? t.replace(',', '.')
          : t.replace(/,/g, '');
      }

      const n = Number(t);
      return Number.isFinite(n) && n > 0 ? n : null;
    };

    const monedaISO = s => {
      const m = String(s || '').toLowerCase();
      if (m.includes('dol')) return 'USD';
      if (m.includes('euro')) return 'EUR';
      return 'PEN';
    };

    const out = [];
    for (let i = 1; i < rows.length; i++) {
      const r = rows[i];
      if (!r || !r[3]) continue;
      out.push({
        entidad: limpiarEntidad(r[1]),
        fecha: String(r[2] || '').trim(),
        nomenclatura: String(r[3] || '').trim(),
        reiniciado: String(r[4] || '').trim(),
        objeto: String(r[5] || '').trim(),
        descripcion: limpiarTexto(r[6]),
        vr: parsearVR(r[7]),
        moneda: monedaISO(r[8]),
        version: String(r[9] || '').trim(),
      });
    }

    fs.writeFileSync(salida, JSON.stringify({ success: true, count: out.length, rows: out }));

    console.log(JSON.stringify({ success: true, count: out.length, archivo }));
  } finally {
    await browser.close();
    fs.rmSync(dlDir, { recursive: true, force: true });
  }
}

run().catch(e => {
  console.error('ERROR:', e.message);
  process.exit(1);
});
