<?php

namespace App\Services;

class TdrAnalysisFormatter
{
    private const MAX_TELEGRAM_CHARS = 3800;
    private const TRUNCATED_NOTICE = "\n\n⚠️ Resumen truncado por limite de Telegram.";

    /**
     * Formatea el resultado para mensajes de Telegram (HTML) priorizando texto legible.
     */
    public function formatForTelegram(array $analysis, string $archivo, ?array $contextoContrato = null, ?string $shareUrl = null): string
    {
        $data = $this->normalize($analysis);
        $mensaje = "🤖 <b>ANÁLISIS IA COMPLETADO</b>\n\n";
        $mensaje .= '📄 <b>Archivo:</b> ' . $this->escapeHtml($archivo) . "\n\n";

        if ($contextoContrato) {
            $mensaje .= $this->formatContractContext($contextoContrato);
        }

        $sectionDefinitions = [
            [
                'title' => 'Resumen Ejecutivo',
                'icon' => '📊',
                'keys' => ['resumen_ejecutivo', 'resumen', 'executive_summary'],
            ],
            [
                'title' => 'Requisitos Técnicos',
                'icon' => '🛠️',
                'keys' => ['requisitos_tecnicos', 'requisitos_calificacion', 'requisitos', 'requirements'],
            ],
            [
                'title' => 'Reglas Operativas',
                'icon' => '⚙️',
                'keys' => ['reglas_de_negocio', 'reglas_ejecucion', 'condiciones_servicio', 'reglas'],
            ],
            [
                'title' => 'Políticas y Penalidades',
                'icon' => '⚖️',
                'keys' => ['politicas_y_penalidades', 'politicas', 'penalidades', 'politicas_penalidades', 'penalidad'],
            ],
            [
                'title' => 'Recomendaciones',
                'icon' => '💡',
                'keys' => ['recomendaciones', 'observaciones'],
                'bullet' => '⭐',
            ],
        ];

        $hasInsights = false;

        foreach ($sectionDefinitions as $definition) {
            $contenido = $this->pickFirstValue($data, $definition['keys']);

            if ($contenido === null) {
                continue;
            }

            $mensaje .= $this->formatSection(
                $definition['title'],
                $contenido,
                $definition['bullet'] ?? '•',
                $definition['icon']
            );

            $hasInsights = true;
        }

        $monto = $this->pickFirstValue($data, ['presupuesto_referencial', 'monto_referencial', 'monto']);
        if ($monto !== null) {
            $mensaje .= '💰 <b>Presupuesto Referencial:</b> ' . $this->escapeHtml((string) $monto) . "\n\n";
            $hasInsights = true;
        }

        $score = $this->pickFirstValue($data, ['score_compatibilidad', 'score', 'puntaje', 'compatibilidad']);
        if ($score !== null) {
            $mensaje .= '🏅 <b>Compatibilidad estimada:</b> ' . $this->escapeHtml((string) $score);
            if (is_numeric($score)) {
                $mensaje .= ' / 10';
            }
            $mensaje .= "\n\n";
            $hasInsights = true;
        }

        if (!$hasInsights) {
            $mensaje .= "📊 <b>Detalle completo:</b>\n";
            $mensaje .= $this->formatFallbackKeyValues($data);
        }

        $mensaje = $this->applyTelegramLimit($mensaje, $shareUrl);

        return trim($mensaje);
    }

    /**
     * Normaliza el arreglo de análisis para que siempre tengamos los campos principales disponibles.
     */
    protected function normalize(array $analysis): array
    {
        $containers = ['analisis', 'analysis', 'data', 'payload', 'resultado'];

        foreach ($containers as $container) {
            if (!array_key_exists($container, $analysis)) {
                continue;
            }

            $parsed = $this->parseContainerValue($analysis[$container]);
            if ($parsed !== null) {
                $analysis = array_merge($analysis, $parsed);
            }
        }

        return $analysis;
    }

    protected function parseContainerValue($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function pickFirstValue(array $data, array $candidateKeys)
    {
        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (!$this->hasContent($value)) {
                continue;
            }

            return $value;
        }

        return null;
    }

    protected function hasContent($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasContent($item)) {
                    return true;
                }
            }
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return true;
        }

        return $value !== '';
    }

    protected function formatContractContext(array $contexto): string
    {
        $campos = [
            'Entidad' => $contexto['entidad'] ?? null,
            'Objeto' => $contexto['objeto'] ?? null,
            'Estado' => $contexto['estado'] ?? null,
            'Etapa' => $contexto['etapa'] ?? null,
            'Código' => $contexto['codigo_proceso'] ?? null,
            'Publicación' => $contexto['fecha_publicacion'] ?? null,
            'Fin Cotización' => $contexto['fecha_cierre'] ?? null,
        ];

        $texto = '';
        foreach ($campos as $label => $valor) {
            if (!$this->hasContent($valor)) {
                continue;
            }
            if ($texto === '') {
                $texto .= "📌 <b>Contexto del Contrato:</b>\n";
            }
            $texto .= '• <b>' . $this->escapeHtml($label) . ':</b> ' . $this->escapeHtml((string) $valor) . "\n";
        }

        return $texto === '' ? '' : $texto . "\n";
    }

    protected function formatSection(string $titulo, $contenido, string $bullet = '•', string $icono = '📋'): string
    {
        if (!$this->hasContent($contenido)) {
            return '';
        }

        $texto = $icono . ' <b>' . $this->escapeHtml($titulo) . ':</b>' . "\n";

        if (is_array($contenido)) {
            $texto .= $this->renderList($contenido, $bullet);
        } else {
            $texto .= $this->escapeHtml((string) $contenido) . "\n";
        }

        return $texto . "\n";
    }

    protected function renderList(array $items, string $bullet = '•', int $depth = 0): string
    {
        $texto = '';
        $indent = str_repeat('    ', $depth);
        $isAssoc = $this->isAssoc($items);

        foreach ($items as $key => $value) {
            if (!$this->hasContent($value)) {
                continue;
            }

            $line = $indent . $bullet . ' ';

            if ($isAssoc) {
                $label = ucfirst(str_replace('_', ' ', (string) $key));
                $line .= '<b>' . $this->escapeHtml($label) . ':</b> ';
            }

            if (is_array($value)) {
                $texto .= $line . "\n" . $this->renderList($value, $bullet, $depth + 1);
            } else {
                $texto .= $line . $this->escapeHtml((string) $value) . "\n";
            }
        }

        return $texto;
    }

    protected function formatFallbackKeyValues(array $data): string
    {
        $texto = $this->renderList($data);
        return $texto === '' ? "• No se encontraron datos legibles.\n" : $texto;
    }

    protected function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function applyTelegramLimit(string $message, ?string $shareUrl = null): string
    {
        $message = trim($message);
        if ($this->stringLength($message) <= self::MAX_TELEGRAM_CHARS) {
            return $message;
        }

        $plain = trim(strip_tags($message));
        $suffix = $shareUrl
            ? "\n\n⚠️ Resumen truncado.\n🔗 Ver información completa: {$shareUrl}"
            : self::TRUNCATED_NOTICE;
        $limit = self::MAX_TELEGRAM_CHARS - $this->stringLength($suffix);
        $truncated = $this->stringSlice($plain, 0, max(0, $limit));

        // Cortar en el último salto de línea para no dejar texto a medias
        $lastNewline = strrpos($truncated, "\n");
        if ($lastNewline !== false && $lastNewline > ($limit * 0.6)) {
            $truncated = substr($truncated, 0, $lastNewline);
        }

        return rtrim($truncated) . $suffix;
    }

    /**
     * Formatea el resultado de análisis de direccionamiento (corrupción) para Telegram (HTML).
     */
    public function formatDireccionamientoForTelegram(array $analysis, string $archivo, ?array $contextoContrato = null, ?string $shareUrl = null): string
    {
        $data = $this->normalize($analysis);

        $score = (int) ($data['score_riesgo_corrupcion'] ?? 0);
        $veredicto = $data['veredicto_flash'] ?? 'SIN VEREDICTO';
        $hallazgos = $data['hallazgos_criticos'] ?? [];
        $argumento = $data['argumento_para_observacion'] ?? '';

        $gauge = $this->buildScoreGauge($score);
        $veredictoIcon = match ($veredicto) {
            'LIMPIO' => '🟢',
            'SOSPECHOSO' => '🟡',
            'ALTAMENTE DIRECCIONADO' => '🔴',
            default => '⚪',
        };

        $mensaje = "🔍 <b>ANÁLISIS DE DIRECCIONAMIENTO</b>\n\n";
        $mensaje .= '📄 <b>Archivo:</b> ' . $this->escapeHtml($archivo) . "\n\n";

        if ($contextoContrato) {
            $mensaje .= $this->formatContractContext($contextoContrato);
        }

        $mensaje .= "📊 <b>Score de Riesgo:</b> {$gauge} <b>{$score}/100</b>\n";
        $mensaje .= "{$veredictoIcon} <b>Veredicto:</b> {$veredicto}\n\n";

        if (!empty($hallazgos) && is_array($hallazgos)) {
            $mensaje .= "🚩 <b>Hallazgos Críticos:</b>\n";
            foreach ($hallazgos as $hallazgo) {
                if (!is_array($hallazgo)) {
                    $mensaje .= '• ' . $this->escapeHtml((string) $hallazgo) . "\n";
                    continue;
                }

                $categoria = $hallazgo['categoria'] ?? 'General';
                $descripcion = $hallazgo['descripcion_hallazgo'] ?? '';
                $redFlag = $hallazgo['red_flag_detectada'] ?? '';
                $gravedad = $hallazgo['nivel_de_gravedad'] ?? 'Medio';

                $gravedadIcon = match ($gravedad) {
                    'Alto' => '🔴',
                    'Medio' => '🟡',
                    'Bajo' => '🟢',
                    default => '⚪',
                };

                $mensaje .= "\n{$gravedadIcon} <b>[{$categoria}]</b> ({$gravedad})\n";
                if ($redFlag) {
                    $mensaje .= '  🚨 ' . $this->escapeHtml($redFlag) . "\n";
                }
                if ($descripcion) {
                    $mensaje .= '  ' . $this->escapeHtml($descripcion) . "\n";
                }
            }
            $mensaje .= "\n";
        }

        if ($argumento) {
            $mensaje .= "📝 <b>Argumento para Observación:</b>\n";
            $mensaje .= $this->escapeHtml($argumento) . "\n";
        }

        return trim($this->applyTelegramLimit($mensaje, $shareUrl));
    }

    /**
     * Construir barra visual de gauge para el score de riesgo.
     */
    protected function buildScoreGauge(int $score): string
    {
        $filled = (int) round($score / 10);
        $empty = 10 - $filled;

        if ($score <= 30) {
            $bar = str_repeat('🟩', $filled) . str_repeat('⬜', $empty);
        } elseif ($score <= 60) {
            $bar = str_repeat('🟨', $filled) . str_repeat('⬜', $empty);
        } else {
            $bar = str_repeat('🟥', $filled) . str_repeat('⬜', $empty);
        }

        return $bar;
    }

    protected function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    protected function stringSlice(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    }
}
