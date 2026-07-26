<?php

namespace App\Livewire;

use App\Models\PagoYape;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PagosYapeAdmin extends Component
{
    public string $estadoFiltro = 'pendiente';
    public ?int $selectedPagoId = null;
    public ?int $confirmPagoId = null;
    public string $confirmAction = '';
    public string $adminNotes = '';
    public string $justificacionRechazo = '';

    public function confirmar(int $id, string $action): void
    {
        $this->confirmPagoId = $id;
        $this->confirmAction = $action;
    }

    public function cancelarConfirm(): void
    {
        $this->confirmPagoId = null;
        $this->confirmAction = '';
    }

    public function aprobar(int $id): void
    {
        $pago = PagoYape::findOrFail($id);

        if ($pago->estado !== PagoYape::ESTADO_PENDIENTE) {
            $this->dispatch('notify', 'Este pago ya fue procesado.', 'warning');
            return;
        }

        $service = app(SubscriptionService::class);
        $days = $pago->plan === Subscription::PLAN_YEARLY ? 365 : 30;

        $service->grantPremium($pago->user, $days, $pago->plan, (float) $pago->monto, 'yape');

        $pago->update([
            'estado' => PagoYape::ESTADO_APROBADO,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_notes' => $this->adminNotes ?: null,
        ]);

        \Mail::to($pago->user)->send(new \App\Mail\PagoAprobado($pago));

        $this->adminNotes = '';
        $this->confirmPagoId = null;
        $this->confirmAction = '';
        $this->dispatch('notify', 'Pago aprobado y premium activado para ' . $pago->user->name, 'success');
    }

    public function rechazar(int $id): void
    {
        $pago = PagoYape::findOrFail($id);

        if ($pago->estado !== PagoYape::ESTADO_PENDIENTE) {
            $this->dispatch('notify', 'Este pago ya fue procesado.', 'warning');
            return;
        }

        $motivo = trim($this->justificacionRechazo) ?: 'Rechazado por el administrador.';

        $pago->update([
            'estado' => PagoYape::ESTADO_RECHAZADO,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_notes' => $motivo,
        ]);

        \Mail::to($pago->user)->send(new \App\Mail\PagoRechazado($pago, $motivo));

        $this->adminNotes = '';
        $this->justificacionRechazo = '';
        $this->confirmPagoId = null;
        $this->confirmAction = '';
        $this->dispatch('notify', 'Pago rechazado.', 'info');

        // Keep the file on disk for audit trail
    }

    public function verComprobante(int $id): void
    {
        $pago = PagoYape::findOrFail($id);
        $this->selectedPagoId = $id;
        $this->adminNotes = $pago->admin_notes ?? '';
    }

    public function cerrarComprobante(): void
    {
        $this->selectedPagoId = null;
    }

    #[Computed]
    public function pagos()
    {
        return PagoYape::with('user')
            ->when($this->estadoFiltro !== 'todas', fn($q) => $q->where('estado', $this->estadoFiltro))
            ->latest()
            ->get();
    }

    #[Computed]
    public function conteoPendientes(): int
    {
        return PagoYape::where('estado', PagoYape::ESTADO_PENDIENTE)->count();
    }

    public function render()
    {
        return view('livewire.pagos-yape-admin', [
            'pagos' => $this->pagos,
            'conteoPendientes' => $this->conteoPendientes,
        ]);
    }
}
