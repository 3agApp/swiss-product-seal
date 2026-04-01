<?php

namespace App\Enums;

enum UserRole: string
{
    case Supplier = 'supplier';
    case InternalEmployee = 'internal_employee';
    case ComplianceManager = 'compliance_manager';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => 'Supplier',
            self::InternalEmployee => 'Internal employee',
            self::ComplianceManager => 'Compliance manager',
            self::Administrator => 'Administrator',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(
                static fn (self $role): array => [$role->value, $role->label()],
                self::cases(),
            ),
            1,
            0,
        );
    }
}
