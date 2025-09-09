<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\Auth;

/**
 * 
 *
 * @property int $id
 * @property int $admin_id
 * @property int $auditor
 * @property int $level_manager
 * @property int $ownership_transfer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereAuditor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereLevelManager($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereOwnershipTransfer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAvailability whereUpdatedAt($value)
 * @property-read array $hidden_fields_for_user
 * @mixin \Eloquent
 */

 class AdminAvailability extends Model
{
    use HasFactory;

    protected $table = 'admin_availability';

    protected $fillable = [
        'admin_id',
        'auditor',
        'level_manager',
        'ownership_transfer',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get an array of fields that should be hidden from the user based on their role.
     *
     * @return array
     */
    public function getHiddenFieldsForUserAttribute()
    {
        $hidden = [];
        $user = Auth::user();
        if ($user && !$user->hasRole(['owner', 'super_admin'], 'admin')) {
            $hidden[] = 'ownership_transfer';
        }
        return $hidden;
    }
} 