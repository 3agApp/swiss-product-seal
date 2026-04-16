<?php

use App\Enums\Role;
use App\Models\Distributor;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->distributor->members()->attach($this->admin, ['role' => Role::Admin->value]);
});

it('user can access their distributors', function () {
    expect($this->owner->canAccessTenant($this->distributor))->toBeTrue()
        ->and($this->admin->canAccessTenant($this->distributor))->toBeTrue();
});

it('user cannot access distributors they do not belong to', function () {
    $otherOrg = Distributor::factory()->create();

    expect($this->owner->canAccessTenant($otherOrg))->toBeFalse();
});

it('user can get tenants', function () {
    $panel = Filament::getPanel('dashboard');

    $tenants = $this->owner->getTenants($panel);

    expect($tenants)->toHaveCount(1)
        ->and($tenants->first()->id)->toBe($this->distributor->id);
});

it('user can get their role for an distributor', function () {
    expect($this->owner->getRoleForDistributor($this->distributor))->toBe(Role::Owner)
        ->and($this->admin->getRoleForDistributor($this->distributor))->toBe(Role::Admin);
});

it('user returns null role for non-member distributor', function () {
    $otherOrg = Distributor::factory()->create();

    expect($this->owner->getRoleForDistributor($otherOrg))->toBeNull();
});

it('user can belong to multiple distributors', function () {
    $secondOrg = Distributor::factory()->create();
    $this->owner->distributors()->attach($secondOrg, ['role' => Role::Admin->value]);

    expect($this->owner->distributors)->toHaveCount(2);
});

it('system admins can access the admin panel', function () {
    $adminPanel = Filament::getPanel('admin');

    config()->set('admin.allowed_emails', [$this->owner->email]);

    expect($this->owner->canAccessPanel($adminPanel))->toBeTrue();
});

it('non system admins cannot access the admin panel', function () {
    $adminPanel = Filament::getPanel('admin');

    config()->set('admin.allowed_emails', ['different-user@example.com']);

    expect($this->owner->canAccessPanel($adminPanel))->toBeFalse();
});
