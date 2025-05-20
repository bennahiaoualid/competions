<?php

namespace App\Models\GuestUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $question_id
 * @property int|null $choice_id
 * @property int $user_id
 * @property float $score
 * @property int $response_duration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GuestUsers\Choice|null $choice
 * @method static \Database\Factories\GuestUsers\GlobalResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereChoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereResponseDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalResponse whereUserId($value)
 * @mixin \Eloquent
 */
class GlobalResponse extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'choice_id',
        'question_id',
        'user_id',
        'score',
        'response_duration',
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
            'response_duration' => 'integer',
        ];
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(Choice::class);
    }
}
