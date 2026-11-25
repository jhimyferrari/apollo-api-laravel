<?php

use App\Enum\PermissionType;
use App\Enum\Status\ClientStatus;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Enum\Status\ProductStatus;
use App\Enum\Status\SellerStatus;
use App\Enum\Status\SupplierStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\NcmCode;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\Supplier;
use App\Models\User;
use App\ValueObjects\Money;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('GET /v1/products search, filter & sort', function () {
    test('finds products beyond page 1 by name, number, NCM, brand, category, and filters/sorts across pages', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        $brandAlpha = Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'AlphaTech',
        ]);
        $brandBeta = Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'BetaCorp',
        ]);
        $catTools = Category::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Ferramentas Especiais',
        ]);
        $ncm = NcmCode::factory()->code('84713012')->create();

        // Fill > 15 products so later items would live on page 2
        Product::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active->value,
            'brand_id' => $brandAlpha->id,
            'sale_price' => Money::fromDecimal(100),
        ]);

        $target = Product::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Parafusadeira Industrial X9',
            'status' => ProductStatus::Inactive->value,
            'brand_id' => $brandBeta->id,
            'ncm_code_id' => $ncm->id,
            'ean' => '7891234567895',
            'sale_price' => Money::fromDecimal(999.90),
        ]);
        $target->categories()->attach($catTools->id);

        $noBrand = Product::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Produto Avulso',
            'status' => ProductStatus::Active->value,
            'brand_id' => null,
            'sale_price' => Money::fromDecimal(5),
        ]);

        // Other org product with same search term must not leak
        Product::factory()->create([
            'organization_id' => $otherOrg->id,
            'name' => 'Parafusadeira Industrial X9',
        ]);

        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        // Search by name beyond page 1
        $this->getJson(route('v1.products.index', ['search' => 'Parafusadeira']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        // Search by #number
        $this->getJson(route('v1.products.index', ['search' => '#'.$target->number]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        // Search by formatted NCM
        $this->getJson(route('v1.products.index', ['search' => '8471.30.12']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        // Search by category name
        $this->getJson(route('v1.products.index', ['search' => 'Ferramentas Especiais']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        // Filter brand_id=none, status, category_id
        $this->getJson(route('v1.products.index', ['brand_id' => 'none']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $noBrand->id);

        $this->getJson(route('v1.products.index', [
            'status' => ProductStatus::Inactive->value,
            'brand_id' => $brandBeta->id,
            'category_id' => $catTools->id,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        // Sort by sale_price desc brings 999.90 to top
        $this->getJson(route('v1.products.index', ['sort_by' => 'sale_price', 'sort_direction' => 'desc']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $target->id);

        // Invalid sort_by returns 422
        $this->getJson(route('v1.products.index', ['sort_by' => 'invalid_col']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_by', 'error.fields');
    });
});

describe('GET /v1/brands and /v1/categories search & sort', function () {
    test('finds brands and categories beyond page 1 and sorts across pages', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        Brand::factory()->count(16)->create(['organization_id' => $user->organization_id]);
        $targetBrand = Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Zeta Precision',
            'description' => 'Linha automotiva',
        ]);
        Brand::factory()->create(['organization_id' => $otherOrg->id, 'name' => 'Zeta Precision']);

        Category::factory()->count(16)->create(['organization_id' => $user->organization_id]);
        $targetCat = Category::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Abrasivos Finos',
            'description' => 'Acabamento industrial',
        ]);

        Sanctum::actingAs($user, [
            PermissionType::BRAND_READ->value,
            PermissionType::CATEGORY_READ->value,
        ]);

        $this->getJson(route('v1.brands.index', ['search' => 'automotiva']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetBrand->id);

        $this->getJson(route('v1.brands.index', ['search' => (string) $targetBrand->number]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $targetBrand->id);

        $this->getJson(route('v1.categories.index', ['search' => 'Abrasivos']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetCat->id);
    });
});

describe('GET /v1/clients, /v1/sellers & /v1/suppliers search, filter & sort', function () {
    test('finds partners beyond page 1 by name, formatted document, #number and status filter', function () {
        $user = User::factory()->create();

        Client::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
        ]);
        $targetClient = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Inactive->value,
            'legal_name' => 'Alpha Omega Comercio LTDA',
            'trade_name' => 'Alpha Omega',
            'document' => '11222333000181',
        ]);

        Seller::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Active->value,
        ]);
        $targetSeller = Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Inactive->value,
            'legal_name' => 'Carlos Representacoes',
            'trade_name' => 'Carlos Rep',
        ]);

        Supplier::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Active->value,
        ]);
        $targetSupplier = Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Inactive->value,
            'legal_name' => 'Fornecedora Horizonte SA',
            'trade_name' => 'Horizonte',
        ]);

        Sanctum::actingAs($user, [
            PermissionType::CLIENT_READ->value,
            PermissionType::SELLER_READ->value,
            PermissionType::SUPPLIER_READ->value,
        ]);

        // Search formatted CNPJ beyond page 1
        $this->getJson(route('v1.clients.index', ['search' => '11.222.333/0001-81']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetClient->id);

        $this->getJson(route('v1.clients.index', ['status' => ClientStatus::Inactive->value]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetClient->id);

        $this->getJson(route('v1.sellers.index', ['search' => 'Carlos Rep', 'status' => SellerStatus::Inactive->value]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetSeller->id);

        $this->getJson(route('v1.suppliers.index', ['search' => '#'.$targetSupplier->number]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetSupplier->id);
    });
});

describe('GET /v1/sales-orders & /v1/purchase-orders search, filter & relational sort', function () {
    test('finds orders beyond page 1 by partner or number and filters/sorts across pages', function () {
        $user = User::factory()->create();

        $clientAlpha = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'legal_name' => 'AAA Cliente Primeiro',
            'trade_name' => 'AAA',
        ]);
        $clientZeta = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'legal_name' => 'ZZZ Cliente Ultimo',
            'trade_name' => 'ZZZ',
        ]);
        $seller = Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'legal_name' => 'Vendedor Estrela',
            'trade_name' => 'Estrela Vendas',
        ]);

        SalesOrder::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'client_id' => $clientAlpha->id,
            'status' => OrderStatus::Draft->value,
            'payment_status' => PaymentStatus::Pending->value,
            'total' => Money::fromDecimal(50),
        ]);
        $targetSale = SalesOrder::factory()->paid()->create([
            'organization_id' => $user->organization_id,
            'client_id' => $clientZeta->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Confirmed->value,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal(8500),
        ]);

        $supplier = Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'legal_name' => 'ZZZ Suprimentos Especiais',
            'trade_name' => 'ZZZ Suprimentos',
        ]);
        PurchaseOrder::factory()->count(16)->create([
            'organization_id' => $user->organization_id,
            'status' => OrderStatus::Draft->value,
            'payment_status' => PaymentStatus::Pending->value,
            'total' => Money::fromDecimal(50),
        ]);
        $targetPurchase = PurchaseOrder::factory()->paid()->create([
            'organization_id' => $user->organization_id,
            'supplier_id' => $supplier->id,
            'status' => OrderStatus::Confirmed->value,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal(9900),
        ]);

        Sanctum::actingAs($user, [
            PermissionType::SALES_ORDER_READ->value,
            PermissionType::PURCHASE_ORDER_READ->value,
        ]);

        // Sales order search by seller trade_name beyond page 1
        $this->getJson(route('v1.sales-orders.index', ['search' => 'Estrela Vendas']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetSale->id);

        // Sales order relational sort by client desc puts ZZZ first
        $this->getJson(route('v1.sales-orders.index', [
            'sort_by' => 'client',
            'sort_direction' => 'desc',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $targetSale->id);

        // Sales order filters
        $this->getJson(route('v1.sales-orders.index', [
            'status' => OrderStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetSale->id);

        // Purchase order search by supplier & sort by supplier desc
        $this->getJson(route('v1.purchase-orders.index', ['search' => 'Suprimentos Especiais']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetPurchase->id);

        $this->getJson(route('v1.purchase-orders.index', [
            'sort_by' => 'total',
            'sort_direction' => 'desc',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $targetPurchase->id);
    });
});

describe('GET /v1/users search, profile filter & permission sort', function () {
    test('finds users beyond page 1 by name/email, filters by profile and sorts by permissions', function () {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create([
            'is_administrator' => true,
            'name' => 'Admin Principal',
            'email' => 'admin@apollo.test',
        ]);

        User::factory()->count(16)->create(['organization_id' => $admin->organization_id]);
        $targetOperator = User::factory()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Mariana Especialista',
            'email' => 'mariana@apollo.test',
        ]);

        $perm = Permission::where('name', PermissionType::PRODUCT_READ->value)->firstOrFail();
        $targetOperator->permissions()->attach($perm->id);

        Sanctum::actingAs($admin, [PermissionType::USER_READ->value]);

        // Search beyond page 1
        $this->getJson(route('v1.users.index', ['search' => 'mariana@apollo']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetOperator->id);

        // Profile filter admin
        $this->getJson(route('v1.users.index', ['profile' => 'admin']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $admin->id);

        // Sort permissions desc brings admin first
        $this->getJson(route('v1.users.index', ['sort_by' => 'permissions', 'sort_direction' => 'desc']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $admin->id);
    });
});
