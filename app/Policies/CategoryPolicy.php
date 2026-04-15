<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isSystemAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isSystemAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isSystemAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }
}
