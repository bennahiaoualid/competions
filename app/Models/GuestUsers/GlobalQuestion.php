<?php

namespace App\Models\GuestUsers;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
