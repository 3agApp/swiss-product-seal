<?php

namespace App\Filament\Resources\Categories\Resources\Templates\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        CheckboxList::make('required_document_types')
                            ->label('Required document types')
                            ->options(DocumentType::options())
                            ->columns(2)
                            ->gridDirection('row')
                            ->default([]),
                        CheckboxList::make('required_data_fields')
                            ->label('Required data fields')
                            ->options(self::requiredDataFieldOptions())
                            ->columns(2)
                            ->gridDirection('row')
                            ->default([]),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function requiredDataFieldOptions(): array
    {
        return [
            'safety_text' => 'Safety text',
            'warning_text' => 'Warning text',
            'age_grading' => 'Age grading',
            'material_information' => 'Material information',
            'usage_restrictions' => 'Usage restrictions',
            'safety_instructions' => 'Safety instructions',
            'additional_notes' => 'Additional notes',
        ];
    }
}
