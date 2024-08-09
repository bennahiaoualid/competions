<?php

namespace App\Models\Competition;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Level extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'competition_id',
        'admin_id',
        'start_date',
        'duration',
        'questions_number',
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
            'duration' => 'integer',
        ];
    }


    /**
     * the competition that this level belong to
     */
    public function competition() :BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * the questions that belong to this level
     */
    public function questions() : HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * get status
     */
    public function getStatus() : string{
        return $this->active == 1 ? 'active' : 'inactive';
    }

    /**
     * return true if the competition level created by the auth admin
     * @return boolean
     */
    public function canEdit() : bool{
        return $this->competition->canEdit();
    }

    /**
     * return true if the auth user is responsible for this level question
     * @return boolean
     */
    public function canEditQuestion() : bool{
        return $this->admin_id == Auth::id();
    }

    /**
     * check if the timing of the new level is conflict with the previews level in the same competition
     * @return boolean
     */
    public static function hasTimeConflict($competitionId, $newStartDate, $newDuration):bool
    {
        $newStartDate = Carbon::parse($newStartDate);
        $newDuration = intval($newDuration);
        $newEndDate = $newStartDate->copy()->addMinutes($newDuration);

        $existingLevels = self::where('competition_id', $competitionId)->get();

        foreach ($existingLevels as $level) {
            $levelStartDate = $level->start_date;
            $levelEndDate = $level->start_date->copy()->addMinutes($level->duration);
            // Check if the new level overlaps with the existing level
            if (
                ($newStartDate->between($levelStartDate, $levelEndDate)) ||
                ($newEndDate->between($levelStartDate, $levelEndDate)) ||
                ($levelStartDate->between($newStartDate, $newEndDate)) ||
                ($levelEndDate->between($newStartDate, $newEndDate))
            ) {
                return true; // Conflict found
            }
        }

        return false; // No conflict
    }


}
