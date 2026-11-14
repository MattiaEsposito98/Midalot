<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CLOSED = 'closed';

    // Da quale pagina arriva la richiesta.
    public const KIND_EVENTO = 'evento';

    public const KIND_SERVIZI = 'servizi';

    public const KINDS = [self::KIND_EVENTO, self::KIND_SERVIZI];

    protected $fillable = [
        'user_id',
        'kind',
        'handled_by',
        'name',
        'contact',
        'event_type',
        'event_date',
        'message',
        'status',
        'admin_note',
        'handled_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'handled_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_CLOSED,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
