<?php

namespace App\Notifications;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public ProductStatus $newStatus,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->body($this->body())
            ->icon($this->icon())
            ->status($this->color())
            ->getDatabaseMessage();
    }

    private function title(): string
    {
        return match ($this->newStatus) {
            ProductStatus::UnderReview => 'Product submitted for review',
            ProductStatus::Approved => 'Product approved',
            ProductStatus::Rejected => 'Product rejected',
            ProductStatus::ClarificationNeeded => 'Clarification requested',
            default => 'Product status changed',
        };
    }

    private function body(): string
    {
        $body = "\"{$this->product->name}\" status changed to {$this->newStatus->label()}.";

        if ($this->newStatus === ProductStatus::ClarificationNeeded && filled($this->product->clarification_note)) {
            $body .= ' Note: '.$this->product->clarification_note;
        }

        return $body;
    }

    private function icon(): string
    {
        return match ($this->newStatus) {
            ProductStatus::UnderReview => 'heroicon-o-paper-airplane',
            ProductStatus::Approved => 'heroicon-o-check-badge',
            ProductStatus::Rejected => 'heroicon-o-x-circle',
            ProductStatus::ClarificationNeeded => 'heroicon-o-chat-bubble-left-right',
            default => 'heroicon-o-arrow-path',
        };
    }

    private function color(): string
    {
        return match ($this->newStatus) {
            ProductStatus::Approved => 'success',
            ProductStatus::Rejected => 'danger',
            ProductStatus::UnderReview => 'warning',
            ProductStatus::ClarificationNeeded => 'warning',
            default => 'info',
        };
    }
}
