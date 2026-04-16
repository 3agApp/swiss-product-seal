<?php

use App\Models\Distributor;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_safety_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Distributor::class)->constrained();
            $table->foreignIdFor(Product::class)->unique()->constrained()->cascadeOnDelete();
            $table->text('safety_text')->nullable();
            $table->text('warning_text')->nullable();
            $table->text('age_grading')->nullable();
            $table->text('material_information')->nullable();
            $table->text('usage_restrictions')->nullable();
            $table->text('safety_instructions')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_safety_entries');
    }
};
