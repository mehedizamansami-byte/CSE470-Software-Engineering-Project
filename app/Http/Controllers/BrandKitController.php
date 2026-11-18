<?php

namespace App\Http\Controllers;

use App\Models\BrandKit;
use App\Models\Shop;
use App\Notifications\AppNotification;
use App\Services\Ai\AiService;
use App\Services\Ai\LocalAiEngine;
use App\Services\AuditLogger;
use App\Services\CreditService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: AI brand identity kit generator (Feature 8).
 */
class BrandKitController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->owner($request);

        return view('brand-kits.index', [
            'kits' => $owner->brandKits()->with('styleGuide')->orderByDesc('is_active')->latest('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $owner = $this->owner($request);

        return view('brand-kits.create', [
            'shop' => $owner->shop,
            'industries' => Shop::CATEGORIES,
            'personalities' => LocalAiEngine::PERSONALITIES,
            'cost' => config('brandbondhu.credit_costs.brand_kit'),
            'credits' => $owner->ai_credits,
        ]);
    }

    public function store(Request $request, AiService $ai, CreditService $credits, SubscriptionService $subscriptions): RedirectResponse
    {
        $owner = $this->owner($request);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'business_name' => ['required', 'string', 'max:80'],
            'industry' => ['required', Rule::in(Shop::CATEGORIES)],
            'audience' => ['nullable', 'string', 'max:120'],
            'personality' => ['required', Rule::in(array_keys(LocalAiEngine::PERSONALITIES))],
            'preferred_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $limit = $subscriptions->current($owner)->plan->brand_kit_limit;

        if ($limit !== null && $owner->brandKits()->count() >= $limit) {
            return back()->withInput()->with('error', "Your plan allows {$limit} brand kit(s). Delete an old kit or upgrade your plan.");
        }

        $credits->ensure($owner, 'brand_kit');
        $generated = $ai->brandKit($data);

        $kit = DB::transaction(function () use ($owner, $credits, $request, $data, $generated) {
            $credits->charge($owner, 'brand_kit', "Brand kit generated for {$data['business_name']}");
            $isFirst = ! $owner->brandKits()->exists();

            return $owner->brandKits()->create([
                'created_by' => $request->user()->id,
                'name' => ($data['name'] ?? null) ?: $data['business_name'].' brand kit',
                'business_name' => $data['business_name'],
                'industry' => $data['industry'],
                'audience' => $data['audience'] ?? null,
                'personality' => $data['personality'],
                'is_active' => $isFirst,
            ] + Arr::only($generated, ['tagline', 'colors', 'typography', 'logo', 'voice', 'mission', 'keywords']));
        });

        AuditLogger::log($owner, 'brand_kit.created', "Generated brand kit \"{$kit->name}\"");

        $request->user()->notify(new AppNotification(
            'Brand kit ready',
            "Your AI brand kit \"{$kit->name}\" is ready.",
            route('brand-kits.show', $kit, false),
            'success',
        ));

        return redirect()->route('brand-kits.show', $kit)
            ->with('success', 'Your AI brand kit is ready! '.config('brandbondhu.credit_costs.brand_kit').' credits were used.');
    }

    public function show(Request $request, BrandKit $kit): View
    {
        $this->ensureWorkspace($request, $kit);

        return view('brand-kits.show', [
            'kit' => $kit->load('styleGuide', 'creator'),
            'assetCost' => config('brandbondhu.credit_costs.brand_asset'),
        ]);
    }

    /** Regenerate one asset (tagline, colours, fonts, logo or voice). */
    public function regenerate(Request $request, BrandKit $kit, AiService $ai, CreditService $credits): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);
        $owner = $this->owner($request);

        $asset = $request->validate([
            'asset' => ['required', Rule::in(array_keys(BrandKit::ASSETS))],
        ])['asset'];

        $credits->ensure($owner, 'brand_asset');
        $result = $ai->brandAsset($asset, $kit->toAiArray());

        DB::transaction(function () use ($owner, $credits, $kit, $asset, $result) {
            $credits->charge($owner, 'brand_asset', BrandKit::ASSETS[$asset]." regenerated for \"{$kit->name}\"");
            $kit->update(Arr::only($result, ['tagline', 'colors', 'typography', 'logo', 'voice', 'mission', 'keywords']));
        });

        AuditLogger::log($owner, 'brand_kit.regenerated', "Regenerated the ".strtolower(BrandKit::ASSETS[$asset])." of \"{$kit->name}\"");

        return back()->with('success', BrandKit::ASSETS[$asset].' regenerated. 1 credit was used.');
    }

    public function activate(Request $request, BrandKit $kit): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);
        $owner = $this->owner($request);

        DB::transaction(function () use ($owner, $kit) {
            $owner->brandKits()->update(['is_active' => false]);
            $kit->update(['is_active' => true]);
        });

        AuditLogger::log($owner, 'brand_kit.activated', "Set \"{$kit->name}\" as the active brand kit");

        return back()->with('success', "\"{$kit->name}\" is now your active brand kit. New captions will use it.");
    }

    public function edit(Request $request, BrandKit $kit): View
    {
        $this->ensureWorkspace($request, $kit);

        return view('brand-kits.edit', [
            'kit' => $kit,
            'industries' => Shop::CATEGORIES,
            'personalities' => LocalAiEngine::PERSONALITIES,
        ]);
    }

    public function update(Request $request, BrandKit $kit): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);

        $hex = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'business_name' => ['required', 'string', 'max:80'],
            'industry' => ['required', Rule::in(Shop::CATEGORIES)],
            'audience' => ['nullable', 'string', 'max:120'],
            'personality' => ['required', Rule::in(array_keys(LocalAiEngine::PERSONALITIES))],
            'tagline' => ['nullable', 'string', 'max:160'],
            'colors.primary' => $hex,
            'colors.secondary' => $hex,
            'colors.accent' => $hex,
            'colors.neutral' => $hex,
            'colors.background' => $hex,
            'typography.heading' => ['required', 'string', 'max:60'],
            'typography.body' => ['required', 'string', 'max:60'],
            'logo.initials' => ['required', 'string', 'max:2'],
            'logo.shape' => ['required', Rule::in(['circle', 'rounded', 'hexagon', 'diamond', 'shield'])],
            'logo.style' => ['required', Rule::in(['solid', 'outline', 'split'])],
            'voice' => ['nullable', 'string', 'max:1000'],
            'mission' => ['nullable', 'string', 'max:600'],
            'keywords' => ['nullable', 'string', 'max:200'],
        ]);

        $data['colors'] = array_map('strtoupper', $data['colors']);
        $data['logo']['initials'] = mb_strtoupper($data['logo']['initials']);
        $data['keywords'] = collect(explode(',', $data['keywords'] ?? ''))->map(fn ($k) => trim($k))->filter()->take(8)->values()->all();

        $kit->update($data);

        AuditLogger::log($this->owner($request), 'brand_kit.updated', "Edited brand kit \"{$kit->name}\"");

        return redirect()->route('brand-kits.show', $kit)->with('success', 'Brand kit saved.');
    }

    public function destroy(Request $request, BrandKit $kit): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);
        $owner = $this->owner($request);
        $wasActive = $kit->is_active;
        $name = $kit->name;

        $kit->delete();

        if ($wasActive) {
            $owner->brandKits()->latest('id')->first()?->update(['is_active' => true]);
        }

        AuditLogger::log($owner, 'brand_kit.deleted', "Deleted brand kit \"{$name}\"");

        return redirect()->route('brand-kits.index')->with('success', "Brand kit \"{$name}\" deleted.");
    }
}
