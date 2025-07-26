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

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $competition_id
 * @property int $admin_id
 * @property int $questions_number
 * @property \Illuminate\Support\Carbon $start_date
 * @property int $duration
 * @property string $status inactive,active,finished
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin $admin
 * @property-read \App\Models\Competition\Competition $competition
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competition\Question> $questions
 * @property-read int|null $questions_count
 * @method static \Database\Factories\Competition\LevelFactory factory($count = null, $state = [])
 * @method static Builder<static>|Level newModelQuery()
 * @method static Builder<static>|Level newQuery()
 * @method static Builder<static>|Level query()
 * @method static Builder<static>|Level whereAdminId($value)
 * @method static Builder<static>|Level whereCompetitionId($value)
 * @method static Builder<static>|Level whereCreatedAt($value)
 * @method static Builder<static>|Level whereDescription($value)
 * @method static Builder<static>|Level whereDuration($value)
 * @method static Builder<static>|Level whereId($value)
 * @method static Builder<static>|Level whereName($value)
 * @method static Builder<static>|Level whereQuestionsNumber($value)
 * @method static Builder<static>|Level whereStartDate($value)
 * @method static Builder<static>|Level whereStatus($value)
 * @method static Builder<static>|Level whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Level extends Model
{
    use HasFactory;
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_FINISHED = 'finished';

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
            'questions_number' => 'integer',
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
     * return true if the level is active and still not pass the duration
     */
    public function isStillActive() : bool{
        $endTime = $this->start_date->copy()->addMinutes($this->duration);
        return  ($this->status == self::STATUS_ACTIVE && $endTime->greaterThan(now()));
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
     * methode check if  the previous finished level already being audited .
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
