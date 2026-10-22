<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['category' => 'Vanilla', 'name' => 'Classic Vanilla Scoop', 'description' => 'Creamy Madagascan vanilla ice cream.', 'unit_price' => 3.50, 'stock_quantity' => 80],
            ['category' => 'Chocolate', 'name' => 'Belgian Chocolate Scoop', 'description' => 'Rich dark chocolate ice cream.', 'unit_price' => 4.00, 'stock_quantity' => 65],
            ['category' => 'Strawberry', 'name' => 'Strawberry Swirl Scoop', 'description' => 'Strawberry ice cream with fruit swirl.', 'unit_price' => 3.75, 'stock_quantity' => 70],
        ];

        foreach ($items as $item) {
            $category = Category::query()->where('name', $item['category'])->firstOrFail();

            Item::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $category->id,
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'stock_quantity' => $item['stock_quantity'],
                ],
            );
        }
    }
}
