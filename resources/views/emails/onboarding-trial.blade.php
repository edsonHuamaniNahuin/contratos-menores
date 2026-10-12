<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigilante SEACE</title>
</head>
<body style="margin:0; padding:0; background-color:#F9FAFB; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F9FAFB; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #025964, #2A737D); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; font-size:20px; font-weight:700; color:#ffffff;">Hola {{ $user->name }} 👋</h1>
                            <p style="margin:8px 0 0; font-size:13px; color:#79E9BC;">
                                @switch($paso)
                                    @case('d1') Tu prueba de 15 días ya está activa @break
                                    @case('d3') Descubre el análisis de TDR con IA @break
                                    @case('d7') Tu prueba va por la mitad @break
                                    @case('d12') Tu prueba vence en 3 días @break
                                    @case('d15') Hoy termina tu prueba @break
                                    @default Tu cuenta te está esperando @break
                                @endswitch
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 40px; font-size:14px; color:#4B5563; line-height:1.7;">

                            @switch($paso)

                                @case('d1')
                                    <p style="margin:0 0 16px;">Para que le saques todo el provecho desde hoy, deja lista tu cuenta en <strong>2 minutos</strong>:</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>1. Activa tus alertas</strong><br>Elige tu rubro, zona y canal (WhatsApp, Telegram o correo). Te avisamos el mismo día en que el Estado publica.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>2. Mira las licitaciones de hoy</strong><br>Filtra por entidad, departamento y objeto para ver qué se está convocando ahora.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>3. Guarda nuestros contactos</strong><br>WhatsApp <strong>+51 918 874 873</strong> — cualquier duda te ayudamos al toque.</p>
                                        </td></tr>
                                    </table>
                                    <p style="margin:24px 0 0;">
                                        <a href="{{ url('/configuracion-alertas') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Activar mis alertas ahora</a>
                                    </p>
                                    @break

                                @case('d3')
                                    <p style="margin:0 0 16px;">¿Ya conoces el <strong>análisis de TDR con IA</strong>? Es la función que más tiempo te ahorra:</p>
                                    <p style="margin:0 0 16px;">Antes de invertir días preparando una propuesta, la IA lee las bases completas y te dice:</p>
                                    <p style="margin:0 0 16px; padding-left:16px;">
                                        ✔️ Si tu empresa califica o no<br>
                                        ✔️ Los requisitos que podrían descalificarte<br>
                                        ✔️ Plazos, penalidades y forma de pago
                                    </p>
                                    <p style="margin:0 0 24px;">Pruébalo con cualquier proceso convocado: entra al buscador, elige un contrato y toca "Analizar con IA".</p>
                                    <p style="margin:0;">
                                        <a href="{{ url('/buscador-contratos-mayores') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Analizar un TDR ahora</a>
                                    </p>
                                    @break

                                @case('d7')
                                    <p style="margin:0 0 16px;">Ya llevas una semana con la cuenta activa. Tres cosas que quizá aún no probaste:</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>Seguimiento de procesos</strong><br>Marca los procesos que te interesan y mira plazos y estados sin volver a buscar.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>Exportar resultados</strong><br>Descarga las convocatorias filtradas para trabajarlas con tu equipo.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>Contratos Mayores</strong><br>Buscador completo con entidades, montos y análisis IA de las fichas.</p>
                                        </td></tr>
                                    </table>
                                    <p style="margin:24px 0 0;">¿Quieres que te mostremos el sistema en vivo? Agenda una demo de 20 minutos:</p>
                                    <p style="margin:16px 0 0;">
                                        <a href="https://wa.me/51918874873?text={{ urlencode('Hola, quiero una demo de Vigilante SEACE') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Agendar demo por WhatsApp</a>
                                    </p>
                                    @break

                                @case('d12')
                                    <p style="margin:0 0 16px;">Tu prueba gratuita vence en <strong>3 días</strong>. Para mantener tus alertas y accesos activos, activa tu plan cuando quieras — toma 2 minutos:</p>
                                    <p style="margin:0 0 16px; padding-left:16px;">
                                        1️⃣ Elige tu plan en <strong>Mi Suscripción</strong><br>
                                        2️⃣ Paga con <strong>Yape</strong> desde tu celular<br>
                                        3️⃣ Sube tu comprobante — lo aprobamos en minutos
                                    </p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:16px 0;">
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>Mensual S/ 49</strong> · <strong>Anual S/ 470</strong> (equivale a 2 meses gratis) · <strong>Premium + Mayores S/ 68</strong></p>
                                        </td></tr>
                                    </table>
                                    <p style="margin:0;">
                                        <a href="{{ url('/billing') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Activar mi plan ahora</a>
                                    </p>
                                    @break

                                @case('d15')
                                    <p style="margin:0 0 16px;"><strong>Hoy vence tu prueba gratuita.</strong> No queremos que pierdas tus alertas configuradas ni el acceso al análisis IA.</p>
                                    <p style="margin:0 0 24px;">Reactiva en 2 minutos: elige tu plan, paga con Yape y sube tu comprobante. Se aprueba al instante.</p>
                                    <p style="margin:0 0 24px;">
                                        <a href="{{ url('/billing') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Reactivar mi cuenta</a>
                                    </p>
                                    <p style="margin:0;">¿Necesitas un par de días más para decidir? Responde este correo y lo vemos.</p>
                                    @break

                                @default
                                    <p style="margin:0 0 16px;">Tu prueba terminó hace un tiempo, pero <strong>esto es lo nuevo</strong> en Vigilante SEACE:</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;">
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>🤖 Análisis de TDR con IA</strong><br>Subes las bases y te dice si calificas, qué te descalifica y los plazos clave.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>🔍 Contratos Mayores completo</strong><br>Buscador con entidades, montos, documentos y seguimientos.</p>
                                        </td></tr>
                                        <tr><td style="height:10px;"></td></tr>
                                        <tr><td style="padding:14px 16px; background-color:#F0F7F5; border-radius:12px;">
                                            <p style="margin:0;"><strong>🔔 Alertas el mismo día</strong><br>Tu rubro, tu zona, por WhatsApp, Telegram o correo.</p>
                                        </td></tr>
                                    </table>
                                    <p style="margin:24px 0 0;">Reactiva tu cuenta hoy mismo:</p>
                                    <p style="margin:16px 0 0;">
                                        <a href="{{ url('/billing') }}" style="display:inline-block; background:#025964; color:#ffffff; text-decoration:none; font-weight:700; font-size:14px; padding:14px 32px; border-radius:999px;">Reactivar mi cuenta</a>
                                    </p>
                                    @break

                            @endswitch

                            <p style="margin:28px 0 0; font-size:12.5px; color:#9CA3AF;">
                                ¿Dudas? Escríbenos por WhatsApp al <a href="https://wa.me/51918874873" style="color:#025964; text-decoration:none; font-weight:600;">+51 918 874 873</a> o responde este correo.
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
