<?php

use App\Enums\DocumentType;
use App\Enums\Role;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\RelationManagers\Documents\DocumentForm;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Models\Brand;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);

    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $this->brand = Brand::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->product = Product::factory()->create([
        'distributor_id' => $this->distributor->id,
        'supplier_id' => $this->supplier->id,
        'brand_id' => $this->brand->id,
    ]);

    $this->document = Document::factory()->create([
        'product_id' => $this->product->id,
        'type' => DocumentType::Manual,
    ]);
});

test('a document belongs to a product and can keep multiple media files', function () {
    Storage::fake('public');
    config()->set('media-library.disk_name', 'public');

    $document = Document::factory()->create([
        'product_id' => $this->product->id,
        'type' => DocumentType::Certificate,
    ]);

    $firstFile = $document
        ->addMedia(UploadedFile::fake()->create('certificate.pdf', 128, 'application/pdf'))
        ->toMediaCollection(Document::FILE_COLLECTION);

    $secondFile = $document
        ->addMedia(UploadedFile::fake()->create('appendix.pdf', 256, 'application/pdf'))
        ->toMediaCollection(Document::FILE_COLLECTION);

    Media::setNewOrder([$secondFile->getKey(), $firstFile->getKey()]);

    $document->refresh();

    $fileNames = $document->getMedia(Document::FILE_COLLECTION)
        ->pluck('file_name')
        ->all();

    expect($document->product->is($this->product))->toBeTrue()
        ->and($document->type)->toBe(DocumentType::Certificate)
        ->and($document->getMedia(Document::FILE_COLLECTION))->toHaveCount(2)
        ->and($fileNames)->toBe(['appendix.pdf', 'certificate.pdf']);
});

test('product resource registers documents as an inline relation manager', function () {
    $relations = ProductResource::getRelations();
    $documents = $this->product->documents();

    expect($relations)->toHaveKey('documents', DocumentsRelationManager::class)
        ->and($documents)->toBeInstanceOf(HasMany::class)
        ->and($documents->getRelated())->toBeInstanceOf(Document::class)
        ->and(DocumentsRelationManager::getTitle($this->product, EditProduct::class))->toBe('Documents')
        ->and(DocumentsRelationManager::canViewForRecord($this->product, EditProduct::class))->toBeTrue();
});

test('document form uses full width type and grid file upload layout', function () {
    $schema = DocumentForm::configure(Schema::make());
    $components = array_values($schema->getComponents());

    expect($components)->toHaveCount(2)
        ->and($components[0])->toBeInstanceOf(Select::class)
        ->and($components[0]->getName())->toBe('type')
        ->and($components[0]->getColumnSpan())->toBe(['default' => 'full'])
        ->and($components[1])->toBeInstanceOf(SpatieMediaLibraryFileUpload::class)
        ->and($components[1]->getName())->toBe('files')
        ->and($components[1]->getColumnSpan())->toBe(['default' => 'full'])
        ->and($components[1]->getPanelLayout())->toBe('grid');
});
