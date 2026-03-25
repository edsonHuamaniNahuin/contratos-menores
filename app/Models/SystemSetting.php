<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'system_settings_all';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Obtener un valor de configuración (BD → .env como fallback).
     */
    public static function getValue(string $key, mixed $default = null): ?string
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Guardar un valor de configuración en BD.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Guardar múltiples valores a la vez.
     */
    public static function setMany(array $data): void
    {
        $rows = [];
        foreach ($data as $key => $value) {
            $rows[] = [
                'key' => $key,
                'value' => (string) $value,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        static::upsert($rows, ['key'], ['value', 'updated_at']);

        Cache::forget(self::CACHE_KEY);
    }
}
