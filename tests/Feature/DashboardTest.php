<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard provides deferred stats and recent products', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();
    $template = Template::factory()->for($category)->create();
    $supplier = Supplier::factory()->create();

    Product::factory()->count(3)->for($category)->for($template)->for($supplier)->create();

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->missing('stats')
            ->missing('recentProducts')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('stats', fn (Assert $stats) => $stats
                    ->where('totalProducts', 3)
                    ->where('totalSuppliers', 1)
                    ->where('totalCategories', 1)
                    ->has('statusCounts')
                    ->has('completenessDistribution')
                )
                ->has('recentProducts', 3)
            )
        );
});
