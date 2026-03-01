<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Vigilante SEACE</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #025964 0%, #2A737D 100%); padding:40px 40px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <h1 style="margin:0; color:#ffffff; font-size:28px; font-weight:800;">
                                            🎉 ¡Bienvenido!
                                        </h1>
                                        <p style="margin:10px 0 0; color:#A4C3C6; font-size:14px;">
                                            Tu cuenta en Vigilante SEACE ha sido creada exitosamente.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px;">

                            {{-- Saludo --}}
                            <p style="margin:0 0 20px; font-size:16px; color:#111827;">
                                Hola <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; color:#4B5563; line-height:1.7;">
                                Gracias por registrarte en <strong>Vigilante SEACE</strong>, la plataforma inteligente para monitorear licitaciones y contrataciones del Estado peruano. Estamos encantados de tenerte con nosotros.
                            </p>

                            {{-- Qué puedes hacer --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="background-color:#F3F4F6; border-radius:16px; padding:24px;">
                                        <p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#111827;">
                                            🚀 ¿Qué puedes hacer ahora?
                                        </p>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding:8px 0;">
                                                    <span style="font-size:13px; color:#025964; font-weight:600;">🔍 Buscador Público</span><br>
                                                    <span style="font-size:12px; color:#4B5563;">Busca licitaciones en tiempo real con filtros avanzados por departamento, entidad, objeto y estado.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;">
                                                    <span style="font-size:13px; color:#025964; font-weight:600;">📊 Dashboard de Gráficos</span><br>
                                                    <span style="font-size:12px; color:#4B5563;">Visualiza tendencias, distribución por departamento y las entidades con más publicaciones.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;">
                                                    <span style="font-size:13px; color:#025964; font-weight:600;">📅 Seguimiento de Procesos</span><br>
                                                    <span style="font-size:12px; color:#4B5563;">Agenda y haz seguimiento de los procesos que te interesan en un calendario visual.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;">
                                                    <span style="font-size:13px; color:#025964; font-weight:600;">🤖 Bot de Telegram</span><br>
                                                    <span style="font-size:12px; color:#4B5563;">Recibe notificaciones automáticas de nuevos procesos que coincidan con tus palabras clave.</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Premium callout --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="padding:16px 20px; background-color:#E6FFF3; border-radius:12px; border-left:4px solid #00D47E;">
                                        <p style="margin:0 0 6px; font-size:13px; color:#025964; font-weight:700;">
                                            ⭐ ¿Quieres sacarle el máximo provecho?
                                        </p>
                                        <p style="margin:0; font-size:12px; color:#025964; line-height:1.6;">
                                            Con el plan <strong>Premium</strong> accedes a análisis de TDR con IA, score de compatibilidad, notificaciones personalizadas por Telegram y mucho más.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Botones CTA --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center" style="padding:8px 0;">
                                        <a href="{{ $manualUrl }}"
                                           target="_blank"
                                           style="display:inline-block; padding:14px 36px; background-color:#025964; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:50px;">
                                            📖 Ver Manual del Usuario
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:8px 0;">
                                        <a href="{{ $buscadorUrl }}"
                                           target="_blank"
                                           style="display:inline-block; padding:14px 36px; background-color:#00D47E; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:50px;">
                                            🔍 Explorar el Buscador
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <p style="margin:12px 0 0; font-size:11px; color:#9CA3AF;">
                                            Revisa nuestro manual para aprender a usar todas las funcionalidades.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#F9FAFB; padding:24px 40px; border-top:1px solid #F3F4F6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 4px; font-size:13px; font-weight:700; color:#111827;">
                                            Vigilante SEACE
                                        </p>
                                        <p style="margin:0; font-size:11px; color:#9CA3AF;">
                                            Un producto de Sunqupacha S.A.C.
                                        </p>
                                        <p style="margin:4px 0 0; font-size:11px; color:#9CA3AF;">
                                            <a href="mailto:services@sunqupacha.com" style="color:#025964; text-decoration:none;">services@sunqupacha.com</a> · +51 918 874 873
                                        </p>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <a href="{{ $planesUrl }}"
                                           style="font-size:12px; color:#025964; text-decoration:none; font-weight:600;">
                                            Ver planes →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0; font-size:10px; color:#9CA3AF; text-align:center;">
                                Recibes este correo porque acabas de crear una cuenta en Vigilante SEACE.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
