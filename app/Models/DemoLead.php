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
        'landing',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
