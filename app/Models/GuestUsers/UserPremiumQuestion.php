<?php

namespace App\Models\GuestUsers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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