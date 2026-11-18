<?php

namespace App\Services\Ai;

use App\Support\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Offline "AI" used when no AI service is configured (AI_DRIVER=local).
 *
 * It builds brand kits, style guides and captions from curated design rules, so the
 * whole app works on a laptop with no internet and no API key. When the FastAPI AI
 * service is available (AI_DRIVER=http), AiService asks it first and uses this as a fallback.
 */
class LocalAiEngine
{
    public const PERSONALITIES = [
        'friendly' => 'Friendly and warm',
        'premium' => 'Premium and elegant',
        'playful' => 'Playful and fun',
        'minimal' => 'Minimal and modern',
        'bold' => 'Bold and energetic',
        'traditional' => 'Traditional and deshi',
        'natural' => 'Natural and organic',
    ];

    public const TONES = [
        'friendly' => 'Friendly',
        'professional' => 'Professional',
        'funny' => 'Funny',
        'urgent' => 'Urgent / offer',
        'festive' => 'Festive',
    ];

    private const PALETTES = [
        'friendly' => [['#FF7A59', '#FFC145', '#2EC4B6', '#2D3047', '#FFF8F0'], ['#F28C38', '#FCD581', '#4DA1A9', '#2E3440', '#FFFBF5'], ['#E76F51', '#F4A261', '#2A9D8F', '#264653', '#FDF8F3']],
        'premium' => [['#1B1B2F', '#C9A227', '#8C7853', '#2E2E3A', '#FAF7F0'], ['#2C2A4A', '#B08D57', '#D9C5B2', '#1E1E24', '#F8F5F0'], ['#0F3D3E', '#C5A880', '#E2DCC8', '#1C1C1C', '#FAF8F3']],
        'playful' => [['#FF4F81', '#FFB400', '#00B2CA', '#3D348B', '#FFF5FA'], ['#7B2FF7', '#FF6B6B', '#FFD93D', '#2B2D42', '#FBF7FF'], ['#00A6ED', '#FF5D8F', '#FFE45E', '#1D3557', '#F5FBFF']],
        'minimal' => [['#111827', '#6B7280', '#10B981', '#374151', '#F9FAFB'], ['#1F2937', '#9CA3AF', '#3B82F6', '#4B5563', '#FFFFFF'], ['#18181B', '#A1A1AA', '#F43F5E', '#3F3F46', '#FAFAFA']],
        'bold' => [['#E63946', '#1D3557', '#F1C40F', '#0B132B', '#F1FAEE'], ['#FF3D00', '#212121', '#FFC400', '#121212', '#FFF8F2'], ['#6A00F4', '#00B8D4', '#FF006E', '#14002E', '#F7F3FF']],
        'traditional' => [['#006A4E', '#F42A41', '#E9B44C', '#3B2F2F', '#FFF9EE'], ['#8B1E3F', '#D4A017', '#1F5F4A', '#3A2A1E', '#FFF8EC'], ['#B5651D', '#2F4F4F', '#F2C14E', '#3B2416', '#FDF6E9']],
        'natural' => [['#2F6B3D', '#A3B18A', '#DDA15E', '#283618', '#FEFAE0'], ['#606C38', '#BC6C25', '#E9EDC9', '#283618', '#FAFAF2'], ['#386641', '#A7C957', '#BC4749', '#1B2A1E', '#F2E8CF']],
    ];

    private const FONT_PAIRS = [
        'friendly' => [['Poppins', 'Nunito'], ['Quicksand', 'Open Sans'], ['Baloo Da 2', 'Hind Siliguri']],
        'premium' => [['Playfair Display', 'Lato'], ['Cormorant Garamond', 'Montserrat'], ['DM Serif Display', 'DM Sans']],
        'playful' => [['Fredoka', 'Nunito'], ['Baloo Da 2', 'Poppins'], ['Comfortaa', 'Open Sans']],
        'minimal' => [['Inter', 'Inter'], ['Manrope', 'Inter'], ['Space Grotesk', 'IBM Plex Sans']],
        'bold' => [['Montserrat', 'Open Sans'], ['Oswald', 'Roboto'], ['Bebas Neue', 'Poppins']],
        'traditional' => [['Hind Siliguri', 'Noto Sans Bengali'], ['Tiro Bangla', 'Hind Siliguri'], ['Merriweather', 'Source Sans 3']],
        'natural' => [['Lora', 'Nunito Sans'], ['Josefin Sans', 'Lato'], ['Merriweather', 'Open Sans']],
    ];

    private const LOGO_SHAPES = ['circle', 'rounded', 'hexagon', 'diamond', 'shield'];

    private const LOGO_STYLES = ['solid', 'outline', 'split'];

    private const TAGLINES = [
        'friendly' => ['Made with love, delivered with a smile', 'Your happy place for {noun}', 'Good {noun}, good vibes, every day', 'From our home to yours'],
        'premium' => ['Crafted for those who notice the details', 'Timeless {noun}, thoughtfully made', 'Elegance in every detail', 'Where quality speaks softly'],
        'playful' => ['Fun {noun} for fun people!', 'Add a little joy to your cart', 'Life is short, shop happy', 'Colourful {noun}, cheerful prices'],
        'minimal' => ['Simply better {noun}', 'Less noise. More quality.', 'Clean design, honest prices', 'Everything you need, nothing you do not'],
        'bold' => ['Stand out. Stay bold.', 'Big style, bold choices', '{Noun} that turns heads', 'Made to be noticed'],
        'traditional' => ['The taste of tradition, delivered', 'Proudly made in Bangladesh', 'Deshi roots, modern style', 'Heritage you can hold'],
        'natural' => ['Pure, natural and honest', 'Naturally good {noun}', 'Grown with care, made with love', 'Kind to you, kind to nature'],
    ];

    private const VOICES = [
        'friendly' => 'Warm, upbeat and conversational. Talk like a helpful friend: simple words, short sentences and a positive tone. Emojis are welcome in moderation.',
        'premium' => 'Calm, confident and refined. Use polished language, avoid slang and let the quality speak for itself. Fewer words, chosen carefully.',
        'playful' => 'Cheerful, witty and full of energy. Use fun wordplay, exclamation marks and emojis, but keep the message clear.',
        'minimal' => 'Clear, honest and to the point. Short sentences, no hype and no unnecessary words. Facts first.',
        'bold' => 'Confident, direct and energetic. Use strong verbs and short punchy lines that make people act now.',
        'traditional' => 'Respectful, warm and rooted in Bangladeshi culture. Celebrate heritage and craftsmanship, and mix Bangla phrases naturally.',
        'natural' => 'Gentle, honest and caring. Talk about ingredients, materials and care for people and nature. Never exaggerate.',
    ];

    private const KEYWORDS = [
        'friendly' => ['warm', 'helpful', 'caring', 'cheerful', 'trusted', 'approachable'],
        'premium' => ['elegant', 'refined', 'exclusive', 'timeless', 'crafted', 'luxurious'],
        'playful' => ['fun', 'colourful', 'joyful', 'creative', 'youthful', 'surprising'],
        'minimal' => ['simple', 'clean', 'honest', 'modern', 'focused', 'smart'],
        'bold' => ['confident', 'daring', 'energetic', 'fearless', 'striking', 'fast'],
        'traditional' => ['authentic', 'heritage', 'handmade', 'deshi', 'trusted', 'proud'],
        'natural' => ['pure', 'organic', 'gentle', 'fresh', 'sustainable', 'honest'],
    ];

    private const NOUNS = [
        'Fashion & Clothing' => 'fashion',
        'Food & Bakery' => 'food',
        'Beauty & Cosmetics' => 'beauty',
        'Handicrafts' => 'handicrafts',
        'Electronics & Gadgets' => 'gadgets',
        'Home & Living' => 'home decor',
        'Jewellery & Accessories' => 'jewellery',
        'Books & Stationery' => 'books',
        'Health & Wellness' => 'wellness',
    ];

    // ---------------------------------------------------------------
    // Feature 8: brand identity kit
    // ---------------------------------------------------------------

    public function brandKit(array $input): array
    {
        $personality = $this->personality($input['personality'] ?? null);
        $preferred = $input['preferred_color'] ?? null;

        return [
            'tagline' => $this->tagline($personality, $input['industry'] ?? ''),
            'colors' => Color::isHex($preferred) ? Color::paletteFrom($preferred) : $this->palette($personality),
            'typography' => $this->typography($personality),
            'logo' => $this->logo($input['business_name'] ?? 'Brand'),
            'voice' => self::VOICES[$personality],
            'mission' => $this->mission($input, $personality),
            'keywords' => $this->keywords($personality),
        ];
    }

    /** Regenerate one part of a kit. Tries to return something different from the current value. */
    public function brandAsset(string $asset, array $kit): array
    {
        $personality = $this->personality($kit['personality'] ?? null);

        return match ($asset) {
            'tagline' => ['tagline' => $this->tagline($personality, $kit['industry'] ?? '', $kit['tagline'] ?? null)],
            'colors' => ['colors' => $this->palette($personality, $kit['colors'] ?? null)],
            'typography' => ['typography' => $this->typography($personality, $kit['typography'] ?? null)],
            'logo' => ['logo' => $this->logo($kit['business_name'] ?? 'Brand', $kit['logo'] ?? null)],
            default => [
                'voice' => self::VOICES[$personality],
                'mission' => $this->mission($kit, $personality, $kit['mission'] ?? null),
                'keywords' => $this->keywords($personality),
            ],
        };
    }

    // ---------------------------------------------------------------
    // Feature 9: style guide
    // ---------------------------------------------------------------

    public function styleGuide(array $kit, string $preset): array
    {
        $personality = $this->personality($kit['personality'] ?? null);
        $name = $kit['business_name'] ?? 'Our brand';
        $initial = mb_strtoupper(mb_substr($kit['logo']['initials'] ?? $name, 0, 1));
        $noun = $this->noun($kit['industry'] ?? '');

        return [
            'title' => "{$name} Brand Style Guide",
            'sections' => array_fill_keys(['overview', 'logo', 'colors', 'typography', 'voice', 'imagery', 'social'], true),
            'rules' => [
                'overview' => trim(($kit['mission'] ?? '').' '.($kit['voice'] ?? '')),
                'logo' => [
                    "Keep clear space around the logo equal to the height of the letter \"{$initial}\".",
                    'Minimum size: 32 px on screens and 15 mm in print.',
                    'Use the full-colour logo on light backgrounds and the white version on the primary colour.',
                    'Do not stretch, rotate, recolour or add shadows to the logo.',
                    'Always place the logo on a clean, uncluttered area of the design.',
                ],
                'colors' => [
                    'primary' => 'Main brand colour for the logo, buttons and headers (about 60% of a design).',
                    'secondary' => 'Supporting colour for backgrounds, cards and highlights (about 30%).',
                    'accent' => 'Use sparingly for prices, offers and calls to action (about 10%).',
                    'neutral' => 'Body text, icons and thin lines.',
                    'background' => 'Page and post backgrounds.',
                ],
                'typography' => ['h1' => 32, 'h2' => 24, 'body' => 16, 'small' => 13],
                'voice_do' => $this->voiceDos($personality),
                'voice_dont' => $this->voiceDonts($personality),
                'imagery' => [
                    "Use bright, natural light when photographing {$noun}.",
                    'Show real products and real customers whenever possible.',
                    'Keep backgrounds simple, using the background or secondary colour.',
                    'Use the same filter or editing style on every photo so the feed looks consistent.',
                ],
                'social' => [
                    'Put the most important message in the first line of every caption.',
                    'Use 3 to 5 relevant hashtags per post, including the brand hashtag.',
                    'Post 4 to 5 times a week and reply to comments within a few hours.',
                    'Always include the price or a clear call to action, such as "Inbox to order".',
                ],
            ],
            'preset' => $preset,
        ];
    }

    // ---------------------------------------------------------------
    // Feature 10: captions and hashtags
    // ---------------------------------------------------------------

    public function captions(array $input): array
    {
        $topic = trim($input['topic'] ?? 'our new collection');
        $details = trim($input['details'] ?? '');
        $brand = trim($input['brand'] ?? '') ?: 'our shop';
        $tone = array_key_exists($input['tone'] ?? '', self::TONES) ? $input['tone'] : 'friendly';
        $platform = $input['platform'] ?? 'facebook';
        $language = ($input['language'] ?? 'en') === 'bn' ? 'bn' : 'en';
        $emojis = (bool) ($input['emojis'] ?? true);

        $templates = $language === 'bn' ? $this->banglaTemplates() : $this->englishTemplates($tone);
        $cta = $language === 'bn'
            ? Arr::random(['অর্ডার করতে ইনবক্স করুন।', 'কমেন্টে "Interested" লিখুন, আমরা মেসেজ করবো।', 'পেজে মেসেজ দিয়ে এখনই অর্ডার করুন।'])
            : ($platform === 'instagram'
                ? Arr::random(['DM us to order 💌', 'Tap the link in bio to shop.', 'Send us a DM to grab yours!'])
                : Arr::random(['Inbox us to order!', 'Comment "Interested" and we will message you.', 'Order now via Messenger.']));

        $captions = collect($templates)->shuffle()->take(3)->map(function (string $template) use ($topic, $details, $brand, $cta, $emojis, $input) {
            $text = strtr($template, [
                '{topic}' => $topic,
                '{brand}' => $brand,
                '{tagline}' => trim($input['tagline'] ?? '') ?: $brand,
                '{details}' => $details !== '' ? rtrim($details, '.!').'.' : '',
                '{cta}' => $cta,
            ]);
            if (! $emojis) {
                $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u', '', $text);
            }

            return trim(preg_replace('/ {2,}/', ' ', $text));
        })->values()->all();

        return [
            'captions' => $captions,
            'hashtags' => $this->hashtags($topic, $brand, $input['industry'] ?? ''),
        ];
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function personality(?string $value): string
    {
        return array_key_exists((string) $value, self::PERSONALITIES) ? $value : 'friendly';
    }

    private function noun(string $industry): string
    {
        return self::NOUNS[$industry] ?? 'products';
    }

    /** Pick a random option, avoiding the current one when there is a choice. */
    private function pickDifferent(array $options, mixed $current): mixed
    {
        $others = array_values(array_filter($options, fn ($o) => $o !== $current));

        return Arr::random($others ?: $options);
    }

    private function tagline(string $personality, string $industry, ?string $current = null): string
    {
        $noun = $this->noun($industry);
        $options = array_map(
            fn ($t) => str_replace(['{noun}', '{Noun}'], [$noun, Str::ucfirst($noun)], $t),
            self::TAGLINES[$personality],
        );

        return $this->pickDifferent($options, $current);
    }

    private function palette(string $personality, ?array $current = null): array
    {
        $currentList = $current ? array_values(array_map('strtoupper', $current)) : null;
        $chosen = $this->pickDifferent(self::PALETTES[$personality], $currentList);

        return array_combine(['primary', 'secondary', 'accent', 'neutral', 'background'], $chosen);
    }

    private function typography(string $personality, ?array $current = null): array
    {
        $currentPair = $current ? [$current['heading'] ?? null, $current['body'] ?? null] : null;
        [$heading, $body] = $this->pickDifferent(self::FONT_PAIRS[$personality], $currentPair);

        return ['heading' => $heading, 'body' => $body];
    }

    private function logo(string $businessName, ?array $current = null): array
    {
        $words = preg_split('/\s+/u', trim($businessName)) ?: ['B'];
        $initials = implode('', array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2)));

        $shape = $this->pickDifferent(self::LOGO_SHAPES, $current['shape'] ?? null);

        return [
            'initials' => $initials ?: 'B',
            'shape' => $shape,
            'style' => Arr::random(self::LOGO_STYLES),
        ];
    }

    private function keywords(string $personality): array
    {
        return collect(self::KEYWORDS[$personality])->shuffle()->take(5)->values()->all();
    }

    private function mission(array $input, string $personality, ?string $current = null): string
    {
        $audience = trim($input['audience'] ?? '') ?: 'customers across Bangladesh';
        $noun = $this->noun($input['industry'] ?? '');
        [$a, $b] = collect(self::KEYWORDS[$personality])->shuffle()->take(2)->values()->all();

        $options = [
            "To bring {$audience} {$noun} that feel {$a} and {$b}, with honest prices and caring service.",
            "To make {$a}, {$b} {$noun} easy to buy for {$audience}, delivered fast to their door.",
            "To help {$audience} express themselves with {$a} {$noun}, made and sold with {$b} care.",
        ];

        return $this->pickDifferent($options, $current);
    }

    private function voiceDos(string $personality): array
    {
        $base = ['Speak directly to the customer using "you".', 'Mention the price, size or delivery details clearly.', 'Reply to every comment politely and quickly.'];
        $extra = [
            'friendly' => 'Use a warm greeting and an emoji or two.',
            'premium' => 'Focus on quality, materials and craftsmanship.',
            'playful' => 'Have fun with wordplay and seasonal jokes.',
            'minimal' => 'Keep sentences short and factual.',
            'bold' => 'Start with a strong verb, such as "Grab", "Discover" or "Upgrade".',
            'traditional' => 'Celebrate festivals like Pohela Boishakh and Eid with the community.',
            'natural' => 'Explain ingredients and where materials come from.',
        ];

        return array_merge([$extra[$personality]], $base);
    }

    private function voiceDonts(string $personality): array
    {
        $base = ['Do not use all capital letters or too many exclamation marks.', 'Do not argue with customers in public comments.', 'Do not make promises about delivery you cannot keep.'];
        $extra = [
            'friendly' => 'Do not sound cold or robotic.',
            'premium' => 'Do not use slang or heavy discount language.',
            'playful' => 'Do not let jokes hide the important details.',
            'minimal' => 'Do not add hype words like "amazing" to every post.',
            'bold' => 'Do not be aggressive or put down competitors.',
            'traditional' => 'Do not use cultural symbols carelessly.',
            'natural' => 'Do not make health claims you cannot prove.',
        ];

        return array_merge([$extra[$personality]], $base);
    }

    private function englishTemplates(string $tone): array
    {
        return match ($tone) {
            'professional' => [
                'Introducing {topic} from {brand}. {details} Quality you can rely on. {cta}',
                '{topic} is now available at {brand}. {details} Carefully selected for our customers. {cta}',
                'Looking for {topic}? {brand} has you covered. {details} {cta}',
                'New arrival: {topic}. {details} Limited stock available. {cta}',
            ],
            'funny' => [
                'Warning: {topic} may cause sudden happiness 😄 {details} {cta}',
                'Our {topic} called. It said it misses you 📞😂 {details} {cta}',
                'Plot twist: you NEED {topic} 🤭 {details} {brand} has it! {cta}',
                'Roses are red, prices are low, {topic} is here, so do not be slow! 🌹 {details} {cta}',
            ],
            'urgent' => [
                '⏰ Hurry! {topic} is selling fast. {details} Offer ends soon! {cta}',
                '🔥 Last chance: {topic} at {brand}. {details} Do not miss out! {cta}',
                '⚡ Flash deal on {topic}! {details} Only a few pieces left. {cta}',
                '🚨 Limited stock alert: {topic}. {details} Grab yours today! {cta}',
            ],
            'festive' => [
                '🌙 Celebrate in style with {topic} from {brand}! {details} Wishing you joy and happiness. {cta}',
                '🎉 This festive season, gift your loved ones {topic}. {details} {cta}',
                '✨ Make your celebration special with {topic}. {details} {brand}: {tagline}. {cta}',
                '🎁 Festive offer on {topic}! {details} Spread the joy. {cta}',
            ],
            default => [
                '✨ {topic} is here at {brand}! {details} Made just for you 💛 {cta}',
                '😍 Say hello to {topic}! {details} {brand}: {tagline}. {cta}',
                '🛍️ New in: {topic}. {details} We think you will love it! {cta}',
                '💛 Treat yourself to {topic} from {brand}. {details} {cta}',
            ],
        };
    }

    private function banglaTemplates(): array
    {
        return [
            '✨ {topic} এখন পাওয়া যাচ্ছে {brand}-এ! {details} {cta} 📦 সারা বাংলাদেশে হোম ডেলিভারি।',
            '🎉 নতুন কালেকশন চলে এসেছে! {topic} {details} স্টক সীমিত, তাই দেরি না করে আজই অর্ডার করুন। {cta}',
            '❤️ আপনার পছন্দের {topic} এখন {brand}-এ। কোয়ালিটি নিয়ে কোনো আপস নয়! {details} {cta}',
            '🛍️ {topic} — {details} সেরা দামে সেরা পণ্য, শুধু {brand}-এ। {cta}',
        ];
    }

    private function hashtags(string $topic, string $brand, string $industry): array
    {
        $tag = fn (string $text) => '#'.preg_replace('/[^\p{L}\p{M}\p{N}]/u', '', Str::title($text));

        $tags = [$tag($brand), $tag($topic)];
        foreach (array_slice(preg_split('/\s+/u', $topic) ?: [], 0, 2) as $word) {
            if (mb_strlen($word) > 3) {
                $tags[] = $tag($word);
            }
        }
        $tags[] = $tag($this->noun($industry).' BD');
        $tags = array_merge($tags, ['#OnlineShoppingBD', '#ShopLocalBD', '#BangladeshiBrand', '#DhakaShopping', '#FacebookShopBD']);

        return collect($tags)->filter(fn ($t) => mb_strlen($t) > 2)->unique()->take(8)->values()->all();
    }
}
