<?php

namespace App\Models\Competition;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
