<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canManageOrganization($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageOrganization($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canManageOrganization($user);
    }

    public function delete(User $user, Product $product): bool
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
