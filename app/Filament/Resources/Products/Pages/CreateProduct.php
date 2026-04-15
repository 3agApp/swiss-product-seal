<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Wizard\Step;

class CreateProduct extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProductResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Category')
                ->description('Start by choosing the category for this product.')
                ->schema([
                    ProductForm::getCategorySelectionSection(),
                ]),
            Step::make('Template')
                ->description('Pick the template that matches the selected category.')
                ->schema([
                    ProductForm::getTemplateSelectionSection(),
                ]),
            Step::make('Details')
                ->description('Fill in the remaining product details.')
                ->schema([
                    ProductForm::getProductIdentitySection(),
                    ProductForm::getSupplyChainSection(),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = ProductResource::mutateFormData($data);
        $data['status'] = ProductStatus::Open->value;

        return $data;
    }
}
