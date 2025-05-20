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
        'admin_id',
        'approved',
        'deleted_admin_name'
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
     * The admin that approved this question.
     */
    public function canDelete() : bool
    {
        $super =Auth::user()->hasRole(['super_admin','owner'], 'admin');
        $created = Auth::id() == $this->admin_id;
        return $super || $created;
    }
}
