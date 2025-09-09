<?php

namespace App\Models\Admin;

use App\Enums\UserTypeEnum;
use App\Observers\AdminObserver;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Admin\AdminAvailability;
use App\Models\Competition\Competition;
use Illuminate\Notifications\Notifiable;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\Monitoring\DeletionRequest;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $birthdate
 * @property string $gender
 * @property int|null $admin_id
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Competition> $competitionsAudit
 * @property-read int|null $competitions_audit_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GlobalQuestion> $globalQuestions
 * @property-read int|null $global_questions_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\Admin\AdminFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereBirthdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutRoles(array $roles)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutTrashed()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Competition> $competitions
 * @property-read int|null $competitions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DeletionRequest> $deletionRequests
 * @property-read int|null $deletion_requests_count
 * @property-read AdminAvailability|null $availability
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin availableAsAuditor()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin availableAsLevelManager()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin availableAsOwnershipTransfer()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\PaymentTransaction> $approvedPayments
 * @property-read int|null $approved_payments_count
 * @property-read \App\Models\Payment\CoinBalance|null $coinBalance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\CoinTransaction> $coinTransactions
 * @property-read int|null $coin_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\PaymentTransaction> $paymentTransactions
 * @property-read int|null $payment_transactions_count
 * @mixin \Eloquent
 */

#[ObservedBy([AdminObserver::class])]
class Admin extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'birthdate',
        'gender',
        'admin_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the user type
     */
    public function getUserType(): UserTypeEnum
    {
        return UserTypeEnum::ADMIN;
    }

    // Define the scope
    public function scopeWithoutRoles($query, array $roles)
    {
        return $query->whereDoesntHave('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }

    /**
     * Scope a query to only include admins available as auditor.
     */
    public function scopeAvailableAsAuditor($query)
    {
        return $query->whereHas('availability', function ($q) {
            $q->where('auditor', true);
        });
    }

    /**
     * Scope a query to only include admins available as level manager.
     */
    public function scopeAvailableAsLevelManager($query)
    {
        return $query->whereHas('availability', function ($q) {
            $q->where('level_manager', true);
        });
    }

    /**
     * Scope a query to only include admins available for ownership transfer.
     */
    public function scopeAvailableAsOwnershipTransfer($query)
    {
        return $query->whereHas('availability', function ($q) {
            $q->where('ownership_transfer', true);
        });
    }

    /**
     * The competitions that created by this admin.
     */
    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    /**
     * The competitions that this admin has permissions to be auditor in.
     */
    public function competitionsAudit(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class);
    }

    /**
     * The global questions that added by this admin.
     */
    public function globalQuestions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlobalQuestion::class);
    }

    /**
     * The deletion requests that this admin has requested to be deleted.
     */
    public function deletionRequests()
    {
        return $this->morphMany(DeletionRequest::class, 'deletable');
    }

    public function availability(){
        return $this->hasOne(AdminAvailability::class);
    } 

    /**
     * The payment transactions that this admin has made.
     */
    public function paymentTransactions()
    {
        return $this->morphMany(\App\Models\Payment\PaymentTransaction::class, 'payable');
    }

    /**
     * The coin balance for this admin.
     */
    public function coinBalance()
    {
        return $this->morphOne(\App\Models\Payment\CoinBalance::class, 'balanceable');
    }

    /**
     * The coin transactions for this admin.
     */
    public function coinTransactions()
    {
        return $this->morphMany(\App\Models\Payment\CoinTransaction::class, 'transactionable');
    }

    /**
     * The payments that this admin has approved.
     */
    public function approvedPayments()
    {
        return $this->hasMany(\App\Models\Payment\PaymentTransaction::class, 'approver_admin_id');
    }

    /**
     * override methode for storing log activity
     */
    protected array  $logAttributes = [   
        'name',
        'email',
        'birthdate',
        'gender',
        ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->logAttributes)
            ->useLogName('admin')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs(); 
    }
}
