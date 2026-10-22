<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<Item|Customer>, 1: UserRole, 2: string, 3: bool}>
     */
    public static function policyMatrix(): array
    {
        return [
            'inventory owner can list' => [Item::class, UserRole::Owner, 'viewAny', true],
            'inventory owner can view' => [Item::class, UserRole::Owner, 'view', true],
            'inventory owner can create' => [Item::class, UserRole::Owner, 'create', true],
            'inventory owner can update' => [Item::class, UserRole::Owner, 'update', true],
            'inventory owner can delete' => [Item::class, UserRole::Owner, 'delete', true],
            'inventory manager can list' => [Item::class, UserRole::Manager, 'viewAny', true],
            'inventory manager can view' => [Item::class, UserRole::Manager, 'view', true],
            'inventory manager can create' => [Item::class, UserRole::Manager, 'create', true],
            'inventory manager can update' => [Item::class, UserRole::Manager, 'update', true],
            'inventory manager cannot delete' => [Item::class, UserRole::Manager, 'delete', false],
            'inventory cashier can list' => [Item::class, UserRole::Cashier, 'viewAny', true],
            'inventory cashier can view' => [Item::class, UserRole::Cashier, 'view', true],
            'inventory cashier cannot create' => [Item::class, UserRole::Cashier, 'create', false],
            'inventory cashier can update' => [Item::class, UserRole::Cashier, 'update', true],
            'inventory cashier cannot delete' => [Item::class, UserRole::Cashier, 'delete', false],
            'customer owner can list' => [Customer::class, UserRole::Owner, 'viewAny', true],
            'customer owner can view' => [Customer::class, UserRole::Owner, 'view', true],
            'customer owner can create' => [Customer::class, UserRole::Owner, 'create', true],
            'customer owner can update' => [Customer::class, UserRole::Owner, 'update', true],
            'customer owner can delete' => [Customer::class, UserRole::Owner, 'delete', true],
            'customer manager can list' => [Customer::class, UserRole::Manager, 'viewAny', true],
            'customer manager can view' => [Customer::class, UserRole::Manager, 'view', true],
            'customer manager can create' => [Customer::class, UserRole::Manager, 'create', true],
            'customer manager can update' => [Customer::class, UserRole::Manager, 'update', true],
            'customer manager can delete' => [Customer::class, UserRole::Manager, 'delete', true],
            'customer cashier can list' => [Customer::class, UserRole::Cashier, 'viewAny', true],
            'customer cashier can view' => [Customer::class, UserRole::Cashier, 'view', true],
            'customer cashier can create' => [Customer::class, UserRole::Cashier, 'create', true],
            'customer cashier can update' => [Customer::class, UserRole::Cashier, 'update', true],
            'customer cashier cannot delete' => [Customer::class, UserRole::Cashier, 'delete', false],
        ];
    }

    #[DataProvider('policyMatrix')]
    public function test_each_role_has_exactly_the_required_permissions(
        string $model,
        UserRole $role,
        string $ability,
        bool $expected,
    ): void {
        $user = User::factory()->make(['role' => $role]);
        $subject = in_array($ability, ['viewAny', 'create'], true) ? $model : new $model;

        $this->assertSame($expected, Gate::forUser($user)->allows($ability, $subject));
    }
}
