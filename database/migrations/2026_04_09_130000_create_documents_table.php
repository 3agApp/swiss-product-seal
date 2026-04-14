<?php

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
            $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->uuid('version_group_uuid');
            $table->foreignId('replaces_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->date('expiry_date')->nullable();
            $table->text('review_comment')->nullable();
            $table->boolean('is_current')->default(true);
            $table->boolean('public_download')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index(['product_id', 'type', 'is_current']);
            $table->unique(['version_group_uuid', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
