<?php

namespace App\Models\Competition;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
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

    /**
     * the question that this response belong to
     */
    public function question() :BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
