<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = ProductResource::mutateFormData($data);
        $data['status'] = $this->record->status?->value;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->canBeSubmittedForReview())
                ->action(function (): void {
                    if (! ($this->record instanceof Product)) {
                        return;
                    }

                    if (! $this->record->submitForReview()) {
                        Notification::make()
                            ->warning()
                            ->title('Product cannot be submitted')
                            ->body('Only products with 100% completeness that are not already under review or approved can be submitted.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Product submitted for review')
                        ->send();

                    $this->refreshFormData(['status', 'completeness_score']);
                }),
            DeleteAction::make(),
        ];
    }
}
