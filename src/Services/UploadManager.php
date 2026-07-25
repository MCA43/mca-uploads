<?php

namespace Mca\Upload\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mca\Upload\Contracts\ObjectStore;
use Mca\Upload\Support\FileNamer;
use Mca\Upload\Support\FileValidator;
use Mca\Upload\Support\StoredFile;
use Mca\Upload\Support\UploadOptions;
use RuntimeException;
use Throwable;

final class UploadManager
{
    public function __construct(
        private readonly FileValidator $validator,
        private readonly FileNamer $namer,
    ) {}

    public function store(UploadedFile $file, ?string $preset = null, array $overrides = []): StoredFile
    {
        $options = UploadOptions::fromConfig($preset, $overrides);
        $this->validator->validate($file, $options);

        $store = $this->storeFor($options->disk);
        $mime = $this->validator->detectMime($file);
        $extension = $this->validator->extensionForMime($mime, $file);
        $filename = $this->namer->make($options, $extension);
        $key = trim($options->directory.'/'.$filename, '/');

        $this->ensureDirectory($options);

        $stream = fopen($file->getRealPath() ?: $file->getPathname(), 'r');
        if ($stream === false) {
            throw new RuntimeException('Yüklenen dosya okunamadı.');
        }

        try {
            $store->putStream($key, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $store->exists($key)) {
            throw new RuntimeException('Dosya kaydedilemedi.');
        }

        [$width, $height] = $this->imageDimensions($file, $mime);

        return new StoredFile(
            path: $key,
            disk: $options->disk,
            originalName: $file->getClientOriginalName(),
            mimeType: $mime,
            size: (int) $file->getSize(),
            url: $store->url($key),
            width: $width,
            height: $height,
        );
    }

    public function replace(UploadedFile $file, ?string $oldPath, ?string $preset = null, array $overrides = []): StoredFile
    {
        $stored = $this->store($file, $preset, $overrides);

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $stored->path) {
            $this->delete($oldPath, $stored->disk);
        }

        return $stored;
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        // Only remove managed upload keys; never touch static brand assets.
        if (str_starts_with($path, 'brand/') || ! str_starts_with(ltrim($path, '/'), 'uploads/')) {
            return;
        }

        $diskName = $this->resolveDisk($disk);
        $this->storeFor($diskName)->delete($path);
    }

    public function url(?string $path, ?string $disk = null): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $diskName = $this->resolveDisk($disk);

        return $this->storeFor($diskName)->url($path) ?? asset(ltrim($path, '/'));
    }

    public function storeFor(string $disk): ObjectStore
    {
        return new LaravelFilesystemObjectStore($disk);
    }

    private function resolveDisk(?string $disk): string
    {
        $diskName = $disk ?: (string) config('upload.disk', 'web');

        if (! array_key_exists($diskName, config('filesystems.disks', []))) {
            return (string) config('upload.fallback_disk', 'public');
        }

        return $diskName;
    }

    private function ensureDirectory(UploadOptions $options): void
    {
        $fs = Storage::disk($options->disk);
        $fs->makeDirectory($options->directory);

        try {
            if (method_exists($fs, 'path')) {
                $absolute = $fs->path($options->directory);
                if (is_string($absolute) && is_dir($absolute) && ! is_writable($absolute)) {
                    throw new RuntimeException(
                        "Yükleme klasörü yazılamıyor ({$options->directory}). İzinleri kontrol edin."
                    );
                }
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable) {
            // Remote disks may not support path().
        }
    }

    /** @return array{0: ?int, 1: ?int} */
    private function imageDimensions(UploadedFile $file, string $mime): array
    {
        if (! str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            return [null, null];
        }

        $info = @getimagesize($file->getRealPath() ?: $file->getPathname());

        if ($info === false) {
            return [null, null];
        }

        return [(int) $info[0], (int) $info[1]];
    }
}
