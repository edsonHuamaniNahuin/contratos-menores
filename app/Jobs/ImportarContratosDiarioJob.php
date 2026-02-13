<?php

namespace App\Jobs;

use App\Models\Contrato;
use App\Services\SeaceBuscadorPublicoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job programado que importa TODOS los contratos del día anterior
 * desde el buscador público SEACE para todos los departamentos.
 *
 * Se ejecuta diariamente a las 2:00 AM (hora Lima).
 * Fecha objetivo: día anterior (ayer).
 *
 * Replica la lógica de ContratosDashboard::importarPorDepartamento()
 * seleccionando TODOS los departamentos y la fecha de ayer.
 */
class ImportarContratosDiarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Reintentar hasta 2 veces ante fallos.
     */
    public int $tries = 2;

    /**
     * Timeout generoso: hasta 10 minutos (son 25 departamentos).
     */
    public int $timeout = 600;

    /**
     * Ejecutar el job.
     */
    public function handle(): void
    {
        $fechaObjetivo = Carbon::now('America/Lima')->subDay()->startOfDay();

        Log::info('ImportarContratosDiario: iniciando', [
            'fecha_objetivo' => $fechaObjetivo->toDateString(),
        ]);

        $buscador = app(SeaceBuscadorPublicoService::class);

        // Obtener lista de departamentos
        $depResponse = $buscador->obtenerDepartamentos();

        if (empty($depResponse['success']) || empty($depResponse['data'])) {
            Log::error('ImportarContratosDiario: no se pudieron obtener departamentos', [
                'response' => $depResponse,
            ]);
            return;
        }

        $departamentos = $depResponse['data'];

        $totalRecibidos = 0;
        $totalFiltrados = 0;
        $nuevos = 0;
        $actualizados = 0;
        $errores = 0;

        foreach ($departamentos as $dep) {
            try {
                $resultado = $this->importarDepartamento($buscador, $dep, $fechaObjetivo);

                $totalRecibidos += $resultado['recibidos'];
                $totalFiltrados += $resultado['filtrados'];
                $nuevos += $resultado['nuevos'];
                $actualizados += $resultado['actualizados'];
            } catch (Exception $e) {
                $errores++;
                Log::warning('ImportarContratosDiario: error en departamento', [
                    'departamento' => $dep['nom'] ?? $dep['id'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Pausa entre departamentos para no saturar el API SEACE
            usleep(500_000); // 0.5 segundos
        }

        Log::info('ImportarContratosDiario: completado', [
            'fecha_objetivo' => $fechaObjetivo->toDateString(),
            'departamentos_procesados' => count($departamentos) - $errores,
            'departamentos_con_error' => $errores,
            'total_recibidos' => $totalRecibidos,
            'filtrados_fecha' => $totalFiltrados,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * Importar contratos de un departamento específico para la fecha objetivo.
     */
    protected function importarDepartamento(
        SeaceBuscadorPublicoService $buscador,
        array $dep,
        Carbon $fechaObjetivo
    ): array {
        $params = [
            'anio' => (int) $fechaObjetivo->year,
            'codigo_departamento' => $dep['id'],
            'page' => 1,
            'page_size' => 150,
            'orden' => 2,
        ];

        $respuesta = $buscador->buscarContratos($params);

        if (empty($respuesta['success'])) {
            Log::warning('ImportarContratosDiario: búsqueda fallida', [
                'dep' => $dep['id'],
                'error' => $respuesta['error'] ?? 'sin detalle',
            ]);
            return ['recibidos' => 0, 'filtrados' => 0, 'nuevos' => 0, 'actualizados' => 0];
        }

        $datos = collect($respuesta['data'] ?? []);
        $recibidos = $datos->count();

        // Filtrar solo contratos publicados en la fecha objetivo
        $datosFiltrados = $datos->filter(function ($item) use ($fechaObjetivo) {
            $fec = $item['fecPublica'] ?? null;
            if (!$fec) {
                return false;
            }

            try {
                $parsed = Carbon::createFromFormat('d/m/Y H:i:s', $fec);
                return $parsed->isSameDay($fechaObjetivo);
            } catch (Exception $e) {
                return false;
            }
        });

        $filtrados = $datosFiltrados->count();
        $nuevos = 0;
        $actualizados = 0;

        foreach ($datosFiltrados as $data) {
            $fechaPublicacion = $this->parseSeaceDate($data['fecPublica'] ?? null);
            $inicioCotizacion = $this->parseSeaceDate($data['fecIniCotizacion'] ?? null);
            $finCotizacion = $this->parseSeaceDate($data['fecFinCotizacion'] ?? null);

            $contrato = Contrato::updateOrCreate(
                ['id_contrato_seace' => $data['idContrato']],
                [
                    'nro_contratacion' => $data['nroContratacion'] ?? 0,
                    'codigo_proceso' => $data['desContratacion'] ?? 'N/A',
                    'entidad' => $data['nomEntidad'] ?? 'N/A',
                    'codigo_departamento' => $dep['id'],
                    'nombre_departamento' => $dep['nom'] ?? null,
                    'codigo_provincia' => $data['codigoProvincia'] ?? null,
                    'nombre_provincia' => $data['nomProvincia'] ?? null,
                    'id_objeto_contrato' => $data['idObjetoContrato'] ?? 0,
                    'objeto' => $data['nomObjetoContrato'] ?? 'N/A',
                    'descripcion' => $data['desObjetoContrato'] ?? 'Sin descripción',
                    'id_estado_contrato' => $data['idEstadoContrato'] ?? 0,
                    'estado' => $data['nomEstadoContrato'] ?? 'Desconocido',
                    'fecha_publicacion' => $fechaPublicacion,
                    'inicio_cotizacion' => $inicioCotizacion,
                    'fin_cotizacion' => $finCotizacion,
                    'etapa_contratacion' => $data['nomEtapaContratacion'] ?? null,
                    'id_tipo_cotizacion' => $data['idTipoCotizacion'] ?? null,
                    'num_subsanaciones_total' => $data['numSubsanacionesTotal'] ?? 0,
                    'num_subsanaciones_pendientes' => $data['numSubsanacionesPendientes'] ?? 0,
                    'datos_raw' => $data,
                ]
            );

            $contrato->wasRecentlyCreated ? $nuevos++ : $actualizados++;
        }

        return [
            'recibidos' => $recibidos,
            'filtrados' => $filtrados,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
        ];
    }

    /**
     * Parsear fecha del SEACE (formato dd/mm/yyyy HH:ii:ss).
     */
    protected function parseSeaceDate(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
        } catch (Exception $e) {
            return null;
        }
    }
}
