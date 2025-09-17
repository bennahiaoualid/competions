<?php

namespace App\Models\Competition;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $response_text
 * @property int $question_id
 * @property int $user_id
 * @property int|null $admin_id
 * @property float $score
 * @property int $response_duration
 * @property int $keystrokes
 * @property float $penalty
 * @property array $flags
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Competition\Question $question
 * @method static \Database\Factories\Competition\ResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereResponseDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereResponseText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereUserId($value)
 * @property string|null $sanpshot_admin
 * @property float|null $final_score
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereFinalScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereKeystrokes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response wherePenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereSanpshotAdmin($value)
 * @property bool $ai_generated
 * @property \Illuminate\Support\Carbon|null $ai_score_generated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereAiGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Response whereAiScoreGeneratedAt($value)
 * @property-read mixed $status
 * @mixin \Eloquent
 */
class Response extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'response_text',
        'question_id',
        'user_id',
        'admin_id',
        'score',
        'response_duration',
        'keystrokes',
        'penalty',
        'flags',
        'ai_generated',
        'ai_score_generated_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'final_score' => 'float',
            'response_duration' => 'integer',
            'keystrokes' => 'integer',
            'penalty' => 'float',
            'flags' => 'array',
            'ai_generated' => 'boolean',
            'ai_score_generated_at' => 'datetime',
        ];
    }

    /**
     * the question that this response belong to
     */
    public function question() :BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * the user that this response belong to
     */
    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute()
    {
        if ($this->admin_id) {
            if($this->ai_generated){
                return 'confirmed';
            }else{
                return 'audited';
            }
        }else{
            if($this->ai_generated){
                return 'need_confirmation';
            }else{
                return 'need_auditing';
            }
        }
    }
}
