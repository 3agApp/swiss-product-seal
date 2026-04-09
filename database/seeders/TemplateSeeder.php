<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::all()->each(function (Category $category): void {
            Template::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($category)
                ->create();
        });
    }
}
