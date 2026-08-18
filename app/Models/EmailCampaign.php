<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaign extends Model
{
    protected $table = 'email_campaigns';

    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_PROGRAMADA = 'programada';
    public const STATUS_ENVIANDO = 'enviando';
    public const STATUS_ENVIADA = 'enviada';
    public const STATUS_ERROR = 'error';

    public const FILTRO_TODOS = 'todos';
    public const FILTRO_PREMIUM = 'premium';
    public const FILTRO_NO_PREMIUM = 'no-premium';
    public const FILTRO_ESPECIFICO = 'especifico';
    public const FILTRO_WSP_VENTANA = 'whatsapp-ventana';

    protected $fillable = [
        'name', 'subject', 'body', 'status',
        'filtro_tipo', 'filtro_ids', 'blacklist_ids',
        'scheduled_at', 'sent_at', 'total_recipients', 'total_sent',
        'created_by',
    ];

    protected $casts = [
        'filtro_ids' => 'array',
        'blacklist_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_BORRADOR, self::STATUS_PROGRAMADA]);
    }
}
