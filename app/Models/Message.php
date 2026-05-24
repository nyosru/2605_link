<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'channel',
        'payload_type',
        'payload',
        'validation',
        'received_at',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'validation' => 'array',
            'received_at' => 'datetime',
        ];
    }

    public function status(): HasOne
    {
        return $this->hasOne(MessageStatus::class);
    }

    public function statuses(): HasOne
    {
        return $this->status();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function latestAnswer(): HasOne
    {
        return $this->hasOne(Answer::class)->latest();
    }
}
