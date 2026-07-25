<?php

namespace Mca\Upload\Services;

use DateTimeInterface;
use Mca\Upload\Contracts\CloudBoxClient;
use Mca\Upload\Contracts\ObjectStore;
use Mca\Upload\Exceptions\CloudBoxException;
use RuntimeException;

/**
 * ObjectStore backed by MCA Cloud Box HTTP API.
 *
 * Stored keys are opaque: cloudbox:{uuid}
 */
final class CloudBoxObjectStore implements ObjectStore
{
    public const KEY_PREFIX = 'cloudbox:';

    private ?string $lastStoredKey = null;

    public function __construct(
        private readonly CloudBoxClient $client,
    ) {}

    public function diskName(): string
    {
        return 'cloudbox';
    }

    public static function isCloudBoxKey(string $key): bool
    {
        return str_starts_with($key, self::KEY_PREFIX);
    }

    public static function uuidFromKey(string $key): string
    {
        if (! self::isCloudBoxKey($key)) {
            throw new RuntimeException("Geçersiz Cloud Box anahtarı: {$key}");
        }

        $uuid = substr($key, strlen(self::KEY_PREFIX));

        if ($uuid === '') {
            throw new RuntimeException("Geçersiz Cloud Box anahtarı: {$key}");
        }

        return $uuid;
    }

    public static function keyFromUuid(string $uuid): string
    {
        return self::KEY_PREFIX.$uuid;
    }

    /** Key written by the last put/putStream (cloudbox:{uuid}). */
    public function consumeLastStoredKey(): ?string
    {
        $key = $this->lastStoredKey;
        $this->lastStoredKey = null;

        return $key;
    }

    public function put(string $key, mixed $contents, array $options = []): void
    {
        $this->upload($contents, $this->filenameFor($key, $options), $options);
    }

    public function putStream(string $key, mixed $stream, array $options = []): void
    {
        $this->upload($stream, $this->filenameFor($key, $options), $options);
    }

    public function readStream(string $key)
    {
        $body = $this->client->download(self::uuidFromKey($key));
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Geçici okuma akışı açılamadı.');
        }

        fwrite($stream, $body);
        rewind($stream);

        return $stream;
    }

    public function delete(string $key): void
    {
        if (! self::isCloudBoxKey($key)) {
            return;
        }

        $this->client->delete(self::uuidFromKey($key));
    }

    public function exists(string $key): bool
    {
        if (! self::isCloudBoxKey($key)) {
            return false;
        }

        try {
            return $this->client->find(self::uuidFromKey($key)) !== null;
        } catch (CloudBoxException) {
            return false;
        }
    }

    public function url(string $key): ?string
    {
        if (! self::isCloudBoxKey($key)) {
            return null;
        }

        try {
            $file = $this->client->find(self::uuidFromKey($key));
        } catch (CloudBoxException) {
            return null;
        }

        if ($file === null) {
            return null;
        }

        $publicUrl = $file['public_url'] ?? null;
        if (is_string($publicUrl) && $publicUrl !== '') {
            return $publicUrl;
        }

        $minutes = max(1, (int) config('upload.cloudbox.signed_url_minutes', 60));

        try {
            return $this->client->signedUrl(self::uuidFromKey($key), $minutes);
        } catch (CloudBoxException) {
            $contentUrl = $file['content_url'] ?? null;

            return is_string($contentUrl) && $contentUrl !== '' ? $contentUrl : null;
        }
    }

    public function temporaryUrl(string $key, DateTimeInterface $expiresAt, array $options = []): ?string
    {
        if (! self::isCloudBoxKey($key)) {
            return null;
        }

        $seconds = max(60, $expiresAt->getTimestamp() - time());
        $minutes = (int) max(1, ceil($seconds / 60));

        try {
            return $this->client->signedUrl(self::uuidFromKey($key), $minutes);
        } catch (CloudBoxException) {
            return $this->url($key);
        }
    }

    /**
     * @param  resource|string  $contents
     * @param  array{visibility?: string, folder_id?: string|null, filename?: string}  $options
     */
    private function upload(mixed $contents, string $filename, array $options): void
    {
        $file = $this->client->upload($contents, $filename, $options);
        $this->lastStoredKey = self::keyFromUuid($file['uuid']);
    }

    /**
     * @param  array{filename?: string}  $options
     */
    private function filenameFor(string $key, array $options): string
    {
        if (isset($options['filename']) && is_string($options['filename']) && $options['filename'] !== '') {
            return $options['filename'];
        }

        if (self::isCloudBoxKey($key)) {
            return 'file.bin';
        }

        $base = basename(str_replace('\\', '/', $key));

        return $base !== '' ? $base : 'file.bin';
    }
}
