<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All products'),
            'completed' => Tab::make('Completed products')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->completed()),
            'under_review' => Tab::make('Under review')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ProductStatus::UnderReview)),
            'incomplete' => Tab::make('Incomplete')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->incomplete()),
        ];
    }
}
