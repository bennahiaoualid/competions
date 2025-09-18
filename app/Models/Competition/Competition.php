<?php

namespace App\Models\Competition;

use App\Models\User;
use App\Models\Admin\Admin;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Admin\AdminApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $admin_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property int $age_start
 * @property int $age_end
 * @property int $levels_number
 * @property string $status inactive,active,finished
 * @property string|null $participants_sync_status
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin $admin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Admin> $auditors
 * @property-read int|null $auditors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competition\Level> $levels
 * @property-read int|null $levels_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static Builder<static>|Competition ageRange($ageStart, $ageEnd)
 * @method static \Database\Factories\Competition\CompetitionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Competition newModelQuery()
 * @method static Builder<static>|Competition newQuery()
 * @method static Builder<static>|Competition query()
 * @method static Builder<static>|Competition startDate($startDateFrom, $startDateTo)
 * @method static Builder<static>|Competition status($state)
 * @method static Builder<static>|Competition title($title)
 * @method static Builder<static>|Competition whereAdminId($value)
 * @method static Builder<static>|Competition whereAgeEnd($value)
 * @method static Builder<static>|Competition whereAgeStart($value)
 * @method static Builder<static>|Competition whereCreatedAt($value)
 * @method static Builder<static>|Competition whereDescription($value)
 * @method static Builder<static>|Competition whereId($value)
 * @method static Builder<static>|Competition whereLevelsNumber($value)
 * @method static Builder<static>|Competition whereStartDate($value)
 * @method static Builder<static>|Competition whereStatus($value)
 * @method static Builder<static>|Competition whereTitle($value)
 * @method static Builder<static>|Competition whereUpdatedAt($value)
 * @property bool $is_suspended
 * @method static Builder<static>|Competition whereIsSuspended($value)
 * @method static Builder<static>|Competition whereLastSyncedAt($value)
 * @method static Builder<static>|Competition whereParticipantsSyncStatus($value)
 * @property int $winner_gifts Number of coins the winner gets
 * @property bool $multi_winner If 2nd and 3rd place also get rewards
 * @property bool $ai_auditing If auditing in this competition will be admins or AI
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AdminApproval> $adminApprovals
 * @property-read int|null $admin_approvals_count
 * @method static Builder<static>|Competition whereAiAuditing($value)
 * @method static Builder<static>|Competition whereMultiWinner($value)
 * @method static Builder<static>|Competition whereWinnerGifts($value)
 * @property int $auditing_time_for_level Minutes after level end before auto-assign/confirm AI audit
 * @method static Builder<static>|Competition whereAuditingTimeForLevel($value)
 * @mixin \Eloquent
 */
class Competition extends Model
{
    use HasFactory, HasSlug;

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'finished';

    protected static function booted(): void
    {
        static::addGlobalScope('not_suspended', function (Builder $builder) {
            $builder->where('is_suspended', false);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'admin_id',
        'start_date',
        'age_start',
        'age_end',
        'levels_number',
        'status',
        'participants_sync_status',
        'last_synced_at',
        'is_suspended',
        'winner_gifts',
        'multi_winner',
        'ai_auditing',
        'auditing_time_for_level',
        'slug'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_suspended' => 'boolean',
            'start_date' => 'datetime',
            'age_start' => 'integer',
            'age_end' => 'integer',
            'levels_number' => 'integer',
            'last_synced_at' => 'datetime',
            'winner_gifts' => 'integer',
            'multi_winner' => 'boolean',
            'ai_auditing' => 'boolean',
            'auditing_time_for_level' => 'integer',
        ];
    }

    /**
     * The relationship of the admin that created it
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class)->withTrashed();
    }

    /**
     * The admins that has permissions to be auditor in this competition.
     */
    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class);
    }

    /**
     * The users that belong to the competition.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The levels that belong to the competition.
     */
    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    /**
     * Get all admin approvals for this competition
     */
    public function adminApprovals(): MorphMany
    {
        return $this->morphMany(AdminApproval::class, 'entity');
    }

    /**
     * return true if the competition created by the auth admin
     * @return boolean
     */
    public function canEdit() : bool{
        return $this->admin_id == Auth::id();
    }

    /**
     * Check if the competition has reached its maximum number of levels.
     * @return bool
     */
    public function hasReachedMaxLevels(): bool
    {
        return $this->levels()->count() >= $this->levels_number;
    }

    /**
     * check if all competition levels start_time are greater then now before competition activation
     * we need to make now as new start date for the level
     * so we make sure all the other levels are after now to avoid time conflict
     * @param int|null $exclude_id the level being activated
     * @return bool
     */
    function isAllLevelAfterNow(?int $exclude_id = null): bool
    {
        foreach ($this->levels as $level){
            if (($exclude_id == null || $level->id != $exclude_id) && $level->status == 0){
                if ($level->start_date->lessThanOrEqualTo(now())){
                    return false;
                }
            }
        }
        return true;
    }

    // Scope for filtering by title
    public function scopeTitle(Builder $query, $title): Builder
    {
        if ($title != null) {
            return $query->where('title', 'like', '%' . $title . '%');
        }
        return $query;
    }

    // Scope for filtering by start date range
    public function scopeStartDate(Builder $query, $startDateFrom, $startDateTo): Builder
    {
        if ($startDateFrom != null && !$startDateTo) {
            return $query->where('start_date', '>=', $startDateFrom);
        }

        if (!$startDateFrom && $startDateTo != null) {
            return $query->where('start_date', '<=', $startDateTo);
        }

        if ($startDateFrom != null && $startDateTo != null) {
            return $query->whereBetween('start_date', [$startDateFrom, $startDateTo]);
        }

        return $query;
    }

    // Scope for filtering by age range
    public function scopeAgeRange(Builder $query, $ageStart, $ageEnd)
    {
        if ($ageStart != null  && !$ageEnd) {
            return $query->where('age_start', '>=', $ageStart);
        }

        if (!$ageStart && $ageEnd != null) {
            return $query->where('age_end', '<=', $ageEnd);
        }

        if ($ageStart != null && $ageEnd != null) {
            return $query->where('age_start', '>=', $ageStart)
                ->where('age_end', '<=', $ageEnd);
        }

        return $query;
    }

    // Scope for filtering by state
    public function scopeStatus(Builder $query, $state): Builder
    {
        if ($state != null) {
            return $query->where('status', '=',  $state );
        }
        return $query;
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
