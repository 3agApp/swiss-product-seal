<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Tenancy\EditDistributorProfile;
use App\Filament\Pages\Tenancy\RegisterDistributor;
use App\Filament\Resources\DistributorMemberResource;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Suppliers\Resources\Brands\BrandResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Distributor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->brandName(config('app.name'))
            ->login()
            ->registration(Register::class)
            ->emailVerification()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->tenant(Distributor::class, slugAttribute: 'slug', ownershipRelationship: 'distributor')
            ->tenantRegistration(RegisterDistributor::class)
            ->tenantProfile(EditDistributorProfile::class)
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Catalog')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Distributor')
                    ->collapsed(),
            ])
            ->resources([
                SupplierResource::class,
                BrandResource::class,
                ProductResource::class,
                DistributorMemberResource::class,
                InvitationResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
