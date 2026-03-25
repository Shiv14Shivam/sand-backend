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
        $steel  = Category::where('slug', 'steel')->first();

        // ── Sand Products (NO BRAND) ───────────────────────────────

        $this->createSandProduct(
            category: $sand,
            name: 'Big Grade',
            slug: 'sand-big-grade',
            shortDesc: 'Big garde  Good for Concrete,Beam',
            detailedDesc: 'Big garde  Good for Concrete,Beam',
            unit: 'Per Cu.ft.',
            unitWeight: null,
            imageUrl: null,
            specs: [
                ['Type', 'Coarse Sand'],
                ['Usage', 'Concrete, Beam'],
                ['Grade', 'Big Grade'],
            ]
        );

        $this->createSandProduct(
            category: $sand,
            name: 'Medium Grade',
            slug: 'sand-medium-grade',
            shortDesc: 'Meduim garde  Good for Joint',
            detailedDesc: 'Meduim garde  Good for Joint',
            unit: 'Per Cu.ft.',
            unitWeight: null,
            imageUrl: null,
            specs: [
                ['Type', 'Medium Sand'],
                ['Usage', 'Joint Work'],
                ['Grade', 'Medium Grade'],
            ]
        );

        $this->createSandProduct(
            category: $sand,
            name: 'Fine Grade',
            slug: 'sand-fine-grade',
            shortDesc: 'Fine Grade Good for Plaster',
            detailedDesc: 'Fine Grade Good for Plaster',
            unit: 'Per Cu.ft.',
            unitWeight: null,
            imageUrl: null,
            specs: [
                ['Type', 'Fine Sand'],
                ['Usage', 'Plastering'],
                ['Grade', 'Fine Grade'],
            ]
        );

        // ── Cement Brands ────────────────────────────────────────────────
        $cementBrands = [
            ['name' => 'UltraTech Cement Ltd',        'slug' => 'ultratech-cement-ltd',        'sort_order' => 1],
            ['name' => 'Shree Cement Ltd (Bangur)',    'slug' => 'shree-cement-ltd-bangur',     'sort_order' => 2],
            ['name' => 'Ambuja Cements Ltd (Adani)',   'slug' => 'ambuja-cements-ltd-adani',    'sort_order' => 3],
            ['name' => 'ACC Ltd (Adani)',               'slug' => 'acc-ltd-adani',               'sort_order' => 4],
            ['name' => 'Dalmia Cement (Bharat) Ltd',   'slug' => 'dalmia-cement-bharat-ltd',    'sort_order' => 5],
            ['name' => 'The Ramco Cements Ltd',        'slug' => 'ramco-cements-ltd',           'sort_order' => 6],
            ['name' => 'The India Cements Ltd',        'slug' => 'india-cements-ltd',           'sort_order' => 7],
            ['name' => 'JK Cement Ltd',                'slug' => 'jk-cement-ltd',               'sort_order' => 8],
            ['name' => 'Birla Cement (UltraTech)',     'slug' => 'birla-cement-ultratech',      'sort_order' => 9],
            ['name' => 'Orient Cement Ltd',            'slug' => 'orient-cement-ltd',           'sort_order' => 10],
        ];

        foreach ($cementBrands as $b) {
            Brand::firstOrCreate(
                ['slug' => $b['slug']],
                array_merge($b, ['category_id' => $cement->id, 'is_active' => true])
            );
        }

        // ── Steel Brands ─────────────────────────────────────────────────
        $steelBrands = [
            ['name' => 'Tata Steel (Tiscon)',           'slug' => 'tata-steel-tiscon',           'sort_order' => 1],
            ['name' => 'JSW Steel (Neosteel)',          'slug' => 'jsw-steel-neosteel',          'sort_order' => 2],
            ['name' => 'SRMB (Shyam Royal Margins)',   'slug' => 'srmb-shyam-royal-margins',    'sort_order' => 3],
            ['name' => 'SAIL',                          'slug' => 'sail',                         'sort_order' => 4],
            ['name' => 'Jindal Steel & Power',          'slug' => 'jindal-steel-power',          'sort_order' => 5],
            ['name' => 'Kamdhenu Ltd',                  'slug' => 'kamdhenu-ltd',                'sort_order' => 6],
            ['name' => 'Prime Gold',                    'slug' => 'prime-gold',                  'sort_order' => 7],
            ['name' => 'Essar Steel',                   'slug' => 'essar-steel',                 'sort_order' => 8],
            ['name' => 'Vizag Steel (RINL)',            'slug' => 'vizag-steel-rinl',            'sort_order' => 9],
            ['name' => 'Shyam Steel',                   'slug' => 'shyam-steel',                 'sort_order' => 10],
        ];

        foreach ($steelBrands as $b) {
            Brand::firstOrCreate(
                ['slug' => $b['slug']],
                array_merge($b, ['category_id' => $steel->id, 'is_active' => true])
            );
        }

        // ── Brand References ─────────────────────────────────────────────
        $ultratech  = Brand::where('slug', 'ultratech-cement-ltd')->first();
        $bangur     = Brand::where('slug', 'shree-cement-ltd-bangur')->first();
        $ambuja     = Brand::where('slug', 'ambuja-cements-ltd-adani')->first();
        $acc        = Brand::where('slug', 'acc-ltd-adani')->first();
        $dalmia     = Brand::where('slug', 'dalmia-cement-bharat-ltd')->first();
        $ramco      = Brand::where('slug', 'ramco-cements-ltd')->first();
        $india      = Brand::where('slug', 'india-cements-ltd')->first();
        $jk         = Brand::where('slug', 'jk-cement-ltd')->first();
        $birla      = Brand::where('slug', 'birla-cement-ultratech')->first();
        $orient     = Brand::where('slug', 'orient-cement-ltd')->first();

        $tata       = Brand::where('slug', 'tata-steel-tiscon')->first();
        $jsw        = Brand::where('slug', 'jsw-steel-neosteel')->first();
        $srmb       = Brand::where('slug', 'srmb-shyam-royal-margins')->first();
        $sail       = Brand::where('slug', 'sail')->first();
        $jindal     = Brand::where('slug', 'jindal-steel-power')->first();
        $kamdhenu   = Brand::where('slug', 'kamdhenu-ltd')->first();
        $primeGold  = Brand::where('slug', 'prime-gold')->first();
        $essar      = Brand::where('slug', 'essar-steel')->first();
        $vizag      = Brand::where('slug', 'vizag-steel-rinl')->first();
        $shyam      = Brand::where('slug', 'shyam-steel')->first();

        // =========================================================
        // ── CEMENT PRODUCTS ───────────────────────────────────────
        // =========================================================

        // ── UltraTech ────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'UltraTech OPC 53 Grade Cement',
            slug: 'ultratech-opc-53-grade',
            shortDesc: 'High-strength cement delivering minimum 53 N/mm² compressive strength for heavy structural construction.',
            detailedDesc: 'UltraTech OPC 53 Grade Cement is formulated for projects demanding superior structural performance and durability. It achieves a minimum compressive strength of 53 N/mm² at 28 days and is suitable for concrete mixes above M25 grade. Commonly used in high-rise buildings, dams, bridges, and heavy-duty RCC structures, it ensures rapid strength development and reliable load-bearing capacity. Due to higher heat of hydration, proper curing and temperature control are essential to prevent thermal cracking.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/1_bkbob4',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '53 N/mm² (28 days)'],
                ['Suitable For', 'Concrete above M25 grade, RCC, High-rise buildings, Bridges, Dams'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'UltraTech OPC 43 Grade Cement',
            slug: 'ultratech-opc-43-grade',
            shortDesc: 'Balanced-strength cement delivering 43 N/mm² compressive strength for general residential construction.',
            detailedDesc: 'UltraTech OPC 43 Grade Cement is commonly used for standard home construction applications due to its balance between strength and cost efficiency. It achieves a minimum compressive strength of 43 N/mm² at 28 days and is suitable for concrete mixes up to M30 grade. It is widely used for plain concrete, plastering work, masonry, and precast items such as tiles and blocks. The cement provides steady strength development and is appropriate for non-heavy structural and finishing works.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/2_m6m4mt',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '43 N/mm² (28 days)'],
                ['Suitable For', 'Plain concrete, Plastering, Masonry, Precast tiles & blocks'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'UltraTech Portland Pozzolana Cement (PPC)',
            slug: 'ultratech-ppc',
            shortDesc: 'Blended cement offering improved workability and long-term durability for residential and mass concrete applications.',
            detailedDesc: 'UltraTech PPC is manufactured by blending Portland cement clinker with pozzolanic materials such as fly ash. The pozzolanic reaction enhances durability, reduces permeability, and improves workability compared to ordinary Portland cement. It generates lower heat during hydration, making it suitable for mass concrete structures like dams and bridges. Commonly used in home construction, masonry, plastering, and foundations, PPC provides better long-term strength and resistance to environmental attack.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/3_evqyfo',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Suitable For', 'Home construction, Masonry, Plastering, Foundations, Mass concrete'],
                ['Conformance', 'IS 1489:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'UltraTech Sulphate Resisting Cement',
            slug: 'ultratech-sulphate-resisting-cement',
            shortDesc: 'Specialized cement formulated to resist sulphate attack in aggressive soil and groundwater conditions.',
            detailedDesc: 'UltraTech Sulphate Resisting Cement is designed to withstand the harmful effects of sulphate salts present in soil and groundwater. It has a controlled chemical composition with reduced C3A content to minimize sulphate reaction and expansion. This makes it suitable for construction in coastal areas, sewage works, canal linings, mines, retaining walls, and foundations exposed to sulphate-bearing environments. The cement enhances structural durability and reduces long-term deterioration in aggressive exposure conditions.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/4_nis7i0',
            specs: [
                ['Grade', 'SRC'],
                ['Weight', '50 kg/unit'],
                ['Suitable For', 'Coastal areas, Sewage works, Canal linings, Mines, Retaining walls'],
                ['Key Feature', 'Reduced C3A content for sulphate resistance'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ultratech,
            name: 'UltraTech Rapid Hardening Cement',
            slug: 'ultratech-rapid-hardening-cement',
            shortDesc: 'High early-strength cement designed for rapid setting and faster project completion.',
            detailedDesc: 'UltraTech Rapid Hardening Cement is formulated to achieve higher early strength compared to ordinary Portland cement. It is manufactured with a higher proportion of fine particles and optimized clinker composition to accelerate strength development. This makes it suitable for pavement construction, precast concrete elements, road repairs, and urgent repair works. The faster strength gain allows earlier formwork removal and quicker structural use, reducing overall project timelines.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/5_fnxqwn',
            specs: [
                ['Grade', 'RHC'],
                ['Weight', '50 kg/unit'],
                ['Suitable For', 'Pavement construction, Precast elements, Road repairs, Urgent repairs'],
                ['Key Feature', 'Higher early strength, faster formwork removal'],
            ]
        );

        // ── Bangur (Shree) ───────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $bangur,
            name: 'Bangur OPC 53 Grade Cement',
            slug: 'bangur-opc-53-grade',
            shortDesc: 'Heavy-duty high-strength cement designed for rapid strength development in large structural projects.',
            detailedDesc: 'Bangur OPC 53 Grade Cement is engineered for applications requiring high early strength and robust load-bearing capacity. It offers rapid setting and faster strength gain, making it suitable for demanding construction environments. The cement is widely used in high-rise buildings, bridges, flyovers, dams, and industrial structures. Its superior structural performance supports heavy-duty infrastructure and reinforced concrete works.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/6_eoikbq',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Suitable For', 'High-rise buildings, Bridges, Flyovers, Dams, Industrial structures'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $bangur,
            name: 'Bangur OPC 43 Grade Cement',
            slug: 'bangur-opc-43-grade',
            shortDesc: 'Balanced-strength cement providing 43 MPa compressive strength for standard construction applications.',
            detailedDesc: 'Bangur OPC 43 Grade Cement achieves a minimum compressive strength of 43 MPa at 28 days and conforms to IS 8112 standards. It is suitable for general construction work where moderate strength and cost efficiency are required. Commonly used in residential buildings, flooring, and precast concrete elements, it supports plain concrete and light structural applications. The cement is widely used in retail construction and small to mid-scale commercial projects.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/7_mh5zha',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '43 MPa (28 days)'],
                ['Conformance', 'IS 8112'],
                ['Suitable For', 'Residential buildings, Flooring, Precast elements'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $bangur,
            name: 'Bangur Cement PPC',
            slug: 'bangur-ppc',
            shortDesc: 'Pozzolana blended cement designed for enhanced durability and performance in corrosive and sulphate-prone environments.',
            detailedDesc: 'Bangur PPC is manufactured using specially treated fly ash, high-quality clinker, and gypsum in compliance with IS 1489 (Part-1): 2015. The treated fly ash improves workability at a reduced water-cement ratio and enhances ultimate compressive strength. It lowers permeability and provides resistance against sulphate attack and alkali-silica reaction, ensuring long-term durability. The cement is suitable for sewage treatment plants, foundations, dams, canals, offshore structures, concrete roads, bridges, and high-rise commercial buildings exposed to aggressive environmental conditions.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/8_tdqmbl',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Conformance', 'IS 1489 (Part-1): 2015'],
                ['Suitable For', 'Sewage treatment plants, Foundations, Dams, Canals, Offshore structures, Bridges'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $bangur,
            name: 'Bangur Portland Slag Cement (PSC)',
            slug: 'bangur-psc',
            shortDesc: 'High-performance slag-blended cement offering superior strength, durability, and crack resistance.',
            detailedDesc: 'Bangur PSC is manufactured by finely grinding high-quality clinker with granulated blast furnace slag using advanced Vertical Roller Mill (VRM) technology to ensure optimized particle size distribution (PSD). Enriched with reactive silica, it enhances strength development, durability, and surface finish quality. The slag content improves resistance to cracking and long-term environmental exposure. It is packaged in moisture-resistant, tamper-proof Laminated Polypropylene (LPP) bags to maintain product quality and extend shelf life.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/9_nkq4lg',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Technology', 'Vertical Roller Mill (VRM)'],
                ['Key Feature', 'Optimized PSD, Reactive silica, LPP moisture-resistant bags'],
            ]
        );

        // ── Ambuja ───────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $ambuja,
            name: 'Ambuja OPC 53 Grade Cement',
            slug: 'ambuja-opc-53-grade',
            shortDesc: 'Premium high-strength cement delivering superior compressive strength for critical load-bearing elements such as beams, columns, slabs, and bridges.',
            detailedDesc: 'Ambuja OPC 53 Grade is produced from high-quality clinker with precise grinding to achieve optimal particle size distribution, ensuring rapid strength gain and exceptional durability. It conforms to IS 269:2015 standards, offering minimum compressive strengths of 37 MPa at 3 days, 47 MPa at 7 days, and 53 MPa at 28 days. Ideal for large-scale infrastructure and high-performance concrete, it is packaged in durable 50 kg HDPE bags for easy handling and moisture protection.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/10_g9ywl2',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
                ['Packaging', '50 kg HDPE bags'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ambuja,
            name: 'Ambuja OPC 43 Grade Cement',
            slug: 'ambuja-opc-43-grade',
            shortDesc: 'Versatile high-quality cement with balanced strength development, suitable for residential buildings, brickwork, and flooring applications.',
            detailedDesc: 'Ambuja OPC 43 Grade is manufactured from premium clinker, finely ground to meet IS 269:2015 standards, delivering minimum compressive strengths of 33 MPa at 3 days, 42 MPa at 7 days, and 43 MPa at 28 days. It provides good workability, moderate heat of hydration to minimize cracking, and initial setting time around 180 minutes with final up to 270-600 minutes. Packaged in sturdy 50 kg HDPE/PP bags for protection against moisture.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/11_uk2ts4',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ambuja,
            name: 'Ambuja PPC Cement',
            slug: 'ambuja-ppc',
            shortDesc: 'High-quality blended cement enhancing workability, crack resistance, and sustainability for RCC, plastering, and mass concreting.',
            detailedDesc: 'Ambuja PPC is manufactured by intergrinding premium clinker with 15-35% high-reactive fly ash, achieving fineness of 300-411 m²/kg per IS 1489 (Part 1):2015 standards. It delivers compressive strengths of min 16 MPa (3 days), 22 MPa (7 days), and 33 MPa (28 days), with initial setting time ~140-160 minutes and final ~215-270 minutes. Benefits include lower heat of hydration, improved sulfate resistance, smooth finish, and reduced permeability; packed in moisture-resistant 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/12_iliua0',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '300-411 m²/kg'],
                ['Compressive Strength', '16 MPa (3 days), 22 MPa (7 days), 33 MPa (28 days)'],
                ['Conformance', 'IS 1489 (Part 1):2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ambuja,
            name: 'Ambuja PSC Cement',
            slug: 'ambuja-psc',
            shortDesc: 'Slag-blended cement providing high durability and resistance to sulfates for marine structures, foundations, and mass concreting.',
            detailedDesc: 'Ambuja PSC (where available) is made by intergrinding 25-70% granulated blast furnace slag with clinker and gypsum per IS 455:2015, achieving fineness ≥225 m²/kg and compressive strengths min 10 MPa (3 days), 16 MPa (7 days), 32 MPa (28 days). It offers low heat of hydration, reduced permeability for chloride/sulfate resistance, better workability, and long-term strength gain; packaged in 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/13_ntbpro',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Slag Content', '25-70%'],
                ['Fineness', '≥225 m²/kg'],
                ['Compressive Strength', '10 MPa (3 days), 16 MPa (7 days), 32 MPa (28 days)'],
                ['Conformance', 'IS 455:2015'],
            ]
        );

        // ── ACC ──────────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $acc,
            name: 'ACC OPC 53 Grade Cement',
            slug: 'acc-opc-53-grade',
            shortDesc: 'Premium cement for high-strength applications like RCC, bridges, and high-rises with rapid strength gain.',
            detailedDesc: 'ACC OPC 53 Grade meets IS 269:2015 standards, delivering min 53 MPa compressive strength at 28 days (37 MPa/3 days, 47 MPa/7 days). Finely ground clinker ensures excellent workability and durability, packaged in 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/15_oa5x7k',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
                ['Packaging', '50 kg HDPE bags'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $acc,
            name: 'ACC OPC 43 Grade Cement',
            slug: 'acc-opc-43-grade',
            shortDesc: 'Reliable general-purpose cement for masonry, plastering, and flooring in residential projects.',
            detailedDesc: 'Conforms to IS 269:2015 with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), moderate heat of hydration, initial setting ~180 min. Suitable for everyday construction, 50 kg moisture-proof bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/16_k5pfrf',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $acc,
            name: 'ACC Suraksha PPC Cement',
            slug: 'acc-suraksha-ppc',
            shortDesc: 'Eco-friendly blended cement with superior crack resistance and smooth finish for plastering/RCC.',
            detailedDesc: 'ACC Suraksha PPC blends 15-35% fly ash with clinker per IS 1489, fineness 320-400 m²/kg, 33 MPa at 28 days. Enhanced sulfate resistance, low permeability, packaged in 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/17_nwhm21',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '320-400 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $acc,
            name: 'ACC PSC Cement',
            slug: 'acc-psc',
            shortDesc: 'Durable slag cement for marine/chemical exposure and mass concrete with low heat generation.',
            detailedDesc: 'Interground 25-70% blast furnace slag per IS 455:2015, min 32 MPa/28 days, fineness ≥225 m²/kg. Excellent for foundations, sulfate resistance, 50 kg HDPE packaging.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/18_vakahe',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Slag Content', '25-70%'],
                ['Fineness', '≥225 m²/kg'],
                ['Compressive Strength', '32 MPa (28 days)'],
                ['Conformance', 'IS 455:2015'],
            ]
        );

        // ── Dalmia ───────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $dalmia,
            name: 'Dalmia OPC 53 Grade Cement',
            slug: 'dalmia-opc-53-grade',
            shortDesc: 'Premium high-strength cement ideal for RCC structures, bridges, and high-rises with rapid early strength development.',
            detailedDesc: 'Dalmia OPC 53 Grade uses superior clinker finely ground via advanced mills to meet IS 269:2015 standards, achieving min 53 MPa at 28 days (37 MPa/3 days, 47 MPa/7 days). It offers excellent workability, low heat of hydration, and durability for critical load-bearing elements; packaged in moisture-resistant 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/19_zhivz8',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $dalmia,
            name: 'Dalmia OPC 43 Grade Cement',
            slug: 'dalmia-opc-43-grade',
            shortDesc: 'Versatile cement suited for general construction including masonry, plastering, and flooring.',
            detailedDesc: 'Manufactured to IS 269:2015 specs with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), providing balanced setting times (initial ~180 min) and good cohesion. Reliable for everyday builds with consistent quality; 50 kg bags ensure easy handling.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/20_fqgwvg',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $dalmia,
            name: 'Dalmia PPC Cement',
            slug: 'dalmia-ppc',
            shortDesc: 'Eco-friendly blended cement for plastering, RCC, and mass concreting with enhanced crack resistance.',
            detailedDesc: 'Blends 15-35% pozzolanic fly ash with clinker per IS 1489, fineness ≥300 m²/kg, delivering 33 MPa at 28 days. Reduces permeability, improves sulfate resistance, and lowers heat—perfect for sustainable projects; packed in 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/21_lwv3we',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '≥300 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $dalmia,
            name: 'Dalmia PSC Cement',
            slug: 'dalmia-psc',
            shortDesc: 'High-durability slag cement for foundations, marine works, and sulfate-prone environments.',
            detailedDesc: 'Intergrinds 25-70% granulated blast furnace slag per IS 455:2015, min 32 MPa/28 days, fineness ≥225 m²/kg. Excels in low heat generation, chemical resistance, and long-term strength; supplied in protective 50 kg bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/22_zg8w0v',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Slag Content', '25-70%'],
                ['Fineness', '≥225 m²/kg'],
                ['Compressive Strength', '32 MPa (28 days)'],
                ['Conformance', 'IS 455:2015'],
            ]
        );

        // ── Ramco ────────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $ramco,
            name: 'Ramco OPC 53 Grade Cement',
            slug: 'ramco-opc-53-grade',
            shortDesc: 'Super-grade cement for critical structures like high-rises, bridges, and RCC with rapid strength gain.',
            detailedDesc: 'Ramco OPC 53 Grade uses high-quality clinker ground in advanced mills to IS 269:2015 specs, delivering min 53 MPa at 28 days (37 MPa/3 days, 47 MPa/7 days). Provides excellent workability, low heat evolution, and consistent performance; packed in 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/23_ps2idl',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ramco,
            name: 'Ramco OPC 43 Grade Cement',
            slug: 'ramco-opc-43-grade',
            shortDesc: 'Balanced cement for masonry, plastering, flooring, and general construction needs.',
            detailedDesc: 'Meets IS 269:2015 with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), initial setting ~180 min, good cohesion for everyday use. Reliable quality control ensures minimal variations; 50 kg moisture-proof bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/24_sn1nft',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ramco,
            name: 'Ramco PPC Cement',
            slug: 'ramco-ppc',
            shortDesc: 'Eco-efficient blended cement for plastering, RCC, and mass concrete with smooth finish.',
            detailedDesc: 'Intergrinds 15-35% select fly ash with clinker per IS 1489, fineness ~350 m²/kg, 33 MPa at 28 days. Enhances sulfate resistance, reduces cracking/permeability; ideal for sustainable builds in 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/25_wqo2gy',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '~350 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $ramco,
            name: 'Ramco PSC Cement',
            slug: 'ramco-psc',
            shortDesc: 'Robust slag cement for marine works, foundations, and sulfate-rich environments.',
            detailedDesc: 'Blends 25-70% granulated blast furnace slag per IS 455:2015, min 32 MPa/28 days, fineness ≥225 m²/kg. Low heat of hydration, superior chemical durability, long-term strength; supplied in 50 kg protective bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/26_wzxwy7',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Slag Content', '25-70%'],
                ['Fineness', '≥225 m²/kg'],
                ['Compressive Strength', '32 MPa (28 days)'],
                ['Conformance', 'IS 455:2015'],
            ]
        );

        // ── India Cements ─────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $india,
            name: 'India Cements OPC 53 Grade',
            slug: 'india-cements-opc-53-grade',
            shortDesc: 'High-strength cement perfect for RCC, beams, columns, and infrastructure demanding rapid load-bearing capacity.',
            detailedDesc: 'Manufactured to IS 269:2015 standards with premium clinker, achieving 53 MPa minimum at 28 days (37 MPa/3 days, 47 MPa/7 days). Ensures superior workability and durability; packed in moisture-resistant 50 kg HDPE bags for site handling.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/27_cann4u',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $india,
            name: 'India Cements PPC Cement',
            slug: 'india-cements-ppc',
            shortDesc: 'Sustainable blended cement ideal for plastering, flooring, and mass concreting with enhanced finish and eco-benefits.',
            detailedDesc: 'Blends high-quality fly ash (15-35%) with clinker per IS 1489 standards, fineness around 350 m²/kg, delivering 33 MPa at 28 days. Provides crack resistance, low heat of hydration, and sulfate durability; supplied in protective 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/28_vmbsri',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '~350 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        // ── JK Cement ─────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $jk,
            name: 'JK Super OPC 53 Grade Cement',
            slug: 'jk-super-opc-53-grade',
            shortDesc: 'Premium high-strength cement for RCC, bridges, and heavy infrastructure with fast strength development.',
            detailedDesc: 'JK Super OPC 53 Grade uses top-grade clinker ground precisely to IS 269:2015 standards, delivering min 53 MPa at 28 days (37 MPa/3 days, 47 MPa/7 days). Excellent workability and durability for critical applications; packaged in 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/29_z3dpve',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $jk,
            name: 'JK Super OPC 43 Grade Cement',
            slug: 'jk-super-opc-43-grade',
            shortDesc: 'Reliable all-purpose cement for masonry, plastering, and general residential construction.',
            detailedDesc: 'Conforms to IS 269:2015 with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), balanced setting time around 180 min initial. Consistent quality for everyday builds; moisture-proof 50 kg packaging.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/30_xyyb5l',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $jk,
            name: 'JK Super PPC Cement',
            slug: 'jk-super-ppc',
            shortDesc: 'Eco-friendly blended cement for plastering, flooring, and RCC with smooth finish and crack resistance.',
            detailedDesc: 'Blends 15-35% quality fly ash per IS 1489, fineness 320-400 m²/kg, achieving 33 MPa at 28 days. Low heat of hydration, enhanced sulfate protection; packed in durable 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/31_bwsbmv',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '320-400 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $jk,
            name: 'JK Super PSC Cement',
            slug: 'jk-super-psc',
            shortDesc: 'Superior slag cement for marine structures, foundations, and sulfate environments.',
            detailedDesc: 'Intergrounds 25-70% blast furnace slag to IS 455:2015 specs, min 32 MPa/28 days, fineness ≥225 m²/kg. Reduces permeability and heat generation for long-term durability; 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/32_pjgi3w',
            specs: [
                ['Grade', 'PSC'],
                ['Weight', '50 kg/unit'],
                ['Slag Content', '25-70%'],
                ['Fineness', '≥225 m²/kg'],
                ['Compressive Strength', '32 MPa (28 days)'],
                ['Conformance', 'IS 455:2015'],
            ]
        );

        // ── Birla ─────────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $birla,
            name: 'Birla OPC 53 Grade Cement',
            slug: 'birla-opc-53-grade',
            shortDesc: 'High-strength cement for RCC, high-rises, and infrastructure requiring rapid load-bearing capacity.',
            detailedDesc: 'Birla OPC 53 Grade is produced from premium clinker finely ground to IS 269:2015 standards, offering min 53 MPa at 28 days (37 MPa/3 days, 47 MPa/7 days). It ensures superior workability, durability, and low heat of hydration; packaged in moisture-resistant 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/33_duip1t',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $birla,
            name: 'Birla OPC 43 Grade Cement',
            slug: 'birla-opc-43-grade',
            shortDesc: 'Versatile cement ideal for masonry, plastering, flooring, and everyday residential builds.',
            detailedDesc: 'Conforms to IS 269:2015 with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), balanced initial setting ~180 min. Reliable cohesion and performance for general use; supplied in protective 50 kg bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/34_vlqp0y',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $birla,
            name: 'Birla PPC Cement',
            slug: 'birla-ppc',
            shortDesc: 'Eco-friendly blended cement for plastering, RCC, and mass concrete with smooth finish.',
            detailedDesc: 'Blends 15-35% pozzolanic fly ash per IS 1489, fineness ≥320 m²/kg, achieving 33 MPa at 28 days. Provides crack resistance, sulfate protection, and reduced permeability; packed in 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/35_tvyx7a',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '≥320 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        // ── Orient ────────────────────────────────────────────────
        $this->createProduct(
            category: $cement,
            brand: $orient,
            name: 'Orient OPC 53 Grade Cement',
            slug: 'orient-opc-53-grade',
            shortDesc: 'High-strength cement ideal for RCC structures, pillars, and infrastructure with superior early strength.',
            detailedDesc: 'Orient OPC 53 Grade is made from quality clinker finely ground to IS 269:2015 standards, providing min 53 MPa at 28 days (37 MPa/3 days, 47 MPa/7 days). Ensures excellent workability and durability for demanding applications; packed in 50 kg HDPE bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/36_lpiucd',
            specs: [
                ['Grade', 'OPC 53'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '37 MPa (3 days), 47 MPa (7 days), 53 MPa (28 days)'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $orient,
            name: 'Orient OPC 43 Grade Cement',
            slug: 'orient-opc-43-grade',
            shortDesc: 'Versatile general-purpose cement for masonry, plastering, and flooring works.',
            detailedDesc: 'Conforms to IS 269:2015 with 43 MPa at 28 days (33 MPa/3 days, 42 MPa/7 days), balanced setting time ~180 min initial. Reliable for routine construction; moisture-proof 50 kg packaging.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/37_xlgl30',
            specs: [
                ['Grade', 'OPC 43'],
                ['Weight', '50 kg/unit'],
                ['Compressive Strength', '33 MPa (3 days), 42 MPa (7 days), 43 MPa (28 days)'],
                ['Initial Setting Time', '~180 minutes'],
                ['Conformance', 'IS 269:2015'],
            ]
        );

        $this->createProduct(
            category: $cement,
            brand: $orient,
            name: 'Orient PPC Cement',
            slug: 'orient-ppc',
            shortDesc: 'Eco-friendly blended cement for plastering, RCC, and mass concreting with smooth finish.',
            detailedDesc: 'Blends 15-35% pozzolanic fly ash per IS 1489, fineness ≥320 m²/kg, 33 MPa at 28 days. Offers crack resistance, low heat of hydration, and sulfate protection; in durable 50 kg LPP bags.',
            unit: '50 kg Bag',
            unitWeight: '50 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/38_bd8jey',
            specs: [
                ['Grade', 'PPC'],
                ['Weight', '50 kg/unit'],
                ['Fly Ash Content', '15-35%'],
                ['Fineness', '≥320 m²/kg'],
                ['Compressive Strength', '33 MPa (28 days)'],
                ['Conformance', 'IS 1489'],
            ]
        );

        // =========================================================
        // ── STEEL PRODUCTS ────────────────────────────────────────
        // =========================================================

        // ── Tata Tiscon ──────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $tata,
            name: 'Tata Tiscon Fe500D TMT Bar',
            slug: 'tata-tiscon-fe500d-tmt',
            shortDesc: 'Ductile high-strength rebar for seismic zones, multi-storey buildings, and bridges with excellent weldability.',
            detailedDesc: 'Tata Tiscon Fe500D uses patented 4-stage TEMPCORE process per IS 1786:2008, yield strength 500 MPa min (tensile 565 MPa), elongation ≥16%, dia 8-55 mm. Features CRS (Corrosion Resistant Steel) variant, uniform ribs for 75% better bond, BIS/GreenPro certified; weighs ~0.617 kg/m for 10mm.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/39_t7g6f3',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-55 mm'],
                ['Process', '4-stage TEMPCORE'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $tata,
            name: 'Tata Tiscon Fe550D TMT Bar',
            slug: 'tata-tiscon-fe550d-tmt',
            shortDesc: 'Ultra-high strength ductile bars for high-rise towers and heavy infrastructure with enhanced safety.',
            detailedDesc: 'Advanced TEMPCORE quenching to IS 1786 standards, yield 550 MPa min (tensile 600 MPa), elongation ≥14.75%, sizes 10-32 mm (CRS up to 55 mm). Superior bendability (bend ratio 4D), corrosion resistance via low carbon equivalent; ideal for seismic design.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/40_w4uas3',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '≥14.75%'],
                ['Available Diameters', '10-32 mm (CRS up to 55mm)'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $tata,
            name: 'Tata Tiscon Fe600 TMT Bar',
            slug: 'tata-tiscon-fe600-tmt',
            shortDesc: 'Maximum strength rebar for extreme load conditions in skyscrapers and bridges.',
            detailedDesc: 'Engineered for yield strength 600 MPa min per IS 1786, high ductility (elongation >12%), available in select dia 12-32 mm. Optimized rib geometry for grip strength, weldable with low impurities; premium choice for advanced RCC.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/41_vzfqe5',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '>12%'],
                ['Available Diameters', '12-32 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── JSW Neosteel ─────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $jsw,
            name: 'JSW Neosteel Fe500 TMT Bar',
            slug: 'jsw-neosteel-fe500-tmt',
            shortDesc: 'Strong ductile rebar for residential buildings, bridges, and seismic zones with excellent bond strength.',
            detailedDesc: 'JSW Neosteel Fe500 uses advanced green quenching process per IS 1786:2008, yield strength 500 MPa min (tensile 545 MPa), elongation ≥12-14.5%, dia 8-50 mm. Features low phosphorus/sulfur (max 0.05%), uniform ribs for superior concrete grip, weldable; certified for corrosion resistance.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/42_zyzgfe',
            specs: [
                ['Grade', 'Fe500'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '545 MPa'],
                ['Elongation', '≥12-14.5%'],
                ['Available Diameters', '8-50 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $jsw,
            name: 'JSW Neosteel Fe550 TMT Bar',
            slug: 'jsw-neosteel-fe550-tmt',
            shortDesc: 'High-strength bars for multi-storey buildings and heavy infrastructure projects.',
            detailedDesc: 'Precision-engineered to IS 1786 standards, yield 550 MPa min (tensile 585 MPa), elongation ≥14.5%, available 10-40 mm dia. Superior bendability (4D ratio), nitrogen-enhanced ductility, low impurities for earthquake resistance; eco-friendly manufacturing.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/43_cyxmot',
            specs: [
                ['Grade', 'Fe550'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '585 MPa'],
                ['Elongation', '≥14.5%'],
                ['Available Diameters', '10-40 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $jsw,
            name: 'JSW Neosteel Fe550D TMT Bar',
            slug: 'jsw-neosteel-fe550d-tmt',
            shortDesc: 'Ductile high-strength variant for seismic zones and high-rise constructions.',
            detailedDesc: 'Advanced TEMPCORE process per IS 1786:2008, yield 550 MPa, tensile 600 MPa, elongation >16%, sizes 12-32 mm. Enhanced ductility for earthquake performance, corrosion-resistant composition, optimal rib pattern for 100% bond strength.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/44_mfyo6y',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>16%'],
                ['Available Diameters', '12-32 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $jsw,
            name: 'JSW Neosteel Fe600 TMT Bar',
            slug: 'jsw-neosteel-fe600-tmt',
            shortDesc: 'Ultra-premium strength bars for skyscrapers and extreme load applications.',
            detailedDesc: 'Highest grade with yield strength 600 MPa min per IS 1786, high elongation >12%, select diameters 16-32 mm. Engineered for maximum safety margins, superior fatigue resistance, consistent quality from integrated steel plants.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/45_h4hsbh',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '>12%'],
                ['Available Diameters', '16-32 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── SRMB ─────────────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $srmb,
            name: 'SRMB Fe500D TMT Bar',
            slug: 'srmb-fe500d-tmt',
            shortDesc: 'Superior ductile high-strength bars ideal for earthquake-resistant buildings and bridges.',
            detailedDesc: 'SRMB Fe500D features advanced quenching-billet conditioning per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, available in 8-40 mm diameters. Enhanced corrosion resistance with uniform ribs ensuring optimal concrete bonding; BIS certified for seismic performance.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/46_hbgul7',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-40 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $srmb,
            name: 'SRMB Fe550D TMT Bar',
            slug: 'srmb-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise structures and heavy infrastructure.',
            detailedDesc: 'Engineered to IS 1786 specifications with yield strength 550 MPa minimum (tensile 600 MPa), elongation >14.5%, sizes 10-32 mm. Low carbon equivalent for weldability, superior bendability (4D ratio), and fatigue resistance; manufactured from virgin billets for consistent quality.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/47_ciipkp',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-32 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── SAIL ─────────────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $sail,
            name: 'SAIL Fe500D TMT Bar',
            slug: 'sail-fe500d-tmt',
            shortDesc: 'Ductile high-strength bars perfect for multi-storey buildings, bridges, and earthquake-prone areas.',
            detailedDesc: 'SAIL Fe500D uses advanced controlled quenching per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-50 mm. Features uniform ribs for superior concrete bond, low carbon equivalent for weldability, and corrosion resistance; manufactured from fully killed steel.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/48_htwrfs',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-50 mm'],
                ['Steel Type', 'Fully killed steel'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $sail,
            name: 'SAIL Fe550D TMT Bar',
            slug: 'sail-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise towers and heavy industrial structures.',
            detailedDesc: 'Precision-engineered to IS 1786 specifications with yield strength 550 MPa min (tensile 600 MPa), elongation >14.5%, sizes 10-40 mm. Superior bendability, fatigue resistance, and consistent quality from integrated steel plants; ideal for seismic Zone V construction.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/49_m5irhn',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-40 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $sail,
            name: 'SAIL Fe600 TMT Bar',
            slug: 'sail-fe600-tmt',
            shortDesc: 'Ultra-high strength bars for skyscrapers and extreme load-bearing applications.',
            detailedDesc: 'Highest grade offering yield strength 600 MPa minimum per IS 1786, high elongation ≥12%, available in 16-32 mm diameters. Engineered for maximum safety margins with optimal rib geometry and low impurities for long-term durability.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/50_v8sm6u',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '≥12%'],
                ['Available Diameters', '16-32 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Jindal Panther ────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $jindal,
            name: 'Jindal Panther Fe500D TMT Bar',
            slug: 'jindal-panther-fe500d-tmt',
            shortDesc: 'High-ductility strength bars ideal for multi-storey buildings and seismic zones with superior weldability.',
            detailedDesc: 'Jindal Panther Fe500D uses advanced 3-stage quenching process per IS 1786:2008, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-40 mm. Features low phosphorus/sulphur (max 0.055%), uniform fish-bone ribs for optimal concrete bonding, and corrosion-resistant composition from integrated steel making.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/51_sbsxcw',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-40 mm'],
                ['Process', '3-stage quenching'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $jindal,
            name: 'Jindal Panther Fe550D TMT Bar',
            slug: 'jindal-panther-fe550d-tmt',
            shortDesc: 'Premium ductile rebars for high-rise structures and heavy infrastructure projects.',
            detailedDesc: 'Engineered to IS 1786 standards with yield strength 550 MPa min (tensile 600 MPa), elongation >14.5%, sizes 10-32 mm. Superior bendability (4D ratio), nitrogen-enhanced ductility for earthquake performance, and fatigue resistance; virgin billet manufacturing ensures consistency.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/52_hu9ldt',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-32 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $jindal,
            name: 'Jindal Panther Fe600 TMT Bar',
            slug: 'jindal-panther-fe600-tmt',
            shortDesc: 'Ultra-high strength bars for skyscrapers and extreme load-bearing applications.',
            detailedDesc: 'Highest grade offering yield strength 600 MPa minimum per IS 1786, elongation ≥12%, available 16-32 mm diameters. Optimized rib geometry for maximum grip strength, low impurities for weldability, and long-term durability from JSPL integrated facilities.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/53_mvllam',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '≥12%'],
                ['Available Diameters', '16-32 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Kamdhenu ─────────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $kamdhenu,
            name: 'Kamdhenu Fe500D TMT Bar',
            slug: 'kamdhenu-fe500d-tmt',
            shortDesc: 'Superior ductile high-strength bars ideal for multi-storey buildings and seismic zones.',
            detailedDesc: 'Kamdhenu Fe500D uses advanced quenching process per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-32 mm. Features uniform ribs for optimal concrete bonding, low carbon equivalent for weldability, and corrosion-resistant composition from quality billets.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/54_pkzjav',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-32 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $kamdhenu,
            name: 'Kamdhenu Fe550D TMT Bar',
            slug: 'kamdhenu-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise structures and infrastructure projects.',
            detailedDesc: 'Engineered to IS 1786 specifications with yield strength 550 MPa min (tensile 600 MPa), elongation >14.5%, sizes 10-25 mm. Enhanced bendability (4D ratio), nitrogen-strengthened core for earthquake performance, and fatigue resistance.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/55_wqbgx7',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-25 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $kamdhenu,
            name: 'Kamdhenu Fe600 TMT Bar',
            slug: 'kamdhenu-fe600-tmt',
            shortDesc: 'Ultra-high strength bars for skyscrapers and extreme load applications.',
            detailedDesc: 'Highest grade offering yield strength 600 MPa minimum per IS 1786, elongation ≥12%, available 16-25 mm diameters. Optimized rib pattern for maximum grip strength, consistent quality control, and superior durability characteristics.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/56_gni0zo',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '≥12%'],
                ['Available Diameters', '16-25 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Prime Gold ────────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $primeGold,
            name: 'Prime Gold Fe500D TMT Bar',
            slug: 'prime-gold-fe500d-tmt',
            shortDesc: 'Ductile high-strength bars suitable for multi-storey buildings and seismic zones.',
            detailedDesc: 'Prime Gold Fe500D follows IS 1786:2008 standards with minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-32 mm. Uniform ribs ensure strong concrete bonding, low impurities support weldability, and corrosion-resistant properties enhance durability.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/57_vfavkt',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-32 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $primeGold,
            name: 'Prime Gold Fe550D TMT Bar',
            slug: 'prime-gold-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise structures and infrastructure.',
            detailedDesc: 'Engineered per IS 1786 with yield strength 550 MPa minimum (tensile 600 MPa), elongation >14.5%, sizes 10-25 mm. Excellent bendability (4D ratio), fatigue resistance, and consistent quality from controlled manufacturing process.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/58_wqpyfa',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-25 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Essar ─────────────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $essar,
            name: 'Essar Fe500D TMT Bar',
            slug: 'essar-fe500d-tmt',
            shortDesc: 'Ductile high-strength bars ideal for earthquake-resistant multi-storey buildings.',
            detailedDesc: 'Essar Fe500D uses advanced quenching process per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-40 mm. Uniform ribs provide optimal concrete bonding, low carbon equivalent ensures weldability, and corrosion-resistant composition enhances durability.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/59_v1coyb',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-40 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $essar,
            name: 'Essar Fe550D TMT Bar',
            slug: 'essar-fe550d-tmt',
            shortDesc: 'Premium ductile rebars for high-rise towers and heavy infrastructure.',
            detailedDesc: 'Engineered to IS 1786 specifications with yield strength 550 MPa minimum (tensile 600 MPa), elongation >14.5%, sizes 10-32 mm. Superior bendability (4D ratio), fatigue resistance, and consistent metallurgical properties from integrated steel production.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/60_y0accb',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-32 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Vizag (RINL) ──────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $vizag,
            name: 'Vizag Fe500D TMT Bar',
            slug: 'vizag-fe500d-tmt',
            shortDesc: 'Ductile high-strength bars ideal for heavy industrial structures and seismic zones.',
            detailedDesc: 'Vizag Fe500D uses advanced quenching process per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-40 mm. Uniform ribs ensure superior concrete bonding, low impurities support weldability, and corrosion-resistant properties from integrated steel production.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/61_nxazvk',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-40 mm'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $vizag,
            name: 'Vizag Fe550D TMT Bar',
            slug: 'vizag-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise industrial buildings and infrastructure.',
            detailedDesc: 'Engineered to IS 1786 specifications with yield strength 550 MPa minimum (tensile 600 MPa), elongation >14.5%, sizes 10-32 mm. Excellent bendability (4D ratio), fatigue resistance, and consistent quality from RINL coastal steel plant facilities.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/62_nihhdn',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-32 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $vizag,
            name: 'Vizag Fe600 TMT Bar',
            slug: 'vizag-fe600-tmt',
            shortDesc: 'Ultra-high strength bars for extreme industrial applications and heavy infrastructure.',
            detailedDesc: 'Highest grade offering yield strength 600 MPa minimum per IS 1786, elongation ≥12%, available 16-32 mm diameters. Optimized rib geometry for maximum grip strength, superior durability characteristics, and proven performance in demanding projects.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/63_hfz2af',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '≥12%'],
                ['Available Diameters', '16-32 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        // ── Shyam Steel ───────────────────────────────────────────
        $this->createProduct(
            category: $steel,
            brand: $shyam,
            name: 'Shyam Fe500D TMT Bar',
            slug: 'shyam-fe500d-tmt',
            shortDesc: 'Superior ductile high-strength bars ideal for multi-storey buildings and seismic zones.',
            detailedDesc: 'Shyam Fe500D uses advanced quenching process per IS 1786:2008 standards, minimum yield strength 500 MPa (tensile 565 MPa), elongation ≥16%, diameters 8-32 mm. Features uniform ribs for optimal concrete bonding, low carbon equivalent for weldability, and corrosion-resistant composition from sponge iron-based manufacturing.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/64_jig77h',
            specs: [
                ['Grade', 'Fe500D'],
                ['Yield Strength', '500 MPa min'],
                ['Tensile Strength', '565 MPa'],
                ['Elongation', '≥16%'],
                ['Available Diameters', '8-32 mm'],
                ['Manufacturing', 'Sponge iron-based'],
                ['Conformance', 'IS 1786:2008'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $shyam,
            name: 'Shyam Fe550D TMT Bar',
            slug: 'shyam-fe550d-tmt',
            shortDesc: 'Premium strength ductile rebars for high-rise structures and heavy infrastructure.',
            detailedDesc: 'Engineered to IS 1786 specifications with yield strength 550 MPa minimum (tensile 600 MPa), elongation >14.5%, sizes 10-25 mm. Enhanced bendability (4D ratio), nitrogen-strengthened core for earthquake performance, and fatigue resistance from quality-controlled production.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/65_i9x7ch',
            specs: [
                ['Grade', 'Fe550D'],
                ['Yield Strength', '550 MPa min'],
                ['Tensile Strength', '600 MPa'],
                ['Elongation', '>14.5%'],
                ['Available Diameters', '10-25 mm'],
                ['Bend Ratio', '4D'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->createProduct(
            category: $steel,
            brand: $shyam,
            name: 'Shyam Fe600 TMT Bar',
            slug: 'shyam-fe600-tmt',
            shortDesc: 'Ultra-high strength bars for skyscrapers and extreme load applications.',
            detailedDesc: 'Highest grade offering yield strength 600 MPa minimum per IS 1786, elongation ≥12%, available 16-25 mm diameters. Optimized rib pattern for maximum grip strength, consistent metallurgical properties, and superior durability for demanding construction projects.',
            unit: 'Per Quantal',
            unitWeight: '100 Kg',
            imageUrl: 'https://res.cloudinary.com/dz5x4nipz/image/upload/v1234567890/66_nhpa9k',
            specs: [
                ['Grade', 'Fe600'],
                ['Yield Strength', '600 MPa min'],
                ['Elongation', '≥12%'],
                ['Available Diameters', '16-25 mm'],
                ['Conformance', 'IS 1786'],
            ]
        );

        $this->command->info('Marketplace seed data created successfully.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function createProduct(
        Category $category,
        Brand $brand,
        string $name,
        string $slug,
        string $shortDesc,
        string $detailedDesc,
        string $unit,
        string $unitWeight,
        ?string $imageUrl,
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
                'image_url'            => $imageUrl,
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
        ?string $unitWeight,
        ?string $imageUrl,
        array $specs
    ): void {
        $product = Product::firstOrCreate(
            ['slug' => $slug],
            [
                'category_id'          => $category->id,
                'brand_id'             => null, // NO BRAND for sand
                'name'                 => $name,
                'short_description'    => $shortDesc,
                'detailed_description' => $detailedDesc,
                'unit'                 => $unit,
                'unit_weight'          => $unitWeight,
                'image_url'            => $imageUrl,
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
