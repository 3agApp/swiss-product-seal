<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\EditAdminProduct;
use App\Filament\Resources\Products\Pages\ListAdminProducts;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\SafetyEntriesRelationManager;
use App\Filament\Resources\Products\Schemas\AdminProductReviewForm;
use App\Filament\Resources\Products\Tables\AdminProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AdminProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $slug = 'products';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AdminProductReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'documents' => DocumentsRelationManager::class,
            'safetyEntries' => SafetyEntriesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['distributor', 'category', 'template', 'supplier', 'brand']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminProducts::route('/'),
            'edit' => EditAdminProduct::route('/{record}/edit'),
        ];
    }
}
