<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'sent_for_processing',
        'sent_at',
        'response_received',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_for_processing' => 'boolean',
            'response_received' => 'boolean',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
