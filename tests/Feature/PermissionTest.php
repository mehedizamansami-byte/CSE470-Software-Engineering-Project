<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 2: role-based access control and per-feature permissions. */
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_see_only_their_features(): void
    {
        $seller = User::factory()->create();
        $designer = User::factory()->role('designer')->create();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($seller)->get('/billing')->assertOk();
        $this->actingAs($seller)->get('/portfolio')->assertForbidden();
        $this->actingAs($seller)->get('/admin/users')->assertForbidden();

        $this->actingAs($designer)->get('/portfolio')->assertOk();
        $this->actingAs($designer)->get('/brand-kits')->assertForbidden();
        $this->actingAs($designer)->get('/billing')->assertForbidden();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk()->assertSee('If seller grants');
        $this->actingAs($admin)->get('/posts')->assertForbidden();
    }

    public function test_collaborator_gets_only_granted_permissions(): void
    {
        $seller = User::factory()->create();
        $collaborator = User::factory()->role('collaborator')->create();
        TeamMember::create([
            'seller_id' => $seller->id, 'user_id' => $collaborator->id, 'email' => $collaborator->email,
            'permissions' => ['content'], 'status' => 'active',
        ]);

        $this->actingAs($collaborator)->get('/posts')->assertOk();
        $this->actingAs($collaborator)->get('/brand-kits')->assertForbidden();
        $this->actingAs($collaborator)->get('/team')->assertForbidden();
        $this->actingAs($collaborator)->get('/billing')->assertForbidden();
    }

    public function test_admin_can_change_roles_but_not_own(): void
    {
        $admin = User::factory()->role('admin')->create();
        $seller = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$seller->id}/role", ['role' => 'designer'])->assertSessionHas('success');
        $this->assertSame('designer', $seller->fresh()->role);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", ['role' => 'seller'])->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);
    }
}
