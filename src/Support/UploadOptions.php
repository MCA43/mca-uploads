<?php

namespace Mca\Upload\Support;

final class UploadOptions
{
    /**
     * @param  list<string>  $allowedMimes
     * @param  list<string>  $blockedExtensions
     */
    public function __construct(
        public readonly string $disk,
        public readonly string $directory,
        public readonly int $maxKb,
        public readonly array $allowedMimes,
        public readonly array $blockedExtensions,
        public readonly string $prefix = 'file',
        public readonly string $nameStrategy = 'ulid',
        public readonly bool $allowSvg = false,
        public readonly ?string $preset = null,
        public readonly ?string $convert = null,
        public readonly int $quality = 80,
        public readonly ?int $maxEdge = null,
    ) {}

    public static function fromConfig(?string $preset = null, array $overrides = []): self
    {
        $imageDefaults = (array) config('upload.image', []);

        $base = [
            'disk' => (string) config('upload.disk', 'web'),
            'directory' => (string) config('upload.directory', 'uploads/mca'),
            'max_kb' => (int) config('upload.max_kb', 2048),
            'allowed_mimes' => (array) config('upload.allowed_mimes', []),
            'blocked_extensions' => (array) config('upload.blocked_extensions', []),
            'prefix' => 'file',
            'name_strategy' => (string) config('upload.name_strategy', 'ulid'),
            'allow_svg' => (bool) config('upload.allow_svg', false),
            'convert' => $imageDefaults['convert'] ?? null,
            'quality' => (int) ($imageDefaults['quality'] ?? 80),
            'max_edge' => array_key_exists('max_edge', $imageDefaults)
                ? ($imageDefaults['max_edge'] !== null ? (int) $imageDefaults['max_edge'] : null)
                : 1920,
        ];

        if (is_string($preset) && $preset !== '') {
            $presets = (array) config('upload.presets', []);
            // Dotted preset keys (e.g. branding.favicon) must not use config('a.b.c') nesting.
            $presetConfig = is_array($presets[$preset] ?? null) ? $presets[$preset] : [];
            $base = array_replace($base, $presetConfig);
            $base['prefix'] = (string) ($presetConfig['prefix'] ?? str_replace('.', '-', $preset));
        }

        $merged = array_replace($base, $overrides);

        $disk = (string) $merged['disk'];
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            $disk = (string) config('upload.fallback_disk', 'public');
        }

        $convert = $merged['convert'] ?? null;
        $convert = is_string($convert) && $convert !== '' ? strtolower($convert) : null;
        if ($convert !== 'webp') {
            $convert = null;
        }

        $maxEdge = $merged['max_edge'] ?? null;
        if ($maxEdge === false || $maxEdge === '' || $maxEdge === null) {
            $maxEdge = null;
        } else {
            $maxEdge = max(1, (int) $maxEdge);
        }

        return new self(
            disk: $disk,
            directory: trim((string) $merged['directory'], '/'),
            maxKb: max(1, (int) $merged['max_kb']),
            allowedMimes: array_values(array_filter((array) $merged['allowed_mimes'])),
            blockedExtensions: array_values(array_filter((array) $merged['blocked_extensions'])),
            prefix: (string) ($merged['prefix'] ?? 'file'),
            nameStrategy: (string) ($merged['name_strategy'] ?? 'ulid'),
            allowSvg: (bool) ($merged['allow_svg'] ?? false),
            preset: $preset,
            convert: $convert,
            quality: max(1, min(100, (int) ($merged['quality'] ?? 80))),
            maxEdge: $maxEdge,
        );
    }
}
