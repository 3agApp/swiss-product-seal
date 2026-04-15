<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->canManageOrganization($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->canManageOrganization($user);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->canManageOrganization($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    private function canManageOrganization(User $user): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return false;
        }

        return $user->getRoleForOrganization($tenant)?->canManageOrganization() ?? false;
    }
}
