<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SupplierSeeder::class,
            CategorySeeder::class,
            TemplateSeeder::class,
            ProductSeeder::class,
            DocumentSeeder::class,
            ProductSafetyEntrySeeder::class,
        ]);
    }
}
