<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case UnderReview = 'under_review';
    case ClarificationNeeded = 'clarification_needed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::UnderReview => 'Under review',
            self::ClarificationNeeded => 'Clarification needed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(
                static fn (self $status): array => [$status->value, $status->label()],
                self::cases(),
            ),
            1,
            0,
        );
    }
}
