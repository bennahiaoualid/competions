<?php

namespace App\Models\Competition;

use App\Models\Admin\Admin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        'status',
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
     * the admin that responsible for selecting level questions
     */
    public function admin() :BelongsTo
    {
        return $this->belongsTo(Admin::class);
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
        switch ($this->status){
            case 1 : $st =  'active';
                break;
            case 0 : $st = 'inactive';
                break;
            case 2 : $st = 'finished';
        }
        return $st;
    }

    /**
     * return true if the level is active and still not pass the duration
     */
    public function isStillActive() : bool{
        $endTime = $this->start_date->copy()->addMinutes($this->duration);
        return  ($this->status == 1 && $endTime->greaterThan(now()));
    }

    /**
     * return true if the competition level created by the auth admin
     * @return boolean
     */
    public function canEdit() : bool{
        return $this->competition->canEdit();
    }

    /**
     * return true if the auth user is a part of this level competition competitors
     * @return boolean
     */
    public function userCanParticipate() : bool{
        return $this->competition->users->contains(Auth::id());
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
    public static function hasTimeConflict($competitionId, $newStartDate, $newDuration , $exclude_id = null):bool
    {
        $newStartDate = Carbon::parse($newStartDate);
        $newDuration = intval($newDuration);
        $newEndDate = $newStartDate->copy()->addMinutes($newDuration);

        $existingLevels = self::where('competition_id', $competitionId)->get();

        foreach ($existingLevels as $level) {
            if($exclude_id != null && $level->id == $exclude_id) {
                continue;
            }
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

    /**
     * methode check if all earliest level are already finished .
     * @return bool
     */
    public function isTheEarliest(): bool
    {
        $level_first = Level::query()
            ->where('competition_id','=',$this->competition_id)
            ->where('status','!=','2')
            ->orderBy("start_date")->first();
        return $level_first->id === $this->id || $level_first === null;
    }

    /**
     * methode check if if the previous finished level already being audition .
     * @return bool
     */
    public function isThePreviousAudit(): bool
    {
        $level_first = Level::query()
            ->where('competition_id','=',$this->competition_id)
            ->where('status','=','2')
            ->orderBy("start_date", 'desc')->first();
        $responses = 0 ;
        if ($level_first){
            $responses = Response::whereHas('question', function ($query) use ($level_first) {
                // Filter questions by the specific level ID
                $query->where('level_id', $level_first->id);
            })
                ->whereNull('admin_id') // Filter responses where admin_id is null
                ->count();
        }

        return $responses == 0;
    }

}
