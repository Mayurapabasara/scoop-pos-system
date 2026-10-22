<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_list_and_view_items_with_their_categories(): void
    {
        $user = User::factory()->cashier()->create();
        $item = Item::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id)
            ->assertJsonPath('data.0.category.id', $item->category_id)
            ->assertJsonPath('data.0.category.name', $item->category->name);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.category.id', $item->category_id);
    }

    public function test_an_owner_can_create_an_item(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/items', [
            'category_id' => $category->id,
            'name' => 'Salted Caramel Scoop',
            'description' => 'Caramel ice cream with sea salt.',
            'unit_price' => '4.50',
            'stock_quantity' => 24,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Location', route('items.show', 1))
            ->assertJsonPath('data.name', 'Salted Caramel Scoop')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.unit_price', '4.50');

        $this->assertDatabaseHas('items', [
            'category_id' => $category->id,
            'name' => 'Salted Caramel Scoop',
            'stock_quantity' => 24,
        ]);
    }

    public function test_a_cashier_can_update_an_item_but_cannot_create_one(): void
    {
        $cashier = User::factory()->cashier()->create();
        $item = Item::factory()->create([
            'name' => 'Before Update',
            'unit_price' => '3.00',
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($cashier);

        $this->patchJson("/api/items/{$item->id}", [
            'name' => 'After Update',
            'unit_price' => '3.75',
            'stock_quantity' => 8,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'After Update')
            ->assertJsonPath('data.unit_price', '3.75')
            ->assertJsonPath('data.stock_quantity', 8);

        $this->postJson('/api/items', [
            'category_id' => $item->category_id,
            'name' => 'Unauthorised Item',
            'unit_price' => '3.50',
            'stock_quantity' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('items', ['name' => 'Unauthorised Item']);
    }

    public function test_an_owner_can_delete_an_item_while_a_manager_cannot(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = User::factory()->manager()->create();
        $item = Item::factory()->create();

        Sanctum::actingAs($manager);

        $this->deleteJson("/api/items/{$item->id}")->assertForbidden();
        $this->assertDatabaseHas('items', ['id' => $item->id]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/items/{$item->id}")->assertNoContent();
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_item_search_matches_item_names_only(): void
    {
        $user = User::factory()->manager()->create();
        $category = Category::factory()->create();
        $match = Item::factory()->for($category)->create(['name' => 'Chocolate Fudge Scoop']);
        Item::factory()->for($category)->create(['name' => 'Vanilla Bean Scoop']);
        Item::factory()->for($category)->create(['name' => 'Berry Swirl Scoop', 'description' => 'Chocolate topping']);

        Sanctum::actingAs($user);

        $this->getJson('/api/items?search=Chocolate')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_item_validation_rejects_invalid_relations_prices_and_quantities_without_persisting(): void
    {
        $owner = User::factory()->owner()->create();

        Sanctum::actingAs($owner);

        $this->postJson('/api/items', [
            'category_id' => 999999,
            'name' => '',
            'unit_price' => '-0.01',
            'stock_quantity' => -1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'unit_price', 'stock_quantity']);

        $this->assertDatabaseCount('items', 0);
    }

    public function test_inventory_routes_require_authentication_and_return_json_not_found_responses(): void
    {
        $item = Item::factory()->create();

        $this->getJson('/api/items')->assertUnauthorized();
        $this->getJson("/api/items/{$item->id}")->assertUnauthorized();
        $this->postJson('/api/items', [])->assertUnauthorized();
        $this->patchJson("/api/items/{$item->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/items/{$item->id}")->assertUnauthorized();

        Sanctum::actingAs(User::factory()->owner()->create());

        $this->getJson('/api/items/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'No query results for model [App\\Models\\Item] 999999');
    }
}
