<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Visor de heatmaps first-party de las landings (clics con coordenadas +
 * profundidad de scroll), sobre screenshot móvil de cada página.
 */
class AdminHeatmapController extends Controller
{
    public const LANDINGS = [
        'alertas-licitaciones' => 'E1 · Alertas',
        'software-licitaciones' => 'E2 · Software',
        'licitaciones-vigentes' => 'E3 · Vigentes',
    ];

    public function index(Request $request)
    {
        $landing = array_key_exists((string) $request->query('landing'), self::LANDINGS)
            ? (string) $request->query('landing')
            : array_key_first(self::LANDINGS);

        $dias = in_array((int) $request->query('dias'), [1, 7, 14, 30], true)
            ? (int) $request->query('dias')
            : 7;

        return view('admin.heatmaps', [
            'landing' => $landing,
            'dias' => $dias,
            'landings' => self::LANDINGS,
        ]);
    }

    public function data(Request $request)
    {
        $landing = array_key_exists((string) $request->query('landing'), self::LANDINGS)
            ? (string) $request->query('landing')
            : array_key_first(self::LANDINGS);

        $dias = in_array((int) $request->query('dias'), [1, 7, 14, 30], true)
            ? (int) $request->query('dias')
            : 7;

        $desde = now()->subDays($dias)->startOfDay();

        $clicks = DB::table('page_events')
            ->where('landing', $landing)
            ->where('tipo', 'click')
            ->where('created_at', '>=', $desde)
            ->orderByDesc('id')
            ->limit(3000)
            ->get(['x_pct', 'y_pct', 'target']);

        $scroll = DB::table('page_events')
            ->where('landing', $landing)
            ->where('tipo', 'scroll')
            ->where('created_at', '>=', $desde)
            ->selectRaw('scroll_pct, COUNT(*) as total')
            ->groupBy('scroll_pct')
            ->pluck('total', 'scroll_pct');

        $targets = DB::table('page_events')
            ->where('landing', $landing)
            ->where('tipo', 'click')
            ->where('created_at', '>=', $desde)
            ->selectRaw('target, COUNT(*) as total')
            ->groupBy('target')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        return response()->json([
            'landing' => $landing,
            'dias' => $dias,
            'clicks' => $clicks,
            'scroll' => [
                '25' => (int) ($scroll[25] ?? 0),
                '50' => (int) ($scroll[50] ?? 0),
                '75' => (int) ($scroll[75] ?? 0),
                '100' => (int) ($scroll[100] ?? 0),
            ],
            'targets' => $targets,
            'total_clicks' => $clicks->count(),
        ]);
    }
}
