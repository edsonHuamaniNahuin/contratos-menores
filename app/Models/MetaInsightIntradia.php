<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Avance INTRADÍA (acumulado del día capturado cada hora) por anuncio,
 * para ver el ritmo de gasto y entrega antes del cierre diario.
 */
class MetaInsightIntradia extends Model
{
    protected $table = 'meta_insights_intradia';

    protected $fillable = [
        'fecha',
        'capturado_at',
        'ad_id',
        'ad_name',
        'adset_id',
        'adset_name',
        'spend',
        'impressions',
        'reach',
        'clicks',
        'link_clicks',
        'leads',
        'ctr',
        'cpc',
        'cpm',
        'frequency',
    ];

    protected $casts = [
        'fecha' => 'date',
        'capturado_at' => 'datetime',
        'spend' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:2',
        'cpm' => 'decimal:2',
        'frequency' => 'decimal:4',
    ];
}
