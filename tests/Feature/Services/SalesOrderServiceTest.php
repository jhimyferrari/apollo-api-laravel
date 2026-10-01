<?php

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Enum\StockMovementType;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\InvalidStatusException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ModelIsDeletedException;
use App\Exceptions\OrderCannotBeDeletedException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\StockException;
use App\Models\Client;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\User;
use App\Services\SalesOrderService;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(SalesOrderService::class);
    $this->organization = $this->user->organization;
});

describe('SalesOrderService', function () {
    describe('create', function () {
        it('should create a SalesOrder successfully', function () {
            $products = Product::factory()->for($this->organization)->withStock(100)->count(5)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $order = $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 10, 'number' => 1],
                    ['product_id' => $products[2]->id, 'quantity' => 20, 'number' => 2],
                    ['product_id' => $products[3]->id, 'quantity' => 30, 'number' => 3],
                ],
            ], $this->user);

            expect($order)
                ->toBeInstanceOf(SalesOrder::class)
                ->client_id->toBe($client->id)
                ->seller_id->toBe($seller->id)
                ->status->toBe(OrderStatus::Draft);
        });

        it('should create items with correct product, quantity and locked unit price', function () {
            $products = Product::factory()->for($this->organization)->withStock(100)->count(5)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $order = $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 10, 'number' => 1],
                    ['product_id' => $products[2]->id, 'quantity' => 20, 'number' => 2],
                    ['product_id' => $products[3]->id, 'quantity' => 30, 'number' => 3],
                ],
            ], $this->user);

            expect($order->items)->toHaveCount(3);

            $expectedItems = [
                ['product' => $products[0], 'quantity' => 10],
                ['product' => $products[2], 'quantity' => 20],
                ['product' => $products[3], 'quantity' => 30],
            ];

            foreach ($order->items as $index => $item) {
                $expected = $expectedItems[$index];

                expect($item->product_id)->toBe($expected['product']->id)
                    ->and($item->number)->toBe($index + 1)
                    ->and((float) $item->quantity)->toBe((float) $expected['quantity'])
                    ->and($item->unit_price)->toBeInstanceOf(Money::class)
                    ->and($item->unit_price->toStorageString())->toBe($expected['product']->sale_price->toStorageString())
                    ->and($item->subtotal->toStorageString())
                    ->toBe($expected['product']->sale_price->multiply($expected['quantity'])->toStorageString());
            }
        });

        it('should calculate the order total as the sum of item subtotals', function () {
            $products = Product::factory()->for($this->organization)->withStock(100)->count(2)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $order = $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 5, 'number' => 1],
                    ['product_id' => $products[1]->id, 'quantity' => 15, 'number' => 2],
                ],
            ], $this->user);

            $expectedTotal = $products[0]->sale_price->multiply(5)
                ->add($products[1]->sale_price->multiply(15));

            expect($order->total)->toBeInstanceOf(Money::class)
                ->and($order->total->toStorageString())->toBe($expectedTotal->toStorageString());
        });

        it('should persist the order and its items in the database', function () {

            $products = Product::factory()->for($this->organization)->withStock(100)->count(2)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $order = $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 5, 'number' => 1],
                    ['product_id' => $products[1]->id, 'quantity' => 15, 'number' => 2],
                ],
            ], $this->user);

            $this->assertDatabaseHas('sales_orders', [
                'id' => $order->id,
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
            ]);

            $this->assertDatabaseCount('sales_order_items', 2);

            foreach ($order->items as $item) {
                $this->assertDatabaseHas('sales_order_items', [
                    'id' => $item->id,
                    'sales_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'number' => $item->number,
                ]);
            }
        });

        it('should always create the order with Draft status regardless of the status passed', function () {
            $products = Product::factory()->for($this->organization)->withStock(100)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $order = $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Confirmed->value,
                'items' => [
                    ['product_id' => $products->id, 'quantity' => 1, 'number' => 1],
                ],
            ], $this->user);

            expect($order->status)->toBe(OrderStatus::Draft);
        });

        it('throws an error when items array is empty', function () {
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [],
            ], $this->user))->toThrow(InvalidFieldException::class, 'The field `items` must have a value');
        });

        it('throws an error when an item has zero quantity', function () {
            $product = Product::factory()->for($this->organization)->withStock(100)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 0, 'number' => 1],
                ],
            ], $this->user))->toThrow(InvalidFieldException::class);
        });

        it('throws an error when an item has negative quantity', function () {
            $product = Product::factory()->for($this->organization)->withStock(100)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => -5, 'number' => 1],
                ],
            ], $this->user))->toThrow(InvalidFieldException::class);
        });

        it('throws an error when an item references a nonexistent product', function () {
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => (string) Str::uuid(), 'quantity' => 5, 'number' => 1],
                ],
            ], $this->user))->toThrow(ResourceNotFoundException::class);
        });

        it('throws an error when a product belongs to a different organization', function () {
            $otherOrganizationProduct = Product::factory()->withStock(100)->create(); // organization diferente
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $otherOrganizationProduct->id, 'quantity' => 5, 'number' => 1],
                ],
            ], $this->user))->toThrow(ResourceNotFoundException::class);
        });

        it('throws an error when client belongs to a different organization', function () {
            $product = Product::factory()->for($this->organization)->withStock(100)->create();
            $otherOrganizationClient = Client::factory()->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $otherOrganizationClient->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5, 'number' => 1],
                ],
            ], $this->user))->toThrow(ResourceNotFoundException::class);
        });

        it('does not persist anything when one item in the middle is invalid', function () {
            $products = Product::factory()->for($this->organization)->withStock(100)->count(3)->create();
            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            expect(fn () => $this->service->create([
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $products[0]->id, 'quantity' => 10, 'number' => 1],
                    ['product_id' => $products[1]->id, 'quantity' => -1, 'number' => 2],
                    ['product_id' => $products[2]->id, 'quantity' => 5, 'number' => 3],
                ],
            ], $this->user))->toThrow(InvalidFieldException::class);

            $this->assertDatabaseCount('sales_orders', 0);
            $this->assertDatabaseCount('sales_order_items', 0);
        });
    });
    describe('delete', function () {
        it('deletes a draft order successfully', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);

            $this->service->delete($order);
            $this->assertSoftDeleted($order);
        });
        it('throws when trying to delete a confirmed order', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);

            expect(fn () => $this->service->delete($order))
                ->toThrow(OrderCannotBeDeletedException::class);

            $this->assertDatabaseHas('sales_orders', ['id' => $order->id]);
        });
        it('throws when trying to delete a cancelled order', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);

            expect(fn () => $this->service->delete($order))
                ->toThrow(OrderCannotBeDeletedException::class);

            $this->assertDatabaseHas('sales_orders', ['id' => $order->id]);
        });
        it('does not delete items when the order cannot be deleted', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
            $product = Product::factory()->for($this->organization)->withStock(10)->create();
            $item = $order->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 2,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(2),
                'organization_id' => $this->organization->id,
            ]);
            expect(fn () => $this->service->delete($order))->toThrow(OrderCannotBeDeletedException::class);

            $this->assertDatabaseHas('sales_order_items', ['id' => $item->id]);
        });
    });
    describe('confirm', function () {
        it('confirms a draft order and decrements stock', function () {
            $product = Product::factory()->for($this->organization)->withStock(20)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);
            $order->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(5),
                'organization_id' => $this->organization->id,
            ]);

            $this->service->confirm($order);

            expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
            expect($order->fresh()->confirmed_at)->not->toBeNull();
            expect($product->fresh()->stock_quantity)->toEqual(15.000);
        });

        it('creates a stock movement referencing the order', function () {
            $product = Product::factory()->for($this->organization)->withStock(20)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);
            $order->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(5),
                'organization_id' => $this->organization->id,
            ]);

            $this->service->confirm($order);

            $this->assertDatabaseHas('stock_movements', [
                'product_id' => $product->id,
                'type' => StockMovementType::Out->value,
                'reference_type' => SalesOrder::class,
                'reference_id' => $order->id,
            ]);
        });

        it('throws when the order is already confirmed', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);

            expect(fn () => $this->service->confirm($order))
                ->toThrow(InvalidStatusTransitionException::class, 'Cannot transition from `confirmed` to `confirmed`.');
        });

        it('throws when the order is cancelled', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);

            expect(fn () => $this->service->confirm($order))
                ->toThrow(InvalidStatusTransitionException::class, 'Cannot transition from `cancelled` to `confirmed`.');
        });

        it('does not change stock when the order cannot be confirmed', function () {
            $product = Product::factory()->for($this->organization)->withStock(20)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);
            $order->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(5),
                'organization_id' => $this->organization->id,
            ]);

            expect(fn () => $this->service->confirm($order))->toThrow(InvalidStatusTransitionException::class);

            expect($product->fresh()->stock_quantity)->toEqual(20.000);
        });

        it('throws when stock is insufficient', function () {
            $product = Product::factory()->for($this->organization)->withStock(2)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);
            $order->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 10,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(10),
                'organization_id' => $this->organization->id,
            ]);

            expect(fn () => $this->service->confirm($order))
                ->toThrow(StockException::class);

            expect($order->fresh()->status)->toBe(OrderStatus::Draft);
            expect($product->fresh()->stock_quantity)->toEqual(2.000);
        });
    });
    describe('cancel', function () {
        it('cancels a draft order without touching stock', function () {
            $product = Product::factory()->for($this->organization)->withStock(20)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);
            $order->items()->create([
                'organization_id' => $this->organization->id,
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(5),
            ]);

            $this->service->cancel($order);

            expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
            expect($product->fresh()->stock_quantity)->toEqual(20.000);
            $this->assertDatabaseCount('stock_movements', 0);
        });

        it('cancels a confirmed order and reverses stock', function () {
            $product = Product::factory()->for($this->organization)->withStock(15)->create();
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
            $order->items()->create([
                'product_id' => $product->id,
                'organization_id' => $this->organization->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(5),
            ]);

            $this->service->cancel($order);

            expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
            expect($product->fresh()->stock_quantity)->toEqual(20.000);

            $this->assertDatabaseHas('stock_movements', [
                'product_id' => $product->id,
                'type' => StockMovementType::In->value,
                'quantity' => 5,
                'reference_type' => SalesOrder::class,
                'reference_id' => $order->id,
            ]);
        });

        it('stores the cancellation reason when provided', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);

            $this->service->cancel($order, 'Cliente desistiu');

            expect($order->fresh()->cancellation_reason)->toBe('Cliente desistiu');
        });

        it('sets cancelled_at when cancelling', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);

            $this->service->cancel($order);

            expect($order->fresh()->cancelled_at)->not->toBeNull();
        });

        it('throws when the order is already cancelled', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);

            expect(fn () => $this->service->cancel($order))
                ->toThrow(InvalidStatusTransitionException::class, 'Cannot transition from `cancelled` to `cancelled`.');
        });

        it('does not change stock when the order cannot be cancelled', function () {
            $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);

            expect(fn () => $this->service->cancel($order))->toThrow(InvalidStatusTransitionException::class);

            $this->assertDatabaseCount('stock_movements', 0);
        });
    });
    describe('recreateFrom', function () {
        describe('from a Confirmed order', function () {
            it('cancels the original order and creates a draft replacement', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create();
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(5),
                ]);

                $result = $this->service->recreateFrom($order, 'Quantidade incorreta');

                expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
                    ->and($order->fresh()->cancellation_reason)->toBe('Quantidade incorreta');

                expect($result['order'])
                    ->toBeInstanceOf(SalesOrder::class)
                    ->status->toBe(OrderStatus::Draft)
                    ->replaces_order_id->toBe($order->id)
                    ->client_id->toBe($order->client_id)
                    ->seller_id->toBe($order->seller_id);
            });

            it('reverses stock as part of the cancellation', function () {
                $product = Product::factory()->for($this->organization)->withStock(15)->create(); // já decrementado
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(5),
                ]);

                $this->service->recreateFrom($order);

                expect($product->fresh()->stock_quantity)->toEqual(20.000);
            });
        });
        describe('from a Cancelled order', function () {
            it('references it directly without cancelling it again', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create();
                $order = SalesOrder::factory()->for($this->organization)->create([
                    'status' => OrderStatus::Cancelled,
                    'cancellation_reason' => 'Motivo original',
                ]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 3,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(3),
                ]);

                $result = $this->service->recreateFrom($order);

                // motivo original preservado — não foi sobrescrito por um segundo cancel()
                expect($order->fresh()->cancellation_reason)->toBe('Motivo original');
                expect($result['order']->replaces_order_id)->toBe($order->id);
                expect($result['order']->status)->toBe(OrderStatus::Draft);
            });

            it('does not create any new stock movement', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create();
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Cancelled]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 3,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(3),
                ]);

                $this->service->recreateFrom($order);

                $this->assertDatabaseCount('stock_movements', 0);
            });
        });
        describe('from a Draft order', function () {
            it('throws because there is nothing to reopen', function () {
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);

                expect(fn () => $this->service->recreateFrom($order))
                    ->toThrow(InvalidStatusTransitionException::class);
            });

            it('does not change the draft order status', function () {
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Draft]);

                expect(fn () => $this->service->recreateFrom($order))->toThrow(InvalidStatusTransitionException::class);

                expect($order->fresh()->status)->toBe(OrderStatus::Draft);
            });
        });
        describe('item replication', function () {
            it('preserves the historical unit_price, not the current product price', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create([
                    'sale_price' => Money::fromStorage('180.0000'), // preço atual, já reajustado
                ]);
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => Money::fromStorage('150.0000'),
                    'subtotal' => Money::fromStorage('750.0000'),
                ]);

                $result = $this->service->recreateFrom($order);

                expect($result['order']->items->first()->unit_price->toStorageString())->toBe('150.0000');
            });

            it('preserves the historical subtotal and order total', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create([
                    'sale_price' => Money::fromStorage('180.0000'),
                ]);
                $order = SalesOrder::factory()->for($this->organization)->create([
                    'status' => OrderStatus::Confirmed,
                    'total' => Money::fromStorage('750.0000'),
                ]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => Money::fromStorage('150.0000'),
                    'subtotal' => Money::fromStorage('750.0000'),
                ]);

                $result = $this->service->recreateFrom($order);

                expect($result['order']->items->first()->subtotal->toStorageString())->toBe('750.0000');
                expect($result['order']->total->toStorageString())->toBe('750.0000');
            });

            it('clones all items, preserving quantity and number', function () {
                $products = Product::factory()->for($this->organization)->withStock(20)->count(3)->create();
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);

                foreach ($products as $index => $product) {
                    $order->items()->create([
                        'organization_id' => $this->organization->id,
                        'product_id' => $product->id,
                        'number' => $index + 1,
                        'quantity' => ($index + 1) * 2,
                        'unit_price' => $product->sale_price,
                        'subtotal' => $product->sale_price->multiply(($index + 1) * 2),
                    ]);
                }

                $result = $this->service->recreateFrom($order);

                expect($result['order']->items)->toHaveCount(3);

                foreach ($result['order']->items as $index => $item) {
                    expect($item->product_id)->toBe($products[$index]->id)
                        ->and($item->number)->toBe($index + 1)
                        ->and((float) $item->quantity)->toBe((float) (($index + 1) * 2));
                }
            });

            it('generates new ids and reassigns items to the new order, not the original', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create();
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $originalItem = $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(5),
                ]);

                $result = $this->service->recreateFrom($order);
                $newItem = $result['order']->items->first();

                expect($newItem->id)->not->toBe($originalItem->id)
                    ->and($newItem->sales_order_id)->toBe($result['order']->id)
                    ->and($newItem->sales_order_id)->not->toBe($order->id);

                // o item original continua intacto, ligado ao pedido antigo
                $this->assertDatabaseHas('sales_order_items', [
                    'id' => $originalItem->id,
                    'sales_order_id' => $order->id,
                ]);
            });
        });
        describe('outdated price detection', function () {
            it('flags an item whose product price changed since negotiation', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create([
                    'sale_price' => Money::fromStorage('180.0000'),
                    'name' => 'Cadeira Gamer X',
                ]);
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => Money::fromStorage('150.0000'),
                    'subtotal' => Money::fromStorage('750.0000'),
                ]);

                $result = $this->service->recreateFrom($order);

                expect($result['outdated_prices'])->toHaveCount(1)
                    ->and($result['outdated_prices'][0]['product_id'])->toBe($product->id)
                    ->and($result['outdated_prices'][0]['product_name'])->toBe('Cadeira Gamer X')
                    ->and($result['outdated_prices'][0]['negotiated_price'])->toBe('150.0000')
                    ->and($result['outdated_prices'][0]['current_price'])->toBe('180.0000');
            });

            it('does not flag an item whose price matches the current product price', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create([
                    'sale_price' => Money::fromStorage('150.0000'),
                ]);
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => Money::fromStorage('150.0000'),
                    'subtotal' => Money::fromStorage('750.0000'),
                ]);

                $result = $this->service->recreateFrom($order);

                expect($result['outdated_prices'])->toHaveCount(0);
            });

            it('flags only the items that diverge when there are multiple', function () {
                $productChanged = Product::factory()->for($this->organization)->withStock(20)
                    ->create(['sale_price' => Money::fromStorage('200.0000')]);
                $productSame = Product::factory()->for($this->organization)->withStock(20)
                    ->create(['sale_price' => Money::fromStorage('50.0000')]);

                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $productChanged->id, 'number' => 1, 'quantity' => 2,
                    'unit_price' => Money::fromStorage('150.0000'), 'subtotal' => Money::fromStorage('300.0000'),
                ]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $productSame->id, 'number' => 2, 'quantity' => 3,
                    'unit_price' => Money::fromStorage('50.0000'), 'subtotal' => Money::fromStorage('150.0000'),
                ]);

                $result = $this->service->recreateFrom($order);

                expect($result['outdated_prices'])->toHaveCount(1)
                    ->and($result['outdated_prices'][0]['product_id'])->toBe($productChanged->id);
            });
        });
        describe('rollback', function () {
            it('rolls back the cancellation if item replication fails', function () {
                $product = Product::factory()->for($this->organization)->withStock(20)->create();
                $order = SalesOrder::factory()->for($this->organization)->create(['status' => OrderStatus::Confirmed]);
                $order->items()->create([
                    'organization_id' => $this->organization->id,
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 5,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(5),
                ]);

                $product->delete();

                expect(fn () => $this->service->recreateFrom($order))->toThrow(ModelIsDeletedException::class);

                expect($order->fresh()->status)->toBe(OrderStatus::Confirmed); // rollback efetivo
                $this->assertDatabaseCount('sales_orders', 1); // nenhum pedido novo foi criado
            });
        });
    });
    describe('update', function () {
        it('should update simple collumns successfully', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
            $product = Product::factory()->for($this->organization)->withStock(10)->create();

            $salesOrder->items()->create([
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 10,
                'unit_price' => $product->sale_price,
                'subtotal' => $product->sale_price->multiply(10),
                'organization_id' => $this->organization->id,
            ]);

            $client = Client::factory()->for($this->organization)->create();
            $seller = Seller::factory()->for($this->organization)->create();

            $result = $this->service->update($salesOrder, [
                'client_id' => $client->id,
                'seller_id' => $seller->id,
                'payment_status' => PaymentStatus::Paid->value,
            ]);

            expect($result)->toBeInstanceOf(SalesOrder::class)
                ->client_id->toBe($client->id)
                ->seller_id->toBe($seller->id)
                ->payment_status->toBe(PaymentStatus::Paid);

            expect($result->items)->toHaveCount(1);
        });

        it('throw an exception when pass an invalid status', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

            expect(fn () => $this->service->update($salesOrder, ['payment_status' => 'invalid']))->toThrow(InvalidStatusException::class);
        });

        it('throw an exception when client_id does not exist', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

            expect(fn () => $this->service->update($salesOrder, [
                'client_id' => (string) Str::uuid(),
            ]))->toThrow(ResourceNotFoundException::class);
        });

        it('throw an exception when client_id belongs to a different organization', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
            $otherOrganizationClient = Client::factory()->create();

            expect(fn () => $this->service->update($salesOrder, [
                'client_id' => $otherOrganizationClient->id,
            ]))->toThrow(ResourceNotFoundException::class);
        });

        it('throw an exception when seller_id does not exist', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

            expect(fn () => $this->service->update($salesOrder, [
                'seller_id' => (string) Str::uuid(),
            ]))->toThrow(ResourceNotFoundException::class);
        });

        it('throw an exception when seller_id belongs to a different organization', function () {
            $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
            $otherOrganizationSeller = Seller::factory()->create();

            expect(fn () => $this->service->update($salesOrder, [
                'seller_id' => $otherOrganizationSeller->id,
            ]))->toThrow(ResourceNotFoundException::class);
        });

        describe('items.to_add', function () {
            it('adds a new item successfully', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $result = $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
                        ],
                    ],
                ]);

                expect($result->items)->toHaveCount(1);
                expect($result->items->first())
                    ->product_id->toBe($product->id)
                    ->number->toBe(1)
                    ->quantity->toEqual(5);
            });

            it('throw an exception when number is null', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => null, 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when number is not numeric', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 'abc', 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when number is negative', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => -1, 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when number is zero', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 0, 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when product_id does not exist', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => (string) Str::uuid(), 'number' => 1, 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });

            it('throw an exception when product_id belongs to a different organization', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $otherOrganizationProduct = Product::factory()->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $otherOrganizationProduct->id, 'number' => 1, 'quantity' => 5],
                        ],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });

            it('throw an exception when quantity is null', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 1, 'quantity' => null],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when quantity is negative', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 1, 'quantity' => -5],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when quantity is zero', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => 1, 'quantity' => 0],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });
        });

        describe('items.to_update', function () {
            it('updates an existing item successfully', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                $result = $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => 2, 'quantity' => 20],
                        ],
                    ],
                ]);

                expect($result->items->first())
                    ->number->toBe(2)
                    ->quantity->toEqual(20);
            });

            it('throw an exception when id does not belong to the order', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $otherOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $otherOrderItem = $otherOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $otherOrderItem->id, 'number' => 2, 'quantity' => 20],
                        ],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });

            it('throw an exception when id does not exist', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => (string) Str::ulid(), 'number' => 2, 'quantity' => 20],
                        ],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });

            it('throw an exception when number is negative', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => -1, 'quantity' => 20],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when number is not numeric', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => 'abc', 'quantity' => 20],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when quantity is negative', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => 2, 'quantity' => -20],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });

            it('throw an exception when quantity is zero', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => 2, 'quantity' => 0],
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);
            });
        });

        describe('items.to_remove', function () {
            it('removes an existing item successfully', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                $result = $this->service->update($salesOrder, [
                    'items' => [
                        'to_remove' => ['id' => $item->id],
                    ],
                ]);

                expect($result->items)->toHaveCount(0);
                $this->assertSoftDeleted($item);
            });

            it('throw an exception when id does not belong to the order', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $otherOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $otherOrderItem = $otherOrder->items()->create([
                    'product_id' => $product->id,
                    'number' => 1,
                    'quantity' => 10,
                    'unit_price' => $product->sale_price,
                    'subtotal' => $product->sale_price->multiply(10),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_remove' => [$otherOrderItem->id],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });

            it('throw an exception when id does not exist', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_remove' => [(string) Str::uuid()],
                    ],
                ]))->toThrow(ResourceNotFoundException::class);
            });
        });

        describe('combined operations', function () {
            it('applies to_remove, to_update and to_add together in a single call', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $products = Product::factory()->for($this->organization)->withStock(10)->count(3)->create();

                $itemToRemove = $salesOrder->items()->create([
                    'product_id' => $products[0]->id, 'number' => 1, 'quantity' => 5,
                    'unit_price' => $products[0]->sale_price, 'subtotal' => $products[0]->sale_price->multiply(5),
                    'organization_id' => $this->organization->id,
                ]);
                $itemToUpdate = $salesOrder->items()->create([
                    'product_id' => $products[1]->id, 'number' => 2, 'quantity' => 5,
                    'unit_price' => $products[1]->sale_price, 'subtotal' => $products[1]->sale_price->multiply(5),
                    'organization_id' => $this->organization->id,
                ]);

                $result = $this->service->update($salesOrder, [
                    'items' => [
                        'to_remove' => [$itemToRemove->id],
                        'to_update' => [
                            ['id' => $itemToUpdate->id, 'number' => 1, 'quantity' => 15],
                        ],
                        'to_add' => [
                            ['product_id' => $products[2]->id, 'number' => 2, 'quantity' => 8],
                        ],
                    ],
                ]);

                expect($result->items)->toHaveCount(2);
                $this->assertSoftDeleted($itemToRemove);
                $this->assertDatabaseHas('sales_order_items', [
                    'id' => $itemToUpdate->id,
                    'number' => 1,
                    'quantity' => 15.000,
                ]);
                $this->assertDatabaseHas('sales_order_items', [
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $products[2]->id,
                    'number' => 2,
                    'quantity' => 8,
                ]);
            });

            it('rolls back everything when one operation in the middle is invalid', function () {
                $salesOrder = SalesOrder::factory()->for($this->organization)->createOne();
                $product = Product::factory()->for($this->organization)->withStock(10)->create();

                $item = $salesOrder->items()->create([
                    'product_id' => $product->id, 'number' => 1, 'quantity' => 5,
                    'unit_price' => $product->sale_price, 'subtotal' => $product->sale_price->multiply(5),
                    'organization_id' => $this->organization->id,
                ]);

                expect(fn () => $this->service->update($salesOrder, [
                    'items' => [
                        'to_update' => [
                            ['id' => $item->id, 'number' => 1, 'quantity' => 15],
                        ],
                        'to_add' => [
                            ['product_id' => $product->id, 'number' => -1, 'quantity' => 8], // inválido
                        ],
                    ],
                ]))->toThrow(InvalidFieldException::class);

                // nada foi persistido — o item original continua intacto
                $this->assertDatabaseHas('sales_order_items', [
                    'id' => $item->id,
                    'number' => 1,
                    'quantity' => 5,
                ]);
            });
        });
    });
});
