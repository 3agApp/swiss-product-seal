<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\AdminProductResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAdminProducts extends ListRecords
{
    protected static string $resource = AdminProductResource::class;

    public function getTabs(): array
    {
        return [
            'awaiting_review' => Tab::make('Awaiting review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::UnderReview))
                ->icon('heroicon-o-clock'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Approved))
                ->icon('heroicon-o-check-badge'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Rejected))
                ->icon('heroicon-o-x-circle'),
            'all' => Tab::make('All products'),
        ];
    }
}
