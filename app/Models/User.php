<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Permission\PlatformTeam;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_platform_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_super_admin' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'tenant_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName): void
    {
        $activity->tenant_id = $this->tenant_id;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Tenant\Models\Tenant::class);
    }

    public function shouldUsePlatformDashboard(): bool
    {
        if ($this->is_platform_super_admin) {
            return true;
        }

        if ($this->tenant_id !== null) {
            return false;
        }

        if (! config('permission.teams', false)) {
            return $this->hasRole('super admin');
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            return $this->hasRole('super admin');
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }

    public function getPermissionTeamId(): ?int
    {
        if ($this->shouldUsePlatformDashboard()) {
            return PlatformTeam::ID;
        }

        return $this->tenant_id !== null ? (int) $this->tenant_id : PlatformTeam::ID;
    }
}
