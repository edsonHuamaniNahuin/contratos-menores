<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContratoMayor extends Model
{
    use HasFactory;

    protected $table = 'contratos_mayores';

    protected $fillable = [
        'ocid',
        'entidad_nombre',
        'entidad_ruc',
        'entidad_direccion',
        'departamento_id',
        'provincia_id',
        'distrito_id',
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
        'ficha_seace_id',
        'items_seace',
        'tdr_aviso_at',
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
        'items_seace' => 'array',
        'tdr_aviso_at' => 'datetime',
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

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    /**
     * Documentos del proceso capturados desde la Ficha de Selección del SEACE
     * (Bases, TDR, etc.). Disponibles aunque el OECE aún no publique el release
     * OCDS con `url_documento`.
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(ContratoMayorDocumento::class, 'contrato_mayor_id');
    }
}
