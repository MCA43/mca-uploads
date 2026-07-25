<?php

namespace Mca\Upload\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mca\Upload\Contracts\CloudBoxClient;
use Mca\Upload\Exceptions\CloudBoxException;

final class HttpCloudBoxClient implements CloudBoxClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $timeout = 30,
        private readonly string $apiPrefix = 'api/v1',
    ) {}

    public static function fromConfig(): self
    {
        $baseUrl = trim((string) config('upload.cloudbox.base_url', ''));
        $token = trim((string) config('upload.cloudbox.token', ''));

        if ($baseUrl === '' || $token === '') {
            throw new CloudBoxException(
                'Cloud Box yapılandırması eksik: MCA_UPLOAD_CLOUDBOX_URL ve MCA_UPLOAD_CLOUDBOX_TOKEN gerekli.'
            );
        }

        return new self(
            baseUrl: rtrim($baseUrl, '/'),
            token: $token,
            timeout: max(1, (int) config('upload.cloudbox.timeout', 30)),
            apiPrefix: trim((string) config('upload.cloudbox.api_prefix', 'api/v1'), '/'),
        );
    }

    public function upload(mixed $contents, string $filename, array $options = []): array
    {
        $body = $this->normalizeContents($contents);

        $fields = array_filter([
            'visibility' => $options['visibility']
                ?? config('upload.cloudbox.visibility', 'public'),
            'folder_id' => $options['folder_id']
                ?? config('upload.cloudbox.folder_id'),
        ], static fn ($value) => $value !== null && $value !== '');

        $response = $this->http()
            ->attach('file', $body, $filename)
            ->post($this->path('files'), $fields);

        return $this->data($response, 'Cloud Box dosya yükleme başarısız.');
    }

    public function find(string $uuid): ?array
    {
        $response = $this->http()->get($this->path('files/'.$uuid));

        if ($response->status() === 404) {
            return null;
        }

        return $this->data($response, 'Cloud Box dosya bilgisi alınamadı.');
    }

    public function delete(string $uuid): void
    {
        $response = $this->http()->delete($this->path('files/'.$uuid));

        if ($response->status() === 404) {
            return;
        }

        $this->assertOk($response, 'Cloud Box dosya silme başarısız.');
    }

    public function signedUrl(string $uuid, int $minutes = 15): string
    {
        $minutes = max(1, min($minutes, 60 * 24 * 7));

        $response = $this->http()->post($this->path('files/'.$uuid.'/signed-url'), [
            'minutes' => $minutes,
        ]);

        $this->assertOk($response, 'Cloud Box imzalı URL alınamadı.');

        $url = $response->json('data.url');

        if (! is_string($url) || $url === '') {
            throw new CloudBoxException('Cloud Box imzalı URL yanıtı geçersiz.');
        }

        return $url;
    }

    public function download(string $uuid): string
    {
        $response = $this->http()->get($this->path('files/'.$uuid.'/content'));

        $this->assertOk($response, 'Cloud Box dosya indirme başarısız.');

        return $response->body();
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout($this->timeout);
    }

    private function path(string $relative): string
    {
        return $this->apiPrefix.'/'.ltrim($relative, '/');
    }

    /**
     * @param  resource|string  $contents
     */
    private function normalizeContents(mixed $contents): string
    {
        if (is_resource($contents)) {
            $body = stream_get_contents($contents);
            if ($body === false) {
                throw new CloudBoxException('Yükleme akışı okunamadı.');
            }

            return $body;
        }

        if (is_string($contents)) {
            return $contents;
        }

        throw new CloudBoxException('Cloud Box yükleme içeriği string veya resource olmalıdır.');
    }

    /**
     * @return array{uuid: string, name?: string, public_url?: string|null, content_url?: string|null, download_url?: string|null, visibility?: string, mime_type?: string, size_bytes?: int}
     */
    private function data(Response $response, string $fallbackMessage): array
    {
        $this->assertOk($response, $fallbackMessage);

        $data = $response->json('data');

        if (! is_array($data) || ! is_string($data['uuid'] ?? null) || $data['uuid'] === '') {
            throw new CloudBoxException($fallbackMessage.' (geçersiz data.uuid)');
        }

        /** @var array{uuid: string, name?: string, public_url?: string|null, content_url?: string|null, download_url?: string|null, visibility?: string, mime_type?: string, size_bytes?: int} $data */
        return $data;
    }

    private function assertOk(Response $response, string $fallbackMessage): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('message');
        $code = $response->json('error_code');

        throw new CloudBoxException(
            is_string($message) && $message !== '' ? $message : $fallbackMessage.' (HTTP '.$response->status().')',
            $response->status(),
            is_string($code) ? $code : null,
        );
    }
}
