{{-- Shared by posts/create and posts/edit. --}}
<div class="grid grid-main">
    <div class="card">
        <div class="field">
            <label for="title">Title <span class="muted">(only you see this)</span></label>
            <input id="title" type="text" name="title" value="{{ old('title', $post->title) }}" required placeholder="Eid panjabi launch">
        </div>
        <div class="field">
            <label for="caption">Caption</label>
            <textarea id="caption" name="caption" required style="min-height:150px" placeholder="Write your caption, or generate one with AI on the right.">{{ old('caption', $post->caption) }}</textarea>
        </div>
        <div class="field">
            <label for="hashtags">Hashtags</label>
            <input id="hashtags" type="text" name="hashtags" value="{{ old('hashtags', $post->hashtags) }}" placeholder="#EidCollection #OnlineShoppingBD">
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="platform">Platform</label>
                <select id="platform" name="platform">
                    @foreach (\App\Models\Post::PLATFORMS as $key => $label) <option value="{{ $key }}" @selected(old('platform', $post->platform) === $key)>{{ $label }}</option> @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" data-post-status>
                    <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                    <option value="scheduled" @selected(old('status', $post->status) === 'scheduled')>Scheduled</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
            </div>
        </div>
        <div class="form-grid" data-schedule-fields>
            <div class="field">
                <label for="scheduled_at">Post on</label>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}">
                <p class="help">Bangladesh time. Evenings (7 to 10 PM) usually get the most reach.</p>
            </div>
            <div class="field">
                <label for="remind_before">Reminder</label>
                <select id="remind_before" name="remind_before">
                    <option value="">No reminder</option>
                    @foreach (\App\Models\Post::REMINDERS as $minutes => $label) <option value="{{ $minutes }}" @selected((string) old('remind_before', $post->remind_before) === (string) $minutes)>{{ $label }}</option> @endforeach
                </select>
                <p class="help">You get an in-app notification so you remember to post.</p>
            </div>
        </div>
        <div class="field">
            <label for="image">Image <span class="muted">(optional)</span></label>
            <input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp">
            @if ($post->imageUrl())
                <div class="row mt"><img class="post-thumb" src="{{ $post->imageUrl() }}" alt=""><label class="check"><input type="checkbox" name="remove_image" value="1"> Remove image</label></div>
            @endif
        </div>
        <div class="actions">
            <button class="btn" type="submit">{{ $post->exists ? 'Save changes' : 'Save post' }}</button>
            <a class="btn btn-ghost" href="{{ route('posts.index') }}">Cancel</a>
        </div>
    </div>

    <div class="card" data-caption-generator data-url="{{ route('captions.generate') }}">
        <div class="card-head">
            <h2>✨ AI caption writer</h2>
            <span class="badge badge-blue">{{ $captionCost }} credit · <span data-credits>{{ $credits }}</span> left</span>
        </div>
        @if ($activeKit)
            <p class="small muted">Using your active brand kit <strong>{{ $activeKit->name }}</strong> ({{ $activeKit->personality }} voice).</p>
        @else
            <p class="small muted">Tip: create a brand kit so captions match your brand name and voice.</p>
        @endif
        <div class="field"><label for="ai_topic">What is the post about?</label><input id="ai_topic" type="text" name="ai_topic" value="{{ old('title', $post->title) }}" placeholder="New Eid panjabi collection"></div>
        <div class="field"><label for="ai_details">Details <span class="muted">(price, offer, sizes)</span></label><input id="ai_details" type="text" name="ai_details" placeholder="Starting from 1,250 taka, free delivery in Dhaka"></div>
        <div class="form-grid">
            <div class="field">
                <label for="ai_tone">Tone</label>
                <select id="ai_tone" name="ai_tone">@foreach ($tones as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach</select>
            </div>
            <div class="field">
                <label for="ai_language">Language</label>
                <select id="ai_language" name="ai_language"><option value="en">English</option><option value="bn">বাংলা (Bangla)</option></select>
            </div>
        </div>
        <label class="check"><input type="checkbox" name="ai_emojis" value="1" checked> Use emojis</label>
        <div class="actions"><button type="button" class="btn" data-generate style="width:100%"><x-icon name="sparkles"/> Generate captions</button></div>
        <div data-results></div>
    </div>
</div>
