<?php

namespace App\Livewire;

use App\Mail\NuevoProcesoSeace;
use App\Models\EmailSubscription;
use App\Models\NotificationKeyword;
use App\Models\SubscriberProfile;
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
    public bool $whatsappEnabled = false;
    public bool $isAdmin = false;

    // ── Permisos de agregar canal ─────────────────────────────────
    public bool $canAddTelegram = false;
    public bool $canAddWhatsApp = false;
    public bool $canAddEmail = false;

    // ── Perfil unificado (company_copy + keywords) ────────────────
    public string $profile_company_copy = '';
    public array $profile_keywords = [];
    public string $profile_keyword_search = '';
    public string $profile_keyword_manual = '';
    public bool $showProfileForm = false;
    public array $availableKeywords = [];

    // ── Telegram Modal ────────────────────────────────────────────
    public bool $showTelegramModal = false;
    public string $nuevo_chat_id = '';
    public string $nuevo_nombre = '';
    public string $nuevo_username = '';
    public bool $nuevo_activo = true;
    public ?int $editando_suscripcion_id = null;

    // ── WhatsApp Modal ────────────────────────────────────────────
    public bool $showWhatsAppModal = false;
    public string $wa_phone_number = '';
    public string $wa_nombre = '';
    public bool $wa_activo = true;
    public ?int $editando_wa_id = null;

    // ── Email Modal ───────────────────────────────────────────────
    public bool $showEmailModal = false;
    public string $email_notificacion = '';
    public bool $email_activo = true;
    public bool $email_notificar_todo = true;
    public ?int $editando_email_id = null;

    public function mount(): void
    {
        $this->telegramEnabled = !empty(config('services.telegram.bot_token'))
            && !empty(config('services.telegram.chat_id'));
        $this->whatsappEnabled = !empty(config('services.whatsapp.token'))
            && !empty(config('services.whatsapp.phone_number_id'));
        $this->isAdmin = auth()->user()?->hasRole('admin') ?? false;

        $user = auth()->user();
        $this->canAddTelegram = $this->isAdmin || ($user && $user->hasPermission('add-telegram-subscription'));
        $this->canAddWhatsApp = $this->isAdmin || ($user && $user->hasPermission('add-whatsapp-subscription'));
        $this->canAddEmail = $this->isAdmin || ($user && $user->hasPermission('add-email-subscription'));

        $this->loadKeywords();
        $this->loadProfile();
        $this->loadEmailSubscription();
        $this->loadWhatsAppSubscription();
    }

    // ── Perfil Unificado ──────────────────────────────────────────

    public function loadProfile(): void
    {
        $profile = SubscriberProfile::with('keywords')
            ->where('user_id', auth()->id())
            ->first();

        if ($profile) {
            $this->profile_company_copy = $profile->company_copy ?? '';
            $this->profile_keywords = $profile->keywords
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        } else {
            $this->profile_company_copy = '';
            $this->profile_keywords = [];
        }
    }

    public function toggleProfileForm(): void
    {
        $this->showProfileForm = !$this->showProfileForm;
        if (!$this->showProfileForm) {
            $this->loadProfile();
            $this->profile_keyword_search = '';
            $this->profile_keyword_manual = '';
            $this->resetValidation(['profile_company_copy', 'profile_keyword_manual']);
        }
    }

    public function guardarProfile(): void
    {
        $this->profile_keywords = $this->sanitizeKeywordSelection($this->profile_keywords);

        $this->validate([
            'profile_company_copy' => 'required|string|min:30',
            'profile_keywords' => 'array|max:' . self::MAX_KEYWORDS,
            'profile_keywords.*' => 'integer|exists:notification_keywords,id',
        ], [
            'profile_company_copy.required' => 'Describe brevemente el rubro de tu empresa.',
            'profile_company_copy.min' => 'La descripcion debe tener al menos 30 caracteres.',
            'profile_keywords.max' => 'Maximo ' . self::MAX_KEYWORDS . ' palabras clave.',
        ]);

        try {
            $profile = SubscriberProfile::updateOrCreate(
                ['user_id' => auth()->id()],
                ['company_copy' => $this->profile_company_copy]
            );

            $profile->keywords()->sync($this->profile_keywords);

            $this->showProfileForm = false;
            $this->profile_keyword_search = '';
            $this->profile_keyword_manual = '';

            session()->flash('profile_success', '✅ Perfil de empresa actualizado exitosamente.');
            Log::info('Perfil: Actualizado', ['user_id' => auth()->id(), 'keywords' => count($this->profile_keywords)]);
        } catch (\Exception $e) {
            session()->flash('profile_error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar perfil', ['error' => $e->getMessage()]);
        }
    }

    public function updatedProfileKeywords($value): void
    {
        $this->profile_keywords = $this->sanitizeKeywordSelection($this->profile_keywords);
    }

    public function agregarKeywordManual(): void
    {
        $this->validate([
            'profile_keyword_manual' => 'required|string|min:3|max:80',
        ], [
            'profile_keyword_manual.required' => 'Ingresa una palabra clave.',
            'profile_keyword_manual.min' => 'La palabra clave debe tener al menos 3 caracteres.',
            'profile_keyword_manual.max' => 'La palabra clave no puede superar 80 caracteres.',
        ]);

        $nombre = trim($this->profile_keyword_manual);

        $keyword = NotificationKeyword::firstOrCreate(
            ['slug' => Str::slug($nombre)],
            [
                'nombre' => $nombre,
                'descripcion' => 'Creado desde suscriptores',
                'es_publico' => true,
            ]
        );

        $this->loadKeywords();

        if (!in_array($keyword->id, $this->profile_keywords, true)) {
            $this->profile_keywords[] = $keyword->id;
            $this->profile_keywords = $this->sanitizeKeywordSelection($this->profile_keywords);
        }

        $this->profile_keyword_manual = '';
        $this->profile_keyword_search = '';

        session()->flash('profile_success', '✓ Palabra clave agregada al catalogo');
    }

    public function quitarKeyword(int $keywordId): void
    {
        $this->profile_keywords = array_values(array_filter(
            $this->profile_keywords,
            fn ($id) => (int) $id !== $keywordId
        ));
    }

    public function getFilteredKeywordsProperty(): array
    {
        $term = trim(Str::lower($this->profile_keyword_search));

        if ($term === '') {
            return $this->availableKeywords;
        }

        return array_values(array_filter($this->availableKeywords, function ($keyword) use ($term) {
            return Str::contains(Str::lower($keyword['nombre']), $term);
        }));
    }

    // ── Telegram ──────────────────────────────────────────────────

    public function toggleTelegramModal(): void
    {
        $this->showTelegramModal = !$this->showTelegramModal;
        if (!$this->showTelegramModal) {
            $this->resetTelegramForm();
        }
    }

    public function agregarSuscriptor(): void
    {
        if (!$this->canAddTelegram) {
            session()->flash('error', 'No tienes permiso para agregar suscriptores de Telegram.');
            return;
        }

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
        ], [
            'nuevo_chat_id.required' => 'El Chat ID es obligatorio.',
            'nuevo_chat_id.unique' => 'Este Chat ID ya esta registrado.',
        ]);

        try {
            auth()->user()->getOrCreateSubscriberProfile();

            if ($this->editando_suscripcion_id) {
                $suscripcion = TelegramSubscription::findOrFail($this->editando_suscripcion_id);
                $this->ensureOwnership($suscripcion);

                $suscripcion->update([
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                ]);

                session()->flash('success', '✅ Suscriptor actualizado exitosamente');
                Log::info('Telegram: Suscriptor actualizado', ['id' => $suscripcion->id]);
            } else {
                $suscripcion = TelegramSubscription::create([
                    'user_id' => auth()->id(),
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                    'subscrito_at' => now(),
                ]);

                session()->flash('success', '✅ Suscriptor agregado exitosamente');
                Log::info('Telegram: Nuevo suscriptor', ['id' => $suscripcion->id]);
            }

            $this->showTelegramModal = false;
            $this->resetTelegramForm();
        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar suscriptor', ['error' => $e->getMessage()]);
        }
    }

    public function editarSuscriptor(int $id): void
    {
        $suscripcion = TelegramSubscription::findOrFail($id);
        $this->ensureOwnership($suscripcion);

        $this->showTelegramModal = true;
        $this->editando_suscripcion_id = $id;
        $this->nuevo_chat_id = $suscripcion->chat_id;
        $this->nuevo_nombre = $suscripcion->nombre ?? '';
        $this->nuevo_username = $suscripcion->username ?? '';
        $this->nuevo_activo = $suscripcion->activo;
    }

    public function toggleTelegramNotificaciones(): void
    {
        try {
            $subs = TelegramSubscription::where('user_id', auth()->id())->get();
            if ($subs->isEmpty()) {
                session()->flash('error', 'No tienes suscriptores de Telegram registrados.');
                return;
            }

            $allActive = $subs->every(fn ($s) => $s->activo);
            $newState = !$allActive;

            TelegramSubscription::where('user_id', auth()->id())
                ->update(['activo' => $newState]);

            $estado = $newState ? 'activadas' : 'desactivadas';
            session()->flash('success', "✅ Notificaciones Telegram {$estado}.");
            Log::info('Telegram: Toggle masivo', ['user_id' => auth()->id(), 'activo' => $newState]);
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

    public function resetTelegramForm(): void
    {
        $this->nuevo_chat_id = '';
        $this->nuevo_nombre = '';
        $this->nuevo_username = '';
        $this->nuevo_activo = true;
        $this->editando_suscripcion_id = null;
        $this->resetValidation(['nuevo_chat_id', 'nuevo_nombre', 'nuevo_username']);
    }

    // ── Email ─────────────────────────────────────────────────────

    public function loadEmailSubscription(): void
    {
        $emailSub = EmailSubscription::where('user_id', auth()->id())->first();
        if ($emailSub) {
            $this->email_notificacion = $emailSub->email;
            $this->email_activo = $emailSub->activo;
            $this->email_notificar_todo = $emailSub->notificar_todo;
            $this->editando_email_id = $emailSub->id;
        } else {
            $this->email_notificacion = auth()->user()->email ?? '';
            $this->email_activo = true;
            $this->email_notificar_todo = true;
            $this->editando_email_id = null;
        }
    }

    public function toggleEmailModal(): void
    {
        $this->showEmailModal = !$this->showEmailModal;
        if (!$this->showEmailModal) {
            $this->loadEmailSubscription();
            $this->resetValidation(['email_notificacion']);
        }
    }

    public function guardarEmailSubscription(): void
    {
        if (!$this->canAddEmail) {
            session()->flash('email_error', 'No tienes permiso para agregar suscripcion de Email.');
            return;
        }

        $this->validate([
            'email_notificacion' => 'required|email|max:255',
        ], [
            'email_notificacion.required' => 'El correo electrónico es obligatorio.',
            'email_notificacion.email' => 'Ingresa un correo electrónico válido.',
        ]);

        try {
            auth()->user()->getOrCreateSubscriberProfile();

            $emailSub = EmailSubscription::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'email' => $this->email_notificacion,
                    'activo' => $this->email_activo,
                    'notificar_todo' => $this->email_notificar_todo,
                ]
            );

            $this->editando_email_id = $emailSub->id;
            $this->showEmailModal = false;

            session()->flash('email_success', '✅ Correo de notificación guardado exitosamente.');
            Log::info('Email: Suscripción guardada', [
                'id' => $emailSub->id,
                'email' => $emailSub->email,
                'notificar_todo' => $this->email_notificar_todo,
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
            $emailSub->delete();

            $this->editando_email_id = null;
            $this->email_notificacion = auth()->user()->email ?? '';
            $this->email_activo = true;
            $this->email_notificar_todo = true;
            $this->showEmailModal = false;

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

    // ── WhatsApp ──────────────────────────────────────────────────

    public function loadWhatsAppSubscription(): void
    {
        $waSub = WhatsAppSubscription::where('user_id', auth()->id())->first();
        if ($waSub) {
            $this->wa_phone_number = $waSub->phone_number;
            $this->wa_nombre = $waSub->nombre ?? '';
            $this->wa_activo = $waSub->activo;
            $this->editando_wa_id = $waSub->id;
        } else {
            $this->wa_phone_number = '';
            $this->wa_nombre = '';
            $this->wa_activo = true;
            $this->editando_wa_id = null;
        }
    }

    public function toggleWhatsAppModal(): void
    {
        $this->showWhatsAppModal = !$this->showWhatsAppModal;
        if (!$this->showWhatsAppModal) {
            $this->loadWhatsAppSubscription();
            $this->resetValidation(['wa_phone_number', 'wa_nombre']);
        }
    }

    public function guardarWhatsAppSubscription(): void
    {
        if (!$this->canAddWhatsApp) {
            session()->flash('wa_error', 'No tienes permiso para agregar suscripcion de WhatsApp.');
            return;
        }

        $this->validate([
            'wa_phone_number' => 'required|string|min:10|max:15|regex:/^\d+$/',
            'wa_nombre' => 'nullable|string|max:255',
        ], [
            'wa_phone_number.required' => 'El numero de WhatsApp es obligatorio.',
            'wa_phone_number.min' => 'El numero debe tener al menos 10 digitos.',
            'wa_phone_number.regex' => 'Solo digitos, sin espacios ni simbolos. Ej: 51987654321',
        ]);

        try {
            auth()->user()->getOrCreateSubscriberProfile();

            $waSub = WhatsAppSubscription::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'phone_number' => $this->wa_phone_number,
                    'nombre' => $this->wa_nombre ?: null,
                    'activo' => $this->wa_activo,
                    'subscrito_at' => now(),
                ]
            );

            $this->editando_wa_id = $waSub->id;
            $this->showWhatsAppModal = false;

            session()->flash('wa_success', '✅ Suscripcion de WhatsApp guardada exitosamente.');
            Log::info('WhatsApp: Suscripcion guardada', [
                'id' => $waSub->id,
                'phone' => $waSub->phone_number,
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
            $waSub->delete();

            $this->editando_wa_id = null;
            $this->wa_phone_number = '';
            $this->wa_nombre = '';
            $this->wa_activo = true;
            $this->showWhatsAppModal = false;

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

            $resultado = $servicio->enviarTemplate($waSub->phone_number, 'hello_world', 'en_US');

            if ($resultado['success']) {
                session()->flash('wa_success', '✅ Mensaje de prueba enviado a +' . $waSub->phone_number . '. Revisa tu WhatsApp. Si lo recibes, responde cualquier mensaje para habilitar notificaciones personalizadas.');
                Log::info('WhatsApp: Prueba exitosa (template)', ['phone' => $waSub->phone_number]);
            } else {
                session()->flash('wa_error', '❌ Error al enviar: ' . ($resultado['message'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            session()->flash('wa_error', '❌ Error: ' . $e->getMessage());
        }
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function loadKeywords(): void
    {
        $this->availableKeywords = NotificationKeyword::orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($keyword) => [
                'id' => $keyword->id,
                'nombre' => $keyword->nombre,
            ])->toArray();
    }

    public function render()
    {
        $query = TelegramSubscription::with('user')
            ->orderBy('created_at', 'desc');

        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }

        $suscripciones = $query->get();

        $canAddMore = $this->isAdmin
            || $suscripciones->where('user_id', auth()->id())->count() < self::MAX_SUSCRIPTORES_POR_USUARIO;

        $emailSubscription = EmailSubscription::where('user_id', auth()->id())->first();
        $whatsappSubscription = WhatsAppSubscription::where('user_id', auth()->id())->first();
        $subscriberProfile = SubscriberProfile::with('keywords')->where('user_id', auth()->id())->first();

        return view('livewire.suscriptores', [
            'suscripciones' => $suscripciones,
            'canAddMore' => $canAddMore,
            'maxSuscriptores' => self::MAX_SUSCRIPTORES_POR_USUARIO,
            'maxKeywords' => self::MAX_KEYWORDS,
            'filteredKeywords' => $this->filteredKeywords,
            'keywordDictionary' => collect($this->availableKeywords)->keyBy('id'),
            'emailSubscription' => $emailSubscription,
            'whatsappSubscription' => $whatsappSubscription,
            'subscriberProfile' => $subscriberProfile,
            'canAddTelegram' => $this->canAddTelegram,
            'canAddWhatsApp' => $this->canAddWhatsApp,
            'canAddEmail' => $this->canAddEmail,
        ]);
    }

    protected function sanitizeKeywordSelection(array $keywords): array
    {
        return collect($keywords)
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
