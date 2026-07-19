<?php

namespace App\Livewire;

use App\Models\ContratoSeguimiento;
use App\Models\ContratoSeguimientoMayor;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SeguimientosCalendario extends Component
{
    public string $mesActual;
    public array $dias = [];
    public array $seguimientos = [];
    public int|string|null $detalleId = null;
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
        $this->dispatch('seguimientos-updated');
    }

    protected function cargarSeguimientos(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->seguimientos = [];
            return;
        }

        $now = Carbon::now($this->zonaHoraria);

        // Menores
        $itemsMenores = ContratoSeguimiento::query()
            ->where('user_id', $userId)
            ->orderByDesc('fecha_fin')
            ->get();

        $seguimientosMenores = $itemsMenores->map(function (ContratoSeguimiento $item) use ($now) {
            $snapshot = $item->snapshot ?? [];
            $inicio = $item->fecha_inicio ?? $item->fecha_publicacion;
            $fin = $item->fecha_fin ?? $inicio;

            return [
                'id'             => $item->id,
                'tipo'           => 'menores',
                'codigo'         => $item->codigo_proceso,
                'entidad'        => $item->entidad,
                'entidad_ruc'    => $snapshot['entidad_ruc'] ?? '',
                'entidad_direccion' => $snapshot['entidad_direccion'] ?? '',
                'objeto'         => $item->objeto,
                'metodo'         => $snapshot['metodo_contratacion'] ?? '',
                'estado'         => $item->estado,
                'vigente'        => $snapshot['vigente'] ?? null,
                'estado_vigencia'=> $snapshot['estado_vigencia'] ?? '',
                'moneda'         => $snapshot['moneda'] ?? '',
                'monto'          => $snapshot['monto_formateado'] ?? ($snapshot['valor_referencial'] ?? null),
                'fecha_publicacion' => $snapshot['fecha_formateada'] ?? '',
                'descripcion'    => $snapshot['descripcion_objeto'] ?? '',
                'url_documento'  => $snapshot['url_documento'] ?? '',
                'ocid'           => $snapshot['ocid'] ?? '',
                'nomenclatura'   => $snapshot['nomenclatura'] ?? $item->codigo_proceso,
                'inicio'         => $inicio?->toDateString(),
                'fin'            => $fin?->toDateString(),
                'inicio_label'   => $inicio?->format('d/m/Y'),
                'fin_label'      => $fin?->format('d/m/Y'),
                'urgencia'       => $this->resolverUrgencia($fin, $now),
            ];
        })->toArray();

        // Mayores
        $itemsMayores = ContratoSeguimientoMayor::query()
            ->where('user_id', $userId)
            ->orderByDesc('fecha_publicacion')
            ->get();

        $seguimientosMayores = $itemsMayores->map(function (ContratoSeguimientoMayor $item) use ($now) {
            $snapshot = $item->snapshot ?? [];
            $inicio = isset($snapshot['fecha_inicio']) ? Carbon::parse($snapshot['fecha_inicio']) : $item->fecha_publicacion;
            $fin = isset($snapshot['fecha_fin']) ? Carbon::parse($snapshot['fecha_fin']) : $inicio;
            $fin = $fin ?? $inicio;

            return [
                'id'             => 'mayor_' . $item->id,
                'tipo'           => 'mayores',
                'db_id'          => $item->id,
                'codigo'         => $item->codigo_proceso,
                'entidad'        => $item->entidad_nombre,
                'entidad_ruc'    => $snapshot['entidad_ruc'] ?? '',
                'entidad_direccion' => $snapshot['entidad_direccion'] ?? '',
                'objeto'         => $item->objeto_contratacion,
                'metodo'         => $snapshot['metodo_contratacion'] ?? '',
                'estado'         => $item->estado,
                'vigente'        => $snapshot['vigente'] ?? null,
                'estado_vigencia'=> $snapshot['estado_vigencia'] ?? '',
                'moneda'         => $snapshot['moneda'] ?? $item->moneda ?? '',
                'monto'          => $snapshot['monto_formateado'] ?? ($item->valor_referencial > 0 ? 'S/ ' . number_format($item->valor_referencial, 2) : '---'),
                'fecha_publicacion' => $snapshot['fecha_formateada'] ?? ($item->fecha_publicacion ? $item->fecha_publicacion->format('d/m/Y') : ''),
                'descripcion'    => $snapshot['descripcion_objeto'] ?? '',
                'url_documento'  => $snapshot['url_documento'] ?? '',
                'ocid'           => $item->ocid,
                'nomenclatura'   => $snapshot['nomenclatura'] ?? $item->codigo_proceso,
                'inicio'         => $inicio?->toDateString(),
                'fin'            => $fin?->toDateString(),
                'inicio_label'   => $inicio?->format('d/m/Y'),
                'fin_label'      => $fin?->format('d/m/Y'),
                'urgencia'       => $this->resolverUrgencia($fin, $now),
            ];
        })->toArray();

        $this->seguimientos = array_merge($seguimientosMenores, $seguimientosMayores);

        $this->hidratarDetalle();
    }

    public function verDetalle(int|string $seguimientoId): void
    {
        $this->detalleId = $seguimientoId;
        $this->hidratarDetalle();
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
        $this->detalleSeguimiento = null;
    }

    public function eliminarSeguimiento(int|string $id): void
    {
        if (is_string($id) && str_starts_with($id, 'mayor_')) {
            $dbId = (int) substr($id, 6);
            ContratoSeguimientoMayor::where('id', $dbId)
                ->where('user_id', Auth::id())
                ->delete();
        } else {
            ContratoSeguimiento::where('id', (int) $id)
                ->where('user_id', Auth::id())
                ->delete();
        }

        if ((string) $this->detalleId === (string) $id) {
            $this->cerrarDetalle();
        }

        $this->cargar();
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
        $col = collect($this->seguimientos);

        return view('livewire.seguimientos-calendario', [
            'totalSeguimientos' => $col->count(),
            'totalCriticos' => $col->where('urgencia', 'critico')->count(),
            'totalAltos' => $col->where('urgencia', 'alto')->count(),
            'totalEstables' => $col->whereNotIn('urgencia', ['critico', 'alto'])->count(),
        ]);
    }
}
