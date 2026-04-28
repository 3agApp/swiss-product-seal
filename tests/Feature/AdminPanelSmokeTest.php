<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;

dataset('admin_filament_pages', [
    'admin dashboard' => ['filament.admin.pages.dashboard'],
    'categories index' => ['filament.admin.resources.categories.index'],
    'categories create' => ['filament.admin.resources.categories.create'],
    'categories edit' => ['filament.admin.resources.categories.edit'],
    'products index' => ['filament.admin.resources.products.index'],
    'products edit' => ['filament.admin.resources.products.edit'],
    'category templates index' => ['filament.admin.resources.categories.templates.index'],
    'category templates create' => ['filament.admin.resources.categories.templates.create'],
    'category templates edit' => ['filament.admin.resources.categories.templates.edit'],
]);

beforeEach(function () {
    $this->systemAdmin = User::factory()->create(['email' => 'system-admin@example.com']);
    config()->set('admin.allowed_emails', [$this->systemAdmin->email]);

    $this->distributor = Distributor::factory()->create();
    $this->distributor->members()->attach($this->systemAdmin, ['role' => Role::Owner->value]);

    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $this->brand = Brand::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->category = Category::factory()->create();

    $this->template = Template::factory()->create([
        'category_id' => $this->category->id,
    ]);

    $this->product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
        'category_id' => $this->category->id,
        'template_id' => $this->template->id,
    ]);
});

it('loads the admin login page', function () {
    $this->get(route('filament.admin.auth.login'))
        ->assertSuccessful();
});

it('loads each admin filament page for system admins', function (string $routeName) {
    $parameters = [];

    if ($routeName === 'filament.admin.resources.categories.edit') {
        $parameters['record'] = $this->category;
    }

    if ($routeName === 'filament.admin.resources.products.edit') {
        $parameters['record'] = $this->product;
    }

    if (str_starts_with($routeName, 'filament.admin.resources.categories.templates.')) {
        $parameters['category'] = $this->category;
    }

    if ($routeName === 'filament.admin.resources.categories.templates.edit') {
        $parameters['record'] = $this->template;
    }

    $this->actingAs($this->systemAdmin)
        ->get(route($routeName, $parameters))
        ->assertSuccessful();
})->with('admin_filament_pages');
