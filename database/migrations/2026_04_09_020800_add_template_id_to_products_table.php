<?php

use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignIdFor(Template::class)->nullable()->after('category_id')->constrained()->restrictOnDelete();
        });

        // Backfill: create a default template for each category that has products without a template.
        $productCategoryIds = DB::table('products')->whereNull('template_id')->distinct()->pluck('category_id');

        foreach ($productCategoryIds as $categoryId) {
            $templateId = DB::table('templates')->insertGetId([
                'category_id' => $categoryId,
                'name' => 'Default',
                'required_document_types' => '[]',
                'optional_document_types' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')
                ->where('category_id', $categoryId)
                ->whereNull('template_id')
                ->update(['template_id' => $templateId]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignIdFor(Template::class)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Template::class);
        });
    }
};
