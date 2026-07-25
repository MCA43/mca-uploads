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

    public function test_dotted_preset_keys_resolve_directory_and_prefix(): void
    {
        config([
            'upload.disk' => 'public',
            'upload.directory' => 'uploads/mca',
            'upload.presets' => [
                'branding.favicon' => [
                    'directory' => 'uploads/branding',
                    'prefix' => 'favicon',
                    'max_kb' => 1024,
                ],
            ],
            'filesystems.disks.public' => [
                'driver' => 'local',
                'root' => storage_path('app/public'),
            ],
        ]);

        $options = UploadOptions::fromConfig('branding.favicon');

        $this->assertSame('uploads/branding', $options->directory);
        $this->assertSame('favicon', $options->prefix);
        $this->assertSame(1024, $options->maxKb);
    }
}
