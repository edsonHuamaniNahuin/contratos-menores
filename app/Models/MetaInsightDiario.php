<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Métrica diaria CERRADA (un registro por anuncio y día), ingerida desde
 * la Marketing API de Meta y almacenada en el servidor propio.
 */
class MetaInsightDiario extends Model
{
    protected $table = 'meta_insights_diarios';

    protected $fillable = [
        'fecha',
        'ad_id',
        'ad_name',
        'adset_id',
        'adset_name',
        'campaign_id',
        'campaign_name',
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
        'spend' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:2',
        'cpm' => 'decimal:2',
        'frequency' => 'decimal:4',
    ];
}
