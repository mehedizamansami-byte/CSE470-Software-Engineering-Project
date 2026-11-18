<?php

namespace App\Services\Ai;

use App\Support\Color;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SERVICE layer: the single entry point for AI features.
 *
 * Controllers never call the AI model directly. With AI_DRIVER=http this class sends the
 * request to the FastAPI AI service (AI_SERVICE_URL); with AI_DRIVER=local (the default)
 * or when that service is down, it uses the offline LocalAiEngine.
 */
class AiService
{
    public function __construct(private LocalAiEngine $local) {}

    public function driver(): string
    {
        return config('services.ai.driver', 'local') === 'http' && config('services.ai.url') ? 'http' : 'local';
    }

    public function brandKit(array $input): array
    {
        $result = $this->ask('brand-kit', $input, fn () => $this->local->brandKit($input));
        $result['colors'] = $this->safePalette($result['colors'] ?? []);

        return $result;
    }

    public function brandAsset(string $asset, array $kit): array
    {
        $result = $this->ask('brand-asset', ['asset' => $asset, 'kit' => $kit], fn () => $this->local->brandAsset($asset, $kit));

        if (isset($result['colors'])) {
            $result['colors'] = $this->safePalette($result['colors']);
        }

        return $result;
    }

    public function styleGuide(array $kit, string $preset): array
    {
        return $this->ask('style-guide', ['kit' => $kit, 'preset' => $preset], fn () => $this->local->styleGuide($kit, $preset));
    }

    /** @return array{captions: list<string>, hashtags: list<string>} */
    public function captions(array $input): array
    {
        return $this->ask('captions', $input, fn () => $this->local->captions($input));
    }

    /**
     * Ask the remote AI service, falling back to the local engine.
     * The local answer also defines the expected shape, so a partial remote answer is safe.
     */
    private function ask(string $endpoint, array $payload, Closure $local): array
    {
        $fallback = $local();

        if ($this->driver() !== 'http') {
            return $fallback;
        }

        try {
            $response = Http::timeout((int) config('services.ai.timeout', 20))
                ->acceptJson()
                ->post(rtrim(config('services.ai.url'), '/').'/'.$endpoint, $payload);

            if ($response->successful() && is_array($response->json())) {
                return $this->overlay($fallback, $response->json());
            }

            Log::warning("AI service returned HTTP {$response->status()} for {$endpoint}; using local engine.");
        } catch (\Throwable $e) {
            Log::warning("AI service unreachable for {$endpoint}: {$e->getMessage()}; using local engine.");
        }

        return $fallback;
    }

    /** Copy values from $remote into $base, only where the type matches. */
    private function overlay(array $base, array $remote): array
    {
        foreach ($base as $key => $value) {
            if (! array_key_exists($key, $remote)) {
                continue;
            }

            $incoming = $remote[$key];

            if (is_array($value) && is_array($incoming)) {
                if (array_is_list($value)) {
                    $strings = array_values(array_filter($incoming, 'is_string'));
                    $base[$key] = $strings ?: $value;
                } else {
                    $base[$key] = $this->overlay($value, $incoming);
                }
            } elseif (is_string($value) && is_string($incoming) && trim($incoming) !== '') {
                $base[$key] = trim($incoming);
            } elseif ((is_int($value) || is_bool($value)) && gettype($incoming) === gettype($value)) {
                $base[$key] = $incoming;
            }
        }

        return $base;
    }

    private function safePalette(array $colors): array
    {
        $defaults = ['primary' => '#4F46E5', 'secondary' => '#A5B4FC', 'accent' => '#F59E0B', 'neutral' => '#1F2937', 'background' => '#F9FAFB'];

        foreach ($defaults as $role => $default) {
            $colors[$role] = Color::isHex($colors[$role] ?? null) ? strtoupper($colors[$role]) : $default;
        }

        return array_intersect_key($colors, $defaults);
    }
}
