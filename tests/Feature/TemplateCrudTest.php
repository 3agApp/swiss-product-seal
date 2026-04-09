<?php

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the templates index page', function () {
    Template::factory()->count(3)->create();

    $this->get(route('templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('templates/index'));
});

it('redirects guests to login from templates index', function () {
    auth()->logout();

    $this->get(route('templates.index'))
        ->assertRedirect(route('login'));
});

it('displays the create template form', function () {
    $this->get(route('templates.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/create')
            ->has('categories')
            ->has('documentTypes')
        );
});

it('stores a new template', function () {
    $category = Category::factory()->create();

    $this->post(route('templates.store'), [
        'category_id' => $category->id,
        'name' => 'Standard Template',
        'required_document_types' => [DocumentType::TestReport->value],
        'optional_document_types' => [DocumentType::Manual->value],
    ])->assertRedirect(route('templates.index'));

    $this->assertDatabaseHas('templates', [
        'category_id' => $category->id,
        'name' => 'Standard Template',
    ]);
});

it('stores a template with empty document types', function () {
    $category = Category::factory()->create();

    $this->post(route('templates.store'), [
        'category_id' => $category->id,
        'name' => 'Empty Template',
        'required_document_types' => [],
        'optional_document_types' => [],
    ])->assertRedirect(route('templates.index'));

    $this->assertDatabaseHas('templates', [
        'name' => 'Empty Template',
    ]);
});

it('validates required fields on store', function () {
    $this->post(route('templates.store'), [])
        ->assertSessionHasErrors(['category_id', 'name', 'required_document_types', 'optional_document_types']);
});

it('validates name minimum length on store', function () {
    $category = Category::factory()->create();

    $this->post(route('templates.store'), [
        'category_id' => $category->id,
        'name' => 'A',
        'required_document_types' => [],
        'optional_document_types' => [],
    ])->assertSessionHasErrors(['name']);
});

it('validates category exists on store', function () {
    $this->post(route('templates.store'), [
        'category_id' => 999,
        'name' => 'Test Template',
        'required_document_types' => [],
        'optional_document_types' => [],
    ])->assertSessionHasErrors(['category_id']);
});

it('validates document type values on store', function () {
    $category = Category::factory()->create();

    $this->post(route('templates.store'), [
        'category_id' => $category->id,
        'name' => 'Test Template',
        'required_document_types' => ['invalid_type'],
        'optional_document_types' => [],
    ])->assertSessionHasErrors(['required_document_types.0']);
});

it('rejects overlapping required and optional document types', function () {
    $category = Category::factory()->create();

    $this->post(route('templates.store'), [
        'category_id' => $category->id,
        'name' => 'Overlap Template',
        'required_document_types' => [DocumentType::TestReport->value],
        'optional_document_types' => [DocumentType::TestReport->value],
    ])->assertSessionHasErrors(['optional_document_types']);
});

it('displays the edit template form', function () {
    $template = Template::factory()->create();

    $this->get(route('templates.edit', $template))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/edit')
            ->has('template')
            ->has('categories')
            ->has('documentTypes')
        );
});

it('updates an existing template', function () {
    $template = Template::factory()->create();
    $newCategory = Category::factory()->create();

    $this->put(route('templates.update', $template), [
        'category_id' => $newCategory->id,
        'name' => 'Updated Template',
        'required_document_types' => [DocumentType::Certificate->value],
        'optional_document_types' => [],
    ])->assertRedirect(route('templates.index'));

    expect($template->fresh())
        ->name->toBe('Updated Template')
        ->category_id->toBe($newCategory->id)
        ->required_document_types->toBe([DocumentType::Certificate->value])
        ->optional_document_types->toBe([]);
});

it('validates required fields on update', function () {
    $template = Template::factory()->create();

    $this->put(route('templates.update', $template), [])
        ->assertSessionHasErrors(['category_id', 'name', 'required_document_types', 'optional_document_types']);
});

it('deletes a template', function () {
    $template = Template::factory()->create();

    $this->delete(route('templates.destroy', $template))
        ->assertRedirect(route('templates.index'));

    $this->assertDatabaseMissing('templates', ['id' => $template->id]);
});

it('does not delete a template with assigned products', function () {
    $template = Template::factory()->create();
    Product::factory()->create([
        'category_id' => $template->category_id,
        'template_id' => $template->id,
    ]);

    $this->delete(route('templates.destroy', $template))
        ->assertRedirect(route('templates.index'))
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    $this->assertDatabaseHas('templates', ['id' => $template->id]);
});

it('filters templates by search term', function () {
    Template::factory()->create(['name' => 'Electronics Template']);
    Template::factory()->create(['name' => 'Furniture Template']);

    $this->get(route('templates.index', ['search' => 'Electro']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/index')
            ->has('templates.data', 1)
            ->where('templates.data.0.name', 'Electronics Template')
        );
});

it('searches templates by category name', function () {
    $category = Category::factory()->create(['name' => 'Safety Gear']);
    Template::factory()->for($category)->create(['name' => 'Basic Template']);
    Template::factory()->create(['name' => 'Other Template']);

    $this->get(route('templates.index', ['search' => 'Safety']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/index')
            ->has('templates.data', 1)
            ->where('templates.data.0.name', 'Basic Template')
        );
});

it('sorts templates by name', function () {
    Template::factory()->create(['name' => 'Zebra Template']);
    Template::factory()->create(['name' => 'Alpha Template']);

    $this->get(route('templates.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/index')
            ->where('templates.data.0.name', 'Alpha Template')
            ->where('templates.data.1.name', 'Zebra Template')
        );
});

it('ignores invalid sort columns', function () {
    Template::factory()->count(2)->create();

    $this->get(route('templates.index', ['sort' => 'DROP TABLE templates']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/index')
            ->where('filters.sort', '')
        );
});

it('includes products count on templates index', function () {
    Template::factory()->create();

    $this->get(route('templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('templates/index')
            ->where('templates.data.0.products_count', 0)
        );
});
