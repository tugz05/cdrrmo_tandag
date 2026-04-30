<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserUpload extends Model
{
    use HasFactory;

    public $fillable = [
        'user_id',
        'id_image',
        'face_image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
