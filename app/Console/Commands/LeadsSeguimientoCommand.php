<?php

namespace App\Console\Commands;

use App\Mail\LeadSeguimientoMail;
use App\Models\DemoLead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Nurture automático de leads: ~24-72h después de capturado, si el lead
 * NO se registró (nadie lo convirtió), le envía un recordatorio de valor.
 *
 * Protege la "última milla humana": ningún lead queda en silencio.
 */
class LeadsSeguimientoCommand extends Command
{
    protected $signature = 'leads:seguimiento
        {--dry-run : Solo lista lo que enviaría}';

    protected $description = 'Envía recordatorio a leads de 24-72h que no se han registrado.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $leads = DemoLead::whereNull('seguimiento_enviado_at')
            ->whereBetween('created_at', [now()->subHours(72), now()->subHours(24)])
            ->orderBy('id')
            ->get();

        $enviados = 0;

        foreach ($leads as $lead) {
            // Si ya se registró, no molestar (y marcar para no reintentar)
            if (User::where('email', $lead->email)->exists()) {
                $lead->update(['seguimiento_enviado_at' => now()]);
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] {$lead->email} ({$lead->nombre}) — creado {$lead->created_at->diffForHumans()}");
                continue;
            }

            try {
                Mail::to($lead->email)->send(new LeadSeguimientoMail($lead->nombre));
                $lead->update(['seguimiento_enviado_at' => now()]);
                $this->line("  enviado: {$lead->email}");
                $enviados++;
            } catch (\Throwable $e) {
                Log::warning('LeadsSeguimiento: fallo al enviar', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  fallo {$lead->email}: " . mb_substr($e->getMessage(), 0, 100));
            }
        }

        $this->info("Seguimientos enviados: {$enviados}" . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
