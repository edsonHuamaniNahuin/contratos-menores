<?php

namespace App\Livewire;

use App\Models\NotificationSend;
use App\Models\NotifiedProcess;
use App\Models\TelegramSubscription;
use App\Models\WhatsAppSubscription;
use App\Models\EmailSubscription;
use App\Services\TelegramNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\NuevoProcesoSeace;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire: Mis Procesos Notificados.
 *
 * Muestra al usuario autenticado el historial de procesos SEACE
 * que le fueron notificados, con opción de re-notificar por
 * cualquier canal (Telegram, WhatsApp, Email).
 *
 * Principios SOLID:
 *   SRP: Solo gestiona la vista y las acciones de re-notificación.
 *   DIP: Inyecta servicios de canal via app() para re-envíos.
 */
class MisProcesosNotificados extends Component
{
    use WithPagination;

    // ── Filtros ──────────────────────────────────────────────────────
    public string $busqueda = '';
    public string $filtroCanal = '';
    public string $filtroFechaDesde = '';
    public string $filtroFechaHasta = '';

    // ── Feedback ─────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage = '';

    // ── KPIs ─────────────────────────────────────────────────────────
    public int $totalProcesos = 0;
    public int $totalTelegram = 0;
    public int $totalWhatsapp = 0;
    public int $totalEmail = 0;

    protected $queryString = [
        'busqueda' => ['except' => ''],
        'filtroCanal' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->actualizarKpis();
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroCanal(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaHasta(): void
    {
        $this->resetPage();
    }

    // ── Re-notificación ─────────────────────────────────────────────

    /**
     * Re-enviar notificación de un proceso por Telegram.
     */
    public function renotificarTelegram(int $procesoId): void
    {
        $this->resetMessages();

        try {
            $proceso = NotifiedProcess::findOrFail($procesoId);
            $payload = $proceso->payload;

            if (empty($payload)) {
                $this->errorMessage = 'No se encontró el payload del proceso para re-enviar.';
                return;
            }

            $userId = Auth::id();
            $suscripciones = TelegramSubscription::where('user_id', $userId)
                ->activas()
                ->get();

            if ($suscripciones->isEmpty()) {
                $this->errorMessage = 'No tienes suscripciones de Telegram activas.';
                return;
            }

            $telegram = app(TelegramNotificationService::class);

            if (!$telegram->isEnabled()) {
                $this->errorMessage = 'El servicio de Telegram no está habilitado.';
                return;
            }

            $envios = 0;

            foreach ($suscripciones as $sub) {
                $respuesta = $telegram->enviarProcesoASuscriptor($sub, $payload, []);

                if ($respuesta['success']) {
                    $envios++;
                }
            }

            $this->successMessage = "Re-notificado por Telegram a {$envios} suscripción(es).";
        } catch (\Exception $e) {
            Log::error('MisProcesosNotificados: error re-notificando Telegram', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = 'Error al re-enviar por Telegram: ' . $e->getMessage();
        }
    }

    /**
     * Re-enviar notificación de un proceso por WhatsApp.
     */
    public function renotificarWhatsapp(int $procesoId): void
    {
        $this->resetMessages();

        try {
            $proceso = NotifiedProcess::findOrFail($procesoId);
            $payload = $proceso->payload;

            if (empty($payload)) {
                $this->errorMessage = 'No se encontró el payload del proceso para re-enviar.';
                return;
            }

            $userId = Auth::id();
            $suscripcion = WhatsAppSubscription::where('user_id', $userId)
                ->activas()
                ->first();

            if (!$suscripcion) {
                $this->errorMessage = 'No tienes una suscripción de WhatsApp activa.';
                return;
            }

            $whatsapp = app(WhatsAppNotificationService::class);

            if (!$whatsapp->isEnabled()) {
                $this->errorMessage = 'El servicio de WhatsApp no está habilitado.';
                return;
            }

            $respuesta = $whatsapp->enviarProcesoASuscriptor($suscripcion, $payload, []);

            if ($respuesta['success']) {
                $this->successMessage = 'Re-notificado por WhatsApp exitosamente.';
            } else {
                $this->errorMessage = 'Error al re-enviar por WhatsApp: ' . ($respuesta['message'] ?? 'desconocido');
            }
        } catch (\Exception $e) {
            Log::error('MisProcesosNotificados: error re-notificando WhatsApp', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = 'Error al re-enviar por WhatsApp: ' . $e->getMessage();
        }
    }

    /**
     * Re-enviar notificación de un proceso por Email.
     */
    public function renotificarEmail(int $procesoId): void
    {
        $this->resetMessages();

        try {
            $proceso = NotifiedProcess::findOrFail($procesoId);
            $payload = $proceso->payload;

            if (empty($payload)) {
                $this->errorMessage = 'No se encontró el payload del proceso para re-enviar.';
                return;
            }

            $userId = Auth::id();
            $suscripcion = EmailSubscription::where('user_id', $userId)
                ->where('activo', true)
                ->first();

            if (!$suscripcion) {
                $this->errorMessage = 'No tienes una suscripción de Email activa.';
                return;
            }

            Mail::to($suscripcion->email)->send(new NuevoProcesoSeace(
                $payload,
                route('buscador.publico'),
                []
            ));
            $this->successMessage = 'Re-notificado por Email a ' . $suscripcion->email;
        } catch (\Exception $e) {
            Log::error('MisProcesosNotificados: error re-notificando Email', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = 'Error al re-enviar por Email: ' . $e->getMessage();
        }
    }

    /**
     * Limpiar filtros.
     */
    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->filtroCanal = '';
        $this->filtroFechaDesde = '';
        $this->filtroFechaHasta = '';
        $this->resetPage();
    }

    // ── Render ───────────────────────────────────────────────────────

    public function render()
    {
        $userId = Auth::id();

        $query = NotifiedProcess::query()
            ->whereHas('sends', fn ($q) => $q->where('user_id', $userId))
            ->with(['sends' => fn ($q) => $q->where('user_id', $userId)->orderBy('notified_at', 'desc')])
            ->orderByDesc(
                NotificationSend::select('notified_at')
                    ->whereColumn('notified_process_id', 'notified_processes.id')
                    ->where('user_id', $userId)
                    ->orderByDesc('notified_at')
                    ->limit(1)
            );

        // Filtro por búsqueda de texto
        if ($this->busqueda !== '') {
            $term = '%' . $this->busqueda . '%';
            $query->where(function ($q) use ($term) {
                $q->where('codigo', 'like', $term)
                    ->orWhere('entidad', 'like', $term)
                    ->orWhere('descripcion', 'like', $term)
                    ->orWhere('objeto_contratacion', 'like', $term);
            });
        }

        // Filtro por canal
        if ($this->filtroCanal !== '') {
            $canal = $this->filtroCanal;
            $query->whereHas('sends', fn ($q) => $q->where('user_id', $userId)->where('canal', $canal));
        }

        // Filtro por rango de fechas
        if ($this->filtroFechaDesde !== '') {
            $query->whereHas('sends', fn ($q) => $q->where('user_id', $userId)->whereDate('notified_at', '>=', $this->filtroFechaDesde));
        }

        if ($this->filtroFechaHasta !== '') {
            $query->whereHas('sends', fn ($q) => $q->where('user_id', $userId)->whereDate('notified_at', '<=', $this->filtroFechaHasta));
        }

        $procesos = $query->paginate(12);

        return view('livewire.mis-procesos-notificados', [
            'procesos' => $procesos,
        ]);
    }

    // ── Helpers privados ─────────────────────────────────────────────

    private function actualizarKpis(): void
    {
        $userId = Auth::id();

        $this->totalProcesos = NotifiedProcess::paraUsuario($userId)->count();

        $this->totalTelegram = NotificationSend::where('user_id', $userId)
            ->where('canal', 'telegram')
            ->count();

        $this->totalWhatsapp = NotificationSend::where('user_id', $userId)
            ->where('canal', 'whatsapp')
            ->count();

        $this->totalEmail = NotificationSend::where('user_id', $userId)
            ->where('canal', 'email')
            ->count();
    }

    private function resetMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }
}
