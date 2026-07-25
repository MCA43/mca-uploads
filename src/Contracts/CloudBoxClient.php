<?php

namespace Mca\Upload\Contracts;

/**
 * MCA Cloud Box HTTP API client (api/v1 files).
 */
interface CloudBoxClient
{
    /**
     * Upload a file (multipart POST /files).
     *
     * @param  resource|string  $contents
     * @param  array{visibility?: string, folder_id?: string|null}  $options
     * @return array{uuid: string, name?: string, public_url?: string|null, content_url?: string|null, download_url?: string|null, visibility?: string, mime_type?: string, size_bytes?: int}
     */
    public function upload(mixed $contents, string $filename, array $options = []): array;

    /**
     * @return array{uuid: string, name?: string, public_url?: string|null, content_url?: string|null, download_url?: string|null, visibility?: string, mime_type?: string, size_bytes?: int}|null
     */
    public function find(string $uuid): ?array;

    public function delete(string $uuid): void;

    public function signedUrl(string $uuid, int $minutes = 15): string;

    /** Raw file body from GET /files/{uuid}/content. */
    public function download(string $uuid): string;
}
