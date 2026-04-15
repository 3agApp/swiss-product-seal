<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

dataset('tenant_filament_pages', [
    'dashboard' => ['filament.dashboard.pages.dashboard'],
    'organization profile' => ['filament.dashboard.tenant.profile'],
    'members index' => ['filament.dashboard.resources.members.index'],
    'invitations index' => ['filament.dashboard.resources.invitations.index'],
    'invitations create' => ['filament.dashboard.resources.invitations.create'],
    'suppliers index' => ['filament.dashboard.resources.suppliers.index'],
    'suppliers create' => ['filament.dashboard.resources.suppliers.create'],
    'suppliers edit' => ['filament.dashboard.resources.suppliers.edit'],
    'products index' => ['filament.dashboard.resources.products.index'],
    'products create' => ['filament.dashboard.resources.products.create'],
    'products edit' => ['filament.dashboard.resources.products.edit'],
    'supplier brands index' => ['filament.dashboard.resources.suppliers.brands.index'],
    'supplier brands create' => ['filament.dashboard.resources.suppliers.brands.create'],
    'supplier brands edit' => ['filament.dashboard.resources.suppliers.brands.edit'],
]);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();
    $this->prospect = User::factory()->create();

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->supplier = Supplier::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->brand = Brand::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->product = Product::factory()->create([
        'organization_id' => $this->organization->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
    ]);

    Invitation::factory()->create([
        'organization_id' => $this->organization->id,
        'invited_by' => $this->owner->id,
    ]);
});

it('loads the dashboard register page', function () {
    $this->get(route('filament.dashboard.auth.register'))
        ->assertSuccessful();
});

it('loads the tenant registration page', function () {
    $this->actingAs($this->prospect)
        ->get(route('filament.dashboard.tenant.registration'))
        ->assertSuccessful();
});

it('loads each tenant filament page', function (string $routeName) {
    $parameters = ['tenant' => $this->organization];

    if ($routeName === 'filament.dashboard.resources.suppliers.edit') {
        $parameters['record'] = $this->supplier;
    }

    if ($routeName === 'filament.dashboard.resources.products.edit') {
        $parameters['record'] = $this->product;
    }

    if (str_starts_with($routeName, 'filament.dashboard.resources.suppliers.brands.')) {
        $parameters['supplier'] = $this->supplier;
    }

    if ($routeName === 'filament.dashboard.resources.suppliers.brands.edit') {
        $parameters['record'] = $this->brand;
    }

    $this->actingAs($this->owner)
        ->get(route($routeName, $parameters))
        ->assertSuccessful();
})->with('tenant_filament_pages');
