<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ReportTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRolesAndPermissions, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
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
                $this->fname . ' ' . 
                ($this->mname ? $this->mname[0] . '. ' : '') . 
                $this->lname . 
                ($this->suffix ? ', ' . $this->suffix : '')
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
