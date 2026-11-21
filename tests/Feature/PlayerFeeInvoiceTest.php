<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sport;
use App\Models\Level;
use App\Models\Batch;
use App\Models\PlayerFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlayerFeeInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $player;
    protected $batch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the admin role and permission
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $permission = Permission::firstOrCreate(['name' => 'fee_view'], ['module_name' => 'Fee']);
        $adminRole->givePermissionTo($permission);

        // Create player role
        Role::firstOrCreate(['name' => 'player']);

        $this->adminUser = User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'status' => 'active',
            'joined_at' => now(),
            'role' => 'admin',
        ]);

        $this->player = User::create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0987654321',
            'password' => 'password123',
            'status' => 'active',
            'joined_at' => now(),
            'role' => 'player',
        ]);
        $this->player->assignRole('player');

        $sport = Sport::create(['name' => 'Tennis', 'slug' => 'tennis', 'status' => 'active']);
        $level = Level::create(['name' => 'Beginner', 'slug' => 'beginner', 'status' => 'active']);
        $this->batch = Batch::create([
            'name' => 'Morning Batch',
            'capacity' => 10,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'sport_id' => $sport->id,
            'level_id' => $level->id,
            'status' => 'active',
        ]);
    }

    public function test_invoice_is_accessible_to_authorized_user_for_paid_fees()
    {
        $fee = PlayerFee::create([
            'player_id' => $this->player->id,
            'batch_id' => $this->batch->id,
            'sub_totalamount' => 1000.00,
            'discount_amount' => 100.00,
            'penalty_amount' => 0.00,
            'total_amt' => 900.00,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'payment_type' => 'cash',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('player-fees.invoice', $fee->id));

        $response->assertStatus(200);
        $response->assertSee('INVOICE');
        $response->assertSee('John Doe');
        $response->assertSee('Morning Batch');
        $response->assertSee('900.00');
    }

    public function test_invoice_aborts_with_404_for_pending_fees()
    {
        $fee = PlayerFee::create([
            'player_id' => $this->player->id,
            'batch_id' => $this->batch->id,
            'sub_totalamount' => 1000.00,
            'discount_amount' => 100.00,
            'penalty_amount' => 0.00,
            'total_amt' => 900.00,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'payment_type' => 'cash',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('player-fees.invoice', $fee->id));

        $response->assertStatus(404);
    }

    public function test_invoice_aborts_with_403_for_unauthorized_user()
    {
        $fee = PlayerFee::create([
            'player_id' => $this->player->id,
            'batch_id' => $this->batch->id,
            'sub_totalamount' => 1000.00,
            'discount_amount' => 100.00,
            'penalty_amount' => 0.00,
            'total_amt' => 900.00,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'payment_type' => 'cash',
            'status' => 'paid',
        ]);

        // Non-admin user without fee_view permission
        $unauthorizedUser = User::create([
            'firstname' => 'Regular',
            'lastname' => 'User',
            'email' => 'regular@example.com',
            'phone' => '1112223334',
            'password' => 'password123',
            'status' => 'active',
            'joined_at' => now(),
            'role' => 'player',
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('player-fees.invoice', $fee->id));

        $response->assertStatus(403);
    }
}
