<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_copy',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationKeyword::class,
            'subscriber_profile_keyword'
        )->withTimestamps();
    }
}
