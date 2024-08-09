<?php

namespace App\Models;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Scope a query to only include users of a certain age range.
     *
     * @param Builder $query
     * @param int $ageMin
     * @param int $ageMax
     * @param int $competitionId
     * @return Builder
     */
    public function scopeEligibleForCompetition(Builder $query, int $ageMin, int $ageMax, int $competitionId) : Builder
    {
        $currentDate = now()->toDateString();

        return $query->whereRaw("TIMESTAMPDIFF(YEAR, birthdate, ?) BETWEEN ? AND ?", [$currentDate, $ageMin, $ageMax])
            ->where("email_verified_at","!=",null)
            ->whereDoesntHave('competitions', function ($query) use ($competitionId) {
                $query->where('competition_id', $competitionId);
            });
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
