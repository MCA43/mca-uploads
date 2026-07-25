<?php

namespace Mca\Upload\Support;

final class StoredFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $disk,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $url = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'disk' => $this->disk,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'url' => $this->url,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
