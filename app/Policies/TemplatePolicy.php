<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function view(User $user, Template $template): bool
    {
        return $user->isSystemAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function update(User $user, Template $template): bool
    {
        return $user->isSystemAdmin();
    }

    public function delete(User $user, Template $template): bool
    {
        return $user->isSystemAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }
}
