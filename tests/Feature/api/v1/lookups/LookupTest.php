<?php

use App\Enum\PermissionType;
use App\Enum\Status\ClientStatus;
use App\Enum\Status\ProductStatus;
use App\Enum\Status\SellerStatus;
use App\Enum\Status\SupplierStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('GET api/v1/lookups/clients', function () {
    test('Logged user with client.view permission can access clients lookup', function () {
        $user = User::factory()->create();
        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Acme Corporation',
            'legal_name' => 'Acme Corp Legal',
            'document' => '12345678000100',
        ]);

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name', 'document', 'status'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Acme Corporation');
        $response->assertJsonPath('data.0.document', '12345678000100');
    });

    test('Logged user with sales-order.create permission can access clients lookup without client.view', function () {
        $user = User::factory()->create();
        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user with sales-order.update permission can access clients lookup', function () {
        $user = User::factory()->create();
        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user with unrelated ability cannot access clients lookup', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertForbidden();
    });

    test('Unauthenticated user cannot access clients lookup', function () {
        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertUnauthorized();
    });

    test('Clients lookup excludes other organizations and inactive by default', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        // Other organization client
        Client::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Other Org Client',
        ]);

        // Own organization active client
        $activeClient = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Own Active Client',
        ]);

        // Own organization inactive client
        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Inactive->value,
            'trade_name' => 'Own Inactive Client',
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.clients'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $activeClient->id);
    });

    test('Clients lookup supports search filter', function () {
        $user = User::factory()->create();

        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Alpha Solutions',
            'legal_name' => 'Alpha Corp',
        ]);

        Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Beta Logistics',
            'legal_name' => 'Beta Corp',
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.clients', ['search' => 'alpha']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Alpha Solutions');
    });
});

describe('GET api/v1/lookups/sellers', function () {
    test('Logged user with seller.view can access sellers lookup', function () {
        $user = User::factory()->create();
        Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Active->value,
            'trade_name' => 'Seller One',
        ]);

        Sanctum::actingAs($user, [PermissionType::SELLER_READ->value]);

        $response = $this->getJson(route('v1.lookups.sellers'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name', 'status'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Seller One');
    });

    test('Logged user with sales-order.create can access sellers lookup', function () {
        $user = User::factory()->create();
        Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.sellers'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user without permission cannot access sellers lookup', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.lookups.sellers'));

        $response->assertForbidden();
    });
});

describe('GET api/v1/lookups/products', function () {
    test('Logged user with product.view can access products lookup', function () {
        $user = User::factory()->create();
        Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active->value,
            'name' => 'Widget Pro',
            'unit' => 'un',
            'sale_price' => Money::fromDecimal(50.00),
            'cost_price' => Money::fromDecimal(25.00),
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.lookups.products'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name', 'unit', 'sale_price', 'cost_price', 'stock_quantity', 'status'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Widget Pro');
    });

    test('Logged user with purchase-order.create can access products lookup', function () {
        $user = User::factory()->create();
        Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.products'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user with sales-order.create can access products lookup', function () {
        $user = User::factory()->create();
        Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.products'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user without permission cannot access products lookup', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.lookups.products'));

        $response->assertForbidden();
    });
});

describe('GET api/v1/lookups/suppliers', function () {
    test('Logged user with supplier.view can access suppliers lookup', function () {
        $user = User::factory()->create();
        Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Active->value,
            'trade_name' => 'Supplier Alpha',
            'legal_name' => 'Supplier Alpha Legal',
            'document' => '99887766000155',
        ]);

        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);

        $response = $this->getJson(route('v1.lookups.suppliers'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name', 'document', 'status'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Supplier Alpha');
    });

    test('Logged user with purchase-order.create can access suppliers lookup', function () {
        $user = User::factory()->create();
        Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Active->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.suppliers'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user without permission cannot access suppliers lookup', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.lookups.suppliers'));

        $response->assertForbidden();
    });
});

describe('GET api/v1/lookups/brands', function () {
    test('Logged user with brand.view can access brands lookup', function () {
        $user = User::factory()->create();
        Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Bosch',
        ]);

        Sanctum::actingAs($user, [PermissionType::BRAND_READ->value]);

        $response = $this->getJson(route('v1.lookups.brands'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Bosch');
    });

    test('Logged user with product.create can access brands lookup', function () {
        $user = User::factory()->create();
        Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Makita',
        ]);

        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.brands'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user without permission cannot access brands lookup', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.lookups.brands'));

        $response->assertForbidden();
    });
});

describe('GET api/v1/lookups/categories', function () {
    test('Logged user with category.view can access categories lookup', function () {
        $user = User::factory()->create();
        Category::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Tools',
        ]);

        Sanctum::actingAs($user, [PermissionType::CATEGORY_READ->value]);

        $response = $this->getJson(route('v1.lookups.categories'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'number', 'name'],
            ],
        ]);
        $response->assertJsonPath('data.0.name', 'Tools');
    });

    test('Logged user with product.create can access categories lookup', function () {
        $user = User::factory()->create();
        Category::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Hardware',
        ]);

        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);

        $response = $this->getJson(route('v1.lookups.categories'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    });

    test('Logged user without permission cannot access categories lookup', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.lookups.categories'));

        $response->assertForbidden();
    });
});
