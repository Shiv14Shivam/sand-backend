<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Create Test User ─────────────
        // User::factory(10)->create();

        // Changed by Aarthak for Seeding purpose
        // To be changed back to original one in main


        // ── Categories ───────────────────────────────────────────────────
        $categories = [
            ['name' => 'Cement', 'slug' => 'cement', 'icon' => 'cement', 'icon_color' => '#4CAF50', 'sort_order' => 1],
            ['name' => 'Sand',   'slug' => 'sand',   'icon' => 'sand',   'icon_color' => '#FF9800', 'sort_order' => 2],
            ['name' => 'Steel',  'slug' => 'steel',  'icon' => 'steel',  'icon_color' => '#F44336', 'sort_order' => 3],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        $cement = Category::where('slug', 'cement')->first();
        $sand   = Category::where('slug', 'sand')->first();

        // ── Sand Products (NO BRAND) ───────────────────────────────

        $this->createSandProduct(
            category: $sand,
            name: 'River Sand - Fine',
            slug: 'river-sand-fine',
            shortDesc: 'Fine quality river sand for plastering',
            detailedDesc: 'Fine river sand ideal for plastering and finishing works. Smooth texture ensures better bonding and finishing.',
            unit: 'ton',
            unitWeight: '1000kg',
            specs: [
                ['Type', 'Fine Sand'],
                ['Usage', 'Plastering'],
                ['Texture', 'Smooth'],
            ]
        );

        $this->createSandProduct(
            category: $sand,
            name: 'River Sand - Medium',
            slug: 'river-sand-medium',
            shortDesc: 'Medium grade sand for masonry',
            detailedDesc: 'Medium grain sand suitable for brickwork and masonry. Provides good strength and durability.',
            unit: 'ton',
            unitWeight: '1000kg',
            specs: [
                ['Type', 'Medium Sand'],
                ['Usage', 'Brickwork'],
                ['Texture', 'Moderate'],
            ]
        );

        $this->createSandProduct(
            category: $sand,
            name: 'River Sand - Coarse',
            slug: 'river-sand-coarse',
            shortDesc: 'Coarse sand for concrete work',
            detailedDesc: 'Coarse sand ideal for RCC and concrete work. Provides excellent strength and load-bearing capacity.',
            unit: 'ton',
            unitWeight: '1000kg',
            specs: [
                ['Type', 'Coarse Sand'],
                ['Usage', 'Concrete'],
                ['Texture', 'Rough'],
            ]
        );
        $steel  = Category::where('slug', 'steel')->first();

        // ── Cement Brands ────────────────────────────────────────────────
        $cementBrands = [
            ['name' => 'Ultratech', 'slug' => 'ultratech', 'sort_order' => 1],
            ['name' => 'ACC',       'slug' => 'acc',       'sort_order' => 2],
            ['name' => 'Ambuja',    'slug' => 'ambuja',    'sort_order' => 3],
            ['name' => 'Shree',     'slug' => 'shree',     'sort_order' => 4],
        ];

        foreach ($cementBrands as $b) {
            Brand::firstOrCreate(
                ['slug' => $b['slug']],
                array_merge($b, ['category_id' => $cement->id, 'is_active' => true])
            );
        }

        $ultratech = Brand::where('slug', 'ultratech')->first();
        $acc       = Brand::where('slug', 'acc')->first();
        $ambuja    = Brand::where('slug', 'ambuja')->first();
        $shree     = Brand::where('slug', 'shree')->first();

        // ── Ultratech Products ────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'Ultratech Cement - OPC 53 Grade',
            slug: 'ultratech-opc-53',
            shortDesc: 'Premium quality Ordinary Portland Cement 53 Grade',
            detailedDesc: 'Ultratech OPC 53 Grade cement is a high-strength cement ideal for all construction purposes. It provides superior strength and durability, making it perfect for constructing beams, pillars, and load-bearing structures. This cement conforms to IS 12269:2013 standards and offers excellent workability.',
            unit: 'unit (50kg)',
            unitWeight: '50kg',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '53 MPa'],
                ['Setting Time', '30 minutes (initial)'],
                ['Fineness', '225 m²/kg'],
                ['Conformance', 'IS 12269:2013'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'Ultratech Cement - PPC',
            slug: 'ultratech-ppc',
            shortDesc: 'Portland Pozzolana Cement for general construction',
            detailedDesc: 'Ultratech PPC is blended cement made with fly ash, offering high durability and resistance to chemical attacks. Ideal for plastering, brickwork, and mass concrete works.',
            unit: 'unit (50kg)',
            unitWeight: '50kg',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Setting Time', '30 minutes (initial)'],
                ['Conformance', 'IS 1489:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $acc,
            name: 'ACC Cement - OPC 43 Grade',
            slug: 'acc-opc-43',
            shortDesc: 'General purpose Ordinary Portland Cement 43 Grade',
            detailedDesc: 'ACC OPC 43 Grade is a versatile cement suitable for general construction works including residential buildings, pavements, and plaster.',
            unit: 'unit (50kg)',
            unitWeight: '50kg',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '43 MPa'],
                ['Conformance', 'IS 8112:2013'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ambuja,
            name: 'Ambuja Cement - PPC',
            slug: 'ambuja-ppc',
            shortDesc: 'Durable Portland Pozzolana Cement by Ambuja',
            detailedDesc: 'Ambuja PPC offers excellent durability and is well-suited for mass concrete and marine construction work.',
            unit: 'unit (50kg)',
            unitWeight: '50kg',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Conformance', 'IS 1489:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $shree,
            name: 'Shree Cement - OPC 53 Grade',
            slug: 'shree-opc-53',
            shortDesc: 'High-strength OPC 53 Grade by Shree Cement',
            detailedDesc: 'Shree OPC 53 is ideal for high-rise buildings, bridges, and industrial structures needing superior compressive strength.',
            unit: 'unit (50kg)',
            unitWeight: '50kg',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '53 MPa'],
                ['Conformance', 'IS 12269:2013'],
            ]
        );

        $this->command->info('Marketplace seed data created successfully.');
    }

    // ─── Helper ───────────────────────────────────────────────────────────
    private function createProduct(
        Category $category,
        Brand $brand,
        string $name,
        string $slug,
        string $shortDesc,
        string $detailedDesc,
        string $unit,
        string $unitWeight,
        array $specs
    ): void {
        $product = Product::firstOrCreate(
            ['slug' => $slug],
            [
                'category_id'          => $category->id,
                'brand_id'             => $brand->id,
                'name'                 => $name,
                'short_description'    => $shortDesc,
                'detailed_description' => $detailedDesc,
                'unit'                 => $unit,
                'unit_weight'          => $unitWeight,
                'is_active'            => true,
            ]
        );

        foreach ($specs as $index => [$key, $value]) {
            ProductSpecification::firstOrCreate(
                ['product_id' => $product->id, 'key' => $key],
                ['value' => $value, 'sort_order' => $index]
            );
        }
    }
    private function createSandProduct(
        Category $category,
        string $name,
        string $slug,
        string $shortDesc,
        string $detailedDesc,
        string $unit,
        string $unitWeight,
        array $specs
    ): void {
        $product = Product::firstOrCreate(
            ['slug' => $slug],
            [
                'category_id'          => $category->id,
                'brand_id'             => null, // ✅ IMPORTANT (NO BRAND)
                'name'                 => $name,
                'short_description'    => $shortDesc,
                'detailed_description' => $detailedDesc,
                'unit'                 => $unit,
                'unit_weight'          => $unitWeight,
                'is_active'            => true,
            ]
        );

        foreach ($specs as $index => [$key, $value]) {
            ProductSpecification::firstOrCreate(
                ['product_id' => $product->id, 'key' => $key],
                ['value' => $value, 'sort_order' => $index]
            );
        }
    }
}
