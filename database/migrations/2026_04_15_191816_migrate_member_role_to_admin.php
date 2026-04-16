<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('distributor_user')
            ->where('role', 'member')
            ->update(['role' => 'admin']);

        DB::table('invitations')
            ->where('role', 'member')
            ->whereNull('accepted_at')
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        // Intentionally irreversible — cannot distinguish which records were originally 'member'.
    }
};
