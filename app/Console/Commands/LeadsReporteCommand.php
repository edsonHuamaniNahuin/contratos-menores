<?php

namespace App\Console\Commands;

use App\Jobs\EnviarReporteConvocatoriasJob;
use App\Models\DemoLead;
use Illuminate\Console\Command;

/**
 * Envía el reporte semanal de convocatorias a los leads recientes
 * (recompensa prometida + reactivación). Uso: leads:reporte --dias=14
 */
class LeadsReporteCommand extends Command
{
    protected $signature = 'leads:reporte
        {--dias=14 : Ventana de leads (días hacia atrás)}
        {--dry-run : Solo lista, no envía}';

    protected $description = 'Envía el reporte de convocatorias a los leads recientes (lead magnet).';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $dry = (bool) $this->option('dry-run');

        $leads = DemoLead::where('created_at', '>=', now()->subDays($dias))
            ->orderByDesc('id')
            ->get()
            ->unique('email');

        $this->info('Leads en la ventana: ' . $leads->count());

        foreach ($leads as $lead) {
            if ($dry) {
                $this->line("  [dry] {$lead->email} ({$lead->nombre}) · {$lead->created_at->format('d/m H:i')}");
                continue;
            }
            EnviarReporteConvocatoriasJob::dispatchSync($lead->id);
            $this->line("  enviado: {$lead->email}");
            usleep(400000);
        }

        $this->info($dry ? 'Dry-run completado.' : 'Reportes enviados: ' . $leads->count());

        return self::SUCCESS;
    }
}
