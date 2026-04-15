<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ProductStatus|string|null $state): string => $state instanceof ProductStatus ? $state->label() : (ProductStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('completeness_score')
                    ->label('Completeness')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('seal_status')
                    ->label('Seal status')
                    ->badge()
                    ->state(fn (Product $record): string => $record->sealStatus()->label()),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatus::options()),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No products found')
            ->emptyStateDescription('Create your first product for this organization.')
            ->recordActions([
                Action::make('submitForReview')
                    ->label('Submit for review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record): bool => $record->canBeSubmittedForReview())
                    ->action(function (Product $record): void {
                        if (! $record->submitForReview()) {
                            Notification::make()
                                ->warning()
                                ->title('Product cannot be submitted')
                                ->body('Only products with 100% completeness that are not already under review or approved can be submitted.')
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Product submitted for review')
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('submitForReview')
                        ->label('Submit for review')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $submittedCount = 0;
                            $skippedCount = 0;

                            $records->each(function (Product $product) use (&$submittedCount, &$skippedCount): void {
                                if ($product->submitForReview()) {
                                    $submittedCount++;

                                    return;
                                }

                                $skippedCount++;
                            });

                            if ($submittedCount === 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('No products submitted for review')
                                    ->body('Select products with 100% completeness that are not already under review or approved.')
                                    ->send();

                                return;
                            }

                            $notification = Notification::make()
                                ->success()
                                ->title(str()->plural('product', $submittedCount, true).' submitted for review');

                            if ($skippedCount > 0) {
                                $notification->body(str()->plural('selected product', $skippedCount, true).' skipped because it was incomplete or already under review/approved.');
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
