<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_item_belongs_to_a_category_and_a_category_has_items(): void
    {
        $category = Category::factory()->create();
        $item = Item::factory()->for($category)->create();

        $this->assertTrue($item->category->is($category));
        $this->assertTrue($category->items->contains($item));
    }

    public function test_a_category_with_items_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Item::factory()->for($category)->create();

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_database_seeder_creates_all_roles_and_sample_inventory(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'owner@scooppos.test',
            'role' => UserRole::Owner->value,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'manager@scooppos.test',
            'role' => UserRole::Manager->value,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'cashier@scooppos.test',
            'role' => UserRole::Cashier->value,
        ]);
        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('items', 3);
        $this->assertDatabaseCount('customers', 2);
        $this->assertSame(UserRole::Owner, User::query()->where('email', 'owner@scooppos.test')->sole()->role);
    }
}
