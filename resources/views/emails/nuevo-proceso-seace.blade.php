<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo proceso SEACE</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #025964 0%, #2A737D 100%); padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:700;">
                                            🔔 Nuevo proceso SEACE
                                        </h1>
                                        <p style="margin:6px 0 0; color:#A4C3C6; font-size:13px;">
                                            Se encontro una licitacion que coincide con tus intereses.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px;">
                            {{-- Codigo del proceso --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background-color:#F3F4F6; border-radius:12px; padding:16px 20px;">
                                        <p style="margin:0 0 4px; font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px;">
                                            Codigo del proceso
                                        </p>
                                        <p style="margin:0; font-size:16px; font-weight:700; color:#111827;">
                                            {{ $contrato['desContratacion'] ?? 'N/A' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Datos del contrato --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">🏢 Entidad</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">{{ $contrato['nomEntidad'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">🎯 Tipo</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">{{ $contrato['nomObjetoContrato'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">📋 Descripcion</span><br>
                                        <span style="font-size:14px; color:#4B5563;">{{ \Illuminate\Support\Str::limit($contrato['desObjetoContrato'] ?? 'N/A', 250) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">💼 Estado</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">{{ $contrato['nomEstadoContrato'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">📍 Etapa</span><br>
                                        <span style="font-size:14px; color:#111827;">{{ $contrato['nomEtapaContratacion'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                            </table>

                            {{-- Fechas --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td width="50%" style="padding:12px; background-color:#F9FAFB; border-radius:12px 0 0 12px;">
                                        <p style="margin:0 0 2px; font-size:11px; color:#9CA3AF;">📅 Publicado</p>
                                        <p style="margin:0; font-size:13px; color:#111827; font-weight:600;">{{ $contrato['fecPublica'] ?? 'N/A' }}</p>
                                    </td>
                                    <td width="50%" style="padding:12px; background-color:#F9FAFB; border-radius:0 12px 12px 0;">
                                        <p style="margin:0 0 2px; font-size:11px; color:#9CA3AF;">⏰ Fin cotizacion</p>
                                        <p style="margin:0; font-size:13px; color:#111827; font-weight:600;">{{ $contrato['fecFinCotizacion'] ?? 'N/A' }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Keywords que coincidieron --}}
                            @if(!empty($matchedKeywords))
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="padding:12px 16px; background-color:#E6FFF3; border-radius:12px; border-left:4px solid #00D47E;">
                                        <p style="margin:0 0 6px; font-size:12px; color:#025964; font-weight:700;">🔎 Palabras clave que coincidieron:</p>
                                        <p style="margin:0; font-size:13px; color:#025964;">
                                            {{ implode(', ', $matchedKeywords) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            {{-- Boton Hacer Seguimiento --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center" style="padding:8px 0;">
                                        <a href="{{ $seguimientoUrl }}"
                                           target="_blank"
                                           style="display:inline-block; padding:14px 36px; background-color:#025964; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:50px;">
                                            📌 Hacer seguimiento
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:8px 0 0; font-size:11px; color:#9CA3AF;">
                                            Agrega este proceso a tu calendario de seguimientos.
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
                                        <a href="{{ url('/buscador-publico') }}"
                                           style="font-size:12px; color:#025964; text-decoration:none; font-weight:600;">
                                            Ver buscador →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0; font-size:10px; color:#9CA3AF; text-align:center;">
                                Recibes este correo porque tienes activadas las notificaciones por email en Vigilante SEACE.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
