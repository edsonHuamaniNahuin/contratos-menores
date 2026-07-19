<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContratoMayor extends Model
{
    use HasFactory;

    protected $table = 'contratos_mayores';

    protected $fillable = [
        'ocid',
        'entidad_nombre',
        'entidad_ruc',
        'entidad_direccion',
        'nomenclatura',
        'descripcion_objeto',
        'objeto_contratacion',
        'valor_referencial',
        'cuantia',
        'moneda',
        'fecha_publicacion',
        'fecha_inicio',
        'fecha_fin',
        'metodo_contratacion',
        'estado',
        'codigo_snip',
        'proveedores',
        'url_documento',
        'datos_raw',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'valor_referencial' => 'decimal:2',
        'cuantia' => 'decimal:2',
        'datos_raw' => 'array',
        'proveedores' => 'array',
    ];

    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_publicacion', 'desc');
    }

    public function scopePorEntidad($query, string $entidad)
    {
        return $query->where('entidad_nombre', 'like', "%{$entidad}%");
    }

    public function scopePorObjeto($query, string $objeto)
    {
        return $query->where('objeto_contratacion', $objeto);
    }
}
