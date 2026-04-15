<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\Suppliers\Resources\Brands\BrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BrandsRelationManager extends RelationManager
{
    protected static string $relationship = 'brands';

    protected static ?string $relatedResource = BrandResource::class;

    protected static ?string $title = 'Brands';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
