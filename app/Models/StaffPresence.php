<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPresence extends Model
{
    protected $fillable = [
        'user_id',
        'state',
        'last_heartbeat_at',
        'voice_client_ready_at',
    ];

    protected function casts(): array
    {
        return [
            'last_heartbeat_at' => 'datetime',
            'voice_client_ready_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
