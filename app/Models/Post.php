<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bg_image',
        'title',
        'type',
        'is_published',
        'content',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
