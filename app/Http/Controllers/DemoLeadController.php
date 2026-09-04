<?php

namespace App\Http\Controllers;

use App\Mail\DemoLeadMail;
use App\Models\DemoLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class DemoLeadController extends Controller
{
    /**
     * Captura una solicitud de demo/contacto desde las landings (E1/E2/E3).
     *
     * Defensas anti-spam / antibot (sin servicios externos):
     *  1. Honeypot: campo oculto 'empresa_web' que un humano nunca llena.
     *  2. Tiempo mínimo de llenado (>= 3 s): los bots envían en < 1 s.
     *  3. Rate limit por IP: 3 intentos / 10 min.
     *  4. Blacklist de dominios de correo desechables.
     */
    public function store(Request $request)
    {
        // 3) Rate limit por IP
        $ipKey = 'demo_lead:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            return back()
                ->withInput()
                ->withErrors(['form' => 'Has enviado varias solicitudes seguidas. Espera unos minutos e inténtalo de nuevo.']);
        }
        RateLimiter::hit($ipKey, 600);

        // 1) Honeypot: si el campo oculto viene lleno, es un bot.
        if ($request->filled('empresa_web')) {
            Log::warning('DemoLead: honeypot activado (bot detectado)', ['ip' => $request->ip()]);
            return back()->with('ok', 'Gracias. Hemos recibido tu solicitud y te contactaremos pronto.');
        }

        // 2) Tiempo mínimo de llenado
        $tiempoMs = (int) $request->input('tiempo_llenado_ms', 0);
        if ($tiempoMs > 0 && $tiempoMs < 3000) {
            Log::warning('DemoLead: envío demasiado rápido (bot)', ['ip' => $request->ip(), 'ms' => $tiempoMs]);
            return back()->with('ok', 'Gracias. Hemos recibido tu solicitud y te contactaremos pronto.');
        }

        // 4) Blacklist de correos desechables
        $email = mb_strtolower(trim($request->input('email', '')));
        if ($this->esCorreoDesechable($email)) {
            return back()->withErrors(['email' => 'Ingresa un correo corporativo válido.']);
        }

        // 5) Captcha dinámico: operación aleatoria guardada en sesión
        //    (nunca validar contra lo que muestra el HTML; solo contra la sesión)
        $captcha = session('demo_captcha', null);
        $respuestaEsperada = $captcha ? $captcha['resultado'] : null;
        $respuestaEnviada = trim((string) $request->input('captcha', ''));

        if ($respuestaEsperada === null || !is_numeric($respuestaEnviada) || (int) $respuestaEnviada !== $respuestaEsperada) {
            Log::warning('DemoLead: captcha incorrecto', ['ip' => $request->ip(), 'enviado' => $respuestaEnviada]);
            return back()->withInput()->withErrors(['captcha' => 'La respuesta de verificación es incorrecta. Intenta de nuevo.']);
        }
        session()->forget('demo_captcha');

        $validated = $request->validate([
            'nombre'   => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\p{L}\s.\'-]+$/u'],
            'email'    => ['required', 'email:rfc,dns', 'max:150'],
            'empresa'  => ['nullable', 'string', 'max:180'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'rubro'    => ['nullable', 'string', 'max:180'],
        ], [
            'nombre.required'  => 'Escribe tu nombre.',
            'nombre.regex'     => 'El nombre solo puede contener letras y espacios.',
            'email.required'   => 'Escribe tu correo.',
            'email.email'      => 'El correo no parece válido. Verifica que tenga el formato nombre@empresa.com.',
            'email.max'        => 'El correo es demasiado largo.',
            'telefono.regex'   => 'El teléfono contiene caracteres no válidos.',
        ]);

        try {
            $landing = (string) $request->input('landing', '');
            $lead = DemoLead::create([
                'nombre'     => $validated['nombre'],
                'email'      => $validated['email'],
                'empresa'    => $validated['empresa'] ?? null,
                'telefono'   => $validated['telefono'] ?? null,
                'rubro'      => $validated['rubro'] ?? null,
                'origen'     => $this->origenDesdeRequest($request),
                'landing'    => $landing,
                'ip'         => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            Mail::to('services@sunqupacha.com')->send(new DemoLeadMail(
                nombre:   $lead->nombre,
                email:    $lead->email,
                empresa:  $lead->empresa,
                telefono: $lead->telefono,
                rubro:    $lead->rubro,
                landing:  $landing,
                origen:   $lead->origen,
            ));

            Log::info('DemoLead: solicitud registrada', ['id' => $lead->id, 'email' => $lead->email]);
        } catch (\Throwable $e) {
            Log::error('DemoLead: error al registrar/enviar', ['error' => $e->getMessage()]);
            return back()->withErrors(['form' => 'No pudimos procesar tu solicitud. Escríbenos a services@sunqupacha.com o intenta más tarde.']);
        }

        return back()->with('ok', 'Gracias. Hemos recibido tu solicitud y te contactaremos para coordinar la reunión.');
    }

    protected function esCorreoDesechable(string $email): bool
    {
        if (!str_contains($email, '@')) {
            return false;
        }
        $dominio = substr($email, strrpos($email, '@') + 1);
        $desechables = [
            'mailinator.com', '10minutemail.com', 'guerrillamail.com', 'guerrillamail.net',
            'tempmail.com', 'temp-mail.org', 'yopmail.com', 'throwawaymail.com',
            'trashmail.com', 'getnada.com', 'maildrop.cc', 'dispostable.com',
            'spam4.me', 'sharklasers.com', 'mailnesia.com', 'mohmal.com',
            'emailondeck.com', 'mailcatch.com', 'mintemail.com', 'tempinbox.com',
        ];
        return in_array($dominio, $desechables, true);
    }

    protected function origenDesdeRequest(Request $request): ?string
    {
        $origen = $request->input('origen', '');
        if ($origen !== '') {
            return mb_substr($origen, 0, 250);
        }
        $ref = (string) $request->headers->get('referer', '');
        return $ref !== '' ? mb_substr($ref, 0, 250) : null;
    }
}
