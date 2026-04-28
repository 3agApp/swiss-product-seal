<?php

use App\Models\Distributor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Distributor::class);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Distributor::class);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignIdFor(Distributor::class)->after('id')->constrained();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->foreignIdFor(Distributor::class)->after('id')->constrained();
        });
    }
};
