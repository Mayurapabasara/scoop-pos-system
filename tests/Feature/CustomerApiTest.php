<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: UserRole}>
     */
    public static function customerEditorRoles(): array
    {
        return [
            'owner' => [UserRole::Owner],
            'manager' => [UserRole::Manager],
            'cashier' => [UserRole::Cashier],
        ];
    }

    #[DataProvider('customerEditorRoles')]
    public function test_every_role_can_create_view_and_update_customers(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $existing = Customer::factory()->create(['name' => 'Existing Customer']);

        Sanctum::actingAs($user);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $existing->id);

        $this->postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => "{$role->value}@example.test",
            'phone' => '+94770000000',
        ])
            ->assertCreated()
            ->assertHeader('Location', route('customers.show', 2))
            ->assertJsonPath('data.name', 'New Customer');

        $this->getJson("/api/customers/{$existing->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->patchJson("/api/customers/{$existing->id}", [
            'name' => 'Updated Customer',
            'phone' => '+94771111111',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Customer')
            ->assertJsonPath('data.phone', '+94771111111');

        $this->assertDatabaseHas('customers', [
            'id' => $existing->id,
            'name' => 'Updated Customer',
            'phone' => '+94771111111',
        ]);
    }

    public function test_only_an_owner_can_delete_a_customer(): void
    {
        $customer = Customer::factory()->create();
        $owner = User::factory()->owner()->create();

        foreach ([UserRole::Manager, UserRole::Cashier] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));

            $this->deleteJson("/api/customers/{$customer->id}")->assertForbidden();
            $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        }

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/customers/{$customer->id}")->assertNoContent();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_email_is_unique_and_updates_can_keep_the_current_email(): void
    {
        $user = User::factory()->manager()->create();
        $customer = Customer::factory()->create(['email' => 'existing@example.test']);
        $otherCustomer = Customer::factory()->create(['email' => 'other@example.test']);

        Sanctum::actingAs($user);

        $this->postJson('/api/customers', [
            'name' => 'Duplicate Email',
            'email' => $customer->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->patchJson("/api/customers/{$customer->id}", [
            'name' => 'Renamed Customer',
            'email' => $customer->email,
        ])
            ->assertOk()
            ->assertJsonPath('data.email', $customer->email);

        $this->patchJson("/api/customers/{$customer->id}", [
            'email' => $otherCustomer->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'existing@example.test',
            'name' => 'Renamed Customer',
        ]);
    }

    public function test_customer_validation_rejects_invalid_fields_without_creating_a_record(): void
    {
        Sanctum::actingAs(User::factory()->cashier()->create());

        $this->postJson('/api/customers', [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => str_repeat('1', 31),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone']);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_customer_routes_require_authentication_and_return_json_not_found_responses(): void
    {
        $customer = Customer::factory()->create();

        $this->getJson('/api/customers')->assertUnauthorized();
        $this->getJson("/api/customers/{$customer->id}")->assertUnauthorized();
        $this->postJson('/api/customers', [])->assertUnauthorized();
        $this->patchJson("/api/customers/{$customer->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/customers/{$customer->id}")->assertUnauthorized();

        Sanctum::actingAs(User::factory()->owner()->create());

        $this->getJson('/api/customers/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'No query results for model [App\\Models\\Customer] 999999');
    }
}
