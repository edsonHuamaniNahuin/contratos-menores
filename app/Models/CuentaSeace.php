<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CuentaSeace extends Model
{
    use HasFactory;

    protected $table = 'cuentas_seace';

    protected $fillable = [
        'nombre',
        'username',
        'password',
        'email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'activa',
        'last_login_at',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'access_token',
        'refresh_token',
    ];

    /**
     * Encriptar password antes de guardar
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    /**
     * Desencriptar password al leer
     */
    public function getPasswordDescifrado()
    {
        try {
            return Crypt::decryptString($this->password);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Alias para compatibilidad (Accessor)
     */
    public function getPasswordDecryptedAttribute()
    {
        return $this->getPasswordDescifrado();
    }

    /**
     * Scope para cuentas activas
     */
    public function scopeActiva($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Alias del scope (plural)
     */
    public function scopeActivas($query)
    {
        return $this->scopeActiva($query);
    }

    /**
     * Scope para cuenta principal (la primera activa)
     */
    public function scopePrincipal($query)
    {
        return $query->where('activa', true)->orderBy('last_login_at', 'desc');
    }

    /**
     * Verificar si el token está expirado
     */
    public function tokenExpirado(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return Carbon::now()->greaterThan($this->token_expires_at);
    }

    /**
     * Actualizar tokens según respuesta de la API SEACE
     */
    public function actualizarTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = 300): void
    {
        $this->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken ?? $this->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
        ]);
    }

    /**
     * Registrar login exitoso
     */
    public function registrarLogin(bool $exitoso = true): void
    {
        if ($exitoso) {
            $this->update(['last_login_at' => Carbon::now()]);
        }
    }

    /**
     * Registrar consulta (para estadísticas)
     */
    public function registrarConsulta(): void
    {
        // Método simplificado - solo para compatibilidad
    }

    /**
     * Accessor para saber si tiene token válido
     */
    public function getTokenValidoAttribute(): bool
    {
        return !empty($this->access_token) && !$this->tokenExpirado();
    }

    /**
     * Alias para compatibilidad
     */
    public function getTieneTokenValidoAttribute(): bool
    {
        return $this->getTokenValidoAttribute();
    }

    /**
     * Accessor para días desde último login
     */
    public function getDiasDesdeUltimoLoginAttribute(): ?int
    {
        if (!$this->last_login_at) {
            return null;
        }

        return Carbon::now()->diffInDays($this->last_login_at);
    }

    /**
     * Accessor para estado de la cuenta
     */
    public function getEstadoAttribute(): string
    {
        if (!$this->activa) {
            return 'Inactiva';
        }

        if ($this->token_valido) {
            return 'Conectada';
        }

        if ($this->last_login_at && $this->dias_desde_ultimo_login <= 1) {
            return 'Token Expirado';
        }

        return 'Sin Conectar';
    }

    /**
     * Accessor para el color del estado (para badges)
     */
    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'Conectada' => 'success',
            'Token Expirado' => 'warning',
            'Sin Conectar' => 'secondary',
            'Inactiva' => 'danger',
            default => 'secondary',
        };
    }
}
