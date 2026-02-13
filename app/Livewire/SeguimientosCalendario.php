<?php

namespace App\Livewire;

use App\Models\ContratoSeguimiento;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SeguimientosCalendario extends Component
{
    public string $mesActual;
    public array $dias = [];
    public array $seguimientos = [];
    public ?int $detalleId = null;
    public ?array $detalleSeguimiento = null;

    protected string $zonaHoraria = 'America/Lima';

    public function mount(): void
    {
        $this->mesActual = Carbon::now($this->zonaHoraria)->format('Y-m');
        $this->cargar();
    }

    public function mesAnterior(): void
    {
        $fecha = Carbon::createFromFormat('Y-m', $this->mesActual, $this->zonaHoraria)->subMonth();
        $this->mesActual = $fecha->format('Y-m');
        $this->cargar();
    }

    public function mesSiguiente(): void
    {
        $fecha = Carbon::createFromFormat('Y-m', $this->mesActual, $this->zonaHoraria)->addMonth();
        $this->mesActual = $fecha->format('Y-m');
        $this->cargar();
    }

    public function getMesLabelProperty(): string
    {
        $fecha = Carbon::createFromFormat('Y-m', $this->mesActual, $this->zonaHoraria)->locale('es');
        return ucfirst($fecha->isoFormat('MMMM YYYY'));
    }

    protected function cargar(): void
    {
        $this->cargarSeguimientos();
        $this->generarCalendario();
    }

    protected function cargarSeguimientos(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->seguimientos = [];
            return;
        }

        $items = ContratoSeguimiento::query()
            ->where('user_id', $userId)
            ->orderByDesc('fecha_fin')
            ->get();

        $now = Carbon::now($this->zonaHoraria);

        $this->seguimientos = $items->map(function (ContratoSeguimiento $item) use ($now) {
            $inicio = $item->fecha_inicio ?? $item->fecha_publicacion;
            $fin = $item->fecha_fin ?? $inicio;

            return [
                'id' => $item->id,
                'codigo' => $item->codigo_proceso,
                'entidad' => $item->entidad,
                'objeto' => $item->objeto,
                'estado' => $item->estado,
                'inicio' => $inicio?->toDateString(),
                'fin' => $fin?->toDateString(),
                'inicio_label' => $inicio?->format('d/m/Y'),
                'fin_label' => $fin?->format('d/m/Y'),
                'urgencia' => $this->resolverUrgencia($fin, $now),
            ];
        })->toArray();

        $this->hidratarDetalle();
    }

    public function verDetalle(int $seguimientoId): void
    {
        $this->detalleId = $seguimientoId;
        $this->hidratarDetalle();
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
        $this->detalleSeguimiento = null;
    }

    protected function generarCalendario(): void
    {
        $inicioMes = Carbon::createFromFormat('Y-m', $this->mesActual, $this->zonaHoraria)->startOfMonth();
        $inicio = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $inicioMes->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $dias = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $fecha = $cursor->toDateString();
            $eventos = array_values(array_filter($this->seguimientos, function ($seguimiento) use ($fecha) {
                if (empty($seguimiento['inicio']) || empty($seguimiento['fin'])) {
                    return false;
                }

                return $fecha >= $seguimiento['inicio'] && $fecha <= $seguimiento['fin'];
            }));

            $eventosLimitados = array_slice($eventos, 0, 2);

            $dias[] = [
                'fecha' => $fecha,
                'numero' => $cursor->day,
                'mesActual' => $cursor->format('Y-m') === $this->mesActual,
                'eventos' => $eventosLimitados,
                'extra' => max(count($eventos) - count($eventosLimitados), 0),
            ];

            $cursor->addDay();
        }

        $this->dias = $dias;
    }

    protected function resolverUrgencia(?Carbon $fin, Carbon $now): string
    {
        if (!$fin) {
            return 'normal';
        }

        $diferencia = $now->diffInDays($fin, false);

        if ($diferencia <= 2) {
            return 'critico';
        }

        if ($diferencia <= 5) {
            return 'alto';
        }

        if ($diferencia <= 7) {
            return 'medio';
        }

        return 'estable';
    }

    protected function hidratarDetalle(): void
    {
        if (!$this->detalleId) {
            $this->detalleSeguimiento = null;
            return;
        }

        $this->detalleSeguimiento = collect($this->seguimientos)
            ->firstWhere('id', $this->detalleId);

        if (!$this->detalleSeguimiento) {
            $this->detalleId = null;
        }
    }

    public function render()
    {
        return view('livewire.seguimientos-calendario');
    }
}
