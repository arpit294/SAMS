<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sport;
use App\Models\Level;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoachScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $coach;
    protected $sport;
    protected $level;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $permissionCreate = Permission::firstOrCreate(['name' => 'batch_create'], ['module_name' => 'Batch']);
        $permissionEdit = Permission::firstOrCreate(['name' => 'batch_edit'], ['module_name' => 'Batch']);
        $adminRole->givePermissionTo([$permissionCreate, $permissionEdit]);

        Role::firstOrCreate(['name' => 'coach']);

        $this->adminUser = User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'admin',
        ]);

        $this->coach = User::create([
            'firstname' => 'Coach',
            'lastname' => 'Aarav',
            'email' => 'coach@example.com',
            'phone' => '0987654321',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'coach',
        ]);
        $this->coach->assignRole('coach');

        $this->sport = Sport::create(['name' => 'Cricket', 'slug' => 'cricket', 'status' => 'active']);
        $this->level = Level::create(['name' => 'Beginner', 'slug' => 'beginner', 'status' => 'active']);
    }

    public function test_coach_cannot_be_assigned_to_overlapping_active_batches_on_creation()
    {
        // First batch: 08:00 AM - 09:30 AM (active)
        $batch1 = Batch::create([
            'name' => 'Morning Cricket A',
            'capacity' => 10,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
        ]);
        $batch1->coaches()->attach($this->coach->id);

        // Try creating overlapping batch: 09:00 AM - 10:30 AM (overlaps by 30 mins)
        $response = $this->actingAs($this->adminUser)->postJson(route('batches.store'), [
            'name' => 'Morning Cricket B',
            'capacity' => 10,
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
            'coaches' => [$this->coach->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "Coach 'Coach Aarav' is already assigned to another batch ('Morning Cricket A') during this time (08:00 AM - 09:30 AM).",
        ]);
    }

    public function test_coach_cannot_be_assigned_to_overlapping_active_batches_on_update()
    {
        // First batch: 08:00 AM - 09:30 AM (active)
        $batch1 = Batch::create([
            'name' => 'Morning Cricket A',
            'capacity' => 10,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
        ]);
        $batch1->coaches()->attach($this->coach->id);

        // Second batch: 12:00 PM - 01:30 PM (active, currently non-overlapping)
        $batch2 = Batch::create([
            'name' => 'Evening Cricket A',
            'capacity' => 10,
            'start_time' => '12:00:00',
            'end_time' => '13:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
        ]);
        $batch2->coaches()->attach($this->coach->id);

        // Try updating batch2 to overlap with batch1: 08:30 AM - 10:00 AM
        $response = $this->actingAs($this->adminUser)->putJson(route('batches.update', $batch2->id), [
            'name' => 'Evening Cricket A Updated',
            'capacity' => 10,
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
            'coaches' => [$this->coach->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "Coach 'Coach Aarav' is already assigned to another batch ('Morning Cricket A') during this time (08:00 AM - 09:30 AM).",
        ]);
    }

    public function test_coach_can_be_assigned_to_non_overlapping_batches()
    {
        // First batch: 08:00 AM - 09:00 AM
        $batch1 = Batch::create([
            'name' => 'Morning Cricket A',
            'capacity' => 10,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
        ]);
        $batch1->coaches()->attach($this->coach->id);

        // Second batch starts right after first batch: 09:00 AM - 10:00 AM
        $response = $this->actingAs($this->adminUser)->postJson(route('batches.store'), [
            'name' => 'Morning Cricket B',
            'capacity' => 10,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
            'coaches' => [$this->coach->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }

    public function test_coach_can_be_assigned_to_overlapping_batches_if_one_is_inactive()
    {
        // First batch: 08:00 AM - 09:30 AM (INACTIVE)
        $batch1 = Batch::create([
            'name' => 'Morning Cricket A',
            'capacity' => 10,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'inactive',
        ]);
        $batch1->coaches()->attach($this->coach->id);

        // Overlapping active batch creation: 09:00 AM - 10:30 AM
        $response = $this->actingAs($this->adminUser)->postJson(route('batches.store'), [
            'name' => 'Morning Cricket B',
            'capacity' => 10,
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'sport_id' => $this->sport->id,
            'level_id' => $this->level->id,
            'status' => 'active',
            'coaches' => [$this->coach->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }
}
