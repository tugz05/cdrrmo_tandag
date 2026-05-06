<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AppMobileRole;
use App\Enums\ReportTypeEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRolesAndPermissions, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'app_role',
        'google_id',
        'password',

        'fname',
        'phone',
        'mname',
        'lname',
        'suffix',
        'address',
        'confirmed_at',
        'image',
        'latitude',
        'longitude',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(
                $this->fname.' '.
                ($this->mname ? $this->mname[0].'. ' : '').
                $this->lname.
                ($this->suffix ? ', '.$this->suffix : '')
            ),
        );
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_role' => AppMobileRole::class,
        ];
    }

    public function user_valid_ids()
    {
        return $this->hasMany(UserValidId::class);
    }

    public function latest_call()
    {
        return $this->hasMany(Report::class)->latest()->take(1);
    }

    public function report_messages()
    {
        return $this->hasMany(Report::class)->whereType(ReportTypeEnum::MESSAGE);
    }

    public function report_calls()
    {
        return $this->hasMany(Report::class)->whereType(ReportTypeEnum::CALL);
    }

    // public function news()
    // {
    //     return $this->hasMany(News::class);
    // }

    public function situational_incident_reports()
    {
        return $this->hasMany(SituationalIncidentReport::class);
    }

    /**
     * Whether this account may use the Flutter mobile app (citizen or staff/rescuer).
     */
    public function canAccessMobileApp(): bool
    {
        return $this->hasRole(UserTypeEnum::mobileAppRoleNames());
    }

    /**
     * Keep users.app_role aligned with Laratrust roles (staff wins over citizen).
     */
    public function syncAppRoleFromRoles(): void
    {
        $next = $this->inferAppMobileRoleFromRoles();
        if ($next === null) {
            return;
        }
        if ($this->app_role !== $next) {
            $this->forceFill(['app_role' => $next])->save();
        }
    }

    public function inferAppMobileRoleFromRoles(): ?AppMobileRole
    {
        if ($this->hasRole(UserTypeEnum::STAFF)) {
            return AppMobileRole::Staff;
        }
        if ($this->hasRole(UserTypeEnum::USER)) {
            return AppMobileRole::Citizen;
        }

        return null;
    }

    public function tips()
    {
        return $this->hasMany(Tip::class);
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    public function user_logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function codes()
    {
        return $this->hasMany(Code::class);
    }

    public function user_uploads()
    {
        return $this->hasMany(UserUpload::class);
    }
}
