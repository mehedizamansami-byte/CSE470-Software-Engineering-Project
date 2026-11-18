<?php

namespace App\Http\Controllers;

use App\Models\BrandKit;
use App\Models\StyleGuide;
use App\Services\Ai\AiService;
use App\Services\AuditLogger;
use App\Services\CreditService;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CONTROLLER layer: auto-generated brand style guide with presets, rules and PDF export (Feature 9).
 */
class StyleGuideController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->owner($request);

        return view('style-guides.index', [
            'kits' => $owner->brandKits()->with('styleGuide')->orderByDesc('is_active')->latest('id')->get(),
            'cost' => config('brandbondhu.credit_costs.style_guide'),
        ]);
    }

    /** Generate (or regenerate) the guide for a kit. */
    public function generate(Request $request, BrandKit $kit, AiService $ai, CreditService $credits): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);
        $owner = $this->owner($request);

        $preset = $request->validate([
            'preset' => ['nullable', Rule::in(array_keys(StyleGuide::PRESETS))],
        ])['preset'] ?? $kit->styleGuide?->preset ?? 'modern';

        $credits->ensure($owner, 'style_guide');
        $guide = $ai->styleGuide($kit->toAiArray(), $preset);

        DB::transaction(function () use ($owner, $credits, $kit, $guide, $preset) {
            $credits->charge($owner, 'style_guide', "Style guide generated for \"{$kit->name}\"");

            $owner->styleGuides()->updateOrCreate(['brand_kit_id' => $kit->id], [
                'title' => $guide['title'],
                'preset' => $preset,
                'sections' => $guide['sections'],
                'rules' => $guide['rules'],
            ]);
        });

        AuditLogger::log($owner, 'style_guide.generated', "Generated the style guide for \"{$kit->name}\"");

        return redirect()->route('style-guides.show', $kit)
            ->with('success', 'Style guide generated. '.config('brandbondhu.credit_costs.style_guide').' credits were used.');
    }

    public function show(Request $request, BrandKit $kit): View|RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);

        if (! $kit->styleGuide) {
            return redirect()->route('style-guides.index')->with('error', 'Generate a style guide for this kit first.');
        }

        return view('style-guides.show', ['kit' => $kit, 'guide' => $kit->styleGuide]);
    }

    /** Save layout preset, visible sections and customised rules. */
    public function update(Request $request, BrandKit $kit): RedirectResponse
    {
        $this->ensureWorkspace($request, $kit);
        $guide = $kit->styleGuide;
        abort_unless($guide, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'preset' => ['required', Rule::in(array_keys(StyleGuide::PRESETS))],
            'sections' => ['nullable', 'array'],
            'sections.*' => [Rule::in(array_keys(StyleGuide::SECTIONS))],
            'overview' => ['nullable', 'string', 'max:1500'],
            'logo_rules' => ['nullable', 'string', 'max:2000'],
            'voice_do' => ['nullable', 'string', 'max:2000'],
            'voice_dont' => ['nullable', 'string', 'max:2000'],
            'imagery' => ['nullable', 'string', 'max:2000'],
            'social' => ['nullable', 'string', 'max:2000'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['nullable', 'string', 'max:200'],
            'typography.h1' => ['required', 'integer', 'between:16,72'],
            'typography.h2' => ['required', 'integer', 'between:14,56'],
            'typography.body' => ['required', 'integer', 'between:10,24'],
            'typography.small' => ['required', 'integer', 'between:8,18'],
        ]);

        $lines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($l) => trim($l))->filter()->values()->all();

        $rules = $guide->rules;
        $rules['overview'] = trim((string) ($data['overview'] ?? ''));
        $rules['logo'] = $lines($data['logo_rules'] ?? '');
        $rules['voice_do'] = $lines($data['voice_do'] ?? '');
        $rules['voice_dont'] = $lines($data['voice_dont'] ?? '');
        $rules['imagery'] = $lines($data['imagery'] ?? '');
        $rules['social'] = $lines($data['social'] ?? '');
        $rules['typography'] = array_map('intval', $data['typography']);
        $rules['colors'] = array_intersect_key(
            array_map(fn ($v) => trim((string) $v), ($data['colors'] ?? []) + ($rules['colors'] ?? [])),
            array_flip(BrandKit::COLOR_ROLES),
        );

        $chosen = $data['sections'] ?? [];
        $sections = collect(array_keys(StyleGuide::SECTIONS))->mapWithKeys(fn ($s) => [$s => in_array($s, $chosen, true)])->all();

        $guide->update([
            'title' => $data['title'],
            'preset' => $data['preset'],
            'sections' => $sections,
            'rules' => $rules,
        ]);

        AuditLogger::log($this->owner($request), 'style_guide.updated', "Customised the style guide for \"{$kit->name}\" ({$data['preset']} preset)");

        return back()->with('success', 'Style guide saved.');
    }

    public function pdf(Request $request, BrandKit $kit, PdfService $pdf): Response
    {
        $this->ensureWorkspace($request, $kit);
        abort_unless($kit->styleGuide, 404);

        AuditLogger::log($this->owner($request), 'style_guide.exported', "Exported the style guide for \"{$kit->name}\" as PDF");

        return $pdf->download('pdf.style-guide', ['kit' => $kit, 'guide' => $kit->styleGuide], Str::slug($kit->business_name).'-style-guide.pdf');
    }
}
