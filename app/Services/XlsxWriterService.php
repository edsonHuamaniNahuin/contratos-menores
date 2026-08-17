<?php

namespace App\Services;

use ZipArchive;

/**
 * Escritor XLSX nativo (OOXML + ZipArchive, sin librerías externas).
 *
 * Genera archivos .xlsx reales para que Excel no muestre el aviso de
 * "el formato y la extensión no coinciden" (que sí ocurría con
 * SpreadsheetML servido como .xls).
 *
 * API: generar(nombreHoja, filas, anchos)
 *   - filas: array de filas; cada fila es un array de celdas:
 *       ['v' => valor, 't' => 'string'|'number', 's' => nombreEstilo]
 *   - fila vacía [] = fila en blanco (espaciador)
 *   - anchos: opcional, [1 => 18, 2 => 50, ...] ancho por columna (1-based)
 *
 * Estilos disponibles por nombre:
 *   header (blanco bold sobre #1A3A5C), title, label, alt, total, section, footer.
 */
class XlsxWriterService
{
    /**
     * Mapa de estilos lógicos → índice de cellXf en styles.xml.
     */
    protected const ESTILOS = [
        ''             => 0, // default
        'header'       => 1,
        'title'        => 2,
        'label'        => 3,
        'alt'          => 4,
        'total'        => 5,
        'section'      => 6,
        'footer'       => 7,
        'header-verde' => 8,
    ];

    /**
     * @param string $nombreHoja
     * @param array<int, array<int, array{v: mixed, t?: string, s?: string}>> $filas
     * @param array<int, float> $anchos [colIndex(1-based) => ancho]
     * @return string binario del .xlsx
     */
    public function generar(string $nombreHoja, array $filas, array $anchos = []): string
    {
        $sheetXml = $this->buildSheetXml($filas, $anchos);

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal XLSX.');
        }

        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo abrir el archivo temporal XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars(substr($nombreHoja, 0, 31), ENT_XML1, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());

        $zip->close();

        $contenido = file_get_contents($tmp);
        @unlink($tmp);

        if ($contenido === false) {
            throw new \RuntimeException('No se pudo leer el archivo XLSX temporal.');
        }

        return $contenido;
    }

    /**
     * Construye el XML de la hoja (inline strings, sin sharedStrings).
     */
    protected function buildSheetXml(array $filas, array $anchos = []): string
    {
        $esc = fn (string $v): string => htmlspecialchars($v, ENT_XML1, 'UTF-8');

        $colsXml = '';
        if (!empty($anchos)) {
            ksort($anchos);
            $colsXml = '<cols>';
            foreach ($anchos as $indice => $ancho) {
                $colsXml .= '<col min="' . $indice . '" max="' . $indice . '" width="' . (float) $ancho . '" customWidth="1"/>';
            }
            $colsXml .= '</cols>';
        }

        $rowsXml = '';
        foreach ($filas as $rowIndex => $celdas) {
            if (empty($celdas)) {
                $rowsXml .= '<row r="' . ($rowIndex + 1) . '"></row>';
                continue;
            }

            $cellsXml = '';
            foreach ($celdas as $colIndex => $celda) {
                $ref = $this->colLetter($colIndex + 1) . ($rowIndex + 1);
                $valor = $celda['v'] ?? '';
                $tipo = $celda['t'] ?? 'string';
                $estilo = $celda['s'] ?? '';

                $xf = self::ESTILOS[$estilo] ?? 0;
                $styleAttr = $xf > 0 ? ' s="' . $xf . '"' : '';

                if ($tipo === 'number') {
                    $cellsXml .= '<c r="' . $ref . '"' . $styleAttr . '><v>' . $esc((string) $valor) . '</v></c>';
                } else {
                    if ($valor === '') {
                        continue;
                    }
                    $cellsXml .= '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">'
                        . $esc((string) $valor)
                        . '</t></is></c>';
                }
            }
            $rowsXml .= '<row r="' . ($rowIndex + 1) . '">' . $cellsXml . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $colsXml
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    /**
     * Styles.xml con los estilos lógicos del mapa ESTILOS.
     */
    protected function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="9">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF1A3A5C"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FF444444"/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FF7D5800"/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FF1A3A5C"/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><i/><color rgb="FF999999"/><sz val="8"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1A3A5C"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF5F8FC"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF9E7"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF025964"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="9">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="0" xfId="0" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="5" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="8" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * Convierte un índice de columna (1-based) a letra de Excel: 1→A, 27→AA, 30→AD.
     */
    protected function colLetter(int $indice): string
    {
        $letras = '';
        while ($indice > 0) {
            $indice--;
            $letras = chr(65 + ($indice % 26)) . $letras;
            $indice = (int) ($indice / 26);
        }
        return $letras;
    }
}
