<?php

use App\Enums\Role;
use App\Filament\Pages\Tenancy\EditOrganizationProfile;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('returns a users organizations as available tenants', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    $panel = Filament::getPanel('dashboard');

    expect($user->getTenants($panel))
        ->toHaveCount(1)
        ->and($user->getTenants($panel)->first()->is($organization))->toBeTrue()
        ->and($user->canAccessTenant($organization))->toBeTrue()
        ->and($user->canAccessTenant($otherOrganization))->toBeFalse();
});

it('labels the active organization in the tenant switcher', function () {
    $organization = Organization::factory()->create();

    expect($organization->getCurrentTenantLabel())->toBe('Active Organization');
});

it('redirects to the updated tenant profile url after changing the slug', function () {
    $organization = Organization::factory()->create(['slug' => 'old-slug']);
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    Livewire::test(EditOrganizationProfile::class)
        ->set('data.name', 'Updated Organization')
        ->set('data.slug', 'new-slug')
        ->call('save')
        ->assertRedirect(EditOrganizationProfile::getUrl(tenant: $organization->fresh()));

    expect($organization->fresh()->slug)->toBe('new-slug');
});
