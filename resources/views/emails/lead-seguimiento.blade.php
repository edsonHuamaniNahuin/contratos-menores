<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Ya viste las licitaciones de tu rubro?</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #025964, #2A737D); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; font-size:20px; font-weight:700; color:#ffffff;">Hola {{ $nombre ?: 'estimado proveedor' }} 👋</h1>
                            <p style="margin:8px 0 0; font-size:13px; color:#79E9BC;">Te escribimos de Vigilante SEACE</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="margin:0 0 18px; font-size:14px; color:#4B5563; line-height:1.7;">
                                Vimos tu interés en licitaciones del Estado. Para que no dependas de revisar el SEACE a mano,
                                recuerda que Vigilante SEACE te avisa <strong>el mismo día</strong> que el Estado publica
                                convocatorias de tu rubro — por Telegram o correo — y la IA revisa el TDR para decirte si calificas.
                            </p>

                            <div style="margin:0 0 22px; padding:16px; background-color:#F0F7F5; border-radius:12px;">
                                <p style="margin:0; font-size:13.5px; color:#025964; font-weight:600;">
                                    Al activar tienes <strong>5 días de garantía</strong>: si no te convence, te devolvemos tu dinero.
                                </p>
                            </div>

                            <p style="margin:0 0 24px;">
                                <a href="{{ url('/register') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">
                                    Crear mi cuenta y activar
                                </a>
                            </p>

                            <p style="margin:0; font-size:13.5px; color:#4B5563; line-height:1.7;">
                                ¿Prefieres que lo veamos juntos en una demo de 20 minutos? Escríbenos por WhatsApp:
                                <a href="https://wa.me/51918874873?text={{ urlencode('Hola, quiero información de Vigilante SEACE') }}" style="color:#025964; font-weight:700; text-decoration:none;">+51 918 874 873</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 40px; background-color:#F9FAFB; border-top:1px solid #E5E7EB; text-align:center;">
                            <p style="margin:0; font-size:11px; color:#9CA3AF; line-height:1.6;">
                                © {{ date('Y') }} Sunqupacha S.A.C. · Vigilante SEACE · licitacionesmype.pe<br>
                                Servicio privado e independiente. No estamos afiliados al OSCE ni al Estado peruano.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
