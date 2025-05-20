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

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $guest
 * @property int|null $admin_id
 * @property string|null $birthdate
 * @property string|null $gender
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $anonymized_identifier
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Competition> $Competitions
 * @property-read int|null $competitions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Admin|null $admin
 * @property-read int $age
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GlobalResponse> $globalResponses
 * @property-read int|null $global_responses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Level> $levelAdminUser
 * @property-read int|null $level_admin_user_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Response> $responses
 * @property-read int|null $responses_count
 * @method static Builder<static>|User eligibleForCompetition(int $ageMin, int $ageMax, ?int $competitionId = null)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onlyTrashed()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereAdminId($value)
 * @method static Builder<static>|User whereAnonymizedIdentifier($value)
 * @method static Builder<static>|User whereBirthdate($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereGender($value)
 * @method static Builder<static>|User whereGuest($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User withTrashed()
 * @method static Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
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
    public function scopeEligibleForCompetition(Builder $query, int $ageMin, int $ageMax, ?int $competitionId = null) : Builder
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
