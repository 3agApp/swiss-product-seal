<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Products\RelationManagers\Documents\DocumentForm;
use App\Filament\Resources\Products\RelationManagers\Documents\DocumentsTable;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentsRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Product) {
            return null;
        }

        return count($ownerRecord->missingRequiredDocumentTypes()) > 0 ? 'danger' : null;
    }

    public static function getMissingRequiredDocumentTypesMessage(Model $ownerRecord): ?string
    {
        if (! $ownerRecord instanceof Product) {
            return null;
        }

        $missingDocumentTypes = $ownerRecord->missingRequiredDocumentTypes();

        if (! filled($missingDocumentTypes)) {
            return null;
        }

        return 'Missing required document types: '.implode(', ', $missingDocumentTypes).'.';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return null;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product;
    }

    public function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table)
            ->description(fn (): ?string => static::getMissingRequiredDocumentTypesMessage($this->getOwnerRecord()))
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'organization_id' => $this->getOwnerRecord()->organization_id,
                    ]),
            ]);
    }
}
