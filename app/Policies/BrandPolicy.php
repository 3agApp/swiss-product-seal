<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\Distributor;
use App\Models\User;
use Filament\Facades\Filament;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageDistributor($user);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->canManageDistributor($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageDistributor($user);
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->canManageDistributor($user);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->canManageDistributor($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageDistributor($user);
    }

    private function canManageDistributor(User $user): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $user->getRoleForDistributor($tenant)?->canManageDistributor() ?? false;
    }
}
