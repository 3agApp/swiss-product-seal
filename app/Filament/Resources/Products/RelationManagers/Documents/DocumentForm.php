<?php

namespace App\Filament\Resources\Products\RelationManagers\Documents;

use App\Enums\DocumentType;
use App\Models\Document;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type')
                    ->options(DocumentType::options())
                    ->native(false)
                    ->required()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('files')
                    ->label('Files')
                    ->collection(Document::FILE_COLLECTION)
                    ->multiple()
                    ->reorderable()
                    ->downloadable()
                    ->openable()
                    ->panelLayout('grid')
                    ->helperText('You can upload and reorder multiple files for the same document type.')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
