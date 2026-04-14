<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'Org Admin',
            'email' => 'admin@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Org Member',
            'email' => 'member@example.com',
        ]);

        $acme = Organization::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $globex = Organization::factory()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        $owner->organizations()->attach($acme, ['role' => Role::Owner->value]);
        $admin->organizations()->attach($acme, ['role' => Role::Admin->value]);
        $member->organizations()->attach($acme, ['role' => Role::Member->value]);
        $admin->organizations()->attach($globex, ['role' => Role::Owner->value]);

        $this->seedOrganizationCatalog($acme);
        $this->seedOrganizationCatalog($globex);
    }

    private function seedOrganizationCatalog(Organization $organization): void
    {
        $categories = Category::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $suppliers = Supplier::factory()
            ->count(3)
            ->create([
                'organization_id' => $organization->id,
            ]);

        $categories->each(function (Category $category) use ($organization, $suppliers): void {
            $templates = Template::factory()
                ->count(2)
                ->create([
                    'organization_id' => $organization->id,
                    'category_id' => $category->id,
                ]);

            $suppliers->each(function (Supplier $supplier) use ($organization, $category, $templates): void {
                $brands = Brand::factory()
                    ->count(2)
                    ->create([
                        'organization_id' => $organization->id,
                        'supplier_id' => $supplier->id,
                    ]);

                Product::factory()
                    ->count(2)
                    ->create([
                        'organization_id' => $organization->id,
                        'category_id' => $category->id,
                        'template_id' => $templates->random()->id,
                        'supplier_id' => $supplier->id,
                        'brand_id' => $brands->random()->id,
                    ]);
            });
        });
    }
}
