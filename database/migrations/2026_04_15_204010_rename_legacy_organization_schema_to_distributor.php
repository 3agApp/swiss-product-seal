<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $distributorOwnedTables = [
        'brands',
        'categories',
        'documents',
        'invitations',
        'organization_user',
        'product_safety_entries',
        'products',
        'suppliers',
        'templates',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('organizations')) {
            return;
        }

        foreach ($this->distributorOwnedTables as $tableName) {
            $this->dropLegacyDistributorForeignKey($tableName, 'organization_id');
        }

        if (Schema::hasTable('organizations') && ! Schema::hasTable('distributors')) {
            Schema::rename('organizations', 'distributors');
        }

        $this->copyRows(
            sourceTable: 'organizations',
            targetTable: 'distributors',
            columns: ['id', 'name', 'slug', 'created_at', 'updated_at'],
        );

        if (Schema::hasTable('organization_user') && ! Schema::hasTable('distributor_user')) {
            Schema::rename('organization_user', 'distributor_user');
        }

        $this->copyRows(
            sourceTable: 'organization_user',
            targetTable: 'distributor_user',
            columns: ['id', 'user_id', 'organization_id as distributor_id', 'role', 'created_at', 'updated_at'],
        );

        foreach ($this->currentDistributorOwnedTables() as $tableName) {
            if (Schema::hasColumn($tableName, 'organization_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->renameColumn('organization_id', 'distributor_id');
                });
            }
        }

        foreach ($this->currentDistributorOwnedTables() as $tableName) {
            if (! Schema::hasColumn($tableName, 'distributor_id')) {
                continue;
            }

            if ($this->foreignKeyExists($tableName, 'distributor_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('distributor_id')->references('id')->on('distributors');
            });
        }

        if (Schema::hasTable('organization_user')) {
            Schema::drop('organization_user');
        }

        if (Schema::hasTable('organizations')) {
            Schema::drop('organizations');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('distributors')) {
            return;
        }

        foreach ($this->currentDistributorOwnedTables() as $tableName) {
            $this->dropLegacyDistributorForeignKey($tableName, 'distributor_id');
        }

        foreach ($this->currentDistributorOwnedTables() as $tableName) {
            if (Schema::hasColumn($tableName, 'distributor_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->renameColumn('distributor_id', 'organization_id');
                });
            }
        }

        if (Schema::hasTable('distributor_user') && ! Schema::hasTable('organization_user')) {
            Schema::rename('distributor_user', 'organization_user');
        }

        if (Schema::hasTable('distributors') && ! Schema::hasTable('organizations')) {
            Schema::rename('distributors', 'organizations');
        }

        foreach ($this->distributorOwnedTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }

            if ($this->foreignKeyExists($tableName, 'organization_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('organization_id')->references('id')->on('organizations');
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function currentDistributorOwnedTables(): array
    {
        return [
            'brands',
            'categories',
            'documents',
            'invitations',
            'distributor_user',
            'product_safety_entries',
            'products',
            'suppliers',
            'templates',
        ];
    }

    private function dropLegacyDistributorForeignKey(string $tableName, string $columnName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $foreignKeyName = $this->foreignKeyName($tableName, $columnName);

        if ($foreignKeyName === null) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName): void {
            $table->dropForeign($foreignKeyName);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function copyRows(string $sourceTable, string $targetTable, array $columns): void
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($targetTable)) {
            return;
        }

        $rows = DB::table($sourceTable)
            ->selectRaw(implode(', ', $columns))
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        if ($rows === []) {
            return;
        }

        DB::table($targetTable)->insertOrIgnore($rows);
    }

    private function foreignKeyExists(string $tableName, string $columnName): bool
    {
        return $this->foreignKeyName($tableName, $columnName) !== null;
    }

    private function foreignKeyName(string $tableName, string $columnName): ?string
    {
        $foreignKeyName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        return is_string($foreignKeyName) ? $foreignKeyName : null;
    }
};
