<?php

namespace Database\Seeders;

use App\Actions\CreateOrganizationSequencialNumbers;
use App\Enum\PermissionType;
use App\Enum\ProductUnit;
use App\Enum\Status\ClientStatus;
use App\Enum\Status\PaymentStatus;
use App\Enum\Status\ProductStatus;
use App\Enum\Status\SellerStatus;
use App\Enum\Status\SupplierStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Client;
use App\Models\NcmCode;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Uf;
use App\Models\User;
use App\Services\AddressService;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\ClientService;
use App\Services\OrganizationService;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\SellerService;
use App\Services\SupplierService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevelopmentSeeder extends Seeder
{
    public const ORG_DOCUMENT = '11222333000181';

    public const ORG_NAME = 'Apollo Tecnologia e Distribuição Ltda';

    public const DEFAULT_PASSWORD = 'Apollo@2026!';

    public const ADMIN_EMAIL = 'admin@apollo.com.br';

    /**
     * Run the development database seeds.
     *
     * Usage:
     *   php artisan db:seed --class=DevelopmentSeeder
     */
    public function run(): void
    {
        $this->command?->info('Iniciando DevelopmentSeeder...');

        $this->seedPrerequisites();

        // Reativa a proteção de mass-assignment ($fillable) dos Models (desativada por padrão pelo Seeder do Laravel)
        // para que todos os Services funcionem exatamente como nas requisições HTTP da API.
        Model::reguard();

        $existingOrg = Organization::where('document', self::ORG_DOCUMENT)->first();
        if (
            $existingOrg !== null &&
            Product::withoutGlobalScopes()->where('organization_id', $existingOrg->id)->exists()
        ) {
            $adminUser = User::withoutGlobalScopes()
                ->where('organization_id', $existingOrg->id)
                ->where('email', self::ADMIN_EMAIL)
                ->first();

            if ($adminUser !== null) {
                Auth::login($adminUser);
            }

            $this->command?->warn('Os dados de desenvolvimento já foram populados anteriormente para a organização padrão.');
            $this->renderSummary($existingOrg);

            return;
        }

        /** @var OrganizationService $organizationService */
        $organizationService = app(OrganizationService::class);
        /** @var UserService $userService */
        $userService = app(UserService::class);
        /** @var AddressService $addressService */
        $addressService = app(AddressService::class);
        /** @var BrandService $brandService */
        $brandService = app(BrandService::class);
        /** @var CategoryService $categoryService */
        $categoryService = app(CategoryService::class);
        /** @var ProductService $productService */
        $productService = app(ProductService::class);
        /** @var SupplierService $supplierService */
        $supplierService = app(SupplierService::class);
        /** @var SellerService $sellerService */
        $sellerService = app(SellerService::class);
        /** @var ClientService $clientService */
        $clientService = app(ClientService::class);
        /** @var PurchaseOrderService $purchaseOrderService */
        $purchaseOrderService = app(PurchaseOrderService::class);
        /** @var SalesOrderService $salesOrderService */
        $salesOrderService = app(SalesOrderService::class);

        $organization = DB::transaction(function () use (
            $existingOrg,
            $organizationService,
            $userService,
            $addressService,
            $brandService,
            $categoryService,
            $productService,
            $supplierService,
            $sellerService,
            $clientService,
            $purchaseOrderService,
            $salesOrderService
        ) {
            // 1. Organização principal e Usuário Administrador
            if ($existingOrg !== null) {
                $organization = $existingOrg;
                $adminUser = User::withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('email', self::ADMIN_EMAIL)
                    ->firstOrFail();
                $adminUser->permissions()->sync(Permission::all());
            } else {
                [$organization, $adminUser] = $organizationService->create([
                    'name' => self::ORG_NAME,
                    'document' => self::ORG_DOCUMENT,
                    'email' => self::ADMIN_EMAIL,
                    'password' => self::DEFAULT_PASSWORD,
                ]);
            }

            // Garante que a tabela sequencial_numbers esteja inicializada para a organização
            if ($organization->sequencialNumber()->count() === 0) {
                app(CreateOrganizationSequencialNumbers::class)->execute($organization);
            }

            // Autentica o administrador para ativar o OrganizationScope nos Services
            Auth::login($adminUser);

            // 2. Usuários operadores com diferentes perfis de permissão
            $this->seedUsers($userService, $adminUser);

            // 3. Marcas
            $brands = $this->seedBrands($brandService, $adminUser);

            // 4. Categorias
            $categories = $this->seedCategories($categoryService, $adminUser);

            // 5. Produtos
            $products = $this->seedProducts($productService, $adminUser, $brands, $categories);

            // 6. Fornecedores + Endereços
            $suppliers = $this->seedSuppliers($supplierService, $addressService, $adminUser);

            // 7. Vendedores + Endereços
            $sellers = $this->seedSellers($sellerService, $addressService, $adminUser);

            // 8. Clientes + Múltiplos Endereços
            $clients = $this->seedClients($clientService, $addressService, $adminUser);

            // 9. Pedidos de Compra (Confirmados, Rascunho, Cancelado e Reaberto)
            $this->seedPurchaseOrders($purchaseOrderService, $productService, $adminUser, $suppliers, $products);

            // 10. Pedidos de Venda (Confirmados, Rascunho, Cancelado e Reaberto)
            $this->seedSalesOrders($salesOrderService, $productService, $adminUser, $clients, $sellers, $products);

            return $organization;
        });

        $this->command?->info('DevelopmentSeeder concluído com sucesso!');
        $this->renderSummary($organization);
    }

    /**
     * Garante que permissões, UFs, municípios e códigos NCM existam no banco.
     */
    private function seedPrerequisites(): void
    {
        if (Permission::count() === 0) {
            $this->command?->info('Populando permissões (PermissionSeeder)...');
            $this->call(PermissionSeeder::class);
        }

        if (Uf::count() === 0) {
            $this->command?->info('Populando estados/UFs (UfSeeder)...');
            $this->call(UfSeeder::class);
        }

        if (City::count() === 0) {
            $this->command?->info('Populando municípios brasileiros...');
            $this->seedCitiesFast();
        }

        $this->seedNcmCodes();
    }

    /**
     * Insere os municípios em lote a partir do CSV (muito mais rápido que inserção unitária)
     * ou garante ao menos as principais capitais/cidades usadas no seeder.
     */
    private function seedCitiesFast(): void
    {
        $path = database_path('data/municipios.csv');

        if (file_exists($path) && is_readable($path)) {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $header = fgetcsv($handle, 0, ',', '"', '\\');
                if ($header !== false) {
                    $header = array_map(fn ($h) => strtolower(trim($h)), $header);
                    $batch = [];

                    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                        if (count($row) !== count($header)) {
                            continue;
                        }
                        $data = array_combine($header, $row);
                        $batch[] = [
                            'ibge_code' => $data['codigo_ibge'],
                            'name' => $data['nome'],
                            'uf_ibge_code' => $data['codigo_uf'],
                        ];

                        if (count($batch) >= 1000) {
                            City::upsert($batch, ['ibge_code'], ['name', 'uf_ibge_code']);
                            $batch = [];
                        }
                    }

                    if (! empty($batch)) {
                        City::upsert($batch, ['ibge_code'], ['name', 'uf_ibge_code']);
                    }
                }
                fclose($handle);

                return;
            }
        }

        // Fallback caso o arquivo CSV não esteja disponível
        $fallbackCities = [
            ['ibge_code' => '3550308', 'name' => 'São Paulo', 'uf_ibge_code' => '35'],
            ['ibge_code' => '3509502', 'name' => 'Campinas', 'uf_ibge_code' => '35'],
            ['ibge_code' => '3505708', 'name' => 'Barueri', 'uf_ibge_code' => '35'],
            ['ibge_code' => '3304557', 'name' => 'Rio de Janeiro', 'uf_ibge_code' => '33'],
            ['ibge_code' => '3106200', 'name' => 'Belo Horizonte', 'uf_ibge_code' => '31'],
            ['ibge_code' => '4106902', 'name' => 'Curitiba', 'uf_ibge_code' => '41'],
            ['ibge_code' => '4205407', 'name' => 'Florianópolis', 'uf_ibge_code' => '42'],
            ['ibge_code' => '4209102', 'name' => 'Joinville', 'uf_ibge_code' => '42'],
            ['ibge_code' => '4314902', 'name' => 'Porto Alegre', 'uf_ibge_code' => '43'],
            ['ibge_code' => '5300108', 'name' => 'Brasília', 'uf_ibge_code' => '53'],
        ];

        City::upsert($fallbackCities, ['ibge_code'], ['name', 'uf_ibge_code']);
    }

    /**
     * Pré-cadastra códigos NCM reais para evitar chamadas HTTP externas durante o seed.
     */
    private function seedNcmCodes(): void
    {
        $ncms = [
            ['code' => '84713012', 'description' => 'Máquinas automáticas para processamento de dados, portáteis (Notebooks)'],
            ['code' => '84716052', 'description' => 'Teclados para máquinas automáticas de processamento de dados'],
            ['code' => '84716053', 'description' => 'Indicadores ou apontadores (mouses e trackballs)'],
            ['code' => '85285220', 'description' => 'Monitores com tela de cristal líquido (LCD/LED) policromáticos'],
            ['code' => '84717012', 'description' => 'Unidades de memória de estado sólido (SSD)'],
            ['code' => '85176255', 'description' => 'Roteadores digitais e switches para redes locais'],
            ['code' => '85044040', 'description' => 'Equipamentos de alimentação ininterrupta de energia (Nobreaks / UPS)'],
            ['code' => '84433111', 'description' => 'Impressoras multifuncionais a laser ou LED'],
            ['code' => '85183000', 'description' => 'Fones de ouvido (headsets), mesmo combinados com microfone'],
            ['code' => '85444200', 'description' => 'Cabos de rede e condutores elétricos munidos de peças de conexão'],
        ];

        foreach ($ncms as $ncm) {
            NcmCode::updateOrCreate(
                ['code' => $ncm['code']],
                [
                    'description' => $ncm['description'],
                    'valid_from' => '2022-04-01',
                    'isActive' => true,
                ]
            );
        }
    }

    /**
     * Cria usuários operadores com diferentes conjuntos de permissões.
     */
    private function seedUsers(UserService $userService, User $adminUser): void
    {
        $users = [
            [
                'name' => 'Ana Paula Comercial',
                'email' => 'vendas@apollo.com.br',
                'password' => self::DEFAULT_PASSWORD,
                'permissions' => array_values(array_unique(array_merge(
                    PermissionType::byModel('client'),
                    PermissionType::byModel('seller'),
                    PermissionType::byModel('sales-order'),
                    [
                        PermissionType::PRODUCT_READ->value,
                        PermissionType::BRAND_READ->value,
                        PermissionType::CATEGORY_READ->value,
                    ]
                ))),
            ],
            [
                'name' => 'Carlos Eduardo Suprimentos',
                'email' => 'compras@apollo.com.br',
                'password' => self::DEFAULT_PASSWORD,
                'permissions' => array_values(array_unique(array_merge(
                    PermissionType::byModel('supplier'),
                    PermissionType::byModel('brand'),
                    PermissionType::byModel('category'),
                    PermissionType::byModel('product'),
                    PermissionType::byModel('purchase-order'),
                ))),
            ],
            [
                'name' => 'Mariana Costa Financeiro',
                'email' => 'financeiro@apollo.com.br',
                'password' => self::DEFAULT_PASSWORD,
                'permissions' => [
                    PermissionType::CLIENT_READ->value,
                    PermissionType::SELLER_READ->value,
                    PermissionType::SUPPLIER_READ->value,
                    PermissionType::PRODUCT_READ->value,
                    PermissionType::SALES_ORDER_READ->value,
                    PermissionType::SALES_ORDER_UPDATE->value,
                    PermissionType::PURCHASE_ORDER_READ->value,
                    PermissionType::PURCHASE_ORDER_UPDATE->value,
                ],
            ],
            [
                'name' => 'Lucas Ferreira Auditoria',
                'email' => 'consulta@apollo.com.br',
                'password' => self::DEFAULT_PASSWORD,
                'permissions' => [
                    PermissionType::USER_READ->value,
                    PermissionType::CLIENT_READ->value,
                    PermissionType::SELLER_READ->value,
                    PermissionType::SUPPLIER_READ->value,
                    PermissionType::BRAND_READ->value,
                    PermissionType::CATEGORY_READ->value,
                    PermissionType::PRODUCT_READ->value,
                    PermissionType::SALES_ORDER_READ->value,
                    PermissionType::PURCHASE_ORDER_READ->value,
                ],
            ],
        ];

        foreach ($users as $userData) {
            $exists = User::withoutGlobalScopes()
                ->where('organization_id', $adminUser->organization_id)
                ->where('email', $userData['email'])
                ->exists();

            if (! $exists) {
                $userService->create($userData, $adminUser);
            }
        }
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(BrandService $brandService, User $adminUser): array
    {
        $brandsData = [
            'dell' => [
                'name' => 'Dell Technologies',
                'description' => 'Computadores corporativos, notebooks, servidores e monitores profissionais.',
            ],
            'logitech' => [
                'name' => 'Logitech',
                'description' => 'Periféricos de alta precisão, mouses, teclados e soluções de videoconferência.',
            ],
            'samsung' => [
                'name' => 'Samsung',
                'description' => 'Monitores profissionais, unidades SSD NVMe e soluções de display.',
            ],
            'kingston' => [
                'name' => 'Kingston',
                'description' => 'Memórias RAM corporativas e armazenamento sólido de alto desempenho.',
            ],
            'intelbras' => [
                'name' => 'Intelbras',
                'description' => 'Equipamentos de redes, conectividade corporativa, nobreaks e segurança.',
            ],
            'hp' => [
                'name' => 'HP Brasil',
                'description' => 'Impressoras multifuncionais corporativas, suprimentos e estações de trabalho.',
            ],
        ];

        $brands = [];
        foreach ($brandsData as $key => $data) {
            $brands[$key] = $brandService->create($data, $adminUser);
        }

        return $brands;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(CategoryService $categoryService, User $adminUser): array
    {
        $categoriesData = [
            'notebooks' => [
                'name' => 'Notebooks e Computadores',
                'description' => 'Notebooks corporativos, desktops e workstations.',
            ],
            'perifericos' => [
                'name' => 'Periféricos e Acessórios',
                'description' => 'Teclados, mouses, headsets e acessórios de escritório.',
            ],
            'monitores' => [
                'name' => 'Monitores e Vídeo',
                'description' => 'Monitores LED, IPS, Ultrawide e telas profissionais.',
            ],
            'armazenamento' => [
                'name' => 'Armazenamento e Memória',
                'description' => 'Unidades SSD SATA/NVMe, memórias RAM e armazenamento externo.',
            ],
            'redes' => [
                'name' => 'Redes e Infraestrutura',
                'description' => 'Switches, roteadores corporativos, nobreaks e cabeamento estruturado.',
            ],
            'impressao' => [
                'name' => 'Impressão e Digitalização',
                'description' => 'Impressoras laser, multifuncionais e suprimentos.',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $key => $data) {
            $categories[$key] = $categoryService->create($data, $adminUser);
        }

        return $categories;
    }

    /**
     * @param  array<string, Brand>  $brands
     * @param  array<string, Category>  $categories
     * @return array<string, Product>
     */
    private function seedProducts(
        ProductService $productService,
        User $adminUser,
        array $brands,
        array $categories
    ): array {
        $productsData = [
            'notebook_dell' => [
                'name' => 'Notebook Dell Latitude 5440 i7 16GB',
                'ean' => '7891000000014',
                'ncm' => '84713012',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '4250.00',
                'sale_price' => '5890.00',
                'stock_quantity' => 25,
                'brand_id' => $brands['dell']->id,
                'categories' => [$categories['notebooks']],
                'description' => 'Notebook corporativo de 14 polegadas Full HD com processador Intel Core i7 de 13ª geração, 16GB RAM e 512GB SSD.',
                'status' => ProductStatus::Active,
            ],
            'monitor_dell' => [
                'name' => 'Monitor Dell UltraSharp 27" 4K USB-C',
                'ean' => '7891000000021',
                'ncm' => '85285220',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '2380.00',
                'sale_price' => '3490.00',
                'stock_quantity' => 18,
                'brand_id' => $brands['dell']->id,
                'categories' => [$categories['monitores'], $categories['perifericos']],
                'description' => 'Monitor profissional 27 polegadas 4K IPS Black com hub USB-C integrado e fornecimento de energia de até 90W.',
                'status' => ProductStatus::Active,
            ],
            'monitor_samsung' => [
                'name' => 'Monitor Samsung ViewFinity S6 34" WQHD',
                'ean' => '7891000000038',
                'ncm' => '85285220',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '1950.00',
                'sale_price' => '2799.90',
                'stock_quantity' => 12,
                'brand_id' => $brands['samsung']->id,
                'categories' => [$categories['monitores']],
                'description' => 'Monitor curvo ultrawide 21:9 com taxa de atualização de 100Hz e conexão LAN RJ45 integrada.',
                'status' => ProductStatus::Active,
            ],
            'mouse_logitech' => [
                'name' => 'Mouse Sem Fio Logitech MX Master 3S',
                'ean' => '7891000000045',
                'ncm' => '84716053',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '390.00',
                'sale_price' => '649.90',
                'stock_quantity' => 60,
                'brand_id' => $brands['logitech']->id,
                'categories' => [$categories['perifericos']],
                'description' => 'Mouse ergonômico sem fio com sensor óptico de 8000 DPI, cliques silenciosos e rolagem eletromagnética MagSpeed.',
                'status' => ProductStatus::Active,
            ],
            'teclado_logitech' => [
                'name' => 'Teclado Mecânico Logitech MX Mechanical',
                'ean' => '7891000000052',
                'ncm' => '84716052',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '580.00',
                'sale_price' => '899.90',
                'stock_quantity' => 40,
                'brand_id' => $brands['logitech']->id,
                'categories' => [$categories['perifericos']],
                'description' => 'Teclado mecânico de perfil baixo com iluminação inteligente e conexão multi-dispositivo Bluetooth/Logi Bolt.',
                'status' => ProductStatus::Active,
            ],
            'headset_logitech' => [
                'name' => 'Headset Logitech Zone Vibe 100 Bluetooth',
                'ean' => '7891000000069',
                'ncm' => '85183000',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '340.00',
                'sale_price' => '559.00',
                'stock_quantity' => 35,
                'brand_id' => $brands['logitech']->id,
                'categories' => [$categories['perifericos']],
                'description' => 'Headset sem fio leve com microfone com cancelamento de ruído para reuniões e trabalho híbrido.',
                'status' => ProductStatus::Active,
            ],
            'ssd_kingston' => [
                'name' => 'SSD Kingston KC3000 1TB M.2 NVMe PCIe 4.0',
                'ean' => '7891000000076',
                'ncm' => '84717012',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '430.00',
                'sale_price' => '689.00',
                'stock_quantity' => 80,
                'brand_id' => $brands['kingston']->id,
                'categories' => [$categories['armazenamento']],
                'description' => 'Unidade SSD M.2 NVMe PCIe 4.0 de alto desempenho com leitura e gravação de até 7000 MB/s.',
                'status' => ProductStatus::Active,
            ],
            'switch_intelbras' => [
                'name' => 'Switch Intelbras 24 Portas Gigabit SG 2404',
                'ean' => '7891000000083',
                'ncm' => '85176255',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '890.00',
                'sale_price' => '1390.00',
                'stock_quantity' => 15,
                'brand_id' => $brands['intelbras']->id,
                'categories' => [$categories['redes']],
                'description' => 'Switch rack 19" com 24 portas Gigabit Ethernet e 4 portas Mini-GBIC (SFP) para infraestrutura de rede.',
                'status' => ProductStatus::Active,
            ],
            'nobreak_intelbras' => [
                'name' => 'Nobreak Senoidal Intelbras SNB 1500VA Bivolt',
                'ean' => '7891000000090',
                'ncm' => '85044040',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '1120.00',
                'sale_price' => '1690.00',
                'stock_quantity' => 10,
                'brand_id' => $brands['intelbras']->id,
                'categories' => [$categories['redes']],
                'description' => 'Nobreak interativo senoidal puro de 1500VA para proteção de servidores e estações de trabalho.',
                'status' => ProductStatus::Active,
            ],
            'impressora_hp' => [
                'name' => 'Multifuncional HP LaserJet Pro MFP 4103fdw',
                'ean' => '7891000000106',
                'ncm' => '84433111',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '2150.00',
                'sale_price' => '3190.00',
                'stock_quantity' => 8,
                'brand_id' => $brands['hp']->id,
                'categories' => [$categories['impressao']],
                'description' => 'Multifuncional laser monocromática de alta velocidade com impressão frente e verso, ADF e Wi-Fi.',
                'status' => ProductStatus::Active,
            ],
            'cabo_rede_cat6' => [
                'name' => 'Cabo de Rede UTP Cat6 Homologado Azul',
                'ean' => '7891000000113',
                'ncm' => '85444200',
                'unit' => ProductUnit::Meter->value,
                'cost_price' => '2.40',
                'sale_price' => '4.50',
                'stock_quantity' => 305,
                'brand_id' => $brands['intelbras']->id,
                'categories' => [$categories['redes']],
                'description' => 'Cabo de par trançado 100% cobre Cat6 vendido por metro para cabeamento estruturado.',
                'status' => ProductStatus::Active,
            ],
            'ssd_sata_legado' => [
                'name' => 'SSD Kingston A400 240GB SATA III 2.5"',
                'ean' => '7891000000120',
                'ncm' => '84717012',
                'unit' => ProductUnit::Unit->value,
                'cost_price' => '115.00',
                'sale_price' => '189.90',
                'stock_quantity' => 0,
                'brand_id' => $brands['kingston']->id,
                'categories' => [$categories['armazenamento']],
                'description' => 'Modelo legado substituído pela linha NVMe de maior capacidade.',
                'status' => ProductStatus::Discontinued,
            ],
        ];

        $products = [];
        foreach ($productsData as $key => $data) {
            $status = $data['status'];
            $description = $data['description'] ?? null;
            unset($data['status'], $data['description']);

            $product = $productService->create($data, $adminUser);
            $product->description = $description;
            $product->status = $status;
            $product->save();

            $products[$key] = $product;
        }

        return $products;
    }

    /**
     * @return array<string, Supplier>
     */
    private function seedSuppliers(
        SupplierService $supplierService,
        AddressService $addressService,
        User $adminUser
    ): array {
        $suppliersData = [
            'ingram' => [
                'supplier' => [
                    'legal_name' => 'Ingram Micro Brasil Distribuidora de Tecnologia Ltda',
                    'trade_name' => 'Ingram Micro Brasil',
                    'document' => '33592510000154',
                    'state_registration' => '110042490114',
                    'email' => 'pedidos.br@ingrammicro.com.br',
                    'phone' => '1130774000',
                    'status' => SupplierStatus::Active->value,
                ],
                'address' => [
                    'street' => 'Alameda Rio Negro',
                    'number' => '585',
                    'complement' => 'Bloco B - 8º Andar',
                    'neighborhood' => 'Alphaville Industrial',
                    'cep' => '06454000',
                    'city_ibge_code' => '3505708', // Barueri - SP
                    'is_default' => true,
                ],
            ],
            'pauta' => [
                'supplier' => [
                    'legal_name' => 'Pauta Distribuição e Logística S.A.',
                    'trade_name' => 'Pauta Distribuição',
                    'document' => '60746948000112',
                    'state_registration' => '254981230',
                    'email' => 'comercial@pauta.com.br',
                    'phone' => '4832165000',
                    'status' => SupplierStatus::Active->value,
                ],
                'address' => [
                    'street' => 'Rodovia SC-401',
                    'number' => '4100',
                    'complement' => 'Parque Tecnológico Alfa',
                    'neighborhood' => 'Saco Grande',
                    'cep' => '88032005',
                    'city_ibge_code' => '4205407', // Florianópolis - SC
                    'is_default' => true,
                ],
            ],
            'allnations' => [
                'supplier' => [
                    'legal_name' => 'All Nations Comércio Exterior e Informática S.A.',
                    'trade_name' => 'All Nations',
                    'document' => '00000000000191',
                    'state_registration' => '0819043200156',
                    'email' => 'faturamento@allnations.com.br',
                    'phone' => '2121289000',
                    'status' => SupplierStatus::Active->value,
                ],
                'address' => [
                    'street' => 'Avenida Embaixador Abelardo Bueno',
                    'number' => '3500',
                    'complement' => 'Sala 601',
                    'neighborhood' => 'Barra da Tijuca',
                    'cep' => '22775040',
                    'city_ibge_code' => '3304557', // Rio de Janeiro - RJ
                    'is_default' => true,
                ],
            ],
            'inativo' => [
                'supplier' => [
                    'legal_name' => 'SulComponentes Importação de Eletrônicos Ltda',
                    'trade_name' => 'SulComponentes',
                    'document' => '90400888000142',
                    'state_registration' => '0962451870',
                    'email' => 'contato@sulcomponentes.com.br',
                    'phone' => '5133448800',
                    'status' => SupplierStatus::Inactive->value,
                ],
                'address' => [
                    'street' => 'Avenida Carlos Gomes',
                    'number' => '700',
                    'complement' => 'Conjunto 302',
                    'neighborhood' => 'Boa Vista',
                    'cep' => '90480000',
                    'city_ibge_code' => '4314902', // Porto Alegre - RS
                    'is_default' => true,
                ],
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $key => $entry) {
            $supplier = $supplierService->create($entry['supplier'], $adminUser);
            $addressService->create($supplier, $entry['address']);
            $suppliers[$key] = $supplier;
        }

        return $suppliers;
    }

    /**
     * @return array<string, Seller>
     */
    private function seedSellers(
        SellerService $sellerService,
        AddressService $addressService,
        User $adminUser
    ): array {
        $sellersData = [
            'rafael' => [
                'seller' => [
                    'legal_name' => 'Rafael Almeida Representações Comerciais Ltda',
                    'trade_name' => 'Rafael Almeida',
                    'document' => '07526557000100',
                    'state_registration' => '148752369110',
                    'email' => 'rafael.almeida@apollo.com.br',
                    'phone' => '11988112233',
                    'status' => SellerStatus::Active->value,
                    'started_at' => now()->subMonths(18)->toDateString(),
                ],
                'address' => [
                    'street' => 'Rua Funchal',
                    'number' => '418',
                    'complement' => '12º Andar',
                    'neighborhood' => 'Vila Olímpia',
                    'cep' => '04551060',
                    'city_ibge_code' => '3550308', // São Paulo - SP
                    'is_default' => true,
                ],
            ],
            'beatriz' => [
                'seller' => [
                    'legal_name' => 'Beatriz Nogueira Mendes',
                    'trade_name' => 'Beatriz Mendes',
                    'document' => '52998224725',
                    'email' => 'beatriz.mendes@apollo.com.br',
                    'phone' => '19997445566',
                    'status' => SellerStatus::Active->value,
                    'started_at' => now()->subMonths(10)->toDateString(),
                ],
                'address' => [
                    'street' => 'Avenida José de Souza Campos',
                    'number' => '900',
                    'complement' => 'Sala 42',
                    'neighborhood' => 'Cambuí',
                    'cep' => '13025320',
                    'city_ibge_code' => '3509502', // Campinas - SP
                    'is_default' => true,
                ],
            ],
            'thiago' => [
                'seller' => [
                    'legal_name' => 'Thiago Oliveira Consultoria em Vendas',
                    'trade_name' => 'Thiago Oliveira',
                    'document' => '11144477735',
                    'email' => 'thiago.oliveira@apollo.com.br',
                    'phone' => '41996223344',
                    'status' => SellerStatus::Active->value,
                    'started_at' => now()->subMonths(5)->toDateString(),
                ],
                'address' => [
                    'street' => 'Avenida Sete de Setembro',
                    'number' => '4698',
                    'complement' => 'Conjunto 805',
                    'neighborhood' => 'Batel',
                    'cep' => '80240000',
                    'city_ibge_code' => '4106902', // Curitiba - PR
                    'is_default' => true,
                ],
            ],
            'inativo' => [
                'seller' => [
                    'legal_name' => 'Fernando Henrique Souza',
                    'trade_name' => 'Fernando Souza',
                    'document' => '12345678909',
                    'email' => 'fernando.souza@apollo.com.br',
                    'phone' => '31991112233',
                    'status' => SellerStatus::Inactive->value,
                    'started_at' => now()->subMonths(24)->toDateString(),
                ],
                'address' => [
                    'street' => 'Avenida do Contorno',
                    'number' => '6594',
                    'neighborhood' => 'Savassi',
                    'cep' => '30110044',
                    'city_ibge_code' => '3106200', // Belo Horizonte - MG
                    'is_default' => true,
                ],
            ],
        ];

        $sellers = [];
        foreach ($sellersData as $key => $entry) {
            $seller = $sellerService->create($entry['seller'], $adminUser);
            $addressService->create($seller, $entry['address']);
            $sellers[$key] = $seller;
        }

        return $sellers;
    }

    /**
     * @return array<string, Client>
     */
    private function seedClients(
        ClientService $clientService,
        AddressService $addressService,
        User $adminUser
    ): array {
        $clientsData = [
            'horizonte' => [
                'client' => [
                    'legal_name' => 'Horizonte Engenharia e Construções S.A.',
                    'trade_name' => 'Horizonte Engenharia',
                    'document' => '60701190000104',
                    'state_registration' => '112358132110',
                    'email' => 'ti.compras@horizonteengenharia.com.br',
                    'phone' => '1135001200',
                    'status' => ClientStatus::Active->value,
                ],
                'addresses' => [
                    [
                        'street' => 'Avenida Brigadeiro Faria Lima',
                        'number' => '3477',
                        'complement' => 'Torre Norte - 10º Andar (Matriz)',
                        'neighborhood' => 'Itaim Bibi',
                        'cep' => '04538133',
                        'city_ibge_code' => '3550308', // São Paulo - SP
                        'is_default' => true,
                    ],
                    [
                        'street' => 'Rua Dr. Quirino',
                        'number' => '1350',
                        'complement' => 'Centro Logístico Filial Interior',
                        'neighborhood' => 'Centro',
                        'cep' => '13015082',
                        'city_ibge_code' => '3509502', // Campinas - SP
                        'is_default' => false,
                    ],
                ],
            ],
            'novaclinica' => [
                'client' => [
                    'legal_name' => 'NovaClínica Diagnósticos Médicos Ltda',
                    'trade_name' => 'NovaClínica Saúde',
                    'document' => '47960950000121',
                    'state_registration' => '0629845100088',
                    'email' => 'suprimentos@novaclinica.med.br',
                    'phone' => '3132804500',
                    'status' => ClientStatus::Active->value,
                ],
                'addresses' => [
                    [
                        'street' => 'Avenida Afonso Pena',
                        'number' => '2150',
                        'complement' => 'Edifício Medical Center',
                        'neighborhood' => 'Funcionários',
                        'cep' => '30130007',
                        'city_ibge_code' => '3106200', // Belo Horizonte - MG
                        'is_default' => true,
                    ],
                ],
            ],
            'atlastech' => [
                'client' => [
                    'legal_name' => 'Atlas Desenvolvimento de Software Ltda',
                    'trade_name' => 'Atlas Tech',
                    'document' => '33000167000101',
                    'state_registration' => '258741369',
                    'email' => 'infra@atlastech.io',
                    'phone' => '4734339900',
                    'status' => ClientStatus::Active->value,
                ],
                'addresses' => [
                    [
                        'street' => 'Rua XV de Novembro',
                        'number' => '1280',
                        'complement' => 'Hub de Inovação - Sala 304',
                        'neighborhood' => 'América',
                        'cep' => '89201602',
                        'city_ibge_code' => '4209102', // Joinville - SC
                        'is_default' => true,
                    ],
                ],
            ],
            'vanguard' => [
                'client' => [
                    'legal_name' => 'Vanguard Contabilidade e Consultoria Empresarial S/S',
                    'trade_name' => 'Vanguard Contábil',
                    'document' => '09346601000125',
                    'state_registration' => '9045123678',
                    'email' => 'administrativo@vanguardcontabil.com.br',
                    'phone' => '4130227788',
                    'status' => ClientStatus::Active->value,
                ],
                'addresses' => [
                    [
                        'street' => 'Rua Marechal Deodoro',
                        'number' => '630',
                        'complement' => '15º Andar',
                        'neighborhood' => 'Centro',
                        'cep' => '80010010',
                        'city_ibge_code' => '4106902', // Curitiba - PR
                        'is_default' => true,
                    ],
                ],
            ],
            'pf_cliente' => [
                'client' => [
                    'legal_name' => 'Gabriel Henrique Vasconcelos',
                    'trade_name' => 'Gabriel Vasconcelos (Arquiteto)',
                    'document' => '98765432100',
                    'email' => 'gabriel@vasconcelosarquitetura.com.br',
                    'phone' => '61998765432',
                    'status' => ClientStatus::Active->value,
                ],
                'addresses' => [
                    [
                        'street' => 'SHIS QI 11 Bloco K',
                        'number' => '45',
                        'complement' => 'Sala 201',
                        'neighborhood' => 'Lago Sul',
                        'cep' => '71625600',
                        'city_ibge_code' => '5300108', // Brasília - DF
                        'is_default' => true,
                    ],
                ],
            ],
            'inativo' => [
                'client' => [
                    'legal_name' => 'Omega Logística Integrada Ltda',
                    'trade_name' => 'Omega Logística',
                    'document' => '02558157000162',
                    'state_registration' => '114789632118',
                    'email' => 'contato@omegalogistica.com.br',
                    'phone' => '1140049090',
                    'status' => ClientStatus::Inactive->value,
                ],
                'addresses' => [
                    [
                        'street' => 'Avenida das Nações Unidas',
                        'number' => '12901',
                        'neighborhood' => 'Brooklin Paulista',
                        'cep' => '04578910',
                        'city_ibge_code' => '3550308', // São Paulo - SP
                        'is_default' => true,
                    ],
                ],
            ],
        ];

        $clients = [];
        foreach ($clientsData as $key => $entry) {
            $client = $clientService->create($entry['client'], $adminUser);
            foreach ($entry['addresses'] as $addressData) {
                $addressService->create($client, $addressData);
            }
            $clients[$key] = $client;
        }

        return $clients;
    }

    /**
     * Popula Pedidos de Compra em diferentes estágios do ciclo de vida:
     * - #1: Confirmado e Pago (gera movimentação de entrada no estoque)
     * - #2: Confirmado e Pendente de pagamento (gera movimentação de entrada no estoque)
     * - #3: Em Rascunho (pronto para edição/confirmação no frontend)
     * - #4: Confirmado e depois Reaberto -> torna-se Cancelado e gera o pedido #5 (Rascunho vinculado via replaces_order_id)
     *
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Product>  $products
     */
    private function seedPurchaseOrders(
        PurchaseOrderService $purchaseOrderService,
        ProductService $productService,
        User $adminUser,
        array $suppliers,
        array $products
    ): void {
        // Pedido de Compra #1: Confirmado e Pago
        $po1 = $purchaseOrderService->create([
            'supplier_id' => $suppliers['ingram']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['notebook_dell']->id, 'quantity' => 10],
                ['number' => 2, 'product_id' => $products['monitor_dell']->id, 'quantity' => 8],
                ['number' => 3, 'product_id' => $products['ssd_kingston']->id, 'quantity' => 20],
            ],
        ], $adminUser);
        $purchaseOrderService->update($po1, ['payment_status' => PaymentStatus::Paid->value]);
        $purchaseOrderService->confirm($po1->refresh());

        // Pedido de Compra #2: Confirmado e Pendente
        $po2 = $purchaseOrderService->create([
            'supplier_id' => $suppliers['pauta']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['mouse_logitech']->id, 'quantity' => 30],
                ['number' => 2, 'product_id' => $products['teclado_logitech']->id, 'quantity' => 15],
                ['number' => 3, 'product_id' => $products['switch_intelbras']->id, 'quantity' => 6],
            ],
        ], $adminUser);
        $purchaseOrderService->confirm($po2->refresh());

        // Pedido de Compra #3: Em Rascunho (Aberto para edição)
        $purchaseOrderService->create([
            'supplier_id' => $suppliers['allnations']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['impressora_hp']->id, 'quantity' => 5],
                ['number' => 2, 'product_id' => $products['nobreak_intelbras']->id, 'quantity' => 8],
            ],
        ], $adminUser);

        // Pedido de Compra #4: Confirmado e posteriormente Reaberto (gera o Pedido #5 com replaces_order_id)
        $po4 = $purchaseOrderService->create([
            'supplier_id' => $suppliers['ingram']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['monitor_samsung']->id, 'quantity' => 6],
                ['number' => 2, 'product_id' => $products['headset_logitech']->id, 'quantity' => 12],
            ],
        ], $adminUser);
        $purchaseOrderService->confirm($po4->refresh());

        // Atualiza o preço de custo do monitor Samsung antes de reabrir para demonstrar histórico de preços
        $productService->update($products['monitor_samsung'], ['cost_price' => '1990.00']);
        $purchaseOrderService->recreateFrom(
            $po4->refresh(),
            'Revisão de cotação do lote e ajuste nas quantidades negociadas com o distribuidor.'
        );
    }

    /**
     * Popula Pedidos de Venda em diferentes estágios do ciclo de vida:
     * - #1: Confirmado e Pago (gera movimentação de saída no estoque)
     * - #2: Confirmado e Pendente de pagamento (gera movimentação de saída no estoque)
     * - #3: Em Rascunho (pronto para edição/confirmação no frontend)
     * - #4: Cancelado diretamente a partir de rascunho
     * - #5: Confirmado e depois Reaberto -> torna-se Cancelado (estorna estoque) e gera o pedido #6 (Rascunho com replaces_order_id)
     *
     * @param  array<string, Client>  $clients
     * @param  array<string, Seller>  $sellers
     * @param  array<string, Product>  $products
     */
    private function seedSalesOrders(
        SalesOrderService $salesOrderService,
        ProductService $productService,
        User $adminUser,
        array $clients,
        array $sellers,
        array $products
    ): void {
        // Pedido de Venda #1: Confirmado e Pago
        $so1 = $salesOrderService->create([
            'client_id' => $clients['horizonte']->id,
            'seller_id' => $sellers['rafael']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['notebook_dell']->id, 'quantity' => 5],
                ['number' => 2, 'product_id' => $products['monitor_dell']->id, 'quantity' => 5],
                ['number' => 3, 'product_id' => $products['mouse_logitech']->id, 'quantity' => 10],
                ['number' => 4, 'product_id' => $products['teclado_logitech']->id, 'quantity' => 10],
            ],
        ], $adminUser);
        $salesOrderService->update($so1, ['payment_status' => PaymentStatus::Paid->value]);
        $salesOrderService->confirm($so1->refresh());

        // Pedido de Venda #2: Confirmado e Pendente
        $so2 = $salesOrderService->create([
            'client_id' => $clients['novaclinica']->id,
            'seller_id' => $sellers['beatriz']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['impressora_hp']->id, 'quantity' => 2],
                ['number' => 2, 'product_id' => $products['nobreak_intelbras']->id, 'quantity' => 3],
                ['number' => 3, 'product_id' => $products['switch_intelbras']->id, 'quantity' => 2],
                ['number' => 4, 'product_id' => $products['cabo_rede_cat6']->id, 'quantity' => 100],
            ],
        ], $adminUser);
        $salesOrderService->confirm($so2->refresh());

        // Pedido de Venda #3: Em Rascunho (Aberto para edição/confirmação)
        $salesOrderService->create([
            'client_id' => $clients['atlastech']->id,
            'seller_id' => $sellers['thiago']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['notebook_dell']->id, 'quantity' => 3],
                ['number' => 2, 'product_id' => $products['monitor_samsung']->id, 'quantity' => 3],
                ['number' => 3, 'product_id' => $products['ssd_kingston']->id, 'quantity' => 6],
            ],
        ], $adminUser);

        // Pedido de Venda #4: Cancelado a partir de Rascunho
        $so4 = $salesOrderService->create([
            'client_id' => $clients['pf_cliente']->id,
            'seller_id' => $sellers['beatriz']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['monitor_dell']->id, 'quantity' => 2],
                ['number' => 2, 'product_id' => $products['headset_logitech']->id, 'quantity' => 2],
            ],
        ], $adminUser);
        $salesOrderService->cancel(
            $so4->refresh(),
            'Cliente solicitou cancelamento do orçamento para aguardar aprovação do projeto no próximo trimestre.'
        );

        // Pedido de Venda #5: Confirmado e posteriormente Reaberto (gera o Pedido #6 com replaces_order_id)
        $so5 = $salesOrderService->create([
            'client_id' => $clients['vanguard']->id,
            'seller_id' => $sellers['rafael']->id,
            'items' => [
                ['number' => 1, 'product_id' => $products['headset_logitech']->id, 'quantity' => 8],
                ['number' => 2, 'product_id' => $products['ssd_kingston']->id, 'quantity' => 8],
            ],
        ], $adminUser);
        $salesOrderService->confirm($so5->refresh());

        // Atualiza preço de venda do SSD Kingston para demonstrar detecção de preço atualizado na reabertura
        $productService->update($products['ssd_kingston'], ['sale_price' => '719.00']);
        $salesOrderService->recreateFrom(
            $so5->refresh(),
            'Cliente pediu inclusão de novos itens e readequação do pedido antes do faturamento.'
        );
    }

    /**
     * Exibe no terminal as credenciais de acesso e o resumo dos dados populados.
     */
    private function renderSummary(Organization $organization): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->newLine();
        $this->command->info('====================================================================');
        $this->command->info('  CREDENCIAIS DE ACESSO (AMBIENTE DE DESENVOLVIMENTO)');
        $this->command->info('====================================================================');
        $this->command->table(
            ['Perfil', 'CNPJ da Organização', 'E-mail', 'Senha'],
            [
                ['Administrador (Acesso Total)', '11.222.333/0001-81 (11222333000181)', self::ADMIN_EMAIL, self::DEFAULT_PASSWORD],
                ['Comercial / Vendas', '11.222.333/0001-81 (11222333000181)', 'vendas@apollo.com.br', self::DEFAULT_PASSWORD],
                ['Compras / Suprimentos', '11.222.333/0001-81 (11222333000181)', 'compras@apollo.com.br', self::DEFAULT_PASSWORD],
                ['Financeiro', '11.222.333/0001-81 (11222333000181)', 'financeiro@apollo.com.br', self::DEFAULT_PASSWORD],
                ['Auditoria / Somente Leitura', '11.222.333/0001-81 (11222333000181)', 'consulta@apollo.com.br', self::DEFAULT_PASSWORD],
            ]
        );

        $this->command->table(
            ['Entidade', 'Total Cadastrado'],
            [
                ['Usuários da Organização', User::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Marcas (Brands)', Brand::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Categorias (Categories)', Category::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Produtos (Products)', Product::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Fornecedores (Suppliers)', Supplier::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Vendedores (Sellers)', Seller::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Clientes (Clients)', Client::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Pedidos de Compra (PurchaseOrders)', PurchaseOrder::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Pedidos de Venda (SalesOrders)', SalesOrder::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
                ['Movimentações de Estoque (StockMovements)', StockMovement::withoutGlobalScopes()->where('organization_id', $organization->id)->count()],
            ]
        );
    }
}
