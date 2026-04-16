<?php

namespace App\Policies;

use App\Models\Distributor;
use App\Models\User;

class DistributorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Distributor $distributor): bool
    {
        return $user->canAccessTenant($distributor);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Distributor $distributor): bool
    {
        $role = $user->getRoleForDistributor($distributor);

        return $role?->canManageDistributor() ?? false;
    }

    public function delete(User $user, Distributor $distributor): bool
    {
        $role = $user->getRoleForDistributor($distributor);

        return $role?->canDeleteDistributor() ?? false;
    }
}
