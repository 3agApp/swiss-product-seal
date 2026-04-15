<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\SafetyEntriesRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'documents' => DocumentsRelationManager::class,
            'safetyEntries' => SafetyEntriesRelationManager::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateFormData(array $data): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return $data;
        }

        $data['organization_id'] = $tenant->getKey();

        static::ensureCategoryBelongsToTenant($tenant, $data['category_id'] ?? null);
        static::ensureTemplateBelongsToCategory($tenant, $data['template_id'] ?? null, $data['category_id'] ?? null);
        static::ensureSupplierBelongsToTenant($tenant, $data['supplier_id'] ?? null);
        static::ensureBrandBelongsToSupplier($tenant, $data['brand_id'] ?? null, $data['supplier_id'] ?? null);

        return $data;
    }

    private static function ensureCategoryBelongsToTenant(Organization $tenant, mixed $categoryId): void
    {
        if (! filled($categoryId)) {
            return;
        }

        $exists = Category::query()
            ->whereBelongsTo($tenant)
            ->whereKey($categoryId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'category_id' => 'Select a valid category for this organization.',
            ]);
        }
    }

    private static function ensureTemplateBelongsToCategory(Organization $tenant, mixed $templateId, mixed $categoryId): void
    {
        if (! filled($templateId) || ! filled($categoryId)) {
            return;
        }

        $exists = Template::query()
            ->whereBelongsTo($tenant)
            ->whereKey($templateId)
            ->where('category_id', $categoryId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'template_id' => 'Select a template that belongs to the chosen category.',
            ]);
        }
    }

    private static function ensureSupplierBelongsToTenant(Organization $tenant, mixed $supplierId): void
    {
        if (! filled($supplierId)) {
            return;
        }

        $exists = Supplier::query()
            ->whereBelongsTo($tenant)
            ->whereKey($supplierId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Select a valid supplier for this organization.',
            ]);
        }
    }

    private static function ensureBrandBelongsToSupplier(Organization $tenant, mixed $brandId, mixed $supplierId): void
    {
        if (! filled($brandId)) {
            return;
        }

        $brandQuery = Brand::query()
            ->whereBelongsTo($tenant)
            ->whereKey($brandId);

        if (filled($supplierId)) {
            $brandQuery->where('supplier_id', $supplierId);
        }

        if (! $brandQuery->exists()) {
            throw ValidationException::withMessages([
                'brand_id' => 'Select a brand that belongs to the chosen supplier.',
            ]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
