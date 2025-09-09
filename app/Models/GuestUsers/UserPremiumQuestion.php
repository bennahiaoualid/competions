<?php

namespace App\Models\GuestUsers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $global_question_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GuestUsers\GlobalQuestion $question
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion whereGlobalQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPremiumQuestion whereUserId($value)
 * @mixin \Eloquent
 */
class UserPremiumQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'global_question_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The user who owns this premium question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The premium question that is owned.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(GlobalQuestion::class, 'global_question_id');
    }

    /**
     * Check if this ownership is still valid.
     */
    public function isValid(): bool
    {
        return $this->question && $this->question->isPremium();
    }
} 