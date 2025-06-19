<?php

namespace App\Models\Competition;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'flags'
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
        ];
    }

    /**
     * the question that this response belong to
     */
    public function question() :BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
