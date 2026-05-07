<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeDuplicateSuppliers();

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->unique(['distributor_id', 'supplier_code']);
            $table->unique(['distributor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropUnique(['distributor_id', 'supplier_code']);
            $table->dropUnique(['distributor_id', 'name']);
        });
    }

    private function normalizeDuplicateSuppliers(): void
    {
        $this->normalizeDuplicateColumn('supplier_code', '-duplicate-');
        $this->normalizeDuplicateColumn('name', ' (duplicate ');
    }

    private function normalizeDuplicateColumn(string $column, string $suffixPrefix): void
    {
        DB::table('suppliers')
            ->select(['id', 'distributor_id', $column])
            ->orderBy('distributor_id')
            ->orderBy($column)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $supplier): string => $supplier->distributor_id.'|'.$supplier->{$column})
            ->each(function ($suppliers) use ($column, $suffixPrefix): void {
                if ($suppliers->count() < 2) {
                    return;
                }

                $suppliers->slice(1)->each(function (object $supplier) use ($column, $suffixPrefix): void {
                    $suffix = $column === 'name'
                        ? $suffixPrefix.$supplier->id.')'
                        : $suffixPrefix.$supplier->id;

                    $maxLength = 255 - strlen($suffix);
                    $normalizedValue = mb_substr((string) $supplier->{$column}, 0, $maxLength).$suffix;

                    DB::table('suppliers')
                        ->where('id', $supplier->id)
                        ->update([
                            $column => $normalizedValue,
                            'updated_at' => now(),
                        ]);
                });
            });
    }
};
