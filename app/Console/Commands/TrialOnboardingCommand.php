<?php

namespace App\Console\Commands;

use App\Mail\OnboardingTrialMail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Secuencia de onboarding de la prueba gratuita:
 * d1 → d3 → d7 → d12 → d15 (vencimiento) y win-back ("vencido").
 *
 * Envía como máximo UN paso por usuario por corrida (el más avanzado cuyo
 * umbral ya se cumplió) y registra cada envío en `user_email_log` para
 * no repetir. Los usuarios que ya pagaron se omiten.
 */
class TrialOnboardingCommand extends Command
{
    protected $signature = 'trial:onboarding
        {--dry-run : Solo lista lo que enviaría, sin enviar}
        {--probar= : Envía los 6 pasos a este correo para revisión de diseño}
        {--vencidos : Envía además el win-back a pruebas vencidas que nunca pagaron}';

    protected $description = 'Onboarding de la prueba gratuita (d1/d3/d7/d12/d15) + win-back de vencidos.';

    private const PASOS = [
        ['dias' => 1, 'clave' => 'd1'],
        ['dias' => 3, 'clave' => 'd3'],
        ['dias' => 7, 'clave' => 'd7'],
        ['dias' => 12, 'clave' => 'd12'],
    ];

    public function handle(): int
    {
        $probar = (string) ($this->option('probar') ?? '');
        if ($probar !== '') {
            return $this->enviarPrueba($probar);
        }

        $enviados = 0;

        if ($this->option('vencidos')) {
            $enviados += $this->recuperarVencidos();
        }

        $enviados += $this->onboardingActivos();

        $this->info("Correos enviados: {$enviados}");

        return self::SUCCESS;
    }

    private function onboardingActivos(): int
    {
        $enviados = 0;
        $dryRun = (bool) $this->option('dry-run');

        $trials = Subscription::with('user')
            ->where('plan', Subscription::PLAN_TRIAL)
            ->where('ends_at', '>=', now()->startOfDay())
            ->get();

        foreach ($trials as $sub) {
            $user = $sub->user;
            if (! $user || $this->yaEsPago($user)) {
                continue;
            }

            $paso = $sub->ends_at->isToday() ? 'd15' : null;

            if ($paso === null) {
                $dias = (int) $sub->created_at->diffInDays(now());
                foreach (self::PASOS as $p) {
                    if ($dias >= $p['dias']) {
                        $paso = $p['clave'];
                    }
                }
            }

            if ($paso === null || $this->yaEnviado($user->id, $paso)) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] {$user->email} → {$paso}");
                continue;
            }

            if ($this->enviar($user, $paso)) {
                $this->marcar($user->id, $paso);
                $this->line("  enviado: {$user->email} → {$paso}");
                $enviados++;
            }
        }

        return $enviados;
    }

    private function recuperarVencidos(): int
    {
        $enviados = 0;
        $dryRun = (bool) $this->option('dry-run');

        $vencidos = Subscription::with('user')
            ->where('plan', Subscription::PLAN_TRIAL)
            ->where('ends_at', '<', now())
            ->orderByDesc('ends_at')
            ->get()
            ->unique('user_id');

        foreach ($vencidos as $sub) {
            $user = $sub->user;
            if (! $user || $this->yaEsPago($user) || $this->yaEnviado($user->id, 'vencido')) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] {$user->email} → vencido (terminó {$sub->ends_at->format('d/m/Y')})");
                continue;
            }

            usleep(2500000);

            if ($this->enviar($user, 'vencido')) {
                $this->marcar($user->id, 'vencido');
                $this->line("  enviado: {$user->email} → vencido");
                $enviados++;
            }
        }

        return $enviados;
    }

    private function enviarPrueba(string $email): int
    {
        $user = User::where('email', $email)->first() ?? new User(['name' => 'Equipo', 'email' => $email]);

        $pasos = ['d1', 'd3', 'd7', 'd12', 'd15', 'vencido'];
        foreach ($pasos as $i => $paso) {
            if ($i > 0) {
                usleep(2500000);
            }
            if ($this->enviar($user, $paso)) {
                $this->line("  prueba enviada: {$paso} → {$email}");
            }
        }

        return count($pasos);
    }

    private function enviar(User $user, string $paso, int $intento = 1): bool
    {
        try {
            Mail::to($user->email)->send(new OnboardingTrialMail($user, $paso));
            return true;
        } catch (\Throwable $e) {
            $mensaje = $e->getMessage();
            $temporal = str_contains($mensaje, '421') || str_contains($mensaje, '429') || str_contains($mensaje, 'Too many');

            if ($temporal && $intento === 1) {
                sleep(30);
                return $this->enviar($user, $paso, 2);
            }

            Log::warning('TrialOnboarding: fallo al enviar', [
                'user_id' => $user->id,
                'paso' => $paso,
                'error' => $mensaje,
            ]);
            $this->error("  fallo {$paso} → {$user->email}: " . mb_substr($mensaje, 0, 120));
            return false;
        }
    }

    private function yaEsPago(User $user): bool
    {
        return Subscription::where('user_id', $user->id)
            ->where('plan', '!=', Subscription::PLAN_TRIAL)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->exists();
    }

    private function yaEnviado(int $userId, string $clave): bool
    {
        return DB::table('user_email_log')
            ->where('user_id', $userId)
            ->where('clave', $clave)
            ->exists();
    }

    private function marcar(int $userId, string $clave): void
    {
        DB::table('user_email_log')->insertOrIgnore([
            'user_id' => $userId,
            'clave' => $clave,
            'sent_at' => now(),
        ]);
    }
}
