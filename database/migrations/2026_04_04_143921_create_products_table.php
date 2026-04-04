<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('internal_article_number')->nullable();
            $table->string('supplier_article_number')->nullable();
            $table->string('order_number')->nullable();
            $table->string('ean')->nullable();
            $table->foreignIdFor(Supplier::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Brand::class)->nullable()->constrained()->nullOnDelete();
            $table->string('status')->nullable()->default(ProductStatus::Open->value);
            $table->string('kontor_id')->nullable();
            $table->timestamp('source_last_sync_at')->nullable();
            $table->uuid('public_uuid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
