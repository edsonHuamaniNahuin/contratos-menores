<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'es_publico',
    ];

    protected $casts = [
        'es_publico' => 'boolean',
    ];

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(
            TelegramSubscription::class,
            'notification_keyword_subscription'
        )->withTimestamps();
    }
}
