<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\DocumentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\Template;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getCategorySelectionSection(),
                static::getTemplateSelectionSection(),
                static::getProductIdentitySection(),
                static::getSupplyChainSection(),
            ]);
    }

    public static function getCategorySelectionSection(): Section
    {
        return Section::make('Category')
            ->description('Choose the catalog category first so template suggestions stay relevant.')
            ->columnSpanFull()
            ->schema([
                static::getCategoryField(),
            ]);
    }

    public static function getCategoryField(): Radio
    {
        return Radio::make('category_id')
            ->label('Category')
            ->options(fn (): array => static::getCategoryOptions())
            ->descriptions(fn (): array => static::getCategoryDescriptions())
            ->helperText('Categories are managed from the admin panel for this organization.')
            ->live()
            ->required()
            ->columnSpanFull()
            ->afterStateUpdated(function (Set $set): void {
                $set('template_id', null);
            });
    }

    public static function getTemplateSelectionSection(): Section
    {
        return Section::make('Template')
            ->description('Pick the compliance template that applies to the chosen category.')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                static::getTemplateField(),
                static::getTemplateRequirementsPlaceholder(),
            ]);
    }

    public static function getTemplateField(): Select
    {
        return Select::make('template_id')
            ->label('Template')
            ->options(fn (Get $get): array => static::getTemplateOptions($get('category_id')))
            ->helperText('Templates are filtered to the selected category.')
            ->native(false)
            ->preload()
            ->searchable()
            ->live()
            ->required()
            ->disabled(fn (Get $get): bool => blank($get('category_id')));
    }

    public static function getTemplateRequirementsPlaceholder(): Placeholder
    {
        return Placeholder::make('template_requirements')
            ->label('Template requirements')
            ->columnSpan(1)
            ->content(fn (Get $get): string => static::getTemplateRequirementsSummary($get('template_id')));
    }

    /**
     * @return array<int, TextInput|Select>
     */
    public static function getDetailFields(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('supplier_id')
                ->label('Supplier')
                ->options(fn (): array => static::getSupplierOptions())
                ->native(false)
                ->preload()
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('brand_id', null);
                }),
            Select::make('brand_id')
                ->label('Brand')
                ->options(fn (Get $get): array => static::getBrandOptions($get('supplier_id')))
                ->helperText('Brands are filtered to the selected supplier.')
                ->native(false)
                ->preload()
                ->searchable()
                ->disabled(fn (Get $get): bool => blank($get('supplier_id'))),
            TextInput::make('internal_article_number')
                ->label('Internal article number')
                ->maxLength(255),
            TextInput::make('supplier_article_number')
                ->label('Supplier article number')
                ->maxLength(255),
            TextInput::make('order_number')
                ->label('Order number')
                ->maxLength(255),
            TextInput::make('ean')
                ->label('EAN')
                ->maxLength(255),
        ];
    }

    public static function getProductIdentitySection(): Section
    {
        return Section::make('Product details')
            ->description('Capture the product name and internal identifiers.')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('internal_article_number')
                    ->label('Internal article number')
                    ->maxLength(255),
                TextInput::make('supplier_article_number')
                    ->label('Supplier article number')
                    ->maxLength(255),
                TextInput::make('order_number')
                    ->label('Order number')
                    ->maxLength(255),
                TextInput::make('ean')
                    ->label('EAN')
                    ->maxLength(255),
            ]);
    }

    public static function getSupplyChainSection(): Section
    {
        return Section::make('Supplier mapping')
            ->description('Link the product to the supplier and brand that own the commercial data.')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn (): array => static::getSupplierOptions())
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('brand_id', null);
                    }),
                Select::make('brand_id')
                    ->label('Brand')
                    ->options(fn (Get $get): array => static::getBrandOptions($get('supplier_id')))
                    ->helperText('Brands are filtered to the selected supplier.')
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('supplier_id'))),
            ]);
    }

    /**
     * @return array<int, array-key>
     */
    private static function getCategoryOptions(): array
    {
        return static::getCategories()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, array-key>
     */
    private static function getCategoryDescriptions(): array
    {
        return static::getCategories()
            ->mapWithKeys(function (Category $category): array {
                $description = filled($category->description)
                    ? $category->description
                    : 'No description available yet.';

                $templateCount = $category->templates_count === 1
                    ? '1 template available'
                    : "{$category->templates_count} templates available";

                return [$category->getKey() => "{$description} {$templateCount}."];
            })
            ->all();
    }

    /**
     * @return array<int, array-key>
     */
    private static function getTemplateOptions(mixed $categoryId): array
    {
        $tenant = static::getTenant();

        if (! $tenant instanceof Organization || blank($categoryId)) {
            return [];
        }

        return Template::query()
            ->whereBelongsTo($tenant)
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, array-key>
     */
    private static function getSupplierOptions(): array
    {
        $tenant = static::getTenant();

        if (! $tenant instanceof Organization) {
            return [];
        }

        return Supplier::query()
            ->whereBelongsTo($tenant)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, array-key>
     */
    private static function getBrandOptions(mixed $supplierId): array
    {
        $tenant = static::getTenant();

        if (! $tenant instanceof Organization || blank($supplierId)) {
            return [];
        }

        return Brand::query()
            ->whereBelongsTo($tenant)
            ->where('supplier_id', $supplierId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function getTemplateRequirementsSummary(mixed $templateId): string
    {
        $tenant = static::getTenant();

        if (! $tenant instanceof Organization || blank($templateId)) {
            return 'Choose a template to preview its required documents and data fields.';
        }

        $template = Template::query()
            ->whereBelongsTo($tenant)
            ->find($templateId);

        if (! $template instanceof Template) {
            return 'Choose a template to preview its required documents and data fields.';
        }

        $documentTypes = collect($template->required_document_types ?? [])
            ->map(fn (string $value): string => DocumentType::options()[$value] ?? $value)
            ->values()
            ->implode(', ');

        $dataFields = collect($template->required_data_fields ?? [])
            ->map(fn (string $value): string => static::requiredDataFieldOptions()[$value] ?? $value)
            ->values()
            ->implode(', ');

        $documentSummary = filled($documentTypes) ? $documentTypes : 'No required documents';
        $dataFieldSummary = filled($dataFields) ? $dataFields : 'No required data fields';

        return "Required documents: {$documentSummary}. Required data fields: {$dataFieldSummary}.";
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

    private static function getTenant(): ?Organization
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization ? $tenant : null;
    }

    /**
     * @return Collection<int, Category>
     */
    private static function getCategories(): Collection
    {
        $tenant = static::getTenant();

        if (! $tenant instanceof Organization) {
            return collect();
        }

        return Category::query()
            ->whereBelongsTo($tenant)
            ->withCount('templates')
            ->orderBy('name')
            ->get();
    }
}
