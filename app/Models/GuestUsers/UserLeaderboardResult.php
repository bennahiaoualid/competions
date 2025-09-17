<?php

namespace App\Models\GuestUsers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property float $total_score
 * @property float $total_duration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereTotalDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereTotalScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLeaderboardResult whereUserId($value)
 * @mixin \Eloquent
 */
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