<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function distributors(): BelongsToMany
    {
        return $this->belongsToMany(Distributor::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isSystemAdmin(),
            'dashboard' => true,
            default => false,
        };
    }

    public function isSystemAdmin(): bool
    {
        return in_array(Str::lower($this->email), config('admin.allowed_emails', []), true);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->distributors;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->distributors()->whereKey($tenant)->exists();
    }

    public function getRoleForDistributor(Distributor $distributor): ?Role
    {
        $membership = $this->distributors()->whereKey($distributor)->first();

        return $membership ? Role::from($membership->pivot->role) : null;
    }

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
        ];
    }
}
