<?php

namespace Mca\Upload\Support;

use Illuminate\Support\Str;

final class FileNamer
{
    public function make(UploadOptions $options, string $extension): string
    {
        $token = match ($options->nameStrategy) {
            'uuid' => (string) Str::uuid(),
            'uniqid' => uniqid(),
            default => (string) Str::ulid(),
        };

        $prefix = Str::slug($options->prefix, '-') ?: 'file';

        return $prefix.'-'.$token.'.'.ltrim($extension, '.');
    }
}
