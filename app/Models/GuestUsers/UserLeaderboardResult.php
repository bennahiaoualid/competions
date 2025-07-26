<?php

namespace App\Models\GuestUsers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserLeaderboardResult extends Model
{
    protected $table = 'user_leaderboard_results';

    protected $fillable = [
        'user_id',
        'total_score',
        'total_duration',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
} 