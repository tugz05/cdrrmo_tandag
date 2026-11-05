<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLocationPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'user_id',
        'lat',
        'lng',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
