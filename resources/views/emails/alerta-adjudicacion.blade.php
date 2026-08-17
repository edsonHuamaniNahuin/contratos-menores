<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buena pro otorgada</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #B45309 0%, #D97706 100%); padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:700;">
                                            🏆 Buena pro otorgada
                                        </h1>
                                        <p style="margin:6px 0 0; color:#FDE68A; font-size:13px;">
                                            Un proceso vigilado cambio de estado a {{ $proceso['estado'] ?? 'ADJUDICADO' }}.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background-color:#F3F4F6; border-radius:12px; padding:16px 20px;">
                                        <p style="margin:0 0 4px; font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.5px;">
                                            Codigo del proceso
                                        </p>
                                        <p style="margin:0; font-size:16px; font-weight:700; color:#111827;">
                                            {{ $proceso['nomenclatura'] ?? 'N/A' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">🏢 Entidad</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">{{ $proceso['entidad_nombre'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">💰 Valor referencial</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">
                                            {{ isset($proceso['valor_referencial']) && $proceso['valor_referencial'] > 0 ? 'S/ ' . number_format($proceso['valor_referencial'], 2) : 'No publicado' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">🏅 Estado</span><br>
                                        <span style="font-size:14px; color:#B45309; font-weight:700;">{{ $proceso['estado'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                @if(!empty($proceso['proveedores']))
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #F3F4F6;">
                                        <span style="font-size:12px; color:#9CA3AF;">🤝 Proveedor(es) adjudicado(s)</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">
                                            {{ implode(', ', array_slice($proceso['proveedores'], 0, 5)) }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 0;">
                                        <span style="font-size:12px; color:#9CA3AF;">📅 Publicado</span><br>
                                        <span style="font-size:14px; color:#111827; font-weight:600;">{{ $proceso['fecha_publicacion'] ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center" style="background-color:#FEF3C7; border-radius:12px; padding:16px 20px;">
                                        <p style="margin:0; font-size:12px; color:#92400E; line-height:1.5;">
                                            🔍 Consulta el detalle completo del proceso en
                                            <a href="https://licitacionesmype.pe/buscador-contratos-mayores" style="color:#B45309; font-weight:700;">Vigilante SEACE</a>
                                            buscando la nomenclatura <strong>{{ $proceso['nomenclatura'] ?? '' }}</strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#F9FAFB; padding:24px 40px; text-align:center;">
                            <p style="margin:0; font-size:11px; color:#9CA3AF;">
                                Alerta automatica de vigilancia de adjudicaciones de Vigilante SEACE.<br>
                                Procesos mayores a S/ {{ number_format($proceso['umbral'] ?? 1000000, 0) }} monitoreados cada 5 horas.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
