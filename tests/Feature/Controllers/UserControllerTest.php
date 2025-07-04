<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $superAdmin;
    protected Admin $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // seed role
        $this->seed(RoleSeeder::class);

        $this->owner = Admin::factory()->create();
        $this->owner->assignRole('owner');
        $this->superAdmin = Admin::factory()->create();
        $this->superAdmin->assignRole('super_admin');
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('manager');
        $this->user = User::factory()->create();

        
    }

    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    public function test_all_routes_require_admin_authentication()
    {
        $routes = [
            ['get', route('admin.users', [], false)],
            ['post', route('admin.users.store', [], false)],
            ['patch', route('admin.users.update', ['user' => $this->user->id], false)],
            ['post', route('admin.users.delete', [], false)],
        ];
        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // USER LISTING
    // ========================================

    public function test_admin_can_view_user_list()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.users'));
        $response->assertOk();
        $response->assertViewIs('pages.admin.users.list');
    }

    // ========================================
    // USER CREATION
    // ========================================

    public function test_admin_can_create_user_with_valid_data()
    {
        $this->actingAs($this->admin, 'admin');
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'birthdate' => '2005-05-05',
            'gender' => 'male',
            'password' => 'password123',
        ];
        $response = $this->post(route('admin.users.store'), $userData);
        $response->assertRedirectBack();
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_cannot_create_user_with_invalid_data()
    {
        $this->actingAs($this->admin, 'admin');
        $invalidData = [
            'name' => '', // required
            'email' => 'not-an-email', // invalid
            'birthdate' => 'not-a-date',
            'gender' => 'other', // not in allowed values
            'password' => 'short', // too short
        ];
        $response = $this->post(route('admin.users.store'), $invalidData);
        $response->assertSessionHasErrors(['name', 'email', 'birthdate', 'gender', 'password'], errorBag:'createUser');
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        $this->actingAs($this->admin, 'admin');
        $existingUser = User::factory()->create(['email' => 'dupe@example.com']);
        $userData = [
            'name' => 'Another User',
            'email' => 'dupe@example.com',
            'birthdate' => '2005-05-05',
            'gender' => 'male',
            'password' => 'password123',
        ];
        $response = $this->post(route('admin.users.store'), $userData);
        $response->assertSessionHasErrors(['email'], errorBag:'createUser');
    }

    // ========================================
    // USER UPDATE
    // ========================================

    public function test_admin_can_update_user_with_valid_data()
    {
        $this->actingAs($this->admin, 'admin');
        $user = User::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];
        $response = $this->patch(route('admin.users.update', ['user' => $user->id]), $updateData);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_cannot_update_user_with_invalid_data()
    {
        $this->actingAs($this->admin, 'admin');
        $user = User::factory()->create();
        $invalidData = [
            'name' => '',
            'email' => 'not-an-email',
        ];
        $response = $this->patch(route('admin.users.update', ['user' => $user]), $invalidData);
        $response->assertSessionHasErrors(['name', 'email'], errorBag:'updateUser');
    }

    public function test_cannot_update_user_with_duplicate_email()
    {
        $this->actingAs($this->admin, 'admin');
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $updateData = [
            'name' => 'User Two',
            'email' => 'user1@example.com', // duplicate
        ];
        $response = $this->patch(route('admin.users.update', ['user' => $user2]), $updateData);
        $response->assertSessionHasErrors(['email'], errorBag:'updateUser');
    }

    // ========================================
    // USER DELETION
    // ========================================

    public function test_owner_and_super_admin_can_delete_any_user()
    {
        $this->actingAs($this->owner, 'admin');
        $user = User::factory()->create();
        $response = $this->post(route('admin.users.delete'), ['id' => $user->id]);
        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->actingAs($this->superAdmin, 'admin');
        $user = User::factory()->create();
        $response = $this->post(route('admin.users.delete'), ['id' => $user->id]);
        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_normal_admin_cannot_delete_user_that_is_not_created_by_them()
    {
        $this->actingAs($this->admin, 'admin');
        $user = User::factory()->create(['admin_id' => $this->owner->id]);
        $response = $this->post(route('admin.users.delete'), ['id' => $user->id]);
        $response->assertRedirect();
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_update_nonexistent_user_returns_404()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->patch(route('admin.users.update', ['user' => 999999]), [
            'name' => 'Name',
            'email' => 'email@example.com',
        ]);
        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_user_returns_404()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->post(route('admin.users.delete'), ['id' => 999999]);
        $response->assertStatus(404);
    }

    // ========================================
    // INDEX, SHOW, AND EDIT TESTS
    // ========================================

    public function test_admin_can_access_user_dashboard()
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('user.index'));
        $response->assertOk();
        $response->assertViewIs('pages.user.dashboard');
        $response->assertViewHas('data');
        $data = $response->viewData('data');

        $this->assertIsArray($data);
    
        $expectedKeys = [
            'latestCompetitions',
            'active_comp',
            'coming_comp',
            'finished_comp',
        ];
    
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    
        $this->assertEqualsCanonicalizing(
            $expectedKeys,
            array_keys($data)
        );
    }

    public function test_admin_can_access_user_list_show()
    {
        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.users'));
        $response->assertOk();
        $response->assertViewIs('pages.admin.users.list');
    }

    public function test_admin_can_access_edit_user_form()
    {
        $this->actingAs($this->admin, 'admin');
        $user = User::factory()->create();
        $response = $this->get(route('admin.users.edit', ['user' => $user->id]));
        $response->assertOk();
        $response->assertViewIs('pages.admin.users.edit-user');
        $response->assertViewHas('user', function ($u) use ($user) {
            return $u->id === $user->id;
        });
    }
} 