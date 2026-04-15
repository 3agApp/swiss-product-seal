<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Enums\SealStatus;
use App\Models\Product;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AdminProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getReviewSummarySection(),
                static::getClarificationNoteSection(),
                static::getProductDetailsSection(),
                static::getComplianceSection(),
                static::getQrCodeSection(),
            ]);
    }

    public static function getClarificationNoteSection(): Section
    {
        return Section::make('Clarification note')
            ->columnSpanFull()
            ->visible(fn (?Product $record): bool => $record instanceof Product
                && $record->status === ProductStatus::ClarificationNeeded
                && filled($record->clarification_note))
            ->schema([
                Callout::make('Pending clarification')
                    ->description(fn (?Product $record): string => $record?->clarification_note ?? '')
                    ->warning()
                    ->columnSpanFull(),
            ]);
    }

    public static function getReviewSummarySection(): Section
    {
        return Section::make('Review summary')
            ->description('Inspect the product status and compliance progress before making a review decision.')
            ->columnSpanFull()
            ->columns(3)
            ->schema([
                Callout::make(fn (?Product $record): string => $record?->status?->label() ?? 'Open')
                    ->description('Current status')
                    ->status(fn (?Product $record): string => static::getStatusColor($record))
                    ->columnSpan(1),
                Callout::make(fn (?Product $record): string => $record instanceof Product
                    ? number_format((float) $record->completeness_score, 0).'% complete'
                    : '0% complete')
                    ->description('Completeness score')
                    ->status(fn (?Product $record): string => static::getCompletenessColor($record))
                    ->columnSpan(1),
                Callout::make(fn (?Product $record): string => $record instanceof Product
                    ? $record->sealStatus()->label()
                    : 'Not verified')
                    ->description('Seal status')
                    ->status(fn (?Product $record): string => static::getSealStatusColor($record))
                    ->columnSpan(1),
            ]);
    }

    public static function getProductDetailsSection(): Section
    {
        return Section::make('Product details')
            ->description('Review the main catalog and ownership data for this product.')
            ->columnSpanFull()
            ->columns(3)
            ->schema([
                Placeholder::make('organization')
                    ->label('Organization')
                    ->content(fn (?Product $record): string => $record?->organization?->name ?? '—'),
                Placeholder::make('name')
                    ->label('Product name')
                    ->content(fn (?Product $record): string => $record?->name ?? '—'),
                Placeholder::make('category')
                    ->label('Category')
                    ->content(fn (?Product $record): string => $record?->category?->name ?? '—'),
                Placeholder::make('template')
                    ->label('Template')
                    ->content(fn (?Product $record): string => $record?->template?->name ?? '—'),
                Placeholder::make('supplier')
                    ->label('Supplier')
                    ->content(fn (?Product $record): string => $record?->supplier?->name ?? '—'),
                Placeholder::make('brand')
                    ->label('Brand')
                    ->content(fn (?Product $record): string => $record?->brand?->name ?? '—'),
                Placeholder::make('internal_article_number')
                    ->label('Internal article number')
                    ->content(fn (?Product $record): string => $record?->internal_article_number ?: '—'),
                Placeholder::make('supplier_article_number')
                    ->label('Supplier article number')
                    ->content(fn (?Product $record): string => $record?->supplier_article_number ?: '—'),
                Placeholder::make('ean')
                    ->label('EAN')
                    ->content(fn (?Product $record): string => $record?->ean ?: '—'),
            ]);
    }

    public static function getComplianceSection(): Section
    {
        return Section::make('Compliance findings')
            ->description('Review the current coverage and the remaining missing requirements.')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                Callout::make('Coverage')
                    ->description(fn (?Product $record): string => $record instanceof Product
                        ? $record->completenessSummary()
                        : 'No required documents or safety fields.')
                    ->info(),
                Callout::make('Missing requirements')
                    ->description(fn (?Product $record): string => $record instanceof Product
                        ? $record->missingRequirementsSummary()
                        : 'All required documents and safety fields are present.')
                    ->status(fn (?Product $record): string => $record instanceof Product && $record->calculateCompletenessScore() >= 100 ? 'success' : 'warning'),
            ]);
    }

    public static function getQrCodeSection(): Section
    {
        return Section::make('QR code')
            ->description('This QR code links to the public compliance page for this product.')
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                Placeholder::make('qr_code')
                    ->label('Product QR code')
                    ->content(fn (?Product $record): HtmlString => new HtmlString(
                        $record instanceof Product
                            ? '<div style="max-width:200px">'.$record->qrCodeSvg().'</div>'
                            : ''
                    ))
                    ->columnSpan(1),
                Placeholder::make('public_url')
                    ->label('Public page URL')
                    ->content(fn (?Product $record): HtmlString => new HtmlString(
                        $record instanceof Product
                            ? '<a href="'.e($record->publicUrl()).'" target="_blank" class="underline text-primary-600">'.e($record->publicUrl()).'</a>'
                            : '—'
                    ))
                    ->columnSpan(1),
            ]);
    }

    private static function getStatusColor(?Product $record): string
    {
        return match ($record?->status) {
            ProductStatus::Approved => 'success',
            ProductStatus::UnderReview => 'warning',
            ProductStatus::Rejected => 'danger',
            ProductStatus::ClarificationNeeded => 'warning',
            default => 'gray',
        };
    }

    private static function getCompletenessColor(?Product $record): string
    {
        $score = $record instanceof Product ? (float) $record->completeness_score : 0.0;

        return match (true) {
            $score >= 100 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }

    private static function getSealStatusColor(?Product $record): string
    {
        return match ($record?->sealStatus()) {
            SealStatus::Verified => 'success',
            SealStatus::InProgress => 'warning',
            default => 'gray',
        };
    }
}
