<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Ai\LocalAiEngine;
use App\Services\AuditLogger;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * Sample data so every feature can be demonstrated right after "php artisan migrate --seed".
 */
class DemoDataSeeder extends Seeder
{
    public function run(SubscriptionService $subscriptions, PaymentService $payments, LocalAiEngine $ai): void
    {
        $seller = User::where('email', 'seller@brandbondhu.test')->firstOrFail();
        $collaborator = User::where('email', 'collaborator@brandbondhu.test')->firstOrFail();
        $designer = User::where('email', 'designer@brandbondhu.test')->firstOrFail();

        // Run once only: the seller's shop marks the demo data as created.
        if ($seller->shop()->exists()) {
            return;
        }

        // Feature 3: shop profile
        $seller->shop()->create([
            'name' => 'Rongdhonu Fashion House',
            'category' => 'Fashion & Clothing',
            'description' => 'Handpicked ethnic and modern wear for women and men, delivered all over Bangladesh.',
            'phone' => '01711000001',
            'email' => 'hello@rongdhonu.test',
            'address' => 'House 12, Road 5, Dhanmondi',
            'city' => 'Dhaka',
            'facebook_url' => 'https://facebook.com/rongdhonu.fashion',
        ]);

        // Feature 5 + 6: Free plan with welcome credits, then a paid Basic plan (simulated bKash)
        $subscriptions->startFree($seller, grantCredits: true);
        $basic = Plan::where('slug', 'basic')->firstOrFail();
        $payment = $payments->createForPlan($seller, $basic);
        $payment->update(['method' => 'bkash', 'account' => '017****0001']);
        $payments->complete($payment);

        // Feature 4: an active collaborator
        TeamMember::create([
            'seller_id' => $seller->id,
            'user_id' => $collaborator->id,
            'email' => $collaborator->email,
            'name' => $collaborator->name,
            'permissions' => ['brand_kit', 'style_guide', 'content'],
            'status' => TeamMember::STATUS_ACTIVE,
            'invited_at' => now()->subDays(3),
            'accepted_at' => now()->subDays(3),
        ]);
        AuditLogger::log($seller, 'team.invited', 'Invited collaborator@brandbondhu.test with access to: Brand identity kits, Brand style guide, Content manager', $seller);
        AuditLogger::log($seller, 'team.joined', 'Demo Collaborator accepted the invitation and joined the team', $collaborator);

        // Feature 8: an active brand kit
        $input = [
            'business_name' => 'Rongdhonu Fashion House',
            'industry' => 'Fashion & Clothing',
            'audience' => 'young professionals in Dhaka',
            'personality' => 'traditional',
        ];
        $kit = $seller->brandKits()->create($input + [
            'name' => 'Rongdhonu main brand',
            'created_by' => $seller->id,
            'is_active' => true,
        ] + $ai->brandKit($input));
        AuditLogger::log($seller, 'brand_kit.created', "Generated brand kit \"{$kit->name}\"", $seller);

        // Feature 9: its style guide
        $guide = $ai->styleGuide($kit->toAiArray(), 'modern');
        $seller->styleGuides()->create([
            'brand_kit_id' => $kit->id,
            'title' => $guide['title'],
            'preset' => 'modern',
            'sections' => $guide['sections'],
            'rules' => $guide['rules'],
        ]);
        AuditLogger::log($seller, 'style_guide.generated', "Generated the style guide for \"{$kit->name}\"", $seller);

        // Feature 10: posts in every state. The first one is due soon, so its reminder fires right away.
        $posts = [
            ['Eid panjabi launch', 'scheduled', now()->addMinutes(25), 30, 'facebook'],
            ['Weekend flash sale', 'scheduled', now()->addDays(2)->setTime(20, 0), 60, 'both'],
            ['Customer review: Nusrat', 'draft', null, null, 'instagram'],
            ['New cotton saree collection', 'published', now()->subDays(2)->setTime(19, 30), null, 'facebook'],
        ];

        foreach ($posts as [$title, $status, $when, $remind, $platform]) {
            $captions = $ai->captions(['topic' => $title, 'brand' => 'Rongdhonu Fashion House', 'tone' => 'friendly', 'platform' => $platform]);
            $seller->posts()->create([
                'created_by' => $status === 'draft' ? $collaborator->id : $seller->id,
                'title' => $title,
                'caption' => $captions['captions'][0],
                'hashtags' => implode(' ', array_slice($captions['hashtags'], 0, 5)),
                'platform' => $platform,
                'status' => $status,
                'scheduled_at' => $when,
                'remind_before' => $remind,
                'published_at' => $status === Post::STATUS_PUBLISHED ? $when : null,
            ]);
        }
        AuditLogger::log($seller, 'post.created', 'Created scheduled post "Eid panjabi launch"', $collaborator);

        // Feature 3: designer portfolio
        $profile = $designer->designerProfile()->create([
            'headline' => 'Logo, packaging and social media designer',
            'bio' => 'I help small Bangladeshi brands look professional online. 5 years of experience with fashion, food and handicraft shops.',
            'skills' => ['Logo design', 'Facebook banners', 'Packaging', 'Brand guidelines'],
            'starting_price' => 1500,
            'experience_years' => 5,
            'location' => 'Chattogram',
            'portfolio_url' => 'https://www.behance.net/',
            'is_available' => true,
        ]);
        $profile->items()->createMany([
            ['title' => 'Logo for Mishti Bari sweets', 'category' => 'Logo', 'description' => 'A warm, traditional logo for a family sweet shop in Sylhet.'],
            ['title' => 'Eid campaign banners', 'category' => 'Social media', 'description' => '12 Facebook banners for a clothing page, 3x more reach than the year before.'],
            ['title' => 'Jute bag packaging', 'category' => 'Packaging', 'description' => 'Eco-friendly packaging design for a handicraft exporter.'],
        ]);

        // Feature 7: a few notifications
        $collaborator->notify(new AppNotification('You joined a team', 'You can now work on Rongdhonu Fashion House: brand kits, style guide and content.', route('dashboard', [], false), 'success'));
        $designer->notify(new AppNotification('Welcome to BrandBondhu!', 'Set up your portfolio so sellers can see your best work.', route('portfolio.edit', [], false), 'success'));
        User::where('email', 'admin@brandbondhu.test')->first()?->notify(new AppNotification('Admin tip', 'See every role and permission on the Roles & permissions page.', route('admin.roles', [], false)));
    }
}
