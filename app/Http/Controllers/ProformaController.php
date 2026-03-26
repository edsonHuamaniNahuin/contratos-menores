<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ProformaController extends Controller
{
    /**
     * Descargar proforma como documento Word (.doc).
     * Genera un HTML compatible con Word enviado con MIME type application/vnd.ms-word.
     */
    public function downloadWord(Request $request, string $token): Response
    {
        $proforma = $this->resolveProforma($token);

        if (!$proforma) {
            abort(404, 'Proforma no encontrada o expirada.');
        }

        $html = $this->buildHtml($proforma);

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-word',
            'Content-Disposition' => 'attachment; filename="proforma-tecnica.doc"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Ver proforma en HTML con formato de impresión (para guardar como PDF desde el navegador).
     */
    public function viewPrint(Request $request, string $token): Response
    {
        $proforma = $this->resolveProforma($token);

        if (!$proforma) {
            abort(404, 'Proforma no encontrada o expirada.');
        }

        $html = $this->buildHtml($proforma, printFriendly: true);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Descargar proforma como hoja de cálculo Excel (SpreadsheetML, sin librería externa).
     */
    public function downloadExcel(Request $request, string $token): Response
    {
        $proforma = $this->resolveProforma($token);

        if (!$proforma) {
            abort(404, 'Proforma no encontrada o expirada.');
        }

        $xml = $this->buildSpreadsheetML($proforma);

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="proforma-tecnica.xls"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────

    private function resolveProforma(string $token): ?array
    {
        // Validar formato UUID para evitar inputs maliciosos
        if (!preg_match('/^[0-9a-f\-]{36}$/i', $token)) {
            return null;
        }

        // Las rutas están protegidas por middleware 'auth', por lo que Auth::check()
        // es siempre verdadero aquí. El token actúa como clave de acceso opaca.
        return Cache::get("proforma:{$token}") ?: null;
    }

    private function buildHtml(array $proforma, bool $printFriendly = false): string
    {
        $titulo    = e($proforma['titulo_proceso'] ?? 'Proforma Técnica');
        $empresa   = e($proforma['empresa_nombre'] ?? '');
        $rubro     = e($proforma['empresa_rubro'] ?? '');
        $items     = $proforma['items'] ?? [];
        $totalCalc = array_sum(array_map(fn($i) => (float)($i['subtotal'] ?? 0), $items));
        $totalFb   = (float) preg_replace('/[^0-9.]/', '', $proforma['total_estimado'] ?? '');
        $total     = 'S/ ' . number_format($totalCalc > 0 ? $totalCalc : $totalFb, 2);
        $viabilidad = $proforma['analisis_viabilidad'] ?? '';
        $condiciones = $proforma['condiciones'] ?? [];
        $entidad   = e($proforma['contexto_contrato']['entidad'] ?? '');
        $fecha     = now()->format('d/m/Y');

        $printScript = $printFriendly
            ? '<script>window.onload = function(){ window.print(); }</script>'
            : '';

        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td style="text-align:right">%s</td><td style="text-align:right">S/ %s</td><td style="text-align:right">S/ %s</td></tr>',
                (int) ($item['item'] ?? 0),
                e($item['descripcion'] ?? ''),
                e($item['unidad'] ?? ''),
                number_format((float) ($item['cantidad'] ?? 0), 2),
                number_format((float) ($item['precio_unitario'] ?? 0), 2),
                number_format((float) ($item['subtotal'] ?? 0), 2)
            );
        }

        $condicionesHtml = '';
        foreach ($condiciones as $cond) {
            $condicionesHtml .= '<li>' . e($cond) . '</li>';
        }

        $viabilidadParrafos = '';
        foreach (explode("\n", $viabilidad) as $parrafo) {
            $parrafo = trim($parrafo);
            if ($parrafo !== '') {
                $viabilidadParrafos .= '<p>' . e($parrafo) . '</p>';
            }
        }

        $condicionesSection = $condicionesHtml
            ? '<h2>Condiciones y Supuestos</h2><ul class="condiciones">' . $condicionesHtml . '</ul>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Proforma Técnica — {$titulo}</title>
  {$printScript}
  <style>
    body { font-family: Arial, sans-serif; font-size: 11pt; margin: 2cm; color: #1a1a1a; }
    h1 { font-size: 16pt; color: #1a3a5c; border-bottom: 2px solid #1a3a5c; padding-bottom: 6px; }
    h2 { font-size: 12pt; color: #1a3a5c; margin-top: 20px; }
    .header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
    .header-table td { padding: 3px 8px; font-size: 10pt; }
    .header-table .label { font-weight: bold; width: 160px; color: #444; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.items th { background: #1a3a5c; color: #fff; padding: 7px 10px; text-align: left; font-size: 10pt; }
    table.items td { padding: 6px 10px; border-bottom: 1px solid #e0e0e0; font-size: 10pt; }
    table.items tr:nth-child(even) td { background: #f5f8fc; }
    .total-row td { font-weight: bold; background: #e8f0fb; border-top: 2px solid #1a3a5c; }
    .viabilidad p { margin: 6px 0; line-height: 1.6; }
    ul.condiciones { margin: 8px 0; padding-left: 20px; }
    ul.condiciones li { margin-bottom: 4px; }
    .footer { margin-top: 40px; border-top: 2px solid #1a3a5c; padding-top: 12px; text-align: center; }
    .footer .firma { font-size: 11pt; font-weight: bold; color: #1a3a5c; letter-spacing: 0.02em; }
    .footer .aviso { font-size: 8pt; color: #999; margin-top: 4px; }
    @media print {
      body { margin: 1.5cm; }
      @page { margin: 1.5cm; }
    }
  </style>
</head>
<body>
  <h1>📋 Proforma Técnica de Cotización</h1>

  <table class="header-table">
    <tr>
      <td class="label">Empresa:</td>
      <td><strong>{$empresa}</strong></td>
      <td class="label">Fecha:</td>
      <td>{$fecha}</td>
    </tr>
    <tr>
      <td class="label">Rubro:</td>
      <td>{$rubro}</td>
      <td class="label">Entidad:</td>
      <td>{$entidad}</td>
    </tr>
    <tr>
      <td class="label">Proceso:</td>
      <td colspan="3"><em>{$titulo}</em></td>
    </tr>
  </table>

  <h2>Tabla de Cotización</h2>
  <table class="items">
    <thead>
      <tr>
        <th width="40">Ítem</th>
        <th>Descripción</th>
        <th width="80">Unidad</th>
        <th width="70">Cantidad</th>
        <th width="110">Precio Unit. (S/)</th>
        <th width="110">Subtotal (S/)</th>
      </tr>
    </thead>
    <tbody>
      {$itemRows}
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="5" style="text-align:right; padding-right:10px">TOTAL ESTIMADO:</td>
        <td style="text-align:right">{$total}</td>
      </tr>
    </tfoot>
  </table>

  <h2>Análisis de Viabilidad Operativa</h2>
  <div class="viabilidad">{$viabilidadParrafos}</div>

  {$condicionesSection}

  <div class="footer">
    <div class="firma">Generado con la inteligencia de LicitacionesMYPE.pe</div>
    <div class="aviso">{$fecha} &mdash; Este documento es un borrador de cotización con fines orientativos. Los precios son referenciales.</div>
  </div>
</body>
</html>
HTML;
    }

    private function buildSpreadsheetML(array $proforma): string
    {
        $titulo     = $proforma['titulo_proceso'] ?? 'Proforma Técnica';
        $empresa    = $proforma['empresa_nombre'] ?? '';
        $rubro      = $proforma['empresa_rubro'] ?? '';
        $items      = $proforma['items'] ?? [];
        $totalCalc  = array_sum(array_map(fn($i) => (float)($i['subtotal'] ?? 0), $items));
        $totalFb    = (float) preg_replace('/[^0-9.]/', '', $proforma['total_estimado'] ?? '');
        $total      = $totalCalc > 0 ? $totalCalc : $totalFb;
        $viabilidad = $proforma['analisis_viabilidad'] ?? '';
        $condiciones = $proforma['condiciones'] ?? [];
        $entidad    = $proforma['contexto_contrato']['entidad'] ?? '';
        $fecha      = now()->format('d/m/Y');

        // Helpers para generar XML seguro
        $esc  = fn(string $v) => htmlspecialchars($v, ENT_XML1, 'UTF-8');
        $cell = function (mixed $v, string $t = 'String', string $style = '') use ($esc): string {
            $sa = $style !== '' ? " ss:StyleID=\"{$style}\"" : '';
            return "<Cell{$sa}><Data ss:Type=\"{$t}\">" . $esc((string) $v) . "</Data></Cell>";
        };
        $row   = fn(array $cells) => "<Row>" . implode('', $cells) . "</Row>\n";
        $empty = "<Row/>\n";

        // ── Encabezado informativo ──────────────────────────────────
        $rows = '';
        $rows .= $row([$cell('Proforma Técnica de Cotización', 'String', 'title')]);
        $rows .= $empty;
        $rows .= $row([$cell('Empresa:', 'String', 'label'), $cell($empresa), $cell('Fecha:', 'String', 'label'), $cell($fecha)]);
        $rows .= $row([$cell('Rubro:', 'String', 'label'), $cell($rubro), $cell('Entidad:', 'String', 'label'), $cell($entidad)]);
        $rows .= $row([$cell('Proceso:', 'String', 'label'), $cell($titulo)]);
        $rows .= $empty;

        // ── Cabeceras de tabla ──────────────────────────────────────
        $rows .= $row([
            $cell('Ítem', 'String', 'header'),
            $cell('Descripción', 'String', 'header'),
            $cell('Unidad', 'String', 'header'),
            $cell('Cantidad', 'String', 'header'),
            $cell('Precio Unit. (S/)', 'String', 'header'),
            $cell('Subtotal (S/)', 'String', 'header'),
        ]);

        // ── Filas de ítems ──────────────────────────────────────────
        foreach ($items as $idx => $item) {
            $st = ($idx % 2 === 1) ? 'alt' : '';
            $rows .= $row([
                $cell((int) ($item['item'] ?? ($idx + 1)), 'Number', $st),
                $cell($item['descripcion'] ?? '', 'String', $st),
                $cell($item['unidad'] ?? '', 'String', $st),
                $cell((float) ($item['cantidad'] ?? 0), 'Number', $st),
                $cell((float) ($item['precio_unitario'] ?? 0), 'Number', $st),
                $cell((float) ($item['subtotal'] ?? 0), 'Number', $st),
            ]);
        }

        // ── Fila TOTAL ──────────────────────────────────────────────
        $rows .= $row([
            $cell('', 'String', 'total'), $cell('', 'String', 'total'),
            $cell('', 'String', 'total'), $cell('', 'String', 'total'),
            $cell('TOTAL ESTIMADO:', 'String', 'total'),
            $cell('S/ ' . number_format($total, 2), 'String', 'total'),
        ]);
        $rows .= $empty;

        // ── Viabilidad ──────────────────────────────────────────────
        if ($viabilidad !== '') {
            $rows .= $row([$cell('Análisis de Viabilidad Operativa', 'String', 'section')]);
            $rows .= $row([$cell($viabilidad)]);
            $rows .= $empty;
        }

        // ── Condiciones ─────────────────────────────────────────────
        if (!empty($condiciones)) {
            $rows .= $row([$cell('Condiciones y Supuestos', 'String', 'section')]);
            foreach ($condiciones as $cond) {
                $rows .= $row([$cell('• ' . $cond)]);
            }
            $rows .= $empty;
        }

        // ── Pie ─────────────────────────────────────────────────────
        $rows .= $row([$cell("Generado con la inteligencia de LicitacionesMYPE.pe — {$fecha}", 'String', 'footer')]);
        $rows .= $row([$cell('Este documento es un borrador de cotización con fines orientativos. Los precios son referenciales.', 'String', 'footer')]);

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?mso-application progid="Excel.Sheet"?>' . "\n"
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n"
            . '  xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n"
            . '  xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n"
            . '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n"
            . '  <Styles>' . "\n"
            . '    <Style ss:ID="title"><Font ss:Bold="1" ss:Size="14" ss:Color="#1A3A5C"/></Style>' . "\n"
            . '    <Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1A3A5C" ss:Pattern="Solid"/></Style>' . "\n"
            . '    <Style ss:ID="total"><Font ss:Bold="1" ss:Color="#7D5800"/><Interior ss:Color="#FEF9E7" ss:Pattern="Solid"/></Style>' . "\n"
            . '    <Style ss:ID="label"><Font ss:Bold="1" ss:Color="#444444"/></Style>' . "\n"
            . '    <Style ss:ID="alt"><Interior ss:Color="#F5F8FC" ss:Pattern="Solid"/></Style>' . "\n"
            . '    <Style ss:ID="section"><Font ss:Bold="1" ss:Size="11" ss:Color="#1A3A5C"/></Style>' . "\n"
            . '    <Style ss:ID="footer"><Font ss:Italic="1" ss:Color="#999999" ss:Size="8"/></Style>' . "\n"
            . '  </Styles>' . "\n"
            . '  <Worksheet ss:Name="Proforma Técnica">' . "\n"
            . '    <Table ss:DefaultColumnWidth="120">' . "\n"
            . $rows
            . '    </Table>' . "\n"
            . '  </Worksheet>' . "\n"
            . '</Workbook>';
    }
}
