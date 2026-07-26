<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoYape extends Model
{
    protected $table = 'pago_yapes';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    public const TIPO_NUEVO = 'nuevo';
    public const TIPO_RENOVACION = 'renovacion';

    protected $fillable = [
        'user_id', 'plan', 'tipo', 'monto', 'comprobante', 'comprobante_dir',
        'nombre_original', 'estado',
        'referencia_adicional', 'telefono', 'admin_notes',
        'processed_by', 'processed_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
}
