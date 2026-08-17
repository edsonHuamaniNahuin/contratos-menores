<?php

namespace App\Livewire;

use App\Jobs\VigilarAdjudicacionesMayoresJob;
use App\Models\SystemSetting;
use App\Models\VigilanciaAdjudicacion;
use App\Models\VigilanciaAdjudicacionDestinatario;
use Livewire\Component;

/**
 * Administración de la vigilancia de adjudicaciones (procesos >= umbral):
 *  - Destinatarios de alertas (email y/o WhatsApp, 1 o varios)
 *  - Umbral de monto configurable
 *  - Ejecución manual del job
 *  - Estadísticas (vigilados, pendientes, notificados)
 */
class VigilanciaAdjudicacionesAdmin extends Component
{
    public array $destinatarios = [];
    public string $nuevoEmail = '';
    public string $nuevoTelefono = '';
    public string $umbral = '1000000';
    public array $stats = [
        'vigilados' => 0,
        'pendientes' => 0,
        'notificados' => 0,
    ];
    public bool $ejecutando = false;

    public function mount(): void
    {
        $this->cargar();
    }

    public function cargar(): void
    {
        $this->destinatarios = VigilanciaAdjudicacionDestinatario::orderBy('id')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'email' => $d->email,
                'telefono' => $d->telefono,
                'activo' => (bool) $d->activo,
            ])
            ->toArray();

        $this->umbral = (string) SystemSetting::getValue('vigilancia_monto_min', 1_000_000);

        $this->stats = [
            'vigilados' => VigilanciaAdjudicacion::count(),
            'pendientes' => VigilanciaAdjudicacion::whereNull('notificado_en')
                ->whereNotIn('estado', VigilanciaAdjudicacion::ESTADOS_FINALES)
                ->count(),
            'notificados' => VigilanciaAdjudicacion::whereNotNull('notificado_en')->count(),
        ];
    }

    public function agregarDestinatario(): void
    {
        $this->validate([
            'nuevoEmail' => ['nullable', 'email', 'max:255'],
            'nuevoTelefono' => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($this->nuevoEmail) && empty($this->nuevoTelefono)) {
            $this->notify('Ingresa un email y/o un teléfono.', 'warning');
            return;
        }

        VigilanciaAdjudicacionDestinatario::create([
            'email' => $this->nuevoEmail ?: null,
            'telefono' => $this->nuevoTelefono ?: null,
            'activo' => true,
        ]);

        $this->nuevoEmail = '';
        $this->nuevoTelefono = '';
        $this->cargar();
        $this->notify('Destinatario agregado.', 'success');
    }

    public function eliminarDestinatario(int $id): void
    {
        VigilanciaAdjudicacionDestinatario::where('id', $id)->delete();
        $this->cargar();
        $this->notify('Destinatario eliminado.', 'info');
    }

    public function toggleActivo(int $id): void
    {
        $d = VigilanciaAdjudicacionDestinatario::find($id);
        if ($d) {
            $d->update(['activo' => !$d->activo]);
        }
        $this->cargar();
    }

    public function guardarUmbral(): void
    {
        $this->validate([
            'umbral' => ['required', 'numeric', 'min:1'],
        ]);

        SystemSetting::setValue('vigilancia_monto_min', (string) (float) $this->umbral);
        $this->notify('Umbral actualizado: S/ ' . number_format((float) $this->umbral, 0), 'success');
        $this->cargar();
    }

    public function ejecutarAhora(): void
    {
        dispatch(new VigilarAdjudicacionesMayoresJob());
        $this->notify('Job de vigilancia encolado. Revisa el log en unos minutos.', 'info');
    }

    protected function notify(string $message, string $type = 'info'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }

    public function render()
    {
        return view('livewire.vigilancia-adjudicaciones-admin');
    }
}
