<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceFcmToken extends Model
{
    protected $table = 'user_device_fcm_tokens';

    protected $fillable = [
        'user_id',
        'platform',
        'fcm_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
