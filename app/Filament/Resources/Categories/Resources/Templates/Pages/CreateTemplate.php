<?php

namespace App\Filament\Resources\Categories\Resources\Templates\Pages;

use App\Filament\Resources\Categories\Resources\Templates\TemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = $this->getParentRecord()->organization_id;

        return $data;
    }
}
