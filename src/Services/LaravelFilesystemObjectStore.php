<?php

namespace Mca\Upload\Services;

use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mca\Upload\Contracts\ObjectStore;
use RuntimeException;

final class LaravelFilesystemObjectStore implements ObjectStore
{
    public function __construct(
        private readonly string $disk,
    ) {}

    public function diskName(): string
    {
        return $this->disk;
    }

    public function put(string $key, mixed $contents, array $options = []): void
    {
        $ok = $this->filesystem()->put($key, $contents, $options);

        if ($ok === false) {
            throw new RuntimeException("Failed to write object [{$key}] to disk [{$this->disk}].");
        }
    }

    public function putStream(string $key, mixed $stream, array $options = []): void
    {
        $ok = $this->filesystem()->writeStream($key, $stream, $options);

        if ($ok === false) {
            throw new RuntimeException("Failed to stream object [{$key}] to disk [{$this->disk}].");
        }
    }

    public function readStream(string $key)
    {
        return $this->filesystem()->readStream($key);
    }

    public function delete(string $key): void
    {
        if ($this->exists($key)) {
            $this->filesystem()->delete($key);
        }
    }

    public function exists(string $key): bool
    {
        return $this->filesystem()->exists($key);
    }

    public function url(string $key): ?string
    {
        if (! $this->exists($key)) {
            return null;
        }

        $disk = $this->filesystem();

        try {
            return $disk->url($key);
        } catch (\Throwable) {
            // web disk under public/ — path is already web-relative
            return asset(ltrim($key, '/'));
        }
    }

    public function temporaryUrl(string $key, DateTimeInterface $expiresAt, array $options = []): ?string
    {
        $disk = $this->filesystem();

        if (! method_exists($disk, 'temporaryUrl')) {
            return $this->url($key);
        }

        try {
            return $disk->temporaryUrl($key, $expiresAt, $options);
        } catch (\Throwable) {
            return $this->url($key);
        }
    }

    private function filesystem(): Filesystem
    {
        return Storage::disk($this->disk);
    }
}
