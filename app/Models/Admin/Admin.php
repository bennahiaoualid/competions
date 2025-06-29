<?php

namespace App\Models\Admin;

use App\Models\Competition\Competition;
use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;


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
 * @mixin \Eloquent
 */
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

    // Define the scope
    public function scopeWithoutRoles($query, array $roles)
    {
        return $query->whereDoesntHave('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });
    }
    /**
     * The admins that has permissions to be auditor in this competition.
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
     * override methode for storing log activity
     */
    protected array  $logAttributes = [   'name',
        'email',
        'birthdate',
        'gender',
        ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->logAttributes)
            ->useLogName('admin')
            ->logOnlyDirty();
    }
}
