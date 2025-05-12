<?php

namespace App\Models;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Response;
use App\Models\GuestUsers\GlobalResponse;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable , LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'birthdate',
        'gender',
        "admin_id",
        'guest'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            $user->anonymized_identifier = Str::uuid();
        });

        static::updating(function ($user) {
            if (!$user->anonymized_identifier) {
                $user->anonymized_identifier = Str::uuid();
            }
        });


    }

    protected static function booted(): void
    {
        // Apply a global scope to exclude guest users by default
        static::addGlobalScope('nonGuest', function (Builder $builder) {
            $builder->where('guest', false);
        });
    }

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
            'guest' => 'boolean'
        ];
    }

    /**
     * Accessor to get the user's age.
     *
     * @return int
     */
    public function getAgeAttribute()
    {
        return Carbon::parse($this->attributes['birthdate'])->age;
    }


    /**
     * The roles that belong to the user.
     */
    public function Competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class);
    }

    /**
     * The admin that created this user.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function levelAdminUser(): BelongsToMany
    {
        return $this->belongsToMany(Level::class, 'level_admin_user', 'user_id', 'level_id')
            ->withPivot('admin_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function globalResponses(): HasMany
    {
        return $this->hasMany(GlobalResponse::class, 'user_id');
    }


    /**
     * Scope a query to only include users of a certain age range.
     *
     * @param Builder $query
     * @param int $ageMin
     * @param int $ageMax
     * @param int|null $competitionId
     * @return Builder
     */
    public function scopeEligibleForCompetition(Builder $query, int $ageMin, int $ageMax, int $competitionId = null) : Builder
    {
        $currentDate = now()->toDateString();

        $query =  $query->whereRaw("TIMESTAMPDIFF(YEAR, birthdate, ?) BETWEEN ? AND ?", [$currentDate, $ageMin, $ageMax])
            ->where("email_verified_at","!=",null);
        if ($competitionId) {
            $query->whereDoesntHave('competitions', function ($query) use ($competitionId) {
                $query->where('competition_id', $competitionId);
            });
        }
        return $query;
    }

    /**
     * override methode for storing log activity
     */
    protected array  $logAttributes = [
        'name',
        'email',
        'birthdate',
        'gender'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->logAttributes)
            ->useLogName('user')
            ->logOnlyDirty();
    }
}
