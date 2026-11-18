<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\AppNotification;
use App\Services\Ai\LocalAiEngine;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: content manager. Create, edit, delete and schedule posts with reminders (Feature 10).
 */
class PostController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->owner($request);
        $tab = in_array($request->query('tab'), ['scheduled', 'draft', 'published'], true) ? $request->query('tab') : 'all';

        $posts = $owner->posts()->with('creator')
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->orderByRaw('scheduled_at is null')
            ->orderBy('scheduled_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('posts.index', [
            'posts' => $posts,
            'tab' => $tab,
            'counts' => [
                'all' => $owner->posts()->count(),
                'scheduled' => $owner->posts()->where('status', Post::STATUS_SCHEDULED)->count(),
                'draft' => $owner->posts()->where('status', Post::STATUS_DRAFT)->count(),
                'published' => $owner->posts()->where('status', Post::STATUS_PUBLISHED)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('posts.create', $this->formData($request, new Post([
            'platform' => 'facebook',
            'status' => Post::STATUS_SCHEDULED,
            'remind_before' => 30,
            'scheduled_at' => now()->addDay()->setTime(19, 0),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->owner($request);
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $post = $owner->posts()->create($data);

        AuditLogger::log($owner, 'post.created', "Created {$post->status} post \"{$post->title}\"");

        if ($post->status === Post::STATUS_SCHEDULED) {
            $request->user()->notify(new AppNotification(
                'Post scheduled',
                "\"{$post->title}\" is scheduled for {$post->scheduled_at->format('d M Y, h:i A')}.",
                route('posts.edit', $post, false),
                'success',
            ));
        }

        return redirect()->route('posts.index')->with('success', 'Post saved.');
    }

    public function edit(Request $request, Post $post): View
    {
        $this->ensureWorkspace($request, $post);

        return view('posts.edit', $this->formData($request, $post));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->ensureWorkspace($request, $post);
        $data = $this->validated($request, $post);

        // A new time or reminder means the reminder must be sent again.
        $oldTime = $post->scheduled_at?->format('Y-m-d H:i');
        $newTime = $data['scheduled_at']?->format('Y-m-d H:i');
        if ($oldTime !== $newTime || $data['remind_before'] !== $post->remind_before) {
            $data['reminder_sent_at'] = null;
        }

        $post->update($data);

        AuditLogger::log($this->owner($request), 'post.updated', "Edited post \"{$post->title}\"");

        return redirect()->route('posts.index')->with('success', 'Post updated.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->ensureWorkspace($request, $post);
        $title = $post->title;
        $post->delete();

        AuditLogger::log($this->owner($request), 'post.deleted', "Deleted post \"{$title}\"");

        return redirect()->route('posts.index')->with('success', "Post \"{$title}\" deleted.");
    }

    /** Publishing to Facebook is out of scope, so the seller confirms it was posted. */
    public function publish(Request $request, Post $post): RedirectResponse
    {
        $this->ensureWorkspace($request, $post);

        $post->update(['status' => Post::STATUS_PUBLISHED, 'published_at' => now()]);

        AuditLogger::log($this->owner($request), 'post.published', "Marked post \"{$post->title}\" as published");

        return back()->with('success', "\"{$post->title}\" marked as published.");
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['required', 'string', 'max:3000'],
            'hashtags' => ['nullable', 'string', 'max:500'],
            'platform' => ['required', Rule::in(array_keys(Post::PLATFORMS))],
            'status' => ['required', Rule::in([Post::STATUS_DRAFT, Post::STATUS_SCHEDULED, Post::STATUS_PUBLISHED])],
            'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date', Rule::when($request->input('status') === Post::STATUS_SCHEDULED, ['after:now'])],
            'remind_before' => ['nullable', Rule::in(array_keys(Post::REMINDERS))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'scheduled_at.required_if' => 'Pick a date and time for a scheduled post.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
        ]);

        $data['scheduled_at'] = filled($data['scheduled_at'] ?? null) ? \Illuminate\Support\Carbon::parse($data['scheduled_at']) : null;
        $data['remind_before'] = filled($data['remind_before'] ?? null) ? (int) $data['remind_before'] : null;

        if ($data['status'] === Post::STATUS_PUBLISHED && ! $post?->published_at) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('posts', 'uploads');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        unset($data['image'], $data['remove_image']);

        return $data;
    }

    private function formData(Request $request, Post $post): array
    {
        $owner = $this->owner($request);
        $kit = $owner->brandKits()->where('is_active', true)->first();

        return [
            'post' => $post,
            'activeKit' => $kit,
            'tones' => LocalAiEngine::TONES,
            'captionCost' => config('brandbondhu.credit_costs.caption'),
            'credits' => $owner->ai_credits,
        ];
    }
}
