<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: shop setup for sellers (Feature 3).
 */
class ShopController extends Controller
{
    public function edit(Request $request): View
    {
        $owner = $this->owner($request);

        return view('profile.shop', [
            'shop' => $owner->shop ?? new Shop(['name' => $owner->name.'\'s Shop']),
            'categories' => Shop::CATEGORIES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $owner = $this->owner($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(Shop::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:60'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'phone.regex' => 'Enter an 11-digit Bangladeshi mobile number, for example 01712345678.',
        ]);

        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'uploads');
        }

        $owner->shop()->updateOrCreate([], $data);

        AuditLogger::log($owner, 'shop.updated', 'Updated the shop profile');

        return back()->with('success', 'Shop profile saved.');
    }
}
