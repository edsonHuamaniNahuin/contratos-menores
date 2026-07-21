<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.analytics', [
            'totals' => $this->queryGa4('ga4_totals', [7]),
            'topPages' => $this->queryGa4('ga4_top_pages', [7, 10]),
            'sources' => $this->queryGa4('ga4_traffic_sources', [7]),
            'events' => $this->queryGa4('ga4_all_events', [7, 15]),
            'pageViews' => $this->pageViewsMap(),
        ]);
    }

    private function queryGa4(string $tool, array $args = []): array
    {
        $script = base_path('scripts/ga4-mcp-server.py');
        $python = PHP_OS_FAMILY === 'Windows'
            ? 'python'
            : (file_exists(base_path('analizador-tdr/venv/bin/python'))
                ? base_path('analizador-tdr/venv/bin/python')
                : 'python3');

        $cmd = escapeshellcmd($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($tool);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string) $arg);
        }
        $cmd .= ' 2>/dev/null';

        $output = shell_exec($cmd);
        if (!$output) return [];

        $data = json_decode($output, true);
        if (!$data || isset($data['error'])) return [];

        return $data['rows'][0] ?? ($data['rows'] ?? []);
    }

    private function pageViewsMap(): array
    {
        $pages = $this->queryGa4('ga4_top_pages', [7, 50]);
        $map = ['/' => 0, '/buscador-publico' => 0, '/buscador-contratos-mayores' => 0, '/planes' => 0, '/register' => 0, '/dashboard' => 0];
        foreach ($pages as $p) {
            $path = rtrim($p['pagePath'] ?? '', '/') ?: '/';
            $map[$path] = ($map[$path] ?? 0) + (int)($p['screenPageViews'] ?? 0);
        }
        return $map;
    }
}
