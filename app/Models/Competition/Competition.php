<?php

namespace App\Models\Competition;

use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Competition extends Model
{
    use HasFactory;
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
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
        ];
    }

    /**
     * The relationship of the admin that created it
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
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
     * get status
     */
    public function getStatus() : string{
        switch ($this->active){
            case 1 : $st =  'active';
                break;
            case 0 : $st = 'inactive';
                break;
            case 2 : $st = 'finished';
        }
        return $st;
    }


    /**
     * return true if the competition created by the auth admin
     * @return boolean
     */
    public function canEdit() : bool{
        return $this->admin_id == Auth::id();
    }

    /**
     * check if the competition already get maximum number of levels
     * @param $competitionId
     * @return boolean
     */
    public static function competitionMaxLevelNumbers($competitionId) : bool
    {
        $competition = self::with('levels')->find($competitionId);
        if($competition->levels->count() == $competition->levels_number){
            return true;
        }
        return false;
    }

    // Scope for filtering by title
    public function scopeTitle(Builder $query, $title): Builder
    {
        if ($title) {
            return $query->where('title', 'like', '%' . $title . '%');
        }
        return $query;
    }

    // Scope for filtering by start date range
    public function scopeStartDate(Builder $query, $startDateFrom, $startDateTo): Builder
    {
        if ($startDateFrom && !$startDateTo) {
            return $query->where('start_date', '>=', $startDateFrom);
        }

        if (!$startDateFrom && $startDateTo) {
            return $query->where('start_date', '<=', $startDateTo);
        }

        if ($startDateFrom && $startDateTo) {
            return $query->whereBetween('start_date', [$startDateFrom, $startDateTo]);
        }

        return $query;
    }

    // Scope for filtering by age range
    public function scopeAgeRange(Builder $query, $ageStart, $ageEnd)
    {
        if ($ageStart && !$ageEnd) {
            return $query->where('age_start', '>=', $ageStart);
        }

        if (!$ageStart && $ageEnd) {
            return $query->where('age_end', '<=', $ageEnd);
        }

        if ($ageStart && $ageEnd) {
            return $query->where('age_start', '>=', $ageStart)
                ->where('age_end', '<=', $ageEnd);
        }

        return $query;
    }
}
