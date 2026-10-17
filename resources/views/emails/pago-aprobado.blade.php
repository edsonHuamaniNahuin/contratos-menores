@component('mail::message')
# ¡Pago Aprobado!

Hola **{{ $userName }}**,

Tu pago por **S/ {{ number_format($monto, 2) }}** ha sido **aprobado**. Tu cuenta Premium ya está activa.

---

### 🧾 Detalle del pago

| | |
|---|---|
| **Plan** | {{ $planLabel }} |
| **Monto** | S/ {{ number_format($monto, 2) }} |
| **Vigencia** | {{ $diasVigencia }} días |
| **Fecha de aprobación** | {{ $fechaAprobacion }} |
| **Vence el** | {{ $fechaVencimiento }} |
| **Medio de pago** | Yape |

---

### 🤝 Tu setup asistido (gratis)

**Responde este correo con tu rubro y tus regiones** y configuramos tus alertas por ti — en minutos queda listo y empiezas a recibir avisos el mismo día.

¿Dudas? Escríbenos por WhatsApp: **+51 918 874 873** (respondemos en menos de 2 horas hábiles).

---

### 📌 Accede a tu cuenta

@component('mail::button', ['url' => config('app.url')])
Ir a Vigilante SEACE
@endcomponent

Gracias por confiar en nosotros.

**Vigilante SEACE**
@endcomponent
