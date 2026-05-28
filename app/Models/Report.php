<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'details',
        'call_started_at',
        'address',
        'call_ended_at',
        'status',
        'type',
        'reported_by',
        'accuracy',
        'reporters_address',
        'is_manually_added',
        'rescue_status',
        'rescuer_user_id',
        'rescue_started_at',
        'rescue_arrived_at',
    ];

    protected $casts = [
        'call_started_at' => 'datetime',
        'call_ended_at' => 'datetime',
        'rescue_started_at' => 'datetime',
        'rescue_arrived_at' => 'datetime',
    ];

    // Mark call as started
    public function markCallStarted()
    {
        $this->update([
            'call_started_at' => now(),
            'status' => 'in_progress' // Assuming you have this status
        ]);
        return $this;
    }

    // Mark call as ended
    public function markCallEnded()
    {
        $this->update([
            'call_ended_at' => now(),
            'status' => 'completed' // or 'canceled' depending on your logic
        ]);
        return $this;
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rescuer()
    {
        return $this->belongsTo(User::class, 'rescuer_user_id');
    }

    public function report_images()
    {
        return $this->hasMany(ReportImage::class);
    }
}
