<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            ['name' => 'Avery Perera', 'email' => 'avery.perera@example.test', 'phone' => '+94771234567'],
            ['name' => 'Nimal Silva', 'email' => 'nimal.silva@example.test', 'phone' => '+94777654321'],
        ];

        foreach ($customers as $customer) {
            Customer::query()->updateOrCreate(
                ['email' => $customer['email']],
                $customer,
            );
        }
    }
}
