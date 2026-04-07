<?php

namespace App\Enums;

enum DocumentType: string
{
    case TestReport = 'test_report';
    case DeclarationOfConformity = 'declaration_of_conformity';
    case Manual = 'manual';
    case Certificate = 'certificate';
    case ProductImage = 'product_image';
    case SafetyImage = 'safety_image';
    case RegulatoryDocument = 'regulatory_document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TestReport => 'Test report',
            self::DeclarationOfConformity => 'Declaration of conformity',
            self::Manual => 'Manual',
            self::Certificate => 'Certificate',
            self::ProductImage => 'Product image',
            self::SafetyImage => 'Safety image',
            self::RegulatoryDocument => 'Regulatory document',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(
                static fn (self $type): array => [$type->value, $type->label()],
                self::cases(),
            ),
            1,
            0,
        );
    }
}
