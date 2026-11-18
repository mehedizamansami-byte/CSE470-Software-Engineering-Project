<?php

namespace App\Support;

/**
 * Small colour helpers used by the brand kit generator and the views.
 */
class Color
{
    public static function isHex(mixed $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) === 1;
    }

    /** @return array{0: float, 1: float, 2: float} hue 0-360, saturation 0-100, lightness 0-100 */
    public static function toHsl(string $hex): array
    {
        $r = hexdec(substr($hex, 1, 2)) / 255;
        $g = hexdec(substr($hex, 3, 2)) / 255;
        $b = hexdec(substr($hex, 5, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $h = 0.0;
        $s = 0.0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            $h = match ($max) {
                $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
                $g => ($b - $r) / $d + 2,
                default => ($r - $g) / $d + 4,
            };
            $h /= 6;
        }

        return [$h * 360, $s * 100, $l * 100];
    }

    public static function fromHsl(float $h, float $s, float $l): string
    {
        $h = fmod(fmod($h, 360) + 360, 360) / 360;
        $s = max(0, min(100, $s)) / 100;
        $l = max(0, min(100, $l)) / 100;

        if ($s == 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }

        return sprintf('#%02X%02X%02X', (int) round($r * 255), (int) round($g * 255), (int) round($b * 255));
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /** Black or white text, whichever reads better on the given background. */
    public static function textOn(string $hex): string
    {
        if (! self::isHex($hex)) {
            return '#FFFFFF';
        }

        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.6 ? '#111827' : '#FFFFFF';
    }

    /** Build a five-colour palette around one main colour. */
    public static function paletteFrom(string $primary): array
    {
        [$h, $s, $l] = self::toHsl($primary);

        return [
            'primary' => strtoupper($primary),
            'secondary' => self::fromHsl($h + 30, max(35, $s - 10), min(72, $l + 15)),
            'accent' => self::fromHsl($h + 180, max(55, $s), 52),
            'neutral' => self::fromHsl($h, min(25, $s), 16),
            'background' => self::fromHsl($h, min(40, $s), 97),
        ];
    }
}
