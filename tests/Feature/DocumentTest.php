<?php

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('creates versioned product documents with generated version groups', function () {
    $product = Product::factory()->create();

    $document = Document::factory()->for($product)->create([
        'type' => DocumentType::Manual,
        'expiry_date' => '2027-04-07',
        'review_comment' => 'Initial product manual.',
    ]);

    $this->assertModelExists($document);
    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'product_id' => $product->id,
        'type' => DocumentType::Manual->value,
        'version' => 1,
        'is_current' => 1,
    ]);

    expect($document->type)->toBe(DocumentType::Manual)
        ->and($document->expiry_date?->toDateString())->toBe('2027-04-07')
        ->and($document->version_group_uuid)->not->toBeEmpty();
});

it('tracks document replacements as version history', function () {
    $product = Product::factory()->create();

    $original = Document::factory()->for($product)->create([
        'type' => DocumentType::Certificate,
        'is_current' => false,
    ]);

    $replacement = Document::factory()->replacementOf($original)->create([
        'review_comment' => 'Updated certificate.',
    ]);

    expect($replacement->replacesDocument->is($original))->toBeTrue()
        ->and($replacement->version)->toBe(2)
        ->and($replacement->version_group_uuid)->toBe($original->version_group_uuid)
        ->and($original->fresh()->replacementDocuments)->toHaveCount(1)
        ->and($product->documents()->count())->toBe(2)
        ->and($product->currentDocuments()->count())->toBe(1);
});

it('stores uploaded media on a document record', function () {
    Storage::fake('public');

    $document = Document::factory()->create([
        'type' => DocumentType::RegulatoryDocument,
    ]);

    $document->addMedia(
        UploadedFile::fake()->create('regulatory-document.pdf', 200, 'application/pdf')
    )->toMediaCollection('file');

    expect($document->getMedia('file'))->toHaveCount(1)
        ->and($document->getFirstMedia('file')?->file_name)->toBe('regulatory-document.pdf');
});
