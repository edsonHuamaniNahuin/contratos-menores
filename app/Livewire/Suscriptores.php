<?php

namespace App\Livewire;

use App\Mail\NuevoProcesoSeace;
use App\Models\EmailSubscription;
use App\Models\NotificationKeyword;
use App\Models\TelegramSubscription;
use App\Models\WhatsAppSubscription;
use App\Services\TelegramNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class Suscriptores extends Component
{
    public const MAX_SUSCRIPTORES_POR_USUARIO = 2;
    public const MAX_KEYWORDS = 5;

    public bool $telegramEnabled = false;
    public bool $isAdmin = false;
    public bool $showForm = false;

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

    // ── Email Subscription ────────────────────────────────
    public string $email_notificacion = '';
    public bool $email_activo = true;
    public bool $email_notificar_todo = true;
    public array $email_keywords = [];
    public string $email_keyword_search = '';
    public string $email_keyword_manual = '';
    public bool $showEmailForm = false;
    public ?int $editando_email_id = null;

    // ── WhatsApp Subscription ─────────────────────────────
    public bool $whatsappEnabled = false;
    public string $wa_phone_number = '';
    public string $wa_nombre = '';
    public string $wa_company_copy = '';
    public bool $wa_activo = true;
    public array $wa_keywords = [];
    public string $wa_keyword_search = '';
    public string $wa_keyword_manual = '';
    public bool $showWhatsAppForm = false;
    public ?int $editando_wa_id = null;

    public function mount(): void
    {
        $this->telegramEnabled = !empty(config('services.telegram.bot_token'))
            && !empty(config('services.telegram.chat_id'));
        $this->whatsappEnabled = !empty(config('services.whatsapp.token'))
            && !empty(config('services.whatsapp.phone_number_id'));
        $this->isAdmin = auth()->user()?->hasRole('admin') ?? false;

        $this->loadKeywords();
        $this->loadEmailSubscription();
        $this->loadWhatsAppSubscription();
    }

    public function loadEmailSubscription(): void
    {
        $emailSub = EmailSubscription::with('keywords')->where('user_id', auth()->id())->first();
        if ($emailSub) {
            $this->email_notificacion = $emailSub->email;
            $this->email_activo = $emailSub->activo;
            $this->email_notificar_todo = $emailSub->notificar_todo;
            $this->email_keywords = $emailSub->keywords
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $this->editando_email_id = $emailSub->id;
        } else {
            $this->email_notificacion = auth()->user()->email ?? '';
            $this->email_activo = true;
            $this->email_notificar_todo = true;
            $this->email_keywords = [];
            $this->editando_email_id = null;
        }
    }

    public function updatedEmailKeywords($value): void
    {
        $this->email_keywords = $this->sanitizeEmailKeywordSelection();
    }

    public function updatedNuevoKeywords($value): void
    {
        $this->nuevo_keywords = $this->sanitizeKeywordSelection();
    }

    public function updatedWaKeywords($value): void
    {
        $this->wa_keywords = $this->sanitizeWaKeywordSelection();
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
            'nuevo_keyword_manual.required' => 'Ingresa una palabra clave.',
            'nuevo_keyword_manual.min' => 'La palabra clave debe tener al menos 3 caracteres.',
            'nuevo_keyword_manual.max' => 'La palabra clave no puede superar 80 caracteres.',
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

    public function toggleForm(): void
    {
        if ($this->showForm) {
            $this->resetSuscriptorForm();
            $this->showForm = false;
            return;
        }

        if (!$this->isAdmin && !$this->editando_suscripcion_id) {
            $count = TelegramSubscription::where('user_id', auth()->id())->count();
            if ($count >= self::MAX_SUSCRIPTORES_POR_USUARIO) {
                session()->flash('error', 'Alcanzaste el limite de ' . self::MAX_SUSCRIPTORES_POR_USUARIO . ' suscriptores. Elimina uno para agregar otro.');
                return;
            }
        }

        $this->showForm = true;
    }

    public function agregarSuscriptor(): void
    {
        $this->nuevo_keywords = $this->sanitizeKeywordSelection();

        if (!$this->isAdmin && !$this->editando_suscripcion_id) {
            $count = TelegramSubscription::where('user_id', auth()->id())->count();
            if ($count >= self::MAX_SUSCRIPTORES_POR_USUARIO) {
                session()->flash('error', 'Alcanzaste el limite de ' . self::MAX_SUSCRIPTORES_POR_USUARIO . ' suscriptores.');
                return;
            }
        }

        $this->validate([
            'nuevo_chat_id' => 'required|string|unique:telegram_subscriptions,chat_id' . ($this->editando_suscripcion_id ? ",{$this->editando_suscripcion_id}" : ''),
            'nuevo_nombre' => 'nullable|string|max:255',
            'nuevo_username' => 'nullable|string|max:255',
            'nuevo_keywords' => 'array|max:' . self::MAX_KEYWORDS,
            'nuevo_keywords.*' => 'integer|exists:notification_keywords,id',
            'nuevo_company_copy' => 'required|string|min:30',
        ], [
            'nuevo_chat_id.required' => 'El Chat ID es obligatorio.',
            'nuevo_chat_id.unique' => 'Este Chat ID ya esta registrado.',
            'nuevo_company_copy.required' => 'Describe brevemente el rubro de tu empresa.',
            'nuevo_company_copy.min' => 'La descripcion debe tener al menos 30 caracteres.',
            'nuevo_keywords.max' => 'Maximo ' . self::MAX_KEYWORDS . ' palabras clave.',
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

            $this->showForm = false;
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

        $this->showForm = true;
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

    // ── Email Subscription Methods ──────────────────────────

    public function toggleEmailForm(): void
    {
        $this->showEmailForm = !$this->showEmailForm;
        if (!$this->showEmailForm) {
            $this->loadEmailSubscription();
            $this->email_keyword_search = '';
            $this->email_keyword_manual = '';
            $this->resetValidation(['email_notificacion', 'email_keyword_manual']);
        }
    }

    public function guardarEmailSubscription(): void
    {
        $this->email_keywords = $this->sanitizeEmailKeywordSelection();

        $rules = [
            'email_notificacion' => 'required|email|max:255',
            'email_keywords' => 'array|max:' . self::MAX_KEYWORDS,
            'email_keywords.*' => 'integer|exists:notification_keywords,id',
        ];

        // Si elige filtrar por keywords, debe tener al menos 1
        if (!$this->email_notificar_todo && empty($this->email_keywords)) {
            session()->flash('email_error', '❌ Debes seleccionar al menos 1 palabra clave o elegir "Recibir todos los procesos".');
            return;
        }

        $this->validate($rules, [
            'email_notificacion.required' => 'El correo electrónico es obligatorio.',
            'email_notificacion.email' => 'Ingresa un correo electrónico válido.',
            'email_keywords.max' => 'Máximo ' . self::MAX_KEYWORDS . ' palabras clave.',
        ]);

        try {
            $emailSub = EmailSubscription::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'email' => $this->email_notificacion,
                    'activo' => $this->email_activo,
                    'notificar_todo' => $this->email_notificar_todo,
                ]
            );

            // Sincronizar keywords
            if ($this->email_notificar_todo) {
                $emailSub->keywords()->detach();
                $this->email_keywords = [];
            } else {
                $emailSub->keywords()->sync($this->email_keywords);
            }

            $this->editando_email_id = $emailSub->id;
            $this->showEmailForm = false;
            $this->email_keyword_search = '';
            $this->email_keyword_manual = '';

            session()->flash('email_success', '✅ Correo de notificación guardado exitosamente.');
            Log::info('Email: Suscripción guardada', [
                'id' => $emailSub->id,
                'email' => $emailSub->email,
                'notificar_todo' => $this->email_notificar_todo,
                'keywords' => count($this->email_keywords),
            ]);
        } catch (\Exception $e) {
            session()->flash('email_error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar email subscription', ['error' => $e->getMessage()]);
        }
    }

    public function toggleEmailActivo(): void
    {
        try {
            $emailSub = EmailSubscription::where('user_id', auth()->id())->firstOrFail();
            $emailSub->update(['activo' => !$emailSub->activo]);
            $this->email_activo = $emailSub->activo;

            $estado = $emailSub->activo ? 'activadas' : 'desactivadas';
            session()->flash('email_success', "✅ Notificaciones por correo {$estado}.");
            Log::info('Email: Toggle activo', ['id' => $emailSub->id, 'activo' => $emailSub->activo]);
        } catch (\Exception $e) {
            session()->flash('email_error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function eliminarEmailSubscription(): void
    {
        try {
            $emailSub = EmailSubscription::where('user_id', auth()->id())->firstOrFail();
            $emailSub->keywords()->detach();
            $emailSub->delete();

            $this->editando_email_id = null;
            $this->email_notificacion = auth()->user()->email ?? '';
            $this->email_activo = true;
            $this->email_notificar_todo = true;
            $this->email_keywords = [];
            $this->showEmailForm = false;

            session()->flash('email_success', '✅ Suscripción de correo eliminada.');
            Log::info('Email: Suscripción eliminada', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('email_error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function probarEmailNotificacion(): void
    {
        try {
            $emailSub = EmailSubscription::where('user_id', auth()->id())->firstOrFail();

            $contratoPrueba = [
                'desContratacion' => 'TEST-001-2026-PRUEBA',
                'nomEntidad' => 'ENTIDAD DE PRUEBA',
                'nomObjetoContrato' => 'Servicio',
                'desObjetoContrato' => 'Este es un mensaje de prueba del sistema Vigilante SEACE para verificar que las notificaciones por correo funcionan correctamente.',
                'nomEstadoContrato' => 'Vigente',
                'nomEtapaContratacion' => 'ETAPA DE COTIZACIÓN',
                'fecPublica' => now()->format('d/m/Y H:i:s'),
                'fecFinCotizacion' => now()->addDays(3)->format('d/m/Y H:i:s'),
            ];

            Mail::to($emailSub->email)->send(new NuevoProcesoSeace(
                contrato: $contratoPrueba,
                seguimientoUrl: route('buscador.publico'),
                matchedKeywords: ['prueba']
            ));

            session()->flash('email_success', '✅ Correo de prueba enviado a ' . $emailSub->email);
            Log::info('Email: Prueba enviada', ['email' => $emailSub->email]);
        } catch (\Exception $e) {
            session()->flash('email_error', '❌ Error al enviar: ' . $e->getMessage());
            Log::error('Error al enviar email de prueba', ['error' => $e->getMessage()]);
        }
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

    public function getFilteredEmailKeywordsProperty(): array
    {
        $term = trim(Str::lower($this->email_keyword_search));

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

    public function quitarEmailKeyword(int $keywordId): void
    {
        $this->email_keywords = array_values(array_filter(
            $this->email_keywords,
            fn ($id) => (int) $id !== $keywordId
        ));
    }

    public function agregarEmailKeywordManual(): void
    {
        $this->validate([
            'email_keyword_manual' => 'required|string|min:3|max:80',
        ], [
            'email_keyword_manual.required' => 'Ingresa una palabra clave.',
            'email_keyword_manual.min' => 'La palabra clave debe tener al menos 3 caracteres.',
            'email_keyword_manual.max' => 'La palabra clave no puede superar 80 caracteres.',
        ]);

        $nombre = trim($this->email_keyword_manual);

        $keyword = NotificationKeyword::firstOrCreate(
            ['slug' => Str::slug($nombre)],
            [
                'nombre' => $nombre,
                'descripcion' => 'Creado desde suscriptores (email)',
                'es_publico' => true,
            ]
        );

        $this->loadKeywords();

        if (!in_array($keyword->id, $this->email_keywords, true)) {
            $this->email_keywords[] = $keyword->id;
            $this->email_keywords = $this->sanitizeEmailKeywordSelection();
        }

        $this->email_keyword_manual = '';
        $this->email_keyword_search = '';

        session()->flash('email_success', '✓ Palabra clave agregada');
    }

    // ── WhatsApp Subscription Methods ───────────────────────

    public function loadWhatsAppSubscription(): void
    {
        $waSub = WhatsAppSubscription::with('keywords')->where('user_id', auth()->id())->first();
        if ($waSub) {
            $this->wa_phone_number = $waSub->phone_number;
            $this->wa_nombre = $waSub->nombre ?? '';
            $this->wa_company_copy = $waSub->company_copy ?? '';
            $this->wa_activo = $waSub->activo;
            $this->wa_keywords = $waSub->keywords
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $this->editando_wa_id = $waSub->id;
        } else {
            $this->wa_phone_number = '';
            $this->wa_nombre = '';
            $this->wa_company_copy = '';
            $this->wa_activo = true;
            $this->wa_keywords = [];
            $this->editando_wa_id = null;
        }
    }

    public function toggleWhatsAppForm(): void
    {
        $this->showWhatsAppForm = !$this->showWhatsAppForm;
        if (!$this->showWhatsAppForm) {
            $this->loadWhatsAppSubscription();
            $this->wa_keyword_search = '';
            $this->wa_keyword_manual = '';
            $this->resetValidation(['wa_phone_number', 'wa_nombre', 'wa_company_copy', 'wa_keyword_manual']);
        }
    }

    public function guardarWhatsAppSubscription(): void
    {
        $this->wa_keywords = $this->sanitizeWaKeywordSelection();

        $this->validate([
            'wa_phone_number' => 'required|string|min:10|max:15|regex:/^\d+$/',
            'wa_nombre' => 'nullable|string|max:255',
            'wa_company_copy' => 'required|string|min:30',
            'wa_keywords' => 'array|max:' . self::MAX_KEYWORDS,
            'wa_keywords.*' => 'integer|exists:notification_keywords,id',
        ], [
            'wa_phone_number.required' => 'El numero de WhatsApp es obligatorio.',
            'wa_phone_number.min' => 'El numero debe tener al menos 10 digitos.',
            'wa_phone_number.regex' => 'Solo digitos, sin espacios ni simbolos. Ej: 51987654321',
            'wa_company_copy.required' => 'Describe brevemente el rubro de tu empresa.',
            'wa_company_copy.min' => 'La descripcion debe tener al menos 30 caracteres.',
            'wa_keywords.max' => 'Maximo ' . self::MAX_KEYWORDS . ' palabras clave.',
        ]);

        try {
            $waSub = WhatsAppSubscription::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'phone_number' => $this->wa_phone_number,
                    'nombre' => $this->wa_nombre ?: null,
                    'activo' => $this->wa_activo,
                    'company_copy' => $this->wa_company_copy,
                    'subscrito_at' => now(),
                ]
            );

            $waSub->keywords()->sync($this->wa_keywords);

            $this->editando_wa_id = $waSub->id;
            $this->showWhatsAppForm = false;
            $this->wa_keyword_search = '';
            $this->wa_keyword_manual = '';

            session()->flash('wa_success', '✅ Suscripcion de WhatsApp guardada exitosamente.');
            Log::info('WhatsApp: Suscripcion guardada', [
                'id' => $waSub->id,
                'phone' => $waSub->phone_number,
                'keywords' => count($this->wa_keywords),
            ]);
        } catch (\Exception $e) {
            session()->flash('wa_error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar WhatsApp subscription', ['error' => $e->getMessage()]);
        }
    }

    public function toggleWhatsAppActivo(): void
    {
        try {
            $waSub = WhatsAppSubscription::where('user_id', auth()->id())->firstOrFail();
            $waSub->update(['activo' => !$waSub->activo]);
            $this->wa_activo = $waSub->activo;

            $estado = $waSub->activo ? 'activadas' : 'desactivadas';
            session()->flash('wa_success', "✅ Notificaciones WhatsApp {$estado}.");
            Log::info('WhatsApp: Toggle activo', ['id' => $waSub->id, 'activo' => $waSub->activo]);
        } catch (\Exception $e) {
            session()->flash('wa_error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function eliminarWhatsAppSubscription(): void
    {
        try {
            $waSub = WhatsAppSubscription::where('user_id', auth()->id())->firstOrFail();
            $waSub->keywords()->detach();
            $waSub->delete();

            $this->editando_wa_id = null;
            $this->wa_phone_number = '';
            $this->wa_nombre = '';
            $this->wa_company_copy = '';
            $this->wa_activo = true;
            $this->wa_keywords = [];
            $this->showWhatsAppForm = false;

            session()->flash('wa_success', '✅ Suscripcion de WhatsApp eliminada.');
            Log::info('WhatsApp: Suscripcion eliminada', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('wa_error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function probarWhatsAppNotificacion(): void
    {
        try {
            $waSub = WhatsAppSubscription::where('user_id', auth()->id())->firstOrFail();
            $servicio = new WhatsAppNotificationService();

            if (!$servicio->isEnabled()) {
                session()->flash('wa_error', '❌ WhatsApp no esta configurado. Verifica WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID en .env');
                return;
            }

            $mensajePrueba = "🧪 *MENSAJE DE PRUEBA*\n\n";
            $mensajePrueba .= "Este es un mensaje de prueba del sistema Vigilante SEACE.\n\n";
            $mensajePrueba .= "✅ Tu suscripcion de WhatsApp esta funcionando correctamente.\n";
            $mensajePrueba .= "📅 Fecha: " . now()->format('d/m/Y H:i:s');

            $resultado = $servicio->enviarMensaje($waSub->phone_number, $mensajePrueba);

            if ($resultado['success']) {
                session()->flash('wa_success', '✅ Mensaje de prueba enviado a +' . $waSub->phone_number);
                Log::info('WhatsApp: Prueba exitosa', ['phone' => $waSub->phone_number]);
            } else {
                session()->flash('wa_error', '❌ Error al enviar: ' . ($resultado['message'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            session()->flash('wa_error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function agregarWaKeywordManual(): void
    {
        $this->validate([
            'wa_keyword_manual' => 'required|string|min:3|max:80',
        ], [
            'wa_keyword_manual.required' => 'Ingresa una palabra clave.',
            'wa_keyword_manual.min' => 'La palabra clave debe tener al menos 3 caracteres.',
            'wa_keyword_manual.max' => 'La palabra clave no puede superar 80 caracteres.',
        ]);

        $nombre = trim($this->wa_keyword_manual);

        $keyword = NotificationKeyword::firstOrCreate(
            ['slug' => Str::slug($nombre)],
            [
                'nombre' => $nombre,
                'descripcion' => 'Creado desde suscriptores (WhatsApp)',
                'es_publico' => true,
            ]
        );

        $this->loadKeywords();

        if (!in_array($keyword->id, $this->wa_keywords, true)) {
            $this->wa_keywords[] = $keyword->id;
            $this->wa_keywords = $this->sanitizeWaKeywordSelection();
        }

        $this->wa_keyword_manual = '';
        $this->wa_keyword_search = '';

        session()->flash('wa_success', '✓ Palabra clave agregada');
    }

    public function quitarWaKeyword(int $keywordId): void
    {
        $this->wa_keywords = array_values(array_filter(
            $this->wa_keywords,
            fn ($id) => (int) $id !== $keywordId
        ));
    }

    public function getFilteredWaKeywordsProperty(): array
    {
        $term = trim(Str::lower($this->wa_keyword_search));

        if ($term === '') {
            return $this->availableKeywords;
        }

        return array_values(array_filter($this->availableKeywords, function ($keyword) use ($term) {
            return Str::contains(Str::lower($keyword['nombre']), $term);
        }));
    }

    public function render()
    {
        $query = TelegramSubscription::with(['keywords', 'user'])
            ->orderBy('created_at', 'desc');

        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }

        $suscripciones = $query->get();

        $canAddMore = $this->isAdmin
            || $suscripciones->where('user_id', auth()->id())->count() < self::MAX_SUSCRIPTORES_POR_USUARIO;

        $emailSubscription = EmailSubscription::with('keywords')->where('user_id', auth()->id())->first();
        $whatsappSubscription = WhatsAppSubscription::with('keywords')->where('user_id', auth()->id())->first();

        return view('livewire.suscriptores', [
            'suscripciones' => $suscripciones,
            'canAddMore' => $canAddMore,
            'maxSuscriptores' => self::MAX_SUSCRIPTORES_POR_USUARIO,
            'maxKeywords' => self::MAX_KEYWORDS,
            'filteredKeywords' => $this->filteredKeywords,
            'filteredEmailKeywords' => $this->filteredEmailKeywords,
            'filteredWaKeywords' => $this->filteredWaKeywords,
            'keywordDictionary' => collect($this->availableKeywords)->keyBy('id'),
            'emailSubscription' => $emailSubscription,
            'whatsappSubscription' => $whatsappSubscription,
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

    protected function sanitizeEmailKeywordSelection(): array
    {
        return collect($this->email_keywords ?? [])
            ->map(function ($id) {
                return is_numeric($id) ? (int) $id : null;
            })
            ->filter(fn ($id) => !is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function sanitizeWaKeywordSelection(): array
    {
        return collect($this->wa_keywords ?? [])
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
