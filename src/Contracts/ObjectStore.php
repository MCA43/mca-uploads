<?php

namespace Mca\Upload\Contracts;

use DateTimeInterface;

interface ObjectStore
{
    public function put(string $key, mixed $contents, array $options = []): void;

    /** @param  resource  $stream */
    public function putStream(string $key, mixed $stream, array $options = []): void;

    /** @return resource|null */
    public function readStream(string $key);

    public function delete(string $key): void;

    public function exists(string $key): bool;

    public function url(string $key): ?string;

    public function temporaryUrl(string $key, DateTimeInterface $expiresAt, array $options = []): ?string;

    public function diskName(): string;
}
