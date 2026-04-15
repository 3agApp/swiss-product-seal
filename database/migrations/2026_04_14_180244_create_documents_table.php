<?php

use App\Enums\DocumentType;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->constrained();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->string('type')->default(DocumentType::Other->value);
            $table->timestamps();

            $table->index(['organization_id', 'product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
