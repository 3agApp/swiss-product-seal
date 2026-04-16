<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\Distributor;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminReviewStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $awaitingReview = Product::where('status', ProductStatus::UnderReview)->count();
        $approvedToday = Product::where('status', ProductStatus::Approved)
            ->where('last_reviewed_at', '>=', now()->startOfDay())
            ->count();
        $totalDistributors = Distributor::count();
        $totalProducts = Product::count();

        return [
            Stat::make('Awaiting review', $awaitingReview)
                ->description('Products needing admin decision')
                ->color($awaitingReview > 0 ? 'warning' : 'success'),
            Stat::make('Approved today', $approvedToday)
                ->description('Products approved today')
                ->color('success'),
            Stat::make('Distributors', $totalDistributors)
                ->description('Total registered distributors')
                ->color('primary'),
            Stat::make('Total products', $totalProducts)
                ->description('Across all distributors')
                ->color('primary'),
        ];
    }
}
