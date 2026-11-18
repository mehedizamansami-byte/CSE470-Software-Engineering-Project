<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 10 (content manager) and Feature 7 (notifications). */
class ContentAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_edit_publish_and_delete_post(): void
    {
        $seller = User::factory()->create();

        $this->actingAs($seller)->get('/posts/create')->assertOk();

        $this->actingAs($seller)->post('/posts', [
            'title' => 'Boishakh sale', 'caption' => 'Shubho Noboborsho!', 'platform' => 'facebook',
            'status' => 'scheduled', 'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('scheduled_at');

        $this->actingAs($seller)->post('/posts', [
            'title' => 'Boishakh sale', 'caption' => 'Shubho Noboborsho!', 'platform' => 'facebook',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'), 'remind_before' => 60,
        ])->assertRedirect(route('posts.index'));

        $post = Post::first();
        $this->assertSame(60, $post->remind_before);

        $this->actingAs($seller)->put("/posts/{$post->id}", [
            'title' => 'Boishakh mega sale', 'caption' => 'Updated', 'platform' => 'both', 'status' => 'draft',
        ])->assertRedirect(route('posts.index'));
        $this->assertSame('draft', $post->fresh()->status);

        $this->actingAs($seller)->post("/posts/{$post->id}/publish")->assertSessionHas('success');
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($seller)->get('/posts?tab=published')->assertOk()->assertSee('Boishakh mega sale');
        $this->actingAs($seller)->delete("/posts/{$post->id}")->assertRedirect(route('posts.index'));
        $this->assertSame(0, Post::count());
    }

    public function test_ai_captions_use_credits_and_support_bangla(): void
    {
        $seller = User::factory()->credits(2)->create();

        $this->actingAs($seller)->postJson('/captions', ['topic' => 'Eid panjabi', 'tone' => 'festive', 'language' => 'en', 'platform' => 'facebook'])
            ->assertOk()->assertJsonCount(3, 'captions')->assertJsonPath('credits', 1);

        $bangla = $this->actingAs($seller)->postJson('/captions', ['topic' => 'পাঞ্জাবি', 'tone' => 'friendly', 'language' => 'bn', 'platform' => 'facebook'])
            ->assertOk()->json('captions.0');
        $this->assertStringContainsString('পাঞ্জাবি', $bangla);

        $this->actingAs($seller)->postJson('/captions', ['topic' => 'More', 'tone' => 'friendly', 'language' => 'en', 'platform' => 'facebook'])
            ->assertStatus(402);
        $this->assertSame(0, $seller->fresh()->ai_credits);
    }

    public function test_reminder_is_sent_to_seller_and_content_collaborators(): void
    {
        $seller = User::factory()->create();
        $staff = User::factory()->role('collaborator')->create();
        TeamMember::create(['seller_id' => $seller->id, 'user_id' => $staff->id, 'email' => $staff->email, 'permissions' => ['content'], 'status' => 'active']);

        $seller->posts()->create([
            'title' => 'Soon', 'caption' => 'x', 'platform' => 'facebook', 'status' => 'scheduled',
            'scheduled_at' => now()->addMinutes(10), 'remind_before' => 30,
        ]);
        $seller->posts()->create([
            'title' => 'Later', 'caption' => 'y', 'platform' => 'facebook', 'status' => 'scheduled',
            'scheduled_at' => now()->addHours(5), 'remind_before' => 30,
        ]);

        $this->actingAs($seller)->getJson('/notifications/feed')->assertOk()->assertJsonPath('unread', 1);
        $this->assertSame(1, $staff->unreadNotifications()->count());
        $this->assertNotNull(Post::where('title', 'Soon')->first()->reminder_sent_at);
        $this->assertNull(Post::where('title', 'Later')->first()->reminder_sent_at);

        // Polling again does not send a second reminder.
        $this->actingAs($seller)->getJson('/notifications/feed')->assertJsonPath('unread', 1);

        $this->artisan('posts:remind')->assertSuccessful();
    }

    public function test_notification_center_mark_read_and_mark_all(): void
    {
        $user = User::factory()->create();
        $user->notify(new AppNotification('One', 'First', '/billing'));
        $user->notify(new AppNotification('Two', 'Second'));

        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('First');

        $first = $user->notifications()->where('data', 'like', '%"One"%')->first();
        $this->actingAs($user)->get("/notifications/{$first->id}/open")->assertRedirect('/billing');
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)->post('/notifications/read-all')->assertSessionHas('success');
        $this->assertSame(0, $user->unreadNotifications()->count());

        $other = User::factory()->create();
        $this->actingAs($other)->get("/notifications/{$first->id}/open")->assertNotFound();
    }
}
