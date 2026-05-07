<?php

namespace App\Filament\Resources\Suppliers\Resources\Brands\Schemas;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    /**
     * @return array<int, TextInput>
     */
    public static function getFields(?Closure $configureNameField = null): array
    {
        $nameField = TextInput::make('name')
            ->required()
            ->maxLength(255);

        if ($configureNameField instanceof Closure) {
            $nameField = $configureNameField($nameField) ?? $nameField;
        }

        return [$nameField];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(static::getFields()),
            ]);
    }
}
