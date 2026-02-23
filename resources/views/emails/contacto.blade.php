<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto Web</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #025964, #2A737D); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; font-size:20px; font-weight:700; color:#ffffff;">Nuevo mensaje de contacto</h1>
                            <p style="margin:8px 0 0; font-size:13px; color:#79E9BC;">Recibido desde el formulario web de Vigilante SEACE</p>
                        </td>
                    </tr>

                    {{-- Contenido --}}
                    <tr>
                        <td style="padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding-bottom:16px;">
                                        <p style="margin:0; font-size:12px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em;">Nombre</p>
                                        <p style="margin:4px 0 0; font-size:15px; color:#111827;">{{ $nombre }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom:16px;">
                                        <p style="margin:0; font-size:12px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em;">Correo</p>
                                        <p style="margin:4px 0 0; font-size:15px; color:#111827;"><a href="mailto:{{ $email }}" style="color:#025964; text-decoration:none;">{{ $email }}</a></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom:16px;">
                                        <p style="margin:0; font-size:12px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em;">Asunto</p>
                                        <p style="margin:4px 0 0; font-size:15px; color:#111827;">{{ $asunto }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom:0;">
                                        <p style="margin:0; font-size:12px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em;">Mensaje</p>
                                        <div style="margin:8px 0 0; padding:16px; background-color:#F9FAFB; border-radius:12px; font-size:14px; color:#4B5563; line-height:1.6;">
                                            {!! nl2br(e($mensajeTexto)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 40px; background-color:#F9FAFB; border-top:1px solid #E5E7EB; text-align:center;">
                            <p style="margin:0; font-size:11px; color:#9CA3AF;">&copy; {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
