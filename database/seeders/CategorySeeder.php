<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Vanilla' => 'Classic vanilla ice cream flavors.',
            'Chocolate' => 'Chocolate-based ice cream flavors.',
            'Strawberry' => 'Strawberry ice cream flavors.',
        ];

        foreach ($categories as $name => $description) {
            Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }
}
