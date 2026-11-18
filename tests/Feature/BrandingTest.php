<?php

namespace Tests\Feature;

use App\Models\BrandKit;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 8 (brand identity kits) and Feature 9 (style guide). */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    private function seller(int $credits): User
    {
        $seller = User::factory()->credits($credits)->create();
        app(SubscriptionService::class)->activate($seller, Plan::where('slug', 'free')->first());

        return $seller;
    }

    public function test_generate_regenerate_edit_activate_and_delete_kit(): void
    {
        $seller = $this->seller(20);

        $this->actingAs($seller)->post('/brand-kits', [
            'business_name' => 'Nakshi Ghor', 'industry' => 'Handicrafts', 'personality' => 'traditional',
        ])->assertRedirect();

        $kit = BrandKit::first();
        $this->assertTrue($kit->is_active);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $kit->colors['primary']);
        $this->assertSame('NG', $kit->logo['initials']);
        $this->assertSame(15, $seller->fresh()->ai_credits);

        $this->actingAs($seller)->get("/brand-kits/{$kit->id}")->assertOk()->assertSee('Nakshi Ghor');

        $this->actingAs($seller)->post("/brand-kits/{$kit->id}/regenerate", ['asset' => 'colors'])->assertSessionHas('success');
        $this->assertSame(14, $seller->fresh()->ai_credits);

        $this->actingAs($seller)->put("/brand-kits/{$kit->id}", [
            'name' => 'Main', 'business_name' => 'Nakshi Ghor', 'industry' => 'Handicrafts', 'personality' => 'traditional',
            'tagline' => 'Handmade with heart',
            'colors' => ['primary' => '#112233', 'secondary' => '#445566', 'accent' => '#778899', 'neutral' => '#000000', 'background' => '#ffffff'],
            'typography' => ['heading' => 'Poppins', 'body' => 'Inter'],
            'logo' => ['initials' => 'ng', 'shape' => 'hexagon', 'style' => 'outline'],
            'keywords' => 'handmade, local',
        ])->assertRedirect(route('brand-kits.show', $kit));
        $kit->refresh();
        $this->assertSame('#FFFFFF', $kit->colors['background']);
        $this->assertSame(['handmade', 'local'], $kit->keywords);

        // Second kit is not allowed on the Free plan (limit 1).
        $this->actingAs($seller)->post('/brand-kits', ['business_name' => 'X', 'industry' => 'Other', 'personality' => 'bold'])->assertSessionHas('error');

        $this->actingAs($seller)->delete("/brand-kits/{$kit->id}")->assertRedirect(route('brand-kits.index'));
        $this->assertSame(0, BrandKit::count());
    }

    public function test_not_enough_credits_blocks_generation(): void
    {
        $seller = $this->seller(2);

        $this->actingAs($seller)->post('/brand-kits', ['business_name' => 'Y', 'industry' => 'Other', 'personality' => 'bold'])
            ->assertSessionHas('error');
        $this->assertSame(0, BrandKit::count());
        $this->assertSame(2, $seller->fresh()->ai_credits);
    }

    public function test_style_guide_generate_customise_and_pdf(): void
    {
        $seller = $this->seller(20);
        $this->actingAs($seller)->post('/brand-kits', ['business_name' => 'Cha Bari', 'industry' => 'Food & Bakery', 'personality' => 'natural']);
        $kit = BrandKit::first();

        $this->actingAs($seller)->get("/brand-kits/{$kit->id}/style-guide")->assertRedirect(route('style-guides.index'));
        $this->actingAs($seller)->post("/brand-kits/{$kit->id}/style-guide", ['preset' => 'bold'])->assertRedirect(route('style-guides.show', $kit));
        $this->assertSame(12, $seller->fresh()->ai_credits);

        $this->actingAs($seller)->get("/brand-kits/{$kit->id}/style-guide")->assertOk()->assertSee('Cha Bari Brand Style Guide');

        $this->actingAs($seller)->put("/brand-kits/{$kit->id}/style-guide", [
            'title' => 'Cha Bari Rules', 'preset' => 'minimal', 'sections' => ['logo', 'colors'],
            'logo_rules' => "Rule one\n\nRule two", 'typography' => ['h1' => 40, 'h2' => 28, 'body' => 15, 'small' => 12],
        ])->assertSessionHas('success');

        $guide = $kit->fresh()->styleGuide;
        $this->assertSame('minimal', $guide->preset);
        $this->assertSame(['Rule one', 'Rule two'], $guide->ruleList('logo'));
        $this->assertFalse($guide->shows('voice'));
        $this->assertSame(40, $guide->rule('typography.h1'));

        $pdf = $this->actingAs($seller)->get("/brand-kits/{$kit->id}/style-guide/pdf");
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_kits_are_private_to_their_workspace(): void
    {
        $owner = $this->seller(20);
        $other = $this->seller(20);
        $this->actingAs($owner)->post('/brand-kits', ['business_name' => 'Mine', 'industry' => 'Other', 'personality' => 'minimal']);
        $kit = BrandKit::first();

        $this->actingAs($other)->get("/brand-kits/{$kit->id}")->assertNotFound();
        $this->actingAs($other)->delete("/brand-kits/{$kit->id}")->assertNotFound();
    }
}
