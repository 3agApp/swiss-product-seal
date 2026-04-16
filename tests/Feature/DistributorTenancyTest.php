<?php

use App\Enums\Role;
use App\Filament\Pages\Tenancy\EditDistributorProfile;
use App\Models\Distributor;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('returns a users distributors as available tenants', function () {
    $distributor = Distributor::factory()->create();
    $otherDistributor = Distributor::factory()->create();
    $user = User::factory()->create();

    $distributor->members()->attach($user, ['role' => Role::Owner->value]);

    $panel = Filament::getPanel('dashboard');

    expect($user->getTenants($panel))
        ->toHaveCount(1)
        ->and($user->getTenants($panel)->first()->is($distributor))->toBeTrue()
        ->and($user->canAccessTenant($distributor))->toBeTrue()
        ->and($user->canAccessTenant($otherDistributor))->toBeFalse();
});

it('labels the active distributor in the tenant switcher', function () {
    $distributor = Distributor::factory()->create();

    expect($distributor->getCurrentTenantLabel())->toBe('Active Distributor');
});

it('redirects to the updated tenant profile url after changing the slug', function () {
    $distributor = Distributor::factory()->create(['slug' => 'old-slug']);
    $user = User::factory()->create();

    $distributor->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($distributor);

    Livewire::test(EditDistributorProfile::class)
        ->set('data.name', 'Updated Distributor')
        ->set('data.slug', 'new-slug')
        ->call('save')
        ->assertRedirect(EditDistributorProfile::getUrl(tenant: $distributor->fresh()));

    expect($distributor->fresh()->slug)->toBe('new-slug');
});
