<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    private function canManageDocument(User $user, Document $document): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return false;
        }

        $role = $user->getRoleForOrganization($tenant);

        if (! $role?->canManageOrganization()) {
            return false;
        }

        return (int) $document->organization_id === (int) $tenant->getKey();
    }

    private function canManageDocuments(User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return false;
        }

        return $user->getRoleForOrganization($tenant)?->canManageOrganization() ?? false;
    }
}
