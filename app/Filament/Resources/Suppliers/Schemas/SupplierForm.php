<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SupplierForm
{
    /**
     * @return array<int, TextInput|Toggle>
     */
    public static function getFields(bool $ignoreRecord = true): array
    {
        return [
            TextInput::make('supplier_code')
                ->label('Supplier code')
                ->required()
                ->unique(
                    table: Supplier::class,
                    column: 'supplier_code',
                    ignoreRecord: $ignoreRecord,
                    modifyRuleUsing: fn (Unique $rule): Unique => static::scopeUniqueRuleToTenant($rule),
                )
                ->maxLength(255),
            TextInput::make('name')
                ->required()
                ->unique(
                    table: Supplier::class,
                    column: 'name',
                    ignoreRecord: $ignoreRecord,
                    modifyRuleUsing: fn (Unique $rule): Unique => static::scopeUniqueRuleToTenant($rule),
                )
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->tel()
                ->maxLength(255),
            TextInput::make('country')
                ->maxLength(255),
            TextInput::make('address')
                ->columnSpanFull()
                ->maxLength(255),
            Toggle::make('active')
                ->default(true),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supplier information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(static::getFields()),
            ]);
    }

    private static function scopeUniqueRuleToTenant(Unique $rule): Unique
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! filled($tenantId)) {
            return $rule;
        }

        return $rule->where('distributor_id', $tenantId);
    }
}
