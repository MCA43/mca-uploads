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
    ) {}

    public static function fromConfig(?string $preset = null, array $overrides = []): self
    {
        $base = [
            'disk' => (string) config('upload.disk', 'web'),
            'directory' => (string) config('upload.directory', 'uploads/mca'),
            'max_kb' => (int) config('upload.max_kb', 2048),
            'allowed_mimes' => (array) config('upload.allowed_mimes', []),
            'blocked_extensions' => (array) config('upload.blocked_extensions', []),
            'prefix' => 'file',
            'name_strategy' => (string) config('upload.name_strategy', 'ulid'),
            'allow_svg' => (bool) config('upload.allow_svg', false),
        ];

        if (is_string($preset) && $preset !== '') {
            $presetConfig = (array) config('upload.presets.'.$preset, []);
            $base = array_replace($base, $presetConfig);
            $base['prefix'] = (string) ($presetConfig['prefix'] ?? str_replace('.', '-', $preset));
        }

        $merged = array_replace($base, $overrides);

        $disk = (string) $merged['disk'];
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            $disk = (string) config('upload.fallback_disk', 'public');
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
        );
    }
}
