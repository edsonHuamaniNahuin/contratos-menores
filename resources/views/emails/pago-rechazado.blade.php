@component('mail::message')
# Pago Rechazado

Hola **{{ $userName }}**,

Lamentamos informarte que tu comprobante de pago por **S/ {{ number_format($monto, 2) }}** ha sido **rechazado**.

---

### 📝 Motivo

> *"{{ $motivo }}"*

---

### ¿Qué puedes hacer?

Es posible que el comprobante no sea legible, el monto no coincida o falte información. Puedes **intentar nuevamente** subiendo un nuevo comprobante con los datos correctos.

@component('mail::button', ['url' => config('app.url') . '/planes'])
Volver a Planes
@endcomponent

Si crees que es un error, responde a este correo y revisaremos tu caso.

**Vigilante SEACE**
@endcomponent
