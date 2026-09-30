<?php

namespace App\Http\Controllers;

use App\Mail\TdrPlantillaMail;
use App\Models\DemoLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lead magnet: "Modelo de TDR en Word".
 *
 * El visitante deja su correo (con las mismas defensas antibot/spam del
 * formulario de demo) y recibe el enlace de descarga del modelo .doc.
 */
class PlantillaTdrController extends Controller
{
    /**
     * GET /descargar/modelo-tdr — sirve el archivo .doc del modelo de TDR.
     */
    public function descargar(): Response
    {
        $html = $this->plantillaHtml();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-word',
            'Content-Disposition' => 'attachment; filename="modelo-tdr-contrataciones-estado.doc"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * POST /plantillas-tdr — captura el correo y devuelve con el enlace de descarga.
     */
    public function capturar(Request $request)
    {
        $ipKey = 'plantilla_tdr:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            return back()->withInput()
                ->withErrors(['form' => 'Has enviado varias solicitudes seguidas. Espera unos minutos e inténtalo de nuevo.']);
        }
        RateLimiter::hit($ipKey, 600);

        // Honeypot
        if ($request->filled('empresa_web')) {
            Log::warning('PlantillaTdr: honeypot activado', ['ip' => $request->ip()]);
            return back()->with('ok', 'Listo. Revisa tu correo: te enviamos el enlace de descarga.');
        }

        // Tiempo mínimo de llenado
        $tiempoMs = (int) $request->input('tiempo_llenado_ms', 0);
        if ($tiempoMs > 0 && $tiempoMs < 3000) {
            Log::warning('PlantillaTdr: envío demasiado rápido', ['ip' => $request->ip()]);
            return back()->with('ok', 'Listo. Revisa tu correo: te enviamos el enlace de descarga.');
        }

        // Blacklist de correos desechables
        $email = mb_strtolower(trim($request->input('email', '')));
        if ($this->esCorreoDesechable($email)) {
            return back()->withErrors(['email' => 'Ingresa un correo válido.']);
        }

        // Captcha dinámico (sesión)
        $captcha = session('demo_captcha', null);
        $respuestaEsperada = $captcha ? $captcha['resultado'] : null;
        $respuestaEnviada = trim((string) $request->input('captcha', ''));
        if ($respuestaEsperada === null || !is_numeric($respuestaEnviada) || (int) $respuestaEnviada !== $respuestaEsperada) {
            Log::warning('PlantillaTdr: captcha incorrecto', ['ip' => $request->ip()]);
            return back()->withInput()->withErrors(['captcha' => 'La respuesta de verificación es incorrecta. Intenta de nuevo.']);
        }
        session()->forget('demo_captcha');

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\p{L}\s.\'-]+$/u'],
            'email'  => ['required', 'email:rfc,dns', 'max:150'],
        ], [
            'nombre.required' => 'Escribe tu nombre.',
            'nombre.regex'    => 'El nombre solo puede contener letras y espacios.',
            'email.required'  => 'Escribe tu correo.',
            'email.email'     => 'El correo no parece válido.',
        ]);

        try {
            $lead = DemoLead::create([
                'nombre'     => $validated['nombre'],
                'email'      => $validated['email'],
                'landing'    => 'plantillas-tdr',
                'origen'     => mb_substr((string) $request->headers->get('referer', ''), 0, 250) ?: null,
                'ip'         => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            Mail::to('services@sunqupacha.com')->send(new TdrPlantillaMail(
                nombre: $lead->nombre,
                email:  $lead->email,
            ));

            Log::info('PlantillaTdr: lead registrado', ['id' => $lead->id, 'email' => $lead->email]);
        } catch (\Throwable $e) {
            Log::error('PlantillaTdr: error', ['error' => $e->getMessage()]);
            return back()->withErrors(['form' => 'No pudimos procesar tu solicitud. Escríbenos a services@sunqupacha.com.']);
        }

        return back()->with('ok', 'Descarga lista');
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

    protected function plantillaHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Modelo de TDR - Términos de Referencia</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; margin: 2cm; color: #1a1a1a; }
  h1 { font-size: 15pt; color: #1a3a5c; border-bottom: 2px solid #1a3a5c; padding-bottom: 6px; }
  h2 { font-size: 12pt; color: #1a3a5c; margin-top: 18px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  td, th { border: 1px solid #bbb; padding: 6px 8px; font-size: 10pt; vertical-align: top; }
  th { background: #e8eef7; }
  .nota { background: #fdf6e3; border: 1px solid #e6d9a8; padding: 8px 12px; font-size: 9.5pt; margin-top: 12px; }
  .relleno { color: #888; }
</style>
</head>
<body>
<h1>TÉRMINOS DE REFERENCIA — [NOMBRE DEL SERVICIO]</h1>
<p>Entidad: [RAZÓN SOCIAL DE LA ENTIDAD] · Fecha: [DD/MM/AAAA]</p>

<div class="nota"><strong>Cómo usar este modelo:</strong> reemplaza todo lo que está entre [CORCHETES] por los datos reales de tu proceso. Conserva los apartados que apliquen a tu servicio y elimina los que no. Este documento es una guía base; el contenido final debe ajustarse a la convocatoria de tu entidad y a la normativa vigente.</div>

<h2>1. Antecedentes</h2>
<p>[Descripción breve de la situación que origina la contratación. Ej.: "La Municipalidad Distrital de X requiere el servicio de mantenimiento vial para conservar la transitabilidad de las vías urbanas durante el periodo de lluvias".]</p>

<h2>2. Objeto de la contratación</h2>
<p>[Descripción del bien/servicio/consultoría. Ej.: "El servicio de mantenimiento periódico de pistas y veredas de la zona urbana del distrito".]</p>

<h2>3. Finalidad pública</h2>
<p>[Beneficio que obtiene la población. Ej.: "Garantizar la transitabilidad vehicular y peatonal segura, reduciendo el deterioro de la infraestructura vial".]</p>

<h2>4. Descripción del servicio / especificaciones técnicas</h2>
<table>
  <tr><th>Ítem</th><th>Descripción</th><th>Unidad</th><th>Cantidad</th></tr>
  <tr><td>1</td><td>[Descripción de la actividad principal]</td><td>[glb/mes/ml]</td><td>[cantidad]</td></tr>
  <tr><td>2</td><td>[Descripción de la actividad secundaria]</td><td>[unidad]</td><td>[cantidad]</td></tr>
</table>
<p class="relleno">(Ajusta las filas según el detalle de tu servicio; incluye los anexos que la entidad indique.)</p>

<h2>5. Requisitos del proveedor (calificación)</h2>
<ul>
  <li>Estar inscrito en el RNP en la especialidad y categoría que corresponda. [El ejecutor debe acreditar la inscripción vigente]</li>
  <li>Experiencia: [N] años y/o [N] contratos similares. [Ej.: "Acreditar un contrato de servicio de mantenimiento vial en los últimos 5 años"]</li>
  <li>Personal clave: [perfil requerido, ej. residente CIP colegiado, técnico especializado].</li>
  <li>Equipamiento: [equipo y maquinaria mínimos].</li>
  <li>Documentación: [RUC activo, poder de representación, declaraciones juradas].</li>
</ul>

<h2>6. Plazo de ejecución y lugar</h2>
<p>El servicio se ejecutará en un plazo de [N] días calendario, contados desde el día siguiente de la orden de servicio, en [lugar/región]. El contratista deberá presentar un cronograma de trabajo al inicio.</p>

<h2>7. Forma de pago</h2>
<p>La contraprestación se pagará en [1/2/3] armadas contra entrega del informe y conformidad de la entidad. Los pagos están sujetos a la disponibilidad presupuestal de la entidad.</p>

<h2>8. Penalidades</h2>
<p>En caso de incumplimiento injustificado del plazo, se aplicará una penalidad de [0.5/1] por mil del monto del contrato por cada día de atraso, hasta un máximo del 10% del monto total, conforme a la normativa vigente.</p>

<h2>9. Confidencialidad y propiedad intelectual</h2>
<p>El contratista se obliga a mantener reserva sobre la información recibida. Toda información, informe o entregable producido con ocasión del servicio es de propiedad de la entidad.</p>

<h2>10. Anexos</h2>
<ul>
  <li>Anexo 1: [Formato de propuesta económica]</li>
  <li>Anexo 2: [Cronograma de trabajo]</li>
  <li>Anexo 3: [Requisitos de calificación - formularios]</li>
</ul>

<p style="margin-top:30px; text-align:center; font-size:9pt; color:#888;">Documento generado con el modelo gratuito de Vigilante SEACE · licitacionesmype.pe</p>
</body>
</html>
HTML;
    }
}
