<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ContratoArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'contrato_id',
        'id_contrato_seace',
        'id_archivo_seace',
        'codigo_proceso',
        'entidad',
        'nombre_original',
        'nombre_sistema',
        'extension',
        'mime_type',
        'tamano_bytes',
        'sha256',
        'storage_disk',
        'storage_path',
        'descargado_en',
        'verificado_en',
        'datos_contrato',
        'metadata',
    ];

    protected $casts = [
        'id_contrato_seace' => 'integer',
        'id_archivo_seace' => 'integer',
        'descargado_en' => 'datetime',
        'verificado_en' => 'datetime',
        'datos_contrato' => 'array',
        'metadata' => 'array',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function analisis(): HasMany
    {
        return $this->hasMany(TdrAnalisis::class, 'contrato_archivo_id');
    }

    public function ultimoAnalisisExitoso(): HasOne
    {
        return $this->hasOne(TdrAnalisis::class, 'contrato_archivo_id')
            ->where('estado', TdrAnalisis::ESTADO_EXITOSO)
            ->latestOfMany('analizado_en');
    }

    public function hasStoredFile(): bool
    {
        if (!$this->storage_path) {
            return false;
        }

        return Storage::disk($this->storage_disk ?? config('filesystems.default'))
            ->exists($this->storage_path);
    }

    public function getAbsolutePathAttribute(): ?string
    {
        if (!$this->hasStoredFile()) {
            return null;
        }

        return Storage::disk($this->storage_disk ?? config('filesystems.default'))
            ->path($this->storage_path);
    }
}
