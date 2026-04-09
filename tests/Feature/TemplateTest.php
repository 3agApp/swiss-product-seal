<?php

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\QueryException;

it('can be created via factory', function () {
    $template = Template::factory()->create();

    $this->assertModelExists($template);

    expect($template->name)->toBeString()
        ->and($template->category_id)->toBeInt()
        ->and($template->required_document_types)->toBeArray()
        ->and($template->optional_document_types)->toBeArray();
});

it('belongs to a category', function () {
    $category = Category::factory()->create();
    $template = Template::factory()->for($category)->create();

    expect($template->category->id)->toBe($category->id);
});

it('stores document types as arrays of enum values', function () {
    $template = Template::factory()->create([
        'required_document_types' => [DocumentType::TestReport->value, DocumentType::Certificate->value],
        'optional_document_types' => [DocumentType::Manual->value],
    ]);

    $template->refresh();

    expect($template->required_document_types)->toBe([DocumentType::TestReport->value, DocumentType::Certificate->value])
        ->and($template->optional_document_types)->toBe([DocumentType::Manual->value]);
});

it('category has many templates', function () {
    $category = Category::factory()->create();

    Template::factory()->count(3)->for($category)->create();

    expect($category->templates)->toHaveCount(3)
        ->each->toBeInstanceOf(Template::class);
});

it('is cascade deleted when its category is deleted', function () {
    $category = Category::factory()->create();
    $template = Template::factory()->for($category)->create();

    $category->delete();

    $this->assertModelMissing($template);
});

it('has many products', function () {
    $template = Template::factory()->create();

    Product::factory()->count(2)->create([
        'category_id' => $template->category_id,
        'template_id' => $template->id,
    ]);

    expect($template->products)->toHaveCount(2)
        ->each->toBeInstanceOf(Product::class);
});

it('cannot be deleted when products reference it', function () {
    $template = Template::factory()->create();

    Product::factory()->create([
        'category_id' => $template->category_id,
        'template_id' => $template->id,
    ]);

    expect(fn () => $template->delete())->toThrow(QueryException::class);
});

it('requires a product to have a template', function () {
    $category = Category::factory()->create();

    expect(fn () => Product::create([
        'name' => 'Test Product',
        'category_id' => $category->id,
    ]))->toThrow(QueryException::class);
});

it('validates template category matches product category on store', function () {
    $user = User::factory()->create();

    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $template = Template::factory()->for($categoryA)->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Test Product',
            'category_id' => $categoryB->id,
            'template_id' => $template->id,
        ])
        ->assertSessionHasErrors('template_id');
});

it('allows product creation when template category matches', function () {
    $user = User::factory()->create();

    $category = Category::factory()->create();
    $template = Template::factory()->for($category)->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Test Product',
            'category_id' => $category->id,
            'template_id' => $template->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'category_id' => $category->id,
        'template_id' => $template->id,
    ]);
});

it('validates template category matches product category on update', function () {
    $user = User::factory()->create();

    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $templateA = Template::factory()->for($categoryA)->create();
    $templateB = Template::factory()->for($categoryB)->create();

    $product = Product::factory()->create([
        'category_id' => $categoryA->id,
        'template_id' => $templateA->id,
    ]);

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'name' => $product->name,
            'category_id' => $categoryA->id,
            'template_id' => $templateB->id,
        ])
        ->assertSessionHasErrors('template_id');
});
