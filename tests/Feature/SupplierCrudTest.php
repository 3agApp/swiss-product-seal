<?php

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the suppliers index page', function () {
    Supplier::factory()->count(3)->create();

    $this->get(route('suppliers.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/index'));
});

it('redirects guests to login from suppliers index', function () {
    auth()->logout();

    $this->get(route('suppliers.index'))
        ->assertRedirect(route('login'));
});

it('displays the create supplier form', function () {
    $this->get(route('suppliers.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/create'));
});

it('stores a new supplier', function () {
    $this->post(route('suppliers.store'), [
        'supplier_code' => 'SUP-99999',
        'name' => 'Test Supplier',
        'email' => 'test@supplier.com',
        'phone' => '+1234567890',
        'address' => '123 Main St',
        'country' => 'Germany',
        'active' => true,
        'kontor_id' => 'KON-0001',
    ])->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', [
        'supplier_code' => 'SUP-99999',
        'name' => 'Test Supplier',
        'email' => 'test@supplier.com',
    ]);
});

it('validates required fields on store', function () {
    $this->post(route('suppliers.store'), [])
        ->assertSessionHasErrors(['supplier_code', 'name']);
});

it('validates email format on store', function () {
    $this->post(route('suppliers.store'), [
        'supplier_code' => 'SUP-001',
        'name' => 'Test',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors(['email']);
});

it('displays a single supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->get(route('suppliers.show', $supplier))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/show'));
});

it('displays the edit supplier form', function () {
    $supplier = Supplier::factory()->create();

    $this->get(route('suppliers.edit', $supplier))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('suppliers/edit'));
});

it('updates an existing supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->put(route('suppliers.update', $supplier), [
        'supplier_code' => 'SUP-UPDATED',
        'name' => 'Updated Name',
        'email' => 'updated@supplier.com',
    ])->assertRedirect(route('suppliers.index'));

    expect($supplier->fresh())
        ->supplier_code->toBe('SUP-UPDATED')
        ->name->toBe('Updated Name')
        ->email->toBe('updated@supplier.com');
});

it('validates required fields on update', function () {
    $supplier = Supplier::factory()->create();

    $this->put(route('suppliers.update', $supplier), [])
        ->assertSessionHasErrors(['supplier_code', 'name']);
});

it('deletes a supplier', function () {
    $supplier = Supplier::factory()->create();

    $this->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});

it('filters suppliers by search term', function () {
    Supplier::factory()->create(['name' => 'Acme Corp']);
    Supplier::factory()->create(['name' => 'Other Company']);

    $this->get(route('suppliers.index', ['search' => 'Acme']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/index')
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.name', 'Acme Corp')
        );
});

it('sorts suppliers by column', function () {
    Supplier::factory()->create(['name' => 'Zebra']);
    Supplier::factory()->create(['name' => 'Alpha']);

    $this->get(route('suppliers.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/index')
            ->where('suppliers.data.0.name', 'Alpha')
            ->where('suppliers.data.1.name', 'Zebra')
        );
});

it('ignores invalid sort columns', function () {
    Supplier::factory()->count(2)->create();

    $this->get(route('suppliers.index', ['sort' => 'DROP TABLE suppliers']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/index')
            ->where('filters.sort', '')
        );
});

it('preserves active filters in supplier pagination links', function () {
    Supplier::factory()
        ->count(20)
        ->sequence(fn (Sequence $sequence) => [
            'name' => "Acme Supplier {$sequence->index}",
        ])
        ->create();

    $response = $this->get(route('suppliers.index', [
        'search' => 'Acme',
        'sort' => 'name',
        'direction' => 'asc',
        'page' => 2,
    ]));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('suppliers/index')
            ->where('suppliers.current_page', 2)
            ->where('filters.search', 'Acme')
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'asc')
        );

    collect($response->inertiaProps('suppliers.links'))
        ->pluck('url')
        ->filter()
        ->each(function (string $url) {
            expect($url)
                ->toContain('search=Acme')
                ->toContain('sort=name')
                ->toContain('direction=asc');
        });
});
