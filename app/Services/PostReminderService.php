<?php

namespace App\Services;

use App\Models\Post;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AppNotification;

/**
 * SERVICE layer: post reminders (Feature 10).
 *
 * Called every minute by the scheduler (php artisan schedule:work) and also whenever
 * the notification bell polls, so reminders work even without the scheduler running.
 */
class PostReminderService
{
    public function sendDue(?int $sellerId = null): int
    {
        $posts = Post::query()
            ->with('seller')
            ->where('status', Post::STATUS_SCHEDULED)
            ->whereNotNull('remind_before')
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [now()->subHour(), now()->addDay()->addMinute()])
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->get();

        $sent = 0;

        foreach ($posts as $post) {
            if ($post->reminderDueAt()?->isFuture()) {
                continue;
            }

            $post->forceFill(['reminder_sent_at' => now()])->save();

            foreach ($this->recipients($post->seller) as $user) {
                $user->notify(new AppNotification(
                    'Post reminder',
                    "\"{$post->title}\" is scheduled for {$post->scheduled_at->format('d M, h:i A')} on {$post->platformLabel()}. Time to publish it!",
                    route('posts.edit', $post, false),
                    'reminder',
                ));
            }

            $sent++;
        }

        return $sent;
    }

    /** The seller plus every active collaborator who can manage content. */
    private function recipients(User $seller): array
    {
        $collaborators = TeamMember::with('user')
            ->where('seller_id', $seller->id)
            ->where('status', TeamMember::STATUS_ACTIVE)
            ->get()
            ->filter(fn (TeamMember $m) => $m->user && in_array('content', $m->permissions ?? [], true))
            ->map(fn (TeamMember $m) => $m->user)
            ->all();

        return [$seller, ...$collaborators];
    }
}
