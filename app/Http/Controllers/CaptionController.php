<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiService;
use App\Services\Ai\LocalAiEngine;
use App\Services\AuditLogger;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CONTROLLER layer: AI caption generation for the content manager (Feature 10).
 * Returns JSON so the post form can show the captions without reloading.
 */
class CaptionController extends Controller
{
    public function generate(Request $request, AiService $ai, CreditService $credits): JsonResponse
    {
        $owner = $this->owner($request);

        $data = $request->validate([
            'topic' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:300'],
            'tone' => ['required', Rule::in(array_keys(LocalAiEngine::TONES))],
            'language' => ['required', Rule::in(['en', 'bn'])],
            'platform' => ['required', Rule::in(['facebook', 'instagram', 'both'])],
            'emojis' => ['nullable', 'boolean'],
        ]);

        // Use the active brand kit so captions match the brand.
        $kit = $owner->brandKits()->where('is_active', true)->first();
        $data['brand'] = $kit?->business_name ?? $owner->shop?->name ?? $owner->name;
        $data['tagline'] = $kit?->tagline;
        $data['industry'] = $kit?->industry ?? $owner->shop?->category;
        $data['voice'] = $kit?->voice;
        $data['emojis'] = $request->boolean('emojis', true);

        $credits->ensure($owner, 'caption');
        $result = $ai->captions($data);
        $credits->charge($owner, 'caption', 'Captions for "'.Str::limit($data['topic'], 60).'"');

        AuditLogger::log($owner, 'post.captions', 'Generated AI captions for "'.Str::limit($data['topic'], 60).'"');

        return response()->json([
            'captions' => $result['captions'],
            'hashtags' => $result['hashtags'],
            'credits' => $owner->ai_credits,
        ]);
    }
}
