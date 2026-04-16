<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\Role;
use App\Models\Distributor;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterDistributor extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Distributor';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Distributor::class, 'slug')
                    ->rules(['alpha_dash:ascii']),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $distributor = Distributor::create($data);

        $distributor->members()->attach(Filament::auth()->id(), [
            'role' => Role::Owner->value,
        ]);

        return $distributor;
    }
}
