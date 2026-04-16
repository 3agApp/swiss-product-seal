<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdminProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('distributor.name')
                    ->label('Distributor')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ProductStatus|string|null $state): string => $state instanceof ProductStatus ? $state->label() : (ProductStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('completeness_score')
                    ->label('Completeness')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatus::options()),
                SelectFilter::make('distributor')
                    ->relationship('distributor', 'name'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No products found')
            ->emptyStateDescription('Products submitted by distributors will appear here for admin review.')
            ->recordActions([
                EditAction::make()
                    ->label('Review'),
            ]);
    }
}
