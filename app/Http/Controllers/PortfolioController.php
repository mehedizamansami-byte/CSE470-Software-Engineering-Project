<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER layer: portfolio setup for designers, plus the public designer profile (Feature 3).
 */
class PortfolioController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->designerProfile;

        return view('profile.portfolio', [
            'profile' => $profile,
            'items' => $profile ? $profile->items()->get() : collect(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'headline' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1500'],
            'skills' => ['nullable', 'string', 'max:300'],
            'starting_price' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'location' => ['nullable', 'string', 'max:80'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['skills'] = collect(explode(',', $data['skills'] ?? ''))
            ->map(fn ($s) => trim($s))->filter()->unique()->take(15)->values()->all();
        $data['is_available'] = $request->boolean('is_available');
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'uploads');
        }

        $request->user()->designerProfile()->updateOrCreate([], $data);

        return back()->with('success', 'Portfolio profile saved.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $profile = $request->user()->designerProfile;

        if (! $profile) {
            return back()->with('error', 'Save your profile details first, then add portfolio items.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:600'],
            'link' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('portfolio', 'uploads');
        }

        $profile->items()->create($data);

        return back()->with('success', 'Portfolio item added.');
    }

    public function destroyItem(Request $request, PortfolioItem $item): RedirectResponse
    {
        abort_unless((int) $item->designer_profile_id === (int) $request->user()->designerProfile?->id, 404);

        $item->delete();

        return back()->with('success', 'Portfolio item removed.');
    }

    /** Public portfolio page that sellers can open. */
    public function show(User $designer): View
    {
        $profile = $designer->designerProfile;

        abort_unless($designer->hasRole(User::ROLE_DESIGNER) && $profile, 404);

        return view('profile.designer-show', [
            'designer' => $designer,
            'profile' => $profile,
            'items' => $profile->items()->get(),
        ]);
    }
}
