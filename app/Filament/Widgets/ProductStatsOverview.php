<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [];
        }

        $query = Product::query()->where('distributor_id', $tenant->id);

        $total = $query->count();
        $approved = (clone $query)->where('status', ProductStatus::Approved)->count();
        $underReview = (clone $query)->where('status', ProductStatus::UnderReview)->count();
        $incomplete = (clone $query)->where('completeness_score', '<', 100)->count();
        $avgScore = (clone $query)->avg('completeness_score');

        return [
            Stat::make('Total products', $total)
                ->description($approved.' approved')
                ->color('primary'),
            Stat::make('Under review', $underReview)
                ->description('Awaiting admin decision')
                ->color($underReview > 0 ? 'warning' : 'success'),
            Stat::make('Incomplete', $incomplete)
                ->description('Need more documents or data')
                ->color($incomplete > 0 ? 'danger' : 'success'),
            Stat::make('Avg. completeness', number_format((float) $avgScore, 0).'%')
                ->description('Across all products')
                ->color((float) $avgScore >= 80 ? 'success' : 'warning'),
        ];
    }
}
