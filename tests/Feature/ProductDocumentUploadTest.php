<?php

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('uploads a new product document', function () {
    $product = Product::factory()->create();

    $this->postJson(route('products.documents.store', $product), [
        'file' => UploadedFile::fake()->create('manual.pdf', 120, 'application/pdf'),
        'type' => DocumentType::Manual->value,
        'expiry_date' => '2027-04-07',
        'review_comment' => 'Reviewed for initial release.',
    ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'documents')
        ->assertJsonPath('documents.0.type', DocumentType::Manual->value)
        ->assertJsonPath('documents.0.version', 1);

    expect($product->fresh()->currentDocuments)->toHaveCount(1)
        ->and($product->fresh()->documents()->first()?->getMedia('file'))->toHaveCount(1);
});

it('allows multiple current documents of the same type when adding new', function () {
    $product = Product::factory()->create();

    Document::factory()->for($product)->create([
        'type' => DocumentType::Certificate,
    ]);

    $this->postJson(route('products.documents.store', $product), [
        'file' => UploadedFile::fake()->create('second-certificate.pdf', 120, 'application/pdf'),
        'type' => DocumentType::Certificate->value,
        'duplicate_strategy' => 'add_new',
    ])->assertSuccessful();

    expect($product->fresh()->currentDocuments()->where('type', DocumentType::Certificate->value)->count())
        ->toBe(2);
});

it('replaces the only current document automatically', function () {
    $product = Product::factory()->create();
    $original = Document::factory()->for($product)->create([
        'type' => DocumentType::TestReport,
    ]);

    $this->postJson(route('products.documents.store', $product), [
        'file' => UploadedFile::fake()->create('replacement-report.pdf', 120, 'application/pdf'),
        'type' => DocumentType::TestReport->value,
        'duplicate_strategy' => 'replace_existing',
    ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'documents')
        ->assertJsonPath('documents.0.version', 2);

    $replacement = $product->fresh()->currentDocuments()->first();

    expect($original->fresh()->is_current)->toBeFalse()
        ->and($replacement?->replaces_document_id)->toBe($original->id)
        ->and($replacement?->version)->toBe(2);
});

it('requires selecting which document to replace when multiple current documents share a type', function () {
    $product = Product::factory()->create();

    Document::factory()->count(2)->for($product)->create([
        'type' => DocumentType::Other,
    ]);

    $this->postJson(route('products.documents.store', $product), [
        'file' => UploadedFile::fake()->create('replacement.pdf', 120, 'application/pdf'),
        'type' => DocumentType::Other->value,
        'duplicate_strategy' => 'replace_existing',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['replace_document_id']);
});

it('includes current documents and version history on the product edit page', function () {
    $product = Product::factory()->create();
    $previousVersion = Document::factory()->for($product)->create([
        'type' => DocumentType::DeclarationOfConformity,
        'is_current' => false,
    ]);

    $currentVersion = Document::factory()->replacementOf($previousVersion)->create([
        'review_comment' => 'Latest declaration of conformity.',
    ]);

    $currentVersion->addMedia(
        UploadedFile::fake()->create('declaration.pdf', 120, 'application/pdf')
    )->toMediaCollection('file');

    $previousVersion->addMedia(
        UploadedFile::fake()->create('declaration-v1.pdf', 120, 'application/pdf')
    )->toMediaCollection('file');

    $this->get(route('products.edit', $product))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/edit')
            ->has('product.documents', 1)
            ->where('product.documents.0.version', 2)
            ->where('product.documents.0.history.0.version', 1)
            ->where('documentTypes.'.DocumentType::DeclarationOfConformity->value, 'Declaration of conformity')
        );
});

it('toggles public download on a document', function () {
    $product = Product::factory()->create();
    $document = Document::factory()->for($product)->create(['public_download' => false]);

    $this->patchJson(route('products.documents.toggle-public-download', [$product, $document]))
        ->assertSuccessful()
        ->assertJsonPath('public_download', true);

    expect($document->fresh()->public_download)->toBeTrue();

    $this->patchJson(route('products.documents.toggle-public-download', [$product, $document]))
        ->assertSuccessful()
        ->assertJsonPath('public_download', false);

    expect($document->fresh()->public_download)->toBeFalse();
});

it('returns 404 when toggling a document that belongs to another product', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $document = Document::factory()->for($otherProduct)->create();

    $this->patchJson(route('products.documents.toggle-public-download', [$product, $document]))
        ->assertNotFound();
});

it('requires authentication to toggle public download', function () {
    $product = Product::factory()->create();
    $document = Document::factory()->for($product)->create();

    auth()->logout();

    $this->patchJson(route('products.documents.toggle-public-download', [$product, $document]))
        ->assertUnauthorized();
});
