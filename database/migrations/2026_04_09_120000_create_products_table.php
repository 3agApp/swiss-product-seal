<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('internal_article_number')->nullable();
            $table->string('supplier_article_number')->nullable();
            $table->string('order_number')->nullable();
            $table->string('ean')->nullable();
            $table->foreignIdFor(Supplier::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Brand::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Category::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Template::class)->constrained()->restrictOnDelete();
            $table->string('status')->nullable()->default(ProductStatus::Open->value);
            $table->decimal('completeness_score', 5, 2)->default(0);
            $table->string('seal_status_override')->nullable();
            $table->string('kontor_id')->nullable();
            $table->timestamp('source_last_sync_at')->nullable();
            $table->uuid('public_uuid')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
