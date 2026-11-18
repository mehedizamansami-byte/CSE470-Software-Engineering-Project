<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 3 (profiles) and Feature 4 (team, collaborators, audit log). */
class ProfileAndTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_sets_up_shop_profile(): void
    {
        $seller = User::factory()->create();

        $this->actingAs($seller)->get('/shop')->assertOk();
        $this->actingAs($seller)->put('/shop', [
            'name' => 'Mishti Mukh', 'category' => 'Food & Bakery', 'phone' => '01812345678', 'city' => 'Sylhet',
        ])->assertSessionHas('success');

        $this->assertSame('Mishti Mukh', $seller->refresh()->shop->name);
        $this->assertTrue(AuditLog::where('action', 'shop.updated')->exists());
    }

    public function test_designer_builds_portfolio_with_public_page(): void
    {
        $designer = User::factory()->role('designer')->create();
        $seller = User::factory()->create();

        $this->actingAs($designer)->put('/portfolio', [
            'headline' => 'Logo designer', 'skills' => 'Logos, Banners, Logos', 'starting_price' => 1200, 'is_available' => 1,
        ])->assertSessionHas('success');
        $this->assertSame(['Logos', 'Banners'], $designer->designerProfile->skills);

        $this->actingAs($designer)->post('/portfolio/items', ['title' => 'Tea brand logo'])->assertSessionHas('success');
        $this->actingAs($seller)->get("/designers/{$designer->id}")->assertOk()->assertSee('Tea brand logo');
    }

    public function test_invite_accept_change_permissions_and_remove(): void
    {
        $seller = User::factory()->create();
        app(SubscriptionService::class)->activate($seller, Plan::where('slug', 'basic')->first());

        $this->actingAs($seller)->post('/team', ['email' => 'staff@example.com', 'permissions' => ['content']])->assertSessionHas('success');
        $invite = TeamMember::where('email', 'staff@example.com')->first();
        $this->assertSame('pending', $invite->status);

        auth()->logout();
        $this->get("/invitations/{$invite->token}")->assertOk()->assertSee('Content manager');
        $this->post("/invitations/{$invite->token}", ['name' => 'Rafi', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $staff = User::where('email', 'staff@example.com')->first();
        $this->assertSame('collaborator', $staff->role);
        $this->assertTrue($staff->hasPermission('content'));
        $this->assertFalse($staff->hasPermission('brand_kit'));

        $this->actingAs($seller)->put("/team/{$invite->id}", ['permissions' => ['content', 'brand_kit']])->assertSessionHas('success');
        $this->assertTrue($staff->fresh()->hasPermission('brand_kit'));

        $this->actingAs($seller)->delete("/team/{$invite->id}")->assertSessionHas('success');
        $this->assertModelMissing($staff);

        $actions = AuditLog::where('seller_id', $seller->id)->pluck('action')->all();
        foreach (['team.invited', 'team.joined', 'team.permissions', 'team.removed'] as $action) {
            $this->assertContains($action, $actions);
        }
        $this->actingAs($seller)->get('/team/audit-log')->assertOk()->assertSee('team.removed');
    }

    public function test_free_plan_cannot_invite(): void
    {
        $seller = User::factory()->create();

        $this->actingAs($seller)->post('/team', ['email' => 'x@example.com'])->assertSessionHas('error');
        $this->assertSame(0, TeamMember::count());
    }
}
