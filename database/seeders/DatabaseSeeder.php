<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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

        $acme = Distributor::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $globex = Distributor::factory()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        $owner->distributors()->attach($acme, ['role' => Role::Owner->value]);
        $admin->distributors()->attach($acme, ['role' => Role::Admin->value]);
        $admin->distributors()->attach($globex, ['role' => Role::Owner->value]);

        $this->seedDistributorScenario($acme, $this->acmeScenario());
        $this->seedDistributorScenario($globex, $this->globexScenario());
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function seedDistributorScenario(Distributor $distributor, array $scenario): void
    {
        $categories = collect($scenario['categories'])
            ->mapWithKeys(function (array $categoryData) use ($distributor): array {
                $category = Category::query()->create([
                    'distributor_id' => $distributor->id,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                ]);

                return [$categoryData['key'] => $category];
            });

        $templates = collect($scenario['categories'])
            ->flatMap(function (array $categoryData) use ($distributor, $categories): Collection {
                /** @var Category $category */
                $category = $categories->get($categoryData['key']);

                return collect($categoryData['templates'])
                    ->mapWithKeys(function (array $templateData) use ($distributor, $category): array {
                        $template = Template::query()->create([
                            'distributor_id' => $distributor->id,
                            'category_id' => $category->id,
                            'name' => $templateData['name'],
                            'required_document_types' => $templateData['required_document_types'],
                            'required_data_fields' => $templateData['required_data_fields'],
                        ]);

                        return [$templateData['key'] => $template];
                    });
            });

        $suppliers = collect($scenario['suppliers'])
            ->mapWithKeys(function (array $supplierData) use ($distributor): array {
                $supplier = Supplier::query()->create([
                    'distributor_id' => $distributor->id,
                    'supplier_code' => $supplierData['supplier_code'],
                    'name' => $supplierData['name'],
                    'address' => $supplierData['address'],
                    'country' => $supplierData['country'],
                    'email' => $supplierData['email'],
                    'phone' => $supplierData['phone'],
                    'active' => $supplierData['active'],
                ]);

                $brands = collect($supplierData['brands'])
                    ->mapWithKeys(function (array $brandData) use ($distributor, $supplier): array {
                        $brand = Brand::query()->create([
                            'distributor_id' => $distributor->id,
                            'supplier_id' => $supplier->id,
                            'name' => $brandData['name'],
                        ]);

                        return [$brandData['key'] => $brand];
                    });

                return [$supplierData['key'] => ['model' => $supplier, 'brands' => $brands]];
            });

        collect($scenario['products'])->each(function (array $productData) use ($distributor, $categories, $templates, $suppliers): void {
            /** @var Category $category */
            $category = $categories->get($productData['category']);
            /** @var Template $template */
            $template = $templates->get($productData['template']);

            /** @var array{model: Supplier, brands: Collection<string, Brand>} $supplierEntry */
            $supplierEntry = $suppliers->get($productData['supplier']);
            $supplier = $supplierEntry['model'];
            /** @var Brand $brand */
            $brand = $supplierEntry['brands']->get($productData['brand']);

            $product = Product::query()->create([
                'distributor_id' => $distributor->id,
                'name' => $productData['name'],
                'internal_article_number' => $productData['internal_article_number'],
                'supplier_article_number' => $productData['supplier_article_number'],
                'order_number' => $productData['order_number'],
                'ean' => $productData['ean'],
                'supplier_id' => $supplier->id,
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'template_id' => $template->id,
                'status' => $productData['status'],
                'completeness_score' => $productData['completeness_score'],
                'source_last_sync_at' => $productData['source_last_sync_at'],
            ]);

            if (filled($productData['safety_entry'] ?? null)) {
                ProductSafetyEntry::query()->create([
                    'distributor_id' => $distributor->id,
                    'product_id' => $product->id,
                    ...$productData['safety_entry'],
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function acmeScenario(): array
    {
        return [
            'categories' => [
                [
                    'key' => 'soft-toys',
                    'name' => 'Soft Toys',
                    'description' => 'Plush and comfort products intended for children ages 0-6, with textile and filling material checks.',
                    'templates' => [
                        [
                            'key' => 'plush-core',
                            'name' => 'Plush Toy Core Compliance Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::DeclarationOfConformity->value,
                                DocumentType::ProductImage->value,
                                DocumentType::Manual->value,
                            ],
                            'required_data_fields' => ['warning_text', 'age_grading', 'material_information', 'safety_instructions'],
                        ],
                        [
                            'key' => 'plush-electronic',
                            'name' => 'Electronic Plush Add-on Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::Certificate->value,
                                DocumentType::SafetyImage->value,
                                DocumentType::DeclarationOfConformity->value,
                            ],
                            'required_data_fields' => ['warning_text', 'age_grading', 'safety_text', 'additional_notes'],
                        ],
                    ],
                ],
                [
                    'key' => 'ride-ons',
                    'name' => 'Ride-On Toys',
                    'description' => 'Push, pedal, and balance products that need stability, brake, and outdoor use documentation.',
                    'templates' => [
                        [
                            'key' => 'balance-bike',
                            'name' => 'Balance Bike EU Readiness Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::DeclarationOfConformity->value,
                                DocumentType::Manual->value,
                                DocumentType::SafetyImage->value,
                            ],
                            'required_data_fields' => ['warning_text', 'age_grading', 'usage_restrictions', 'safety_instructions'],
                        ],
                        [
                            'key' => 'scooter',
                            'name' => 'Scooter and Wheeled Toy Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::Certificate->value,
                                DocumentType::ProductImage->value,
                            ],
                            'required_data_fields' => ['warning_text', 'material_information', 'usage_restrictions'],
                        ],
                    ],
                ],
                [
                    'key' => 'creative-play',
                    'name' => 'Creative Play Sets',
                    'description' => 'Craft and building products with component traceability, imagery, and instruction content.',
                    'templates' => [
                        [
                            'key' => 'construction-set',
                            'name' => 'Construction Set Documentation Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::ProductImage->value,
                                DocumentType::Manual->value,
                            ],
                            'required_data_fields' => ['age_grading', 'material_information', 'safety_instructions'],
                        ],
                        [
                            'key' => 'arts-crafts',
                            'name' => 'Arts and Crafts Consumables Pack',
                            'required_document_types' => [
                                DocumentType::Certificate->value,
                                DocumentType::DeclarationOfConformity->value,
                                DocumentType::ProductImage->value,
                            ],
                            'required_data_fields' => ['warning_text', 'usage_restrictions', 'additional_notes'],
                        ],
                    ],
                ],
            ],
            'suppliers' => [
                [
                    'key' => 'northern-toyworks',
                    'supplier_code' => 'ACM-SUP-1001',
                    'name' => 'Northern Toy Works GmbH',
                    'address' => 'Borsigstrasse 14, 22113 Hamburg, Germany',
                    'country' => 'Germany',
                    'email' => 'qa@northerntoyworks.example',
                    'phone' => '+49 40 555 1100',
                    'active' => true,
                    'brands' => [
                        ['key' => 'moon-hollow', 'name' => 'Moon Hollow'],
                        ['key' => 'trailsmith', 'name' => 'Trailsmith'],
                    ],
                ],
                [
                    'key' => 'pearl-river',
                    'supplier_code' => 'ACM-SUP-1002',
                    'name' => 'Pearl River Kids Manufacturing Co., Ltd.',
                    'address' => '88 Yongsheng Road, Dongguan, Guangdong, China',
                    'country' => 'China',
                    'email' => 'compliance@pearlriverkids.example',
                    'phone' => '+86 769 5555 8877',
                    'active' => true,
                    'brands' => [
                        ['key' => 'harbor-build', 'name' => 'Harbor Build'],
                        ['key' => 'color-lab', 'name' => 'Color Lab Kids'],
                    ],
                ],
                [
                    'key' => 'alpine-play',
                    'supplier_code' => 'ACM-SUP-1003',
                    'name' => 'Alpine Play Equipment AG',
                    'address' => 'Werkstrasse 7, 6340 Baar, Switzerland',
                    'country' => 'Switzerland',
                    'email' => 'docs@alpineplay.example',
                    'phone' => '+41 41 555 2050',
                    'active' => true,
                    'brands' => [
                        ['key' => 'stride-way', 'name' => 'StrideWay'],
                        ['key' => 'summit-junior', 'name' => 'Summit Junior'],
                    ],
                ],
            ],
            'products' => [
                [
                    'name' => 'Moon Hollow Bedtime Bear',
                    'internal_article_number' => 'ACM-ST-001',
                    'supplier_article_number' => 'NTW-BB-2401',
                    'order_number' => 'PO-ACM-240318',
                    'ean' => '4006381333931',
                    'category' => 'soft-toys',
                    'template' => 'plush-core',
                    'supplier' => 'northern-toyworks',
                    'brand' => 'moon-hollow',
                    'status' => ProductStatus::Approved,
                    'completeness_score' => 100,
                    'source_last_sync_at' => now()->subDays(2),
                    'safety_entry' => [
                        'warning_text' => 'Remove all packaging attachments before handing to a child.',
                        'age_grading' => '0m+',
                        'material_information' => 'Outer fabric: recycled polyester; filling: polyester fiber.',
                        'safety_instructions' => 'Machine wash cold. Do not tumble dry. Inspect seams regularly.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'EN 71 migration and tensile testing completed.', 'expiry_date' => now()->addMonths(18)->toDateString()],
                        ['type' => DocumentType::DeclarationOfConformity, 'public_download' => true],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Moon Hollow Starlight Bunny',
                    'internal_article_number' => 'ACM-ST-014',
                    'supplier_article_number' => 'NTW-SB-7710',
                    'order_number' => 'PO-ACM-240406',
                    'ean' => '4006381333948',
                    'category' => 'soft-toys',
                    'template' => 'plush-electronic',
                    'supplier' => 'northern-toyworks',
                    'brand' => 'moon-hollow',
                    'status' => ProductStatus::UnderReview,
                    'completeness_score' => 84,
                    'source_last_sync_at' => now()->subDays(5),
                    'safety_entry' => [
                        'warning_text' => 'Contains sound module. Remove battery tab before first use.',
                        'age_grading' => '6m+',
                        'safety_text' => 'Battery compartment secured with captive screw.',
                        'additional_notes' => 'Night mode sound level capped below 75 dB.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'EMC retest requested after PCB revision.'],
                        ['type' => DocumentType::Certificate, 'review_comment' => 'Battery transport certificate on file.'],
                        ['type' => DocumentType::SafetyImage, 'public_download' => true],
                        ['type' => DocumentType::DeclarationOfConformity],
                    ],
                ],
                [
                    'name' => 'StrideWay TrailScout Balance Bike',
                    'internal_article_number' => 'ACM-RT-203',
                    'supplier_article_number' => 'APL-TS-203',
                    'order_number' => 'PO-ACM-240422',
                    'ean' => '7612345678901',
                    'category' => 'ride-ons',
                    'template' => 'balance-bike',
                    'supplier' => 'alpine-play',
                    'brand' => 'stride-way',
                    'status' => ProductStatus::Approved,
                    'completeness_score' => 96,
                    'source_last_sync_at' => now()->subDay(),
                    'safety_entry' => [
                        'warning_text' => 'Protective equipment should be worn. Not to be used in traffic.',
                        'age_grading' => '2y+',
                        'usage_restrictions' => 'Maximum rider weight 30 kg. For flat, dry surfaces only.',
                        'safety_instructions' => 'Handlebar clamp torque must be checked before sale and after assembly.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'Dynamic load test completed.', 'replacement' => ['version' => 2, 'review_comment' => 'Updated with final packaging references.', 'expiry_date' => now()->addMonths(24)->toDateString()]],
                        ['type' => DocumentType::DeclarationOfConformity, 'public_download' => true],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                        ['type' => DocumentType::SafetyImage, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Summit Junior Metro Fold Scooter',
                    'internal_article_number' => 'ACM-RT-231',
                    'supplier_article_number' => 'APL-MFS-231',
                    'order_number' => 'PO-ACM-240517',
                    'ean' => '7612345678918',
                    'category' => 'ride-ons',
                    'template' => 'scooter',
                    'supplier' => 'alpine-play',
                    'brand' => 'summit-junior',
                    'status' => ProductStatus::ClarificationNeeded,
                    'completeness_score' => 68,
                    'source_last_sync_at' => now()->subDays(9),
                    'safety_entry' => [
                        'warning_text' => 'Helmet, knee, and elbow protection strongly recommended.',
                        'material_information' => 'Aluminum deck with TPU wheels and EVA grips.',
                        'usage_restrictions' => 'Not suitable for jumps or stunt use.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'Brake wear section missing from annex.'],
                        ['type' => DocumentType::Certificate],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Harbor Build Rescue Crane Set',
                    'internal_article_number' => 'ACM-CP-110',
                    'supplier_article_number' => 'PRK-RCS-110',
                    'order_number' => 'PO-ACM-240301',
                    'ean' => '6941234501109',
                    'category' => 'creative-play',
                    'template' => 'construction-set',
                    'supplier' => 'pearl-river',
                    'brand' => 'harbor-build',
                    'status' => ProductStatus::Approved,
                    'completeness_score' => 92,
                    'source_last_sync_at' => now()->subDays(3),
                    'safety_entry' => [
                        'age_grading' => '5y+',
                        'material_information' => 'ABS plastic components with steel axle pins.',
                        'safety_instructions' => 'Small parts warning required on retail packaging.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'expiry_date' => now()->addYear()->toDateString()],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Color Lab Washable Finger Paint Studio',
                    'internal_article_number' => 'ACM-CP-215',
                    'supplier_article_number' => 'PRK-FPS-215',
                    'order_number' => 'PO-ACM-240514',
                    'ean' => '6941234502151',
                    'category' => 'creative-play',
                    'template' => 'arts-crafts',
                    'supplier' => 'pearl-river',
                    'brand' => 'color-lab',
                    'status' => ProductStatus::InProgress,
                    'completeness_score' => 74,
                    'source_last_sync_at' => now()->subDays(6),
                    'safety_entry' => [
                        'warning_text' => 'Use under adult supervision. Avoid contact with eyes.',
                        'usage_restrictions' => 'Not intended for children under 36 months.',
                        'additional_notes' => 'Batch colorant declaration still pending supplier confirmation.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::Certificate, 'review_comment' => 'Ingredient declaration attached to certificate appendix.'],
                        ['type' => DocumentType::DeclarationOfConformity],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function globexScenario(): array
    {
        return [
            'categories' => [
                [
                    'key' => 'feeding',
                    'name' => 'Infant Feeding Accessories',
                    'description' => 'Contact-safe feeding products with migration, dishwasher, and care instruction requirements.',
                    'templates' => [
                        [
                            'key' => 'silicone-feeding',
                            'name' => 'Silicone Feeding Set Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::DeclarationOfConformity->value,
                                DocumentType::ProductImage->value,
                            ],
                            'required_data_fields' => ['material_information', 'usage_restrictions', 'safety_instructions'],
                        ],
                        [
                            'key' => 'stainless-bottle',
                            'name' => 'Insulated Bottle Compliance Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::Certificate->value,
                                DocumentType::Manual->value,
                            ],
                            'required_data_fields' => ['material_information', 'warning_text', 'additional_notes'],
                        ],
                    ],
                ],
                [
                    'key' => 'stem-learning',
                    'name' => 'STEM Learning Kits',
                    'description' => 'Educational kits with chemical, electrical, or optics components and strong instruction dependencies.',
                    'templates' => [
                        [
                            'key' => 'microscope-kit',
                            'name' => 'Microscope Kit Documentation Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::Manual->value,
                                DocumentType::SafetyImage->value,
                                DocumentType::DeclarationOfConformity->value,
                            ],
                            'required_data_fields' => ['warning_text', 'age_grading', 'safety_instructions', 'additional_notes'],
                        ],
                        [
                            'key' => 'science-lab',
                            'name' => 'Science Lab Consumables Pack',
                            'required_document_types' => [
                                DocumentType::Certificate->value,
                                DocumentType::Manual->value,
                                DocumentType::RegulatoryDocument->value,
                            ],
                            'required_data_fields' => ['warning_text', 'usage_restrictions', 'safety_text'],
                        ],
                    ],
                ],
                [
                    'key' => 'outdoor-play',
                    'name' => 'Outdoor Play',
                    'description' => 'Seasonal sand, water, and garden play products with packaging and age-grading controls.',
                    'templates' => [
                        [
                            'key' => 'sand-water',
                            'name' => 'Sand and Water Play Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::ProductImage->value,
                                DocumentType::SafetyImage->value,
                            ],
                            'required_data_fields' => ['age_grading', 'usage_restrictions', 'safety_instructions'],
                        ],
                        [
                            'key' => 'garden-play',
                            'name' => 'Garden Tool Playset Pack',
                            'required_document_types' => [
                                DocumentType::TestReport->value,
                                DocumentType::DeclarationOfConformity->value,
                                DocumentType::Manual->value,
                            ],
                            'required_data_fields' => ['warning_text', 'material_information', 'usage_restrictions'],
                        ],
                    ],
                ],
            ],
            'suppliers' => [
                [
                    'key' => 'baltic-family',
                    'supplier_code' => 'GLX-SUP-2101',
                    'name' => 'Baltic Family Goods OÜ',
                    'address' => 'Peterburi tee 49, 11415 Tallinn, Estonia',
                    'country' => 'Estonia',
                    'email' => 'compliance@balticfamily.example',
                    'phone' => '+372 600 1212',
                    'active' => true,
                    'brands' => [
                        ['key' => 'nest-ease', 'name' => 'NestEase'],
                        ['key' => 'morrow-baby', 'name' => 'Morrow Baby'],
                    ],
                ],
                [
                    'key' => 'orion-learning',
                    'supplier_code' => 'GLX-SUP-2102',
                    'name' => 'Orion Learning Systems Ltd.',
                    'address' => 'Unit 4, Meridian Park, Cambridge, United Kingdom',
                    'country' => 'United Kingdom',
                    'email' => 'qa@orionlearning.example',
                    'phone' => '+44 1223 555 880',
                    'active' => true,
                    'brands' => [
                        ['key' => 'orbit-lab', 'name' => 'Orbit Lab'],
                        ['key' => 'nova-classroom', 'name' => 'Nova Classroom'],
                    ],
                ],
                [
                    'key' => 'coastline-play',
                    'supplier_code' => 'GLX-SUP-2103',
                    'name' => 'Coastline Play Products S.L.',
                    'address' => 'Carrer de la Marina 88, 08005 Barcelona, Spain',
                    'country' => 'Spain',
                    'email' => 'technical@coastlineplay.example',
                    'phone' => '+34 93 555 9090',
                    'active' => true,
                    'brands' => [
                        ['key' => 'shoreline-junior', 'name' => 'Shoreline Junior'],
                        ['key' => 'field-day', 'name' => 'Field Day'],
                    ],
                ],
            ],
            'products' => [
                [
                    'name' => 'NestEase Silicone Feeding Set',
                    'internal_article_number' => 'GLX-IF-301',
                    'supplier_article_number' => 'BFG-SFS-301',
                    'order_number' => 'PO-GLX-240210',
                    'ean' => '7351234567890',
                    'category' => 'feeding',
                    'template' => 'silicone-feeding',
                    'supplier' => 'baltic-family',
                    'brand' => 'nest-ease',
                    'status' => ProductStatus::Approved,
                    'completeness_score' => 100,
                    'source_last_sync_at' => now()->subDays(4),
                    'safety_entry' => [
                        'material_information' => 'LFGB-grade silicone with PP suction base insert.',
                        'usage_restrictions' => 'Suitable for hot and cold foods. Do not use on stovetop.',
                        'safety_instructions' => 'Inspect suction cup before each use and discard if torn.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'Food-contact migration tests passed.'],
                        ['type' => DocumentType::DeclarationOfConformity, 'public_download' => true],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Morrow Baby Stainless Straw Bottle',
                    'internal_article_number' => 'GLX-IF-344',
                    'supplier_article_number' => 'BFG-SSB-344',
                    'order_number' => 'PO-GLX-240228',
                    'ean' => '7351234567906',
                    'category' => 'feeding',
                    'template' => 'stainless-bottle',
                    'supplier' => 'baltic-family',
                    'brand' => 'morrow-baby',
                    'status' => ProductStatus::UnderReview,
                    'completeness_score' => 88,
                    'source_last_sync_at' => now()->subDays(8),
                    'safety_entry' => [
                        'material_information' => '18/8 stainless steel bottle body with silicone straw top.',
                        'warning_text' => 'Check straw valve alignment after cleaning.',
                        'additional_notes' => 'Retail carton artwork revision expected next week.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport],
                        ['type' => DocumentType::Certificate],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Orbit Lab Junior Microscope Kit',
                    'internal_article_number' => 'GLX-ST-410',
                    'supplier_article_number' => 'OLS-MIC-410',
                    'order_number' => 'PO-GLX-240401',
                    'ean' => '5012345678907',
                    'category' => 'stem-learning',
                    'template' => 'microscope-kit',
                    'supplier' => 'orion-learning',
                    'brand' => 'orbit-lab',
                    'status' => ProductStatus::UnderReview,
                    'completeness_score' => 82,
                    'source_last_sync_at' => now()->subDays(7),
                    'safety_entry' => [
                        'warning_text' => 'Contains glass slide components. Adult supervision recommended.',
                        'age_grading' => '8y+',
                        'safety_instructions' => 'Store staining solution away from direct sunlight.',
                        'additional_notes' => 'Lens coating supplier declaration requested.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport, 'review_comment' => 'Optical housing and small parts reviewed.'],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                        ['type' => DocumentType::SafetyImage, 'public_download' => true],
                        ['type' => DocumentType::DeclarationOfConformity],
                    ],
                ],
                [
                    'name' => 'Nova Classroom Crystal Lab Set',
                    'internal_article_number' => 'GLX-ST-433',
                    'supplier_article_number' => 'OLS-CLS-433',
                    'order_number' => 'PO-GLX-240509',
                    'ean' => '5012345678914',
                    'category' => 'stem-learning',
                    'template' => 'science-lab',
                    'supplier' => 'orion-learning',
                    'brand' => 'nova-classroom',
                    'status' => ProductStatus::ClarificationNeeded,
                    'completeness_score' => 61,
                    'source_last_sync_at' => now()->subDays(11),
                    'safety_entry' => [
                        'warning_text' => 'Contains chemical sachets. Follow enclosed handling instructions.',
                        'usage_restrictions' => 'Not for children under 10 years without direct adult supervision.',
                        'safety_text' => 'Wear eye protection during mixing activities.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::Certificate, 'review_comment' => 'Awaiting signed lab stamp on annex B.'],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                        ['type' => DocumentType::RegulatoryDocument],
                    ],
                ],
                [
                    'name' => 'Shoreline Junior Sand Bucket Explorer Set',
                    'internal_article_number' => 'GLX-OP-520',
                    'supplier_article_number' => 'CPS-SBE-520',
                    'order_number' => 'PO-GLX-240325',
                    'ean' => '8412345678903',
                    'category' => 'outdoor-play',
                    'template' => 'sand-water',
                    'supplier' => 'coastline-play',
                    'brand' => 'shoreline-junior',
                    'status' => ProductStatus::Approved,
                    'completeness_score' => 94,
                    'source_last_sync_at' => now()->subDays(3),
                    'safety_entry' => [
                        'age_grading' => '18m+',
                        'usage_restrictions' => 'Rinse after salt water exposure. Store dry between seasons.',
                        'safety_instructions' => 'Do not leave children unattended near water.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport],
                        ['type' => DocumentType::ProductImage, 'public_download' => true],
                        ['type' => DocumentType::SafetyImage, 'public_download' => true],
                    ],
                ],
                [
                    'name' => 'Field Day Garden Helper Tool Belt',
                    'internal_article_number' => 'GLX-OP-544',
                    'supplier_article_number' => 'CPS-GHT-544',
                    'order_number' => 'PO-GLX-240512',
                    'ean' => '8412345678910',
                    'category' => 'outdoor-play',
                    'template' => 'garden-play',
                    'supplier' => 'coastline-play',
                    'brand' => 'field-day',
                    'status' => ProductStatus::InProgress,
                    'completeness_score' => 77,
                    'source_last_sync_at' => now()->subDays(10),
                    'safety_entry' => [
                        'warning_text' => 'Contains functional edges on rake tips. Use only under supervision.',
                        'material_information' => 'Beech wood handles with recycled ABS tool heads.',
                        'usage_restrictions' => 'Not intended for digging compacted soil or stone beds.',
                    ],
                    'documents' => [
                        ['type' => DocumentType::TestReport],
                        ['type' => DocumentType::DeclarationOfConformity],
                        ['type' => DocumentType::Manual, 'public_download' => true],
                    ],
                ],
            ],
        ];
    }
}
