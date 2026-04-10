<?php

namespace App\Livewire;

use App\Models\TdrAnalisis;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ConsumoIaDashboard extends Component
{
    use WithPagination;

    public string $rangoFechas = '30'; // últimos 30 días por defecto
    public string $filtroOrigen = '';
    public string $filtroUsuario = '';

    /** Resumen global: totales de tokens, análisis, costo */
    public array $resumen = [];

    public function mount(): void
    {
        $this->calcularResumen();
    }

    public function updatedRangoFechas(): void
    {
        $this->resetPage();
        $this->calcularResumen();
    }

    public function updatedFiltroOrigen(): void
    {
        $this->resetPage();
        $this->calcularResumen();
    }

    public function updatedFiltroUsuario(): void
    {
        $this->resetPage();
        $this->calcularResumen();
    }

    protected function baseQuery()
    {
        $query = TdrAnalisis::query()
            ->where('estado', TdrAnalisis::ESTADO_EXITOSO);

        if ($this->rangoFechas !== 'todo') {
            $query->where('analizado_en', '>=', now()->subDays((int) $this->rangoFechas));
        }

        if ($this->filtroOrigen !== '') {
            $query->where('origin', $this->filtroOrigen);
        }

        if ($this->filtroUsuario !== '') {
            $query->where('requested_by_user_id', (int) $this->filtroUsuario);
        }

        return $query;
    }

    public function calcularResumen(): void
    {
        $agg = $this->baseQuery()
            ->selectRaw('COUNT(*) as total_analisis')
            ->selectRaw('COALESCE(SUM(tokens_prompt), 0) as total_tokens_prompt')
            ->selectRaw('COALESCE(SUM(tokens_respuesta), 0) as total_tokens_respuesta')
            ->selectRaw('COALESCE(SUM(costo_estimado), 0) as total_costo')
            ->first();

        $this->resumen = [
            'total_analisis' => $agg->total_analisis ?? 0,
            'total_tokens_prompt' => $agg->total_tokens_prompt ?? 0,
            'total_tokens_respuesta' => $agg->total_tokens_respuesta ?? 0,
            'total_costo' => $agg->total_costo ?? 0,
        ];
    }

    public function render()
    {
        // Detalle por usuario
        $porUsuario = $this->baseQuery()
            ->select('requested_by_user_id')
            ->selectRaw('COUNT(*) as total_analisis')
            ->selectRaw('COALESCE(SUM(tokens_prompt), 0) as tokens_prompt')
            ->selectRaw('COALESCE(SUM(tokens_respuesta), 0) as tokens_respuesta')
            ->selectRaw('COALESCE(SUM(costo_estimado), 0) as costo_estimado')
            ->selectRaw('MAX(analizado_en) as ultimo_analisis')
            ->groupBy('requested_by_user_id')
            ->orderByDesc('tokens_prompt')
            ->paginate(15);

        // Cargar nombres de usuario en una sola consulta
        $userIds = $porUsuario->pluck('requested_by_user_id')->filter()->unique()->values();
        $usuarios = [];
        if ($userIds->isNotEmpty()) {
            $usuarios = DB::table('users')
                ->whereIn('id', $userIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        // Lista de usuarios para el filtro
        $listaUsuarios = DB::table('tdr_analisis')
            ->whereNotNull('requested_by_user_id')
            ->where('estado', TdrAnalisis::ESTADO_EXITOSO)
            ->distinct()
            ->pluck('requested_by_user_id');

        $usuariosParaFiltro = [];
        if ($listaUsuarios->isNotEmpty()) {
            $usuariosParaFiltro = DB::table('users')
                ->whereIn('id', $listaUsuarios)
                ->pluck('name', 'id')
                ->toArray();
        }

        return view('livewire.consumo-ia-dashboard', [
            'porUsuario' => $porUsuario,
            'usuarios' => $usuarios,
            'usuariosParaFiltro' => $usuariosParaFiltro,
        ]);
    }
}
