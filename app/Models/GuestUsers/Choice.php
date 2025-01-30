<?php

namespace App\Models\GuestUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Choice extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'choice_text',
        'correct',
        'question_id',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correct' => 'boolean',
        ];
    }

    /**
     * get the question
     */

    public function question(): BelongsTo
    {
        return $this->belongsTo(GlobalQuestion::class);
    }
}
