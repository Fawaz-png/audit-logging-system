<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasUuid;
use App\Notifications\ActivateAccount;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasUuid;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'preferences' => 'array',
        ];
    }

    protected $fillable = [
        'department_id',
        'role_id',
        'name',
        'email',
        'password',
        'active',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sendAccountActivationNotification(string $token): void
    {
        $this->notify(new ActivateAccount($token));
    }

    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class)
            ->withPivot('uuid', 'assignment_type')
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    public function canViewAuditPortfolio(): bool
    {
        return $this->hasPermission('audit.view')
            && in_array($this->role?->slug, [
                'system-administrator',
                'head-of-is-audit',
                'auditor',
                'auditee',
            ], true);
    }

    public function responsibleFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'responsible_user_id');
    }

    public function createdFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'created_by');
    }

    public function updatedFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'updated_by');
    }
}
