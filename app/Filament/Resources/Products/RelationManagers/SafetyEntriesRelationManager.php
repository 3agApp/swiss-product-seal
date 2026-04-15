<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductSafetyEntry;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SafetyEntriesRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'safetyEntries';

    protected static ?string $title = 'Safety information';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('safety_text')
                    ->label('Safety text')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'safety_text'))
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('warning_text')
                    ->label('Warning text')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'warning_text'))
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('age_grading')
                    ->label('Age grading')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'age_grading'))
                    ->rows(3),
                Textarea::make('material_information')
                    ->label('Material information')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'material_information'))
                    ->rows(3),
                Textarea::make('usage_restrictions')
                    ->label('Usage restrictions')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'usage_restrictions'))
                    ->rows(3),
                Textarea::make('safety_instructions')
                    ->label('Safety instructions')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'safety_instructions'))
                    ->rows(3),
                Textarea::make('additional_notes')
                    ->label('Additional notes')
                    ->helperText(fn (): ?string => ProductSafetyEntry::templateFieldHelperTextForProduct($this->getOwnerRecord(), 'additional_notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product.template'))
            ->columns([
                TextColumn::make('template_completion')
                    ->label('Template coverage')
                    ->state(fn (ProductSafetyEntry $record): string => $record->templateCompletionStatus())
                    ->badge()
                    ->icon(fn (ProductSafetyEntry $record): string => match ($record->templateCompletionStatus()) {
                        'Complete' => 'heroicon-o-check-badge',
                        'Incomplete' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->color(fn (ProductSafetyEntry $record): string => match ($record->templateCompletionStatus()) {
                        'Complete' => 'success',
                        'Incomplete' => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (ProductSafetyEntry $record): string => $record->templateCompletionSummary())
                    ->wrap(),
                TextColumn::make('required_template_fields')
                    ->label('Required by template')
                    ->state(fn (ProductSafetyEntry $record): string => $record->requiredTemplateFieldsSummary())
                    ->wrap(),
                TextColumn::make('missing_required_template_fields')
                    ->label('Missing')
                    ->state(fn (ProductSafetyEntry $record): string => $record->missingRequiredTemplateFieldsSummary())
                    ->color(fn (ProductSafetyEntry $record): string => filled($record->missingRequiredTemplateFields()) ? 'danger' : 'success')
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add safety information')
                    ->visible(fn (): bool => $this->getOwnerRecord()->safetyEntries()->doesntExist())
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'organization_id' => $this->getOwnerRecord()->organization_id,
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No safety information added')
            ->emptyStateDescription('Create the safety entry for this product.')
            ->paginated(false);
    }
}
