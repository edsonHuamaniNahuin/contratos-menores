<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoLead extends Model
{
    protected $table = 'demo_leads';

    protected $fillable = [
        'nombre',
        'email',
        'empresa',
        'telefono',
        'rubro',
        'origen',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'fbclid',
        'landing',
        'ip',
        'user_agent',
        'seguimiento_enviado_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
