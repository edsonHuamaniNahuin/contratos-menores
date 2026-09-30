<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu modelo de TDR está listo</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #025964, #2A737D); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; font-size:20px; font-weight:700; color:#ffffff;">Hola {{ $nombre }} 👋</h1>
                            <p style="margin:8px 0 0; font-size:13px; color:#79E9BC;">Descarga tu modelo de TDR desde el botón de abajo</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px; text-align:center;">
                            <p style="margin:0 0 24px; font-size:14px; color:#4B5563; line-height:1.7;">
                                Preparamos para ti un <strong>modelo de Términos de Referencia en Word</strong> con la estructura que usan las entidades del Estado: objeto, especificaciones, requisitos, plazos, pagos y penalidades.
                            </p>
                            <a href="{{ url('/descargar/modelo-tdr') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">
                                Descargar modelo de TDR (.doc)
                            </a>
                            <p style="margin:24px 0 0; font-size:12px; color:#9CA3AF; line-height:1.6;">
                                ¿Quieres que te avisemos cuando se publique una licitación de tu rubro?<br>
                                Escríbenos por WhatsApp al <strong>+51 918 874 873</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 40px; background-color:#F9FAFB; border-top:1px solid #E5E7EB; text-align:center;">
                            <p style="margin:0; font-size:11px; color:#9CA3AF;">© {{ date('Y') }} Sunqupacha S.A.C. · Vigilante SEACE · licitacionesmype.pe</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
