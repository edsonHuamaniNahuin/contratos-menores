<?php

namespace App\Livewire;

use App\Models\NotificationKeyword;
use App\Models\TelegramSubscription;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class Suscriptores extends Component
{
    public bool $telegramEnabled = false;
    public bool $isAdmin = false;

    public string $nuevo_chat_id = '';
    public string $nuevo_nombre = '';
    public string $nuevo_username = '';
    public bool $nuevo_activo = true;
    public ?int $editando_suscripcion_id = null;
    public array $availableKeywords = [];
    public array $nuevo_keywords = [];
    public string $nuevo_keyword_manual = '';
    public string $nuevo_company_copy = '';
    public string $keywordSearch = '';

    public function mount(): void
    {
        $this->telegramEnabled = !empty(config('services.telegram.bot_token'))
            && !empty(config('services.telegram.chat_id'));
        $this->isAdmin = auth()->user()?->hasRole('admin') ?? false;

        $this->loadKeywords();
    }

    public function updatedNuevoKeywords($value): void
    {
        $this->nuevo_keywords = $this->sanitizeKeywordSelection();
    }

    public function loadKeywords(): void
    {
        $this->availableKeywords = NotificationKeyword::orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($keyword) => [
                'id' => $keyword->id,
                'nombre' => $keyword->nombre,
            ])->toArray();
    }

    public function agregarKeywordManual(): void
    {
        $this->validate([
            'nuevo_keyword_manual' => 'required|string|min:3|max:80',
        ], [
            'nuevo_keyword_manual.required' => 'Ingresa una palabra clave',
        ]);

        $nombre = trim($this->nuevo_keyword_manual);

        $keyword = NotificationKeyword::firstOrCreate(
            ['slug' => Str::slug($nombre)],
            [
                'nombre' => $nombre,
                'descripcion' => 'Creado desde suscriptores',
                'es_publico' => true,
            ]
        );

        $this->loadKeywords();

        if (!in_array($keyword->id, $this->nuevo_keywords, true)) {
            $this->nuevo_keywords[] = $keyword->id;
            $this->nuevo_keywords = $this->sanitizeKeywordSelection();
        }

        $this->nuevo_keyword_manual = '';
        $this->keywordSearch = '';

        session()->flash('success', '✓ Palabra clave agregada al catalogo');
    }

    public function agregarSuscriptor(): void
    {
        $this->nuevo_keywords = $this->sanitizeKeywordSelection();

        $this->validate([
            'nuevo_chat_id' => 'required|string|unique:telegram_subscriptions,chat_id' . ($this->editando_suscripcion_id ? ",{$this->editando_suscripcion_id}" : ''),
            'nuevo_nombre' => 'nullable|string|max:255',
            'nuevo_username' => 'nullable|string|max:255',
            'nuevo_keywords' => 'array',
            'nuevo_keywords.*' => 'integer|exists:notification_keywords,id',
            'nuevo_company_copy' => 'required|string|min:30',
        ], [
            'nuevo_chat_id.required' => 'El Chat ID es obligatorio',
            'nuevo_chat_id.unique' => 'Este Chat ID ya esta registrado',
            'nuevo_company_copy.required' => 'Describe brevemente el rubro de la empresa',
        ]);

        try {
            if ($this->editando_suscripcion_id) {
                $suscripcion = TelegramSubscription::findOrFail($this->editando_suscripcion_id);
                $this->ensureOwnership($suscripcion);

                $suscripcion->update([
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                    'company_copy' => $this->nuevo_company_copy,
                ]);

                $suscripcion->keywords()->sync($this->nuevo_keywords);

                session()->flash('success', '✅ Suscriptor actualizado exitosamente');
                Log::info('Telegram: Suscriptor actualizado', ['id' => $suscripcion->id]);
            } else {
                $suscripcion = TelegramSubscription::create([
                    'user_id' => auth()->id(),
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                    'company_copy' => $this->nuevo_company_copy,
                    'subscrito_at' => now(),
                ]);

                if (!empty($this->nuevo_keywords)) {
                    $suscripcion->keywords()->sync($this->nuevo_keywords);
                }

                session()->flash('success', '✅ Suscriptor agregado exitosamente');
                Log::info('Telegram: Nuevo suscriptor', ['id' => $suscripcion->id]);
            }

            $this->resetSuscriptorForm();
        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar suscriptor', ['error' => $e->getMessage()]);
        }
    }

    public function editarSuscriptor(int $id): void
    {
        $suscripcion = TelegramSubscription::findOrFail($id);
        $this->ensureOwnership($suscripcion);

        $this->editando_suscripcion_id = $id;
        $this->nuevo_chat_id = $suscripcion->chat_id;
        $this->nuevo_nombre = $suscripcion->nombre ?? '';
        $this->nuevo_username = $suscripcion->username ?? '';
        $this->nuevo_activo = $suscripcion->activo;
        $this->nuevo_company_copy = $suscripcion->company_copy ?? '';
        $this->nuevo_keywords = $suscripcion->keywords()
            ->pluck('notification_keywords.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function toggleActivoSuscriptor(int $id): void
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $this->ensureOwnership($suscripcion);

            $suscripcion->update(['activo' => !$suscripcion->activo]);

            $estado = $suscripcion->activo ? 'activado' : 'desactivado';
            session()->flash('success', "✅ Suscriptor {$estado}");
            Log::info('Telegram: Toggle activo', ['id' => $id, 'nuevo_estado' => $suscripcion->activo]);
        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function eliminarSuscriptor(int $id): void
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $this->ensureOwnership($suscripcion);

            $chatId = $suscripcion->chat_id;
            $suscripcion->delete();

            session()->flash('success', '✅ Suscriptor eliminado');
            Log::info('Telegram: Suscriptor eliminado', ['id' => $id, 'chat_id' => $chatId]);
        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function probarNotificacionSuscriptor(int $id): void
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $this->ensureOwnership($suscripcion);

            $servicio = new TelegramNotificationService();

            $mensajePrueba = "🧪 <b>MENSAJE DE PRUEBA</b>\n\n";
            $mensajePrueba .= "Este es un mensaje de prueba del sistema Vigilante SEACE.\n\n";
            $mensajePrueba .= "✅ Tu suscripcion esta funcionando correctamente.\n";
            $mensajePrueba .= "📅 Fecha: " . now()->format('d/m/Y H:i:s');

            $resultado = $servicio->enviarMensaje($suscripcion->chat_id, $mensajePrueba);

            if ($resultado['success']) {
                session()->flash('success', '✅ Mensaje de prueba enviado exitosamente');
                Log::info('Telegram: Prueba exitosa', ['id' => $id, 'chat_id' => $suscripcion->chat_id]);
            } else {
                session()->flash('error', '❌ Error al enviar: ' . $resultado['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function resetSuscriptorForm(): void
    {
        $this->nuevo_chat_id = '';
        $this->nuevo_nombre = '';
        $this->nuevo_username = '';
        $this->nuevo_activo = true;
        $this->nuevo_company_copy = '';
        $this->nuevo_keywords = [];
        $this->nuevo_keyword_manual = '';
        $this->keywordSearch = '';
        $this->editando_suscripcion_id = null;
        $this->resetValidation(['nuevo_chat_id', 'nuevo_nombre', 'nuevo_username']);
    }

    public function getFilteredKeywordsProperty(): array
    {
        $term = trim(Str::lower($this->keywordSearch));

        if ($term === '') {
            return $this->availableKeywords;
        }

        return array_values(array_filter($this->availableKeywords, function ($keyword) use ($term) {
            return Str::contains(Str::lower($keyword['nombre']), $term);
        }));
    }

    public function quitarKeyword(int $keywordId): void
    {
        $this->nuevo_keywords = array_values(array_filter(
            $this->nuevo_keywords,
            fn ($id) => (int) $id !== $keywordId
        ));
    }

    public function render()
    {
        $query = TelegramSubscription::with(['keywords', 'user'])
            ->orderBy('created_at', 'desc');

        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }

        return view('livewire.suscriptores', [
            'suscripciones' => $query->get(),
            'filteredKeywords' => $this->filteredKeywords,
            'keywordDictionary' => collect($this->availableKeywords)->keyBy('id'),
        ]);
    }

    protected function sanitizeKeywordSelection(): array
    {
        return collect($this->nuevo_keywords ?? [])
            ->map(function ($id) {
                return is_numeric($id) ? (int) $id : null;
            })
            ->filter(fn ($id) => !is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function ensureOwnership(TelegramSubscription $suscripcion): void
    {
        if ($this->isAdmin) {
            return;
        }

        if ($suscripcion->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
