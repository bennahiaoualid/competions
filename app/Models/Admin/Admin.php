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
