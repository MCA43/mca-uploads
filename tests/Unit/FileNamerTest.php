<?php

use Orchestra\Testbench\TestCase;
use Mca\Upload\UploadServiceProvider;
use Mca\Upload\Support\FileNamer;
use Mca\Upload\Support\UploadOptions;

class FileNamerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [UploadServiceProvider::class];
    }

    public function test_namer_uses_prefix_and_extension(): void
    {
        $options = new UploadOptions(
            disk: 'public',
            directory: 'uploads',
            maxKb: 1024,
            allowedMimes: ['image/png'],
            blockedExtensions: ['php'],
            prefix: 'dark-logo',
            nameStrategy: 'uniqid',
        );

        $name = app(FileNamer::class)->make($options, 'png');

        $this->assertStringStartsWith('dark-logo-', $name);
        $this->assertStringEndsWith('.png', $name);
    }
}
