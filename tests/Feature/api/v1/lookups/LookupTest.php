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

describe('GET api/v1/lookups status validation', function () {
    test('Lookup fails with 422 when an invalid status filter is provided', function (string $routeName, string $permission) {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [$permission]);

        $response = $this->getJson(route($routeName, ['status' => 'invalid_status']));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status', 'error.fields');
    })->with([
        'clients' => ['v1.lookups.clients', PermissionType::CLIENT_READ->value],
        'sellers' => ['v1.lookups.sellers', PermissionType::SELLER_READ->value],
        'products' => ['v1.lookups.products', PermissionType::PRODUCT_READ->value],
        'suppliers' => ['v1.lookups.suppliers', PermissionType::SUPPLIER_READ->value],
    ]);
});

describe('GET api/v1/lookups/clients/{number}', function () {
    test('Logged user with client.view or sales-order permission can fetch client by number', function (string $permission) {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
            'trade_name' => 'Acme By Number',
            'legal_name' => 'Acme By Number Legal',
            'document' => '12345678000100',
        ]);

        Sanctum::actingAs($user, [$permission]);

        $response = $this->getJson(route('v1.lookups.clients.by-number', ['number' => $client->number]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'number', 'name', 'document', 'status'],
            ])
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.number', $client->number)
            ->assertJsonPath('data.name', 'Acme By Number');
    })->with([
        'client.view' => [PermissionType::CLIENT_READ->value],
        'sales-order.create' => [PermissionType::SALES_ORDER_CREATE->value],
        'sales-order.update' => [PermissionType::SALES_ORDER_UPDATE->value],
        'sales-order.view' => [PermissionType::SALES_ORDER_READ->value],
    ]);

    test('Client lookup by number enforces status, multi-tenant isolation, soft deletes, and permissions', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        $otherOrgClient = Client::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => ClientStatus::Active->value,
        ]);

        $inactiveClient = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Inactive->value,
        ]);

        $deletedClient = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active->value,
        ]);
        $deletedNumber = $deletedClient->number;
        $deletedClient->delete();

        // Unauthenticated
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $inactiveClient->number]))
            ->assertUnauthorized();

        // Forbidden
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $inactiveClient->number]))
            ->assertForbidden();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        // Inactive returns 404 by default, 200 with status=all
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $inactiveClient->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $inactiveClient->number, 'status' => 'all']))
            ->assertOk()
            ->assertJsonPath('data.id', $inactiveClient->id);

        // Invalid status returns 422
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $inactiveClient->number, 'status' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status', 'error.fields');

        // Soft deleted and other organization return 404
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $deletedNumber]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.clients.by-number', ['number' => $otherOrgClient->number]))
            ->assertNotFound();
    });
});

describe('GET api/v1/lookups/sellers/{number}', function () {
    test('Logged user with valid permission can fetch seller by number', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Active->value,
            'trade_name' => 'Seller By Number',
        ]);
        $inactiveSeller = Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Inactive->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $this->getJson(route('v1.lookups.sellers.by-number', ['number' => $seller->number]))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'number', 'name', 'status']])
            ->assertJsonPath('data.id', $seller->id)
            ->assertJsonPath('data.name', 'Seller By Number');

        $this->getJson(route('v1.lookups.sellers.by-number', ['number' => $inactiveSeller->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.sellers.by-number', ['number' => $inactiveSeller->number, 'status' => 'all']))
            ->assertOk()
            ->assertJsonPath('data.id', $inactiveSeller->id);

        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);
        $this->getJson(route('v1.lookups.sellers.by-number', ['number' => $seller->number]))
            ->assertForbidden();
    });
});

describe('GET api/v1/lookups/products/{number}', function () {
    test('Logged user with valid permission can fetch product by number', function (string $permission) {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active->value,
            'name' => 'Widget By Number',
            'unit' => 'un',
            'sale_price' => Money::fromDecimal(50.00),
            'cost_price' => Money::fromDecimal(25.00),
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($user, [$permission]);

        $this->getJson(route('v1.lookups.products.by-number', ['number' => $product->number]))
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'number', 'name', 'unit', 'sale_price', 'cost_price', 'stock_quantity', 'status'],
            ])
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Widget By Number');
    })->with([
        'product.view' => [PermissionType::PRODUCT_READ->value],
        'sales-order.create' => [PermissionType::SALES_ORDER_CREATE->value],
        'purchase-order.create' => [PermissionType::PURCHASE_ORDER_CREATE->value],
    ]);

    test('Product lookup by number enforces inactive status, multi-tenant isolation, and permissions', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        $otherOrgProduct = Product::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => ProductStatus::Active->value,
        ]);

        $inactiveProduct = Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Inactive->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $this->getJson(route('v1.lookups.products.by-number', ['number' => $inactiveProduct->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.products.by-number', ['number' => $inactiveProduct->number, 'status' => 'all']))
            ->assertOk()
            ->assertJsonPath('data.id', $inactiveProduct->id);
        $this->getJson(route('v1.lookups.products.by-number', ['number' => $otherOrgProduct->number]))
            ->assertNotFound();

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);
        $this->getJson(route('v1.lookups.products.by-number', ['number' => $inactiveProduct->number]))
            ->assertForbidden();
    });
});

describe('GET api/v1/lookups/suppliers/{number}', function () {
    test('Logged user with valid permission can fetch supplier by number', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Active->value,
            'trade_name' => 'Supplier By Number',
        ]);
        $inactiveSupplier = Supplier::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SupplierStatus::Inactive->value,
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $this->getJson(route('v1.lookups.suppliers.by-number', ['number' => $supplier->number]))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'number', 'name', 'document', 'status']])
            ->assertJsonPath('data.id', $supplier->id)
            ->assertJsonPath('data.name', 'Supplier By Number');

        $this->getJson(route('v1.lookups.suppliers.by-number', ['number' => $inactiveSupplier->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.suppliers.by-number', ['number' => $inactiveSupplier->number, 'status' => 'all']))
            ->assertOk()
            ->assertJsonPath('data.id', $inactiveSupplier->id);

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);
        $this->getJson(route('v1.lookups.suppliers.by-number', ['number' => $supplier->number]))
            ->assertForbidden();
    });
});

describe('GET api/v1/lookups/brands/{number}', function () {
    test('Logged user with brand.view or product permission can fetch brand by number', function (string $permission) {
        $user = User::factory()->create();
        $brand = Brand::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Bosch By Number',
        ]);

        Sanctum::actingAs($user, [$permission]);

        $this->getJson(route('v1.lookups.brands.by-number', ['number' => $brand->number]))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'number', 'name']])
            ->assertJsonPath('data.id', $brand->id)
            ->assertJsonPath('data.number', $brand->number)
            ->assertJsonPath('data.name', 'Bosch By Number');
    })->with([
        'brand.view' => [PermissionType::BRAND_READ->value],
        'product.create' => [PermissionType::PRODUCT_CREATE->value],
        'product.update' => [PermissionType::PRODUCT_UPDATE->value],
    ]);

    test('Brand lookup by number enforces isolation, soft delete, and permissions', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        $otherBrand = Brand::factory()->create(['organization_id' => $otherOrg->id]);
        $deletedBrand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        $deletedNumber = $deletedBrand->number;
        $deletedBrand->delete();

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);
        $this->getJson(route('v1.lookups.brands.by-number', ['number' => $otherBrand->number]))
            ->assertForbidden();

        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);
        $this->getJson(route('v1.lookups.brands.by-number', ['number' => $otherBrand->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.brands.by-number', ['number' => $deletedNumber]))
            ->assertNotFound();
    });
});

describe('GET api/v1/lookups/categories/{number}', function () {
    test('Logged user with category.view or product permission can fetch category by number', function (string $permission) {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Tools By Number',
        ]);

        Sanctum::actingAs($user, [$permission]);

        $this->getJson(route('v1.lookups.categories.by-number', ['number' => $category->number]))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'number', 'name']])
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.number', $category->number)
            ->assertJsonPath('data.name', 'Tools By Number');
    })->with([
        'category.view' => [PermissionType::CATEGORY_READ->value],
        'product.create' => [PermissionType::PRODUCT_CREATE->value],
        'product.update' => [PermissionType::PRODUCT_UPDATE->value],
    ]);

    test('Category lookup by number enforces isolation, soft delete, and permissions', function () {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();

        $otherCategory = Category::factory()->create(['organization_id' => $otherOrg->id]);
        $deletedCategory = Category::factory()->create(['organization_id' => $user->organization_id]);
        $deletedNumber = $deletedCategory->number;
        $deletedCategory->delete();

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);
        $this->getJson(route('v1.lookups.categories.by-number', ['number' => $otherCategory->number]))
            ->assertForbidden();

        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);
        $this->getJson(route('v1.lookups.categories.by-number', ['number' => $otherCategory->number]))
            ->assertNotFound();
        $this->getJson(route('v1.lookups.categories.by-number', ['number' => $deletedNumber]))
            ->assertNotFound();
    });
});

describe('GET api/v1/lookups/{resource}/{number} route parameter validation', function () {
    test('Returns 404 for nonexistent, zero, non-numeric, or overflowing number', function (string $number) {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [
            PermissionType::CLIENT_READ->value,
            PermissionType::SELLER_READ->value,
            PermissionType::PRODUCT_READ->value,
            PermissionType::SUPPLIER_READ->value,
            PermissionType::BRAND_READ->value,
            PermissionType::CATEGORY_READ->value,
        ]);

        foreach (['clients', 'sellers', 'products', 'suppliers', 'brands', 'categories'] as $resource) {
            $this->getJson("/api/v1/lookups/{$resource}/{$number}")->assertNotFound();
        }
    })->with([
        'nonexistent' => ['999999'],
        'zero' => ['0'],
        'non-numeric' => ['abc'],
        'overflow' => ['99999999999999999999999'],
    ]);
});
