<?php

namespace Mca\Upload\Exceptions;

use RuntimeException;
use Throwable;

final class CloudBoxException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
