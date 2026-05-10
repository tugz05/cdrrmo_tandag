<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AppMobileRole;
use App\Enums\ReportTypeEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Builder;
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
            get: function () {
                $fromParts = trim(
                    ($this->fname ?? '').' '.
                    ($this->mname ? $this->mname[0].'. ' : '').
                    ($this->lname ?? '').
                    ($this->suffix ? ', '.$this->suffix : '')
                );

                return $fromParts !== '' ? $fromParts : (string) ($this->name ?? '');
            },
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
     * Users who may appear in the Twilio Voice dispatch pool (heartbeat + {@code <Client>} targets).
     */
    public function scopeVoiceDispatchOperators(Builder $query): Builder
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', [
                UserTypeEnum::STAFF,
                UserTypeEnum::ADMIN,
                UserTypeEnum::SUPER_ADMIN,
            ]);
        });
    }

    /**
     * Field staff, admin, or super_admin — dispatch app / Twilio Voice operator.
     */
    public function isVoiceDispatchOperator(): bool
    {
        return $this->hasRole([
            UserTypeEnum::STAFF,
            UserTypeEnum::ADMIN,
            UserTypeEnum::SUPER_ADMIN,
        ]);
    }

    /**
     * Whether this account may use the Flutter mobile app (roles from Laratrust `roles` / `role_user`).
     */
    public function canAccessMobileApp(): bool
    {
        return $this->hasRole(UserTypeEnum::mobileAppRoleNames());
    }

    /**
     * Map Laratrust roles to mobile `app_role`: admin & super_admin → staff (rescuer); `staff` → staff; `user` → citizen.
     */
    public function inferAppMobileRoleFromRoles(): ?AppMobileRole
    {
        if ($this->hasRole([UserTypeEnum::SUPER_ADMIN, UserTypeEnum::ADMIN])) {
            return AppMobileRole::Staff;
        }
        if ($this->hasRole(UserTypeEnum::STAFF)) {
            return AppMobileRole::Staff;
        }
        if ($this->hasRole(UserTypeEnum::USER)) {
            return AppMobileRole::Citizen;
        }

        return null;
    }

    /**
     * Value returned on mobile login / profile payloads (`citizen` | `staff`).
     */
    public function mobileApiAppRole(): AppMobileRole
    {
        return $this->inferAppMobileRoleFromRoles() ?? AppMobileRole::Citizen;
    }

    /**
     * Field staff, admin, or super_admin — may access any situational incident report via the mobile API.
     */
    public function mayAccessAllSituationalIncidentReportsViaApi(): bool
    {
        return $this->isVoiceDispatchOperator();
    }

    /**
     * Persist users.app_role from Laratrust roles (roles / role_user).
     * Uses saveQuietly() so role-only updates do not fire unrelated user observers.
     */
    public function syncAppRoleFromRoles(): void
    {
        $this->unsetRelation('roles');

        $next = $this->inferAppMobileRoleFromRoles() ?? AppMobileRole::Citizen;

        if ($this->app_role !== $next) {
            $this->forceFill(['app_role' => $next]);
            $this->saveQuietly();
        }
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
