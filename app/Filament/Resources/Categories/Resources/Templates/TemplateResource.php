<?php

namespace App\Filament\Resources\Categories\Resources\Templates;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Categories\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Categories\Resources\Templates\Pages\ListTemplates;
use App\Filament\Resources\Categories\Resources\Templates\Schemas\TemplateForm;
use App\Filament\Resources\Categories\Resources\Templates\Tables\TemplatesTable;
use App\Models\Template;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $parentResource = CategoryResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
