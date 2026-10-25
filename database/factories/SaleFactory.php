<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 1, 100);

        return [
            'receipt_number' => 'SALE-'.Str::upper((string) Str::ulid()),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'subtotal' => $total,
            'total' => $total,
        ];
    }
}
