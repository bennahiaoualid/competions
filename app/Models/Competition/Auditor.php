<?php

namespace App\Models\Competition;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 
 *
 * @property-read Admin|null $admin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competition\Competition> $auditCompetition
 * @property-read int|null $audit_competition_count
 * @property-read \App\Models\Competition\Competition|null $competition
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditor query()
 * @mixin \Eloquent
 */
class Auditor extends Model
{
    /**
     * The relationship of the admin that has audit permmision to a competition
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * The relationship of the competition admin  that will audit by this admin(auditor))
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * The competition that this admin audit in.
     */
    public function auditCompetition(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class);
    }
}
