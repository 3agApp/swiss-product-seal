<?php

namespace App\Filament\Resources\Categories\Resources\Templates\Pages;

use App\Filament\Resources\Categories\Resources\Templates\TemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
