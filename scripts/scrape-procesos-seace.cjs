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
 *   SCRAPE_DOCS            "0" desactiva la captura de documentos de las fichas
 *   SCRAPE_DOCS_BUDGET     Presupuesto en ms para la captura de documentos
 *   SCRAPE_DOCS_PAUSA      Pausa en ms entre fichas (cortesía con el SEACE)
 *   SCRAPE_FICHA_ID        On-demand: captura SOLO esa ficha (UUID) y termina
 *   SCRAPE_FICHA_CLAVE     On-demand: busca esa nomenclatura normalizada en el
 *                          listado y captura SOLO su ficha (con ID hace fallback
 *                          a búsqueda si el ID ya no es válido)
 *
 * Salida: { success, count, rows: [{entidad, fecha, nomenclatura, reiniciado,
 *           objeto, descripcion, vr, moneda, version}],
 *           documentos: [{nomenclatura, clave, documentos: [{nombre, etapa,
 *           tipo, fileCode, filename, href, fecha}]}] }
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
const URL_FICHA = 'https://prod2.seace.gob.pe/seacebus-uiwd-pub/fichaSeleccion/fichaSeleccion.xhtml';

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

const sleep = ms => new Promise(r => setTimeout(r, ms));
const norm = s => String(s || '').toLowerCase().replace(/[^a-z0-9]/g, '');

/**
 * Parsear la Ficha de Selección abierta en la página: documentos (Bases, TDR,
 * ...), nomenclatura e items. Compartido por el barrido diario y la captura
 * on-demand de una sola ficha.
 */
async function parseFichaActual(page) {
  return page.evaluate(() => {
    const doc = document;
    const txt = (doc.body ? doc.body.innerText : '').replace(/\s+/g, ' ');
    const tbody = doc.getElementById('tbFicha:dtDocumentos_data');
    const docs = [];
    if (tbody) {
      tbody.querySelectorAll('tr[data-ri]').forEach(tr => {
        const tds = tr.querySelectorAll('td');
        const a = tr.querySelector('a[onclick*="descarga"], a[href*="download"], a[href*="fileCode"]');
        let fileCode = '', tipo = '', filename = '', href = '';
        if (a) {
          href = a.getAttribute('href') || '';
          const mm = (a.getAttribute('onclick') || '').match(/descarga[A-Za-z]*\('([^']+)','([^']+)','([^']*)'\)/);
          if (mm) { fileCode = mm[1]; tipo = mm[2]; filename = mm[3]; }
        }
        docs.push({
          etapa: (tds[1] ? tds[1].innerText : '').replace(/\s+/g, ' ').trim(),
          nombre: (tds[2] ? tds[2].innerText : '').replace(/\s+/g, ' ').trim(),
          fileCode, tipo, filename, href,
          fecha: (tds[4] ? tds[4].innerText : '').replace(/\s+/g, ' ').trim(),
        });
      });
    }
    // Documentos adicionales de otras secciones de la ficha (ej. el
    // Expediente Técnico de obras en frmArchivoExpedienteTecnicoObra):
    // cualquier link de descarga de la ficha que no esté en la tabla.
    const codigosVistos = new Set(docs.map(d => d.fileCode).filter(Boolean));
    doc.querySelectorAll('a[onclick*="descarga"], a[href*="download"], a[href*="fileCode"]').forEach(a => {
      const oc = a.getAttribute('onclick') || '';
      const mm = oc.match(/descarga[A-Za-z]*\('([^']+)','([^']+)','([^']*)'\)/);
      const fileCode = mm ? mm[1] : '';
      const href = a.getAttribute('href') || '';
      // Solo links de descarga reales (evita href="#" y botones JS)
      if (!fileCode && !/download\?|fileCode=|Alfresco|\.pdf|\.docx?/i.test(href)) return;
      if (fileCode && codigosVistos.has(fileCode)) return;
      if (fileCode) codigosVistos.add(fileCode);
      const cont = a.closest('td, tr, div, li');
      docs.push({
        etapa: 'Ficha',
        nombre: ((cont ? cont.innerText : '') || 'Documento').replace(/\s+/g, ' ').trim().slice(0, 90),
        fileCode,
        tipo: mm ? mm[2] : '',
        filename: mm ? mm[3] : '',
        href,
        fecha: '',
      });
    });

    const mn = txt.match(/Nomenclatura:\s*([A-Z0-9][A-Za-z0-9./_ -]{3,60})/);

    // Ítems del proceso (resumen compacto): tablas `itemDetalle*` de la
    // ficha (una por ítem). Máx. 50 filas y 120 chars por celda (escalado).
    let items = [];
    const tablasItems = Array.from(doc.querySelectorAll('table[id*="itemDetalle"], table[id*="ItemDet"]'));
    for (const t of tablasItems) {
      for (const tr of t.querySelectorAll('tr')) {
        if (tr.closest('thead')) continue;
        const celdas = Array.from(tr.querySelectorAll('td'))
          .map(td => (td.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 120))
          .filter(v => v !== '');
        if (celdas.length) items.push(celdas);
        if (items.length >= 50) break;
      }
      if (items.length >= 50) break;
    }

    return {
      esFicha: /Ficha de Seleccion/i.test(txt),
      nomenclatura: mn ? mn[1].trim() : '',
      docs: docs.filter(d => d.fileCode || d.href),
      items,
    };
  });
}

/**
 * Captura los documentos (Bases, TDR, ...) de la Ficha de Selección de cada
 * proceso listado. Cubre los procesos cuyo release OCDS aún no publica el
 * link del documento (o no lo publicará nunca: el OCDS solo cubre una parte
 * de los procedimientos del SEACE).
 *
 * Cómo funciona: la tabla de resultados expone por fila un submit con
 * nidProceso/nidConvocatoria. Se replica ese POST con fetch (misma sesión,
 * sin navegar) y se parsea la tabla `tbFicha:dtDocumentos` de la ficha.
 * El `fileCode` obtenido resuelve la descarga on-demand (el ticket expira).
 */
async function extraerDocumentos(page, clavesFiltro = null, fichaIdDirecta = null) {
  const presupuesto = parseInt(process.env.SCRAPE_DOCS_BUDGET || '900000', 10);
  const pausa = parseInt(process.env.SCRAPE_DOCS_PAUSA || '400', 10);
  const filtrar = clavesFiltro instanceof Set && clavesFiltro.size > 0;
  const fichaIdObjetivo = String(fichaIdDirecta || '').trim();

  const documentos = [];
  const vistos = new Set();
  let ok = 0, fallos = 0, sinDocs = 0, seguidosFallos = 0, presupuestoAgotado = false, filasListado = 0;
  let fichaObjetivoLista = false;
  const tFichas = Date.now();

  // ── On-demand: ir directo a la ficha por id (deep-link público) ──
  // Si el id ya no es válido y hay nomenclatura, se continúa con la
  // búsqueda por listado (misma sesión del navegador).
  if (fichaIdObjetivo) {
    await page.goto(`${URL_FICHA}?id=${encodeURIComponent(fichaIdObjetivo)}&ptoRetorno=LOCAL`, {
      waitUntil: 'domcontentloaded',
      timeout: 45000,
    }).catch(() => null);

    for (let w = 0; w < 20; w++) {
      await sleep(750);
      const listo = await page.evaluate(() => /Ficha de Seleccion/i.test(document.body ? document.body.innerText : '')).catch(() => false);
      if (listo) break;
    }

    const parsed = await parseFichaActual(page).catch(() => null);

    if (parsed && parsed.esFicha) {
      console.log(JSON.stringify({
        docs_ficha_directa: 1,
        docs_encontrados: parsed.docs.length,
        items_encontrados: (parsed.items || []).length,
      }));
      return [{
        nomenclatura: parsed.nomenclatura || '',
        clave: filtrar ? [...clavesFiltro][0] : '',
        fichaId: fichaIdObjetivo,
        items: parsed.items || [],
        documentos: parsed.docs,
      }];
    }

    console.log('DOCS_FICHA_DIRECTA_FALLO:', JSON.stringify({ url: page.url().slice(0, 120) }));

    // El fallback a búsqueda por nomenclatura lo decide run() (necesita
    // volver a cargar el listado del buscador).
    return [];
  }

  // Procesos ya cubiertos por barridos previos (ficha/documentos capturados):
  // no se vuelven a navegar. El servicio escribe la lista (DB = fuente de verdad).
  // En modo on-demand el filtro manda: se quiere re-visitar ese proceso.
  const cubiertos = new Set();
  const skipFile = process.env.SCRAPE_SKIP_FILE;
  if (skipFile && fs.existsSync(skipFile)) {
    try {
      const lista = JSON.parse(fs.readFileSync(skipFile, 'utf8'));
      if (Array.isArray(lista)) lista.forEach(c => { if (c) cubiertos.add(String(c)); });
    } catch (e) { /* lista corrupta: se ignora */ }
  }
  let saltados = 0;

  // La tabla de resultados se procesa PÁGINA POR PÁGINA: el ViewState del
  // servidor solo conserva la página actual; si se recolectan todas las filas
  // y recién al final se postean, los botones de páginas anteriores ya no
  // existen en el view y el POST solo re-renderiza el buscador.
  let riPrimero = null;

  const diag = await page.evaluate(() => {
    const f = document.querySelector('form[id*="idFormBuscarProceso"]');
    return {
      url: location.href.slice(0, 160),
      form: !!f,
      action: f ? (f.getAttribute('action') || '').slice(0, 160) : null,
      rows: document.querySelectorAll('tbody tr[data-ri]').length,
    };
  });
  console.log('DOCS_DIAG:', JSON.stringify(diag));

  for (let pag = 1; pag <= 60; pag++) {
    const lote = await page.evaluate(() => {
      const cont = document.querySelector('[id*="pnlGrdResultadosProcesos"]') || document;
      const res = [];
      cont.querySelectorAll('tbody tr[data-ri]').forEach(tr => {
        const a = tr.querySelector('a[onclick*="ptoRetorno"]');
        if (!a) return;
        const celdas = Array.from(tr.querySelectorAll('td')).map(td => (td.innerText || '').replace(/\s+/g, ' ').trim());
        res.push({ ri: tr.getAttribute('data-ri'), celdas, onclick: a.getAttribute('onclick') || '' });
      });
      return res;
    });

    if (!lote.length) break;
    if (lote[0].ri === riPrimero) break; // el paginador no avanzó
    riPrimero = lote[0].ri;
    filasListado += lote.length;

    // ── Fichas de la página actual ──
    for (const fila of lote) {
      if (Date.now() - tFichas > presupuesto) { presupuestoAgotado = true; break; }

      const nom = fila.celdas.find(c => /^[A-Z]{2,6}-[A-Z0-9]{2,6}-\d+-\d{4}/.test(c)) || '';
      const clave = norm(nom);
      if (!clave || vistos.has(clave) || (filtrar && !clavesFiltro.has(clave))) continue;
      vistos.add(clave);

      // Salto incremental: ya cubierto por un barrido previo.
      // En modo on-demand con filtro se re-visita el proceso pedido.
      if (!filtrar && cubiertos.has(clave)) { saltados++; continue; }

      const m = (fila.onclick || '').match(/addSubmitParam\('[^']+',(\{.*?\})\)/s);
      if (!m) continue;
      let params;
      try { params = JSON.parse(m[1].replace(/'/g, '"')); } catch (e) { continue; }

      // Navegación real a la ficha (spike verificado): se inyectan los params
      // como campos ocultos y se hace submit del formulario; el POST completo
      // desde la página no es posible (fetch/XHR mueren a nivel red).
      const disparo = await page.evaluate((p) => {
        const f = document.querySelector('form[id*="idFormBuscarProceso"]');
        if (!f) return false;
        f.querySelectorAll('input[data-qa-ficha]').forEach(el => el.remove());
        for (const [k, v] of Object.entries(p)) {
          const i = document.createElement('input');
          i.type = 'hidden';
          i.name = k;
          i.value = v;
          i.setAttribute('data-qa-ficha', '1');
          f.appendChild(i);
        }
        f.submit();
        return true;
      }, params);

      if (!disparo) { fallos++; seguidosFallos++; if (seguidosFallos >= 5) break; continue; }

      await page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => null);
      await sleep(1200);

      // Id de la ficha (deep-link público): con esto el buscador enlaza a
      // SEACE on-demand (cronograma, ítems, contratos, historial) sin
      // almacenar el contenido.
      const fichaId = (page.url().match(/[?&]id=([a-f0-9-]{36})/i) || [])[1] || null;

      // Diagnóstico puntual (SCRAPE_DUMP_FICHA=1): guarda una ficha con datos
      // para inspeccionar sus tablas (ej. ítems) sin repetir corridas.
      if (process.env.SCRAPE_DUMP_FICHA === '1' && !extraerDocumentos._dumped) {
        try {
          fs.writeFileSync('/tmp/ficha-dump.html', await page.content());
          extraerDocumentos._dumped = true;
        } catch (e) { /* diagnóstico opcional */ }
      }

      const parsed = await parseFichaActual(page);

      // Volver al listado de resultados para la siguiente fila
      await page.goBack({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => null);
      await sleep(1200);

      const volvio = await page.evaluate(() => !!document.querySelector('form[id*="idFormBuscarProceso"]')).catch(() => false);

      if (!parsed || !parsed.esFicha) {
        fallos++;
        if (fallos <= 3) console.log('DOCS_NOFICHA:', JSON.stringify({ url: page.url().slice(0, 120), volvio }));
        seguidosFallos++;
        if (seguidosFallos >= 5) break;
        await sleep(pausa);
        continue;
      }

      ok++;
      seguidosFallos = 0;
      if (!parsed.docs.length) sinDocs++;
      documentos.push({ nomenclatura: parsed.nomenclatura || nom, clave, fichaId, items: parsed.items || [], documentos: parsed.docs });

      // On-demand: ya se capturó el proceso pedido, no seguir con el listado.
      if (filtrar) { fichaObjetivoLista = true; break; }

      await sleep(pausa);
    }

    if (fichaObjetivoLista) break;
    if (presupuestoAgotado || seguidosFallos >= 5) break;

    // ── Siguiente página ──
    const avanzo = await page.evaluate(() => {
      const btns = Array.from(document.querySelectorAll('.ui-paginator-next'));
      const btn = btns.find(b => ((b.closest('.ui-paginator') || {}).id || '').includes('Procesos')) || btns[0];
      if (!btn || btn.classList.contains('ui-state-disabled')) return false;
      btn.click();
      return true;
    });
    if (!avanzo) break;

    // Esperar a que la tabla cambie de página (poll hasta 8s)
    const riEsperado = riPrimero;
    for (let w = 0; w < 26; w++) {
      await sleep(300);
      const actual = await page.evaluate(() => {
        const cont = document.querySelector('[id*="pnlGrdResultadosProcesos"]') || document;
        const tr = cont.querySelector('tbody tr[data-ri]');
        return tr ? tr.getAttribute('data-ri') : null;
      });
      if (actual && actual !== riEsperado) break;
    }
  }

  console.log(JSON.stringify({
    docs_procesos: ok,
    docs_encontrados: documentos.reduce((n, d) => n + d.documentos.length, 0),
    docs_sin_documentos: sinDocs,
    docs_fallos: fallos,
    docs_saltados_ya_cubiertos: saltados,
    filas_listado: filasListado,
    docs_presupuesto_agotado: presupuestoAgotado,
  }));

  return documentos;
}

/**
 * Dejar el buscador público en la pestaña de Procedimientos de Selección, con
 * el rango de fechas aplicado y los resultados cargados. Flujo verificado que
 * comparten el barrido diario y la búsqueda on-demand.
 */
async function prepararBuscador(page, desde, hasta) {
  await page.goto(URL, { waitUntil: 'networkidle2', timeout: 90000 });
  await sleep(4000);

  await page.evaluate(() => {
    const t = Array.from(document.querySelectorAll('a')).find(l => (l.textContent || '').includes('Procedimientos de Selecci'));
    if (t) t.click();
  });
  await sleep(3000);

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
  await sleep(25000);
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

    // ── On-demand: captura de una sola ficha, sin export del Excel ──
    const fichaClaveModo = (process.env.SCRAPE_FICHA_CLAVE || '').trim();
    const fichaIdModo = (process.env.SCRAPE_FICHA_ID || '').trim();

    if (fichaClaveModo || fichaIdModo) {
      let documentos = [];
      try {
        if (fichaIdModo) {
          documentos = await extraerDocumentos(
            page,
            fichaClaveModo ? new Set([fichaClaveModo]) : null,
            fichaIdModo
          );
        }

        // Fallback: el id de ficha ya no es válido → buscar por nomenclatura
        // en el listado (rango = fecha de publicación ±1 día, lo fija Laravel).
        if (!documentos.length && fichaClaveModo) {
          await prepararBuscador(page, desde, hasta);
          documentos = await extraerDocumentos(page, new Set([fichaClaveModo]));
        }
      } catch (e) {
        console.error('DOCS_ERROR:', e.message);
      }

      fs.writeFileSync(salida, JSON.stringify({ success: true, count: 0, rows: [], documentos }));
      console.log(JSON.stringify({
        success: true,
        modo: fichaIdModo ? 'ficha' : 'busqueda',
        documentos: documentos.length,
      }));
      return;
    }

    const client = await page.createCDPSession();
    await client.send('Page.setDownloadBehavior', { behavior: 'allow', downloadPath: dlDir });

    await prepararBuscador(page, desde, hasta);

    // ── Documentos por proceso (Ficha de Selección) ──
    // Se ejecuta ANTES del export del Excel: el export deja la página en un
    // estado donde fetch() falla ("Failed to fetch"), y la captura de fichas
    // necesita postear desde la propia página (misma sesión y ViewState).
    let documentos = [];
    if (process.env.SCRAPE_DOCS !== '0') {
      try {
        documentos = await extraerDocumentos(page);
      } catch (e) {
        console.error('DOCS_ERROR:', e.message);
      }
    }

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

    fs.writeFileSync(salida, JSON.stringify({ success: true, count: out.length, rows: out, documentos }));

    console.log(JSON.stringify({ success: true, count: out.length, archivo, documentos: documentos.length }));
  } finally {
    await browser.close();
    fs.rmSync(dlDir, { recursive: true, force: true });
  }
}

run().catch(e => {
  console.error('ERROR:', e.message);
  process.exit(1);
});
