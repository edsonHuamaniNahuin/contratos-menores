<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu reporte de convocatorias</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:36px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #025964, #2A737D); padding:30px 36px;">
                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:#79E9BC;">📋 Reporte semanal · Vigilante SEACE</p>
                            <h1 style="margin:0; font-size:22px; font-weight:800; color:#ffffff; line-height:1.25;">
                                {{ $nombre ?: 'Hola' }}: estas son las convocatorias de la semana
                            </h1>
                            <p style="margin:8px 0 0; font-size:13px; color:rgba(255,255,255,.82);">
                                Del {{ $fechaInicio }} al {{ $fechaFin }} · seleccionadas para proveedores del Estado
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px 36px 8px;">
                            <p style="margin:0 0 16px; font-size:14px; color:#4B5563; line-height:1.7;">
                                Como te prometimos, aquí está tu <strong>reporte de convocatorias</strong> con los procesos
                                más relevantes de los últimos 7 días:
                            </p>

                            @forelse ($convocatorias as $i => $c)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:10px;">
                                    <tr>
                                        <td style="padding:13px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0 0 4px; font-size:10.5px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#0B7A54;">
                                                {{ $c['rubro'] }}{{ $c['departamento'] ? ' · ' . $c['departamento'] : '' }}
                                            </p>
                                            <p style="margin:0; font-size:13.5px; color:#111827; font-weight:600; line-height:1.5;">
                                                {{ $c['descripcion'] }}
                                            </p>
                                            <p style="margin:5px 0 0; font-size:11.5px; color:#6B7280;">
                                                {{ $c['entidad'] }}{{ $c['cierre'] ? ' · cierre: ' . $c['cierre'] : '' }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @empty
                                <p style="margin:0 0 16px; font-size:13.5px; color:#4B5563;">Esta semana no encontramos procesos en las categorías principales — revisa el buscador para ver todo.</p>
                            @endforelse

                            <p style="margin:18px 0 10px; font-size:14px; color:#111827; font-weight:700;">
                                🎯 ¿Quieres SOLO las de TU rubro?
                            </p>
                            <p style="margin:0 0 20px; font-size:13.5px; color:#4B5563; line-height:1.7;">
                                <strong>Responde este correo con tu rubro</strong> (ej: obras viales, saneamiento, electricidad)
                                y te envío el reporte filtrado a tu especialidad — y te explico cómo recibirlo
                                automáticamente cada semana.
                            </p>

                            <p style="margin:0 0 8px;">
                                <a href="{{ url('/buscador-publico') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:13.5px; padding:12px 26px; border-radius:999px; margin-right:8px;">
                                    Ver el buscador gratis
                                </a>
                                <a href="{{ url('/register') }}" style="display:inline-block; background:#79E9BC; color:#025964; text-decoration:none; font-weight:700; font-size:13.5px; padding:12px 26px; border-radius:999px;">
                                    Activar mis alertas
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 36px 22px;">
                            <p style="margin:0; font-size:12px; color:#6B7280; line-height:1.7;">
                                ¿Dudas? Escríbenos por WhatsApp:
                                <a href="https://wa.me/51918874873?text={{ urlencode('Hola, vi el reporte de convocatorias') }}" style="color:#025964; font-weight:700; text-decoration:none;">+51 918 874 873</a>
                                · Garantía de 5 días en cualquier plan.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 36px; background-color:#F9FAFB; border-top:1px solid #E5E7EB; text-align:center;">
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
