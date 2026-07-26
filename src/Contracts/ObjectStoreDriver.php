<?php

namespace Mca\Upload\Contracts;

/**
 * Optional remote / alternate ObjectStore driver (e.g. mca/uploads-cloudbox).
 */
interface ObjectStoreDriver
{
    public function enabled(): bool;

    public function store(): ObjectStore;

    public function managesKey(string $key): bool;
}
