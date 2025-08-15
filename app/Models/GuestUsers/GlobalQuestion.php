<?php

namespace App\Models\GuestUsers;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * 
 *
 * @property int $id
 * @property string $question_text
 * @property int $score
 * @property int $duration
 * @property string $text_direction
 * @property int|null $admin_id
 * @property int|null $approved
 * @property string|null $deleted_admin_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GuestUsers\Choice> $choices
 * @property-read int|null $choices_count
 * @property-read Admin|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GuestUsers\GlobalResponse> $responses
 * @property-read int|null $responses_count
 * @method static \Database\Factories\GuestUsers\GlobalQuestionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereDeletedAdminName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereQuestionText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereTextDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalQuestion whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GlobalQuestion extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question_text',
        'score',
        'duration',
        'text_direction',
        'admin_id',
        'approved',
        'deleted_admin_name',
        'ai',
        'user_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'duration' => 'integer',
        ];
    }

    /**
     * The admin that created this question.
     */
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id','id');
    }

    /**
     * The admin that approved this question.
     */
    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class,'approved','id');
    }

    /**
     * The choices related to this question .
     */
    public function choices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Choice::class,'question_id','id');
    }

    /**
     * The users response for this question .
     */
    public function responses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlobalResponse::class,'question_id','id');
    }

    /**
     * The user who generated this AI question.
     */
    public function generatedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    /**
     * Check if this is an AI-generated question.
     */
    public function isAiGenerated(): bool
    {
        return $this->ai === true;
    }

    /**
     * Check if this question is still in exclusive period (first 48 hours).
     */
    public function isExclusive(): bool
    {
        if (!$this->isAiGenerated()) {
            return false;
        }
        
        return $this->created_at->diffInHours(now()) < 48;
    }

    /**
     * Check if this question is available as premium (after 48 hours).
     */
    public function isPremium(): bool
    {
        if (!$this->isAiGenerated()) {
            return false;
        }
        
        return $this->created_at->diffInHours(now()) >= 48;
    }

    /**
     * Check if a specific user can access this question.
     */
    public function canUserAccess(\App\Models\User $user): bool
    {
        // Generator always has access
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // AI questions have time-based access
        if ($this->isAiGenerated()) {
            if ($this->isExclusive()) {
                return false; // Still exclusive to generator
            }
            
            return true; // Premium access available
        }
        
        // Regular questions always accessible
        return true;
    }

    /**
     * Scope for AI-generated questions.
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('ai', true);
    }

    /**
     * Scope for questions available to a specific user.
     */
    public function scopeAvailableToUser($query, \App\Models\User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Regular questions
            $q->where('ai', false);
            
            // AI questions generated by this user
            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('ai', true)
                      ->where('user_id', $user->id);
            });
            
            // AI questions that are no longer exclusive (premium)
            $q->orWhere(function ($subQ) {
                $subQ->where('ai', true)
                      ->where('created_at', '<=', now()->subHours(48));
            });
        });
    }

    /**
     * Scope for premium AI questions (after 48 hours).
     */
    public function scopePremium($query)
    {
        return $query->where('ai', true)
                    ->where('created_at', '<=', now()->subHours(48));
    }

    /**
     * The admin that approved this question.
     */
    public function canDelete() : bool
    {
        $super =Auth::user()->hasRole(['super_admin','owner'], 'admin');
        $created = Auth::id() == $this->admin_id;
        return $super || $created;
    }
}
