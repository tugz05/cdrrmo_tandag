<?php

namespace App\Models;

use App\Enums\ReportStatusEnum;
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

    // Mark call as started. Answering a call means someone is now handling the report.
    public function markCallStarted()
    {
        $this->call_started_at = now();
        $this->moveToInProgress();
        $this->save();

        return $this;
    }

    /**
     * Pending reports become In Progress; a report already marked Rescued keeps that status.
     * Accepts the lower-case values older code wrote, which the migration normalizes.
     */
    public function moveToInProgress(): void
    {
        if (in_array(strtolower(trim((string) $this->status)), ['', 'pending', 'in progress', 'in_progress'], true)) {
            $this->status = ReportStatusEnum::IN_PROGRESS;
        }
    }

    // Mark call as ended. Ending a call doesn't resolve the emergency, so the
    // report keeps its status: a rescue can still start and track this report.
    public function markCallEnded()
    {
        $this->update([
            'call_ended_at' => now(),
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
