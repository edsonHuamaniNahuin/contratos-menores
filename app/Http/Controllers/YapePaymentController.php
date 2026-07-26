<?php

namespace App\Http\Controllers;

use App\Models\PagoYape;
use App\Models\Subscription;
use App\Services\Payments\MercadoPagoGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class YapePaymentController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function show(string $plan): \Illuminate\View\View
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('home')->with('info', 'Administrador no necesita pagar.');
        }

        $validPlans = [Subscription::PLAN_MONTHLY, Subscription::PLAN_YEARLY, Subscription::PLAN_MAYORES_PREMIUM];
        if (!in_array($plan, $validPlans)) {
            abort(404);
        }

        $prices = MercadoPagoGateway::planPrices();
        $monto = $prices[$plan] ?? 0;

        $planLabels = [
            Subscription::PLAN_MONTHLY => 'Plan Premium Mensual',
            Subscription::PLAN_YEARLY => 'Plan Premium Anual',
            Subscription::PLAN_MAYORES_PREMIUM => 'Plan Premium + Contratos Mayores',
        ];

        return view('pago-yape', [
            'plan' => $plan,
            'monto' => $monto,
            'planLabel' => $planLabels[$plan] ?? $plan,
            'pagosPendientes' => PagoYape::where('user_id', $user->id)
                ->where('estado', PagoYape::ESTADO_PENDIENTE)
                ->count(),
        ]);
    }

    public function submit(Request $request, string $plan): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $validPlans = [Subscription::PLAN_MONTHLY, Subscription::PLAN_YEARLY, Subscription::PLAN_MAYORES_PREMIUM];
        if (!in_array($plan, $validPlans)) {
            abort(404);
        }

        $prices = MercadoPagoGateway::planPrices();
        $monto = $prices[$plan] ?? 0;

        // Check for existing pending payment
        $pending = PagoYape::where('user_id', $user->id)
            ->where('estado', PagoYape::ESTADO_PENDIENTE)
            ->first();
        if ($pending) {
            return back()->with('error', 'Ya tienes un pago pendiente de validación. Espera a que sea procesado.');
        }

        $request->validate([
            'comprobante' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
            'referencia_adicional' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
        ], [
            'comprobante.required' => 'Debes adjuntar el screenshot del pago de Yape.',
            'comprobante.image' => 'El archivo debe ser una imagen (JPG, PNG o WebP).',
            'comprobante.mimes' => 'Formato no permitido. Usa JPG, PNG o WebP.',
            'comprobante.max' => 'La imagen no debe superar los 5 MB.',
        ]);

        $file = $request->file('comprobante');

        // Validate MIME type using file inspection (not just extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname());
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            return back()->withErrors(['comprobante' => 'Formato de archivo no permitido. Usa JPG, PNG o WebP reales.'])->withInput();
        }

        // Validate dimensions (must be a real image)
        $dimensions = @getimagesize($file->getPathname());
        if (!$dimensions || $dimensions[0] < 100 || $dimensions[1] < 100) {
            return back()->withErrors(['comprobante' => 'La imagen es demasiado pequeña. Debe tener al menos 100x100 píxeles.'])->withInput();
        }

        // Sanitize original filename
        $nombreOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $nombreOriginal = substr($nombreOriginal, -100); // max 100 chars

        // Generate unique identifier
        $identificador = date('Ymd_His') . '_' . strtolower(Str::random(12));
        $extension = strtolower($file->getClientOriginalExtension());

        // Store in dedicated folder: comprobantes/yape/{user_id}/
        $carpeta = 'comprobantes/yape/' . $user->id;
        $filename = "pago_{$identificador}.{$extension}";
        $path = $file->storeAs($carpeta, $filename, 'public');

        // Strip EXIF metadata for JPEG files
        $fullPath = storage_path('app/public/' . $path);
        if ($mime === 'image/jpeg') {
            try {
                $img = @imagecreatefromjpeg($fullPath);
                if ($img) {
                    @imagejpeg($img, $fullPath, 92);
                    @imagedestroy($img);
                }
            } catch (\Throwable $e) {
                // Silently continue if EXIF stripping fails
            }
        }

        PagoYape::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'tipo' => PagoYape::TIPO_NUEVO,
            'monto' => $monto,
            'comprobante' => $path,
            'comprobante_dir' => $carpeta,
            'nombre_original' => $nombreOriginal,
            'estado' => PagoYape::ESTADO_PENDIENTE,
            'referencia_adicional' => $request->referencia_adicional,
            'telefono' => $request->telefono,
        ]);

        return redirect()->route('mi.suscripcion')
            ->with('success', 'Comprobante enviado correctamente. Tu pago será validado en breve (máximo 24 horas). Recibirás una notificación cuando se active tu plan.');
    }
}
