<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the categories index page', function () {
    Category::factory()->count(3)->create();

    $this->get(route('categories.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('categories/index'));
});

it('redirects guests to login from categories index', function () {
    auth()->logout();

    $this->get(route('categories.index'))
        ->assertRedirect(route('login'));
});

it('displays the create category form', function () {
    $this->get(route('categories.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('categories/create'));
});

it('stores a new category', function () {
    $this->post(route('categories.store'), [
        'name' => 'Electronics',
        'description' => 'Electronic products and components',
    ])->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Electronics',
        'description' => 'Electronic products and components',
    ]);
});

it('stores a category without description', function () {
    $this->post(route('categories.store'), [
        'name' => 'Misc',
    ])->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Misc',
        'description' => null,
    ]);
});

it('validates required fields on store', function () {
    $this->post(route('categories.store'), [])
        ->assertSessionHasErrors(['name']);
});

it('validates name minimum length on store', function () {
    $this->post(route('categories.store'), [
        'name' => 'A',
    ])->assertSessionHasErrors(['name']);
});

it('displays the edit category form', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.edit', $category))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('categories/edit'));
});

it('updates an existing category', function () {
    $category = Category::factory()->create();

    $this->put(route('categories.update', $category), [
        'name' => 'Updated Name',
        'description' => 'Updated description',
    ])->assertRedirect(route('categories.index'));

    expect($category->fresh())
        ->name->toBe('Updated Name')
        ->description->toBe('Updated description');
});

it('validates required fields on update', function () {
    $category = Category::factory()->create();

    $this->put(route('categories.update', $category), [])
        ->assertSessionHasErrors(['name']);
});

it('deletes a category', function () {
    $category = Category::factory()->create();

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('filters categories by search term', function () {
    Category::factory()->create(['name' => 'Electronics']);
    Category::factory()->create(['name' => 'Furniture']);

    $this->get(route('categories.index', ['search' => 'Electro']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Electronics')
        );
});

it('sorts categories by column', function () {
    Category::factory()->create(['name' => 'Zebra']);
    Category::factory()->create(['name' => 'Alpha']);

    $this->get(route('categories.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('categories.data.0.name', 'Alpha')
            ->where('categories.data.1.name', 'Zebra')
        );
});

it('ignores invalid sort columns', function () {
    Category::factory()->count(2)->create();

    $this->get(route('categories.index', ['sort' => 'DROP TABLE categories']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('filters.sort', '')
        );
});

it('includes products count on categories index', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('categories.data.0.products_count', 0)
        );
});

it('preserves active filters in category pagination links', function () {
    Category::factory()
        ->count(20)
        ->sequence(fn (Sequence $sequence) => [
            'name' => "Category {$sequence->index}",
        ])
        ->create();

    $response = $this->get(route('categories.index', [
        'search' => 'Category',
        'sort' => 'name',
        'direction' => 'asc',
        'page' => 2,
    ]));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/index')
            ->where('categories.current_page', 2)
            ->where('filters.search', 'Category')
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'asc')
        );

    collect($response->inertiaProps('categories.links'))
        ->pluck('url')
        ->filter()
        ->each(function (string $url) {
            expect($url)
                ->toContain('search=Category')
                ->toContain('sort=name')
                ->toContain('direction=asc');
        });
});
