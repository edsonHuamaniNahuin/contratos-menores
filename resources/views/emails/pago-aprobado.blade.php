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

### 📌 Accede a tu cuenta

@component('mail::button', ['url' => config('app.url')])
Ir a Vigilante SEACE
@endcomponent

Gracias por confiar en nosotros.

**Vigilante SEACE**
@endcomponent
