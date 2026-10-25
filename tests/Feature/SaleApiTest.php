<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cashier_can_complete_a_sale_and_stock_is_reduced(): void
    {
        $cashier = User::factory()->cashier()->create();
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $vanilla = Item::factory()->for($category)->create([
            'name' => 'Vanilla Scoop',
            'unit_price' => '3.50',
            'stock_quantity' => 10,
        ]);
        $chocolate = Item::factory()->for($category)->create([
            'name' => 'Chocolate Scoop',
            'unit_price' => '4.00',
            'stock_quantity' => 3,
        ]);

        $response = $this->actingAs($cashier)->postJson('/api/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['item_id' => $vanilla->id, 'quantity' => 2],
                ['item_id' => $chocolate->id, 'quantity' => 1],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => '11.00'],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Location')
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.sold_by.id', $cashier->id)
            ->assertJsonPath('data.subtotal', '11.00')
            ->assertJsonPath('data.total', '11.00')
            ->assertJsonPath('data.items.0.item_name', 'Vanilla Scoop')
            ->assertJsonPath('data.items.1.line_total', '4.00')
            ->assertJsonPath('data.payments.0.method', 'cash');

        $saleId = $response->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'customer_id' => $customer->id,
            'user_id' => $cashier->id,
            'subtotal' => '11.00',
        ]);
        $this->assertDatabaseHas('sale_lines', [
            'sale_id' => $saleId,
            'item_id' => $vanilla->id,
            'item_name' => 'Vanilla Scoop',
            'quantity' => 2,
            'line_total' => '7.00',
        ]);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $saleId,
            'method' => 'cash',
            'amount' => '11.00',
        ]);
        $this->assertSame(8, $vanilla->fresh()->stock_quantity);
        $this->assertSame(2, $chocolate->fresh()->stock_quantity);
    }

    public function test_a_sale_is_rejected_without_creating_records_when_stock_is_insufficient(): void
    {
        $cashier = User::factory()->cashier()->create();
        $item = Item::factory()->create([
            'name' => 'Limited Mango Scoop',
            'unit_price' => '3.50',
            'stock_quantity' => 2,
        ]);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['item_id' => $item->id, 'quantity' => 3],
            ],
            'payments' => [
                ['method' => 'card', 'amount' => '10.50'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_lines', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(2, $item->fresh()->stock_quantity);
    }

    public function test_a_sale_is_rejected_when_payments_do_not_equal_the_total(): void
    {
        $cashier = User::factory()->cashier()->create();
        $item = Item::factory()->create([
            'unit_price' => '3.50',
            'stock_quantity' => 2,
        ]);

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['item_id' => $item->id, 'quantity' => 1],
            ],
            'payments' => [
                ['method' => 'digital', 'amount' => '3.00'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payments');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(2, $item->fresh()->stock_quantity);
    }

    public function test_cashiers_only_see_their_own_sales_while_managers_see_all_sales(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();
        $manager = User::factory()->manager()->create();
        $cashierSale = Sale::factory()->for($cashier)->create();
        $otherCashierSale = Sale::factory()->for($otherCashier)->create();

        $this->actingAs($cashier)->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cashierSale->id);

        $this->actingAs($cashier)->getJson("/api/sales/{$otherCashierSale->id}")
            ->assertForbidden();

        $this->actingAs($manager)->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_sale_routes_require_authentication_and_validate_the_payload(): void
    {
        $this->getJson('/api/sales')->assertUnauthorized();
        $this->postJson('/api/sales', [])->assertUnauthorized();

        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [],
            'payments' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items', 'payments']);
    }
}
