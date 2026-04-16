<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Distributor;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditDistributorProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Distributor Settings';
    }

    public static function canView(Model $tenant): bool
    {
        $role = Filament::auth()->user()->getRoleForDistributor($tenant);

        return $role?->canManageDistributor() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Distributor details')
                    ->description('Update how your distributor appears across the workspace and in tenant URLs.')
                    ->aside()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Distributor name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Acme Procurement GmbH')
                            ->helperText('Shown across the dashboard and member-facing distributor screens.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->label('Distributor slug')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. acme-procurement')
                            ->helperText('Used in the dashboard URL. Saving redirects you to the updated address.')
                            ->unique(Distributor::class, 'slug', ignorable: fn () => Filament::getTenant())
                            ->rules(['alpha_dash:ascii']),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getUrl(tenant: $this->tenant);
    }
}
