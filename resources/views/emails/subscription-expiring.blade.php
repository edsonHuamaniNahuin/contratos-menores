<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;padding:40px 0">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08)">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:32px 40px;text-align:center">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0 0 8px">
                                @if($type === 'trial_ending')
                                    ⏰ Tu prueba gratuita está por terminar
                                @elseif($type === 'renewal_upcoming')
                                    🔔 Próximo cobro automático
                                @else
                                    ⏰ Tu suscripción está por vencer
                                @endif
                            </h1>
                            <p style="color:#93c5fd;font-size:14px;margin:0">Vigilante SEACE</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px">

                            <p style="font-size:15px;color:#374151;margin:0 0 24px;line-height:1.6">
                                Hola <strong>{{ $userName }}</strong>,
                            </p>

                            @if($type === 'trial_ending')
                                <p style="font-size:15px;color:#374151;margin:0 0 16px;line-height:1.6">
                                    Tu <strong>prueba gratuita de 15 días</strong> del plan Premium de Vigilante SEACE
                                    está por finalizar. Te quedan <strong>{{ $daysRemaining }} día(s)</strong>.
                                </p>

                                @if($willAutoRenew)
                                    <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:16px;margin:0 0 24px;border-radius:4px">
                                        <p style="font-size:14px;color:#92400e;margin:0;line-height:1.5">
                                            <strong>⚠️ Se realizará un cobro automático de S/ 49.00</strong> al finalizar tu prueba
                                            ({{ $endsAt }}), usando la tarjeta que registraste. Si deseas cancelar,
                                            puedes hacerlo desde tu panel de suscripción.
                                        </p>
                                    </div>
                                @else
                                    <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:16px;margin:0 0 24px;border-radius:4px">
                                        <p style="font-size:14px;color:#92400e;margin:0;line-height:1.5">
                                            <strong>Tu renovación automática está desactivada.</strong> Tu acceso premium
                                            finalizará el {{ $endsAt }}. Actívala para continuar sin interrupciones.
                                        </p>
                                    </div>
                                @endif

                            @elseif($type === 'renewal_upcoming')
                                <p style="font-size:15px;color:#374151;margin:0 0 16px;line-height:1.6">
                                    Tu suscripción <strong>{{ $planLabel }}</strong> se renovará automáticamente
                                    en <strong>{{ $daysRemaining }} día(s)</strong> ({{ $endsAt }}).
                                </p>

                                <div style="background-color:#dbeafe;border-left:4px solid #3b82f6;padding:16px;margin:0 0 24px;border-radius:4px">
                                    <p style="font-size:14px;color:#1e40af;margin:0;line-height:1.5">
                                        <strong>Se cobrará automáticamente a tu tarjeta registrada.</strong>
                                        Si deseas cancelar o cambiar tu plan, hazlo antes del {{ $endsAt }}.
                                    </p>
                                </div>

                            @else
                                <p style="font-size:15px;color:#374151;margin:0 0 16px;line-height:1.6">
                                    Tu suscripción <strong>{{ $planLabel }}</strong> está por vencer.
                                    Te quedan <strong>{{ $daysRemaining }} día(s)</strong> (hasta el {{ $endsAt }}).
                                </p>

                                <div style="background-color:#fee2e2;border-left:4px solid #ef4444;padding:16px;margin:0 0 24px;border-radius:4px">
                                    <p style="font-size:14px;color:#991b1b;margin:0;line-height:1.5">
                                        <strong>Tu acceso premium expirará pronto.</strong> Renueva para seguir disfrutando
                                        de análisis de TDR con IA, seguimiento de contratos y más.
                                    </p>
                                </div>
                            @endif

                            {{-- CTA Buttons --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px">
                                <tr>
                                    <td align="center" style="padding-bottom:12px">
                                        <a href="{{ $miSuscripcionUrl }}"
                                           style="display:inline-block;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:14px 40px;border-radius:100px">
                                            Gestionar mi suscripción
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <a href="{{ $planesUrl }}"
                                           style="display:inline-block;color:#6b7280;font-size:13px;text-decoration:underline">
                                            Ver todos los planes
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0">

                            <p style="font-size:12px;color:#9ca3af;margin:0;line-height:1.6">
                                Recibes este correo porque tienes una suscripción activa en Vigilante SEACE.
                                Si tienes dudas, responde a este correo o contáctanos en
                                <a href="mailto:soporte@licitacionesmype.pe" style="color:#2563eb">soporte@licitacionesmype.pe</a>.
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f9fafb;padding:20px 40px;text-align:center">
                            <p style="font-size:11px;color:#9ca3af;margin:0">
                                &copy; {{ date('Y') }} Sunqupacha S.A.C. — Vigilante SEACE
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
