<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }

    public function test_guests_are_redirected_to_login_from_admin_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_manager_cannot_access_user_management(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    }

    public function test_sales_executive_cannot_access_user_management(): void
    {
        $exec = User::factory()->create();
        $this->actingAs($exec)->get('/admin/users')->assertForbidden();
        $this->actingAs($exec)->post('/admin/users', [])->assertForbidden();
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Manager',
            'email' => 'new.manager@example.com',
            'role' => User::ROLE_MANAGER,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.manager@example.com',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_demoted_or_deleted_by_another_admin_flow(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_MANAGER,
        ])->assertSessionHasErrors('role');

        $this->assertEquals(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_all_roles_can_view_the_dashboard(): void
    {
        foreach ([User::factory()->admin(), User::factory()->manager(), User::factory()] as $factory) {
            $user = $factory->create();
            $this->actingAs($user)->get('/dashboard')->assertOk();
        }
    }
}
