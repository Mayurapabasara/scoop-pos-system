<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'ScoopPOS Owner', 'email' => 'owner@scooppos.test', 'role' => UserRole::Owner],
            ['name' => 'ScoopPOS Manager', 'email' => 'manager@scooppos.test', 'role' => UserRole::Manager],
            ['name' => 'ScoopPOS Cashier', 'email' => 'cashier@scooppos.test', 'role' => UserRole::Cashier],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
