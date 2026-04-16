<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAccessProductRecords($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canAccessProductRecords($user);
    }

    public function create(User $user): bool
    {
        return $this->canAccessProductRecords($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canAccessProductRecords($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canAccessProductRecords($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canAccessProductRecords($user);
    }

    private function canAccessProductRecords(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Distributor) {
            return false;
        }

        return $user->getRoleForDistributor($tenant)?->canManageDistributor() ?? false;
    }
}
