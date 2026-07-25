<?php

namespace Mca\Upload\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'mca:upload:install')]
class InstallUploadCommand extends Command
{
    protected $signature = 'mca:upload:install
                            {--force : Overwrite existing published files}';

    protected $description = 'Publish mca/uploads config and ensure branding upload directory';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'mca-upload-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $directory = (string) config('upload.presets.branding.light_logo.directory', 'uploads/branding');
        $disk = (string) config('upload.disk', 'web');

        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            $disk = (string) config('upload.fallback_disk', 'public');
            $this->warn("Disk [".config('upload.disk')."] bulunamadı; fallback [{$disk}] kullanılıyor.");
            $this->line('  config/filesystems.php içine "web" diski eklemeniz önerilir (public_path kökü).');
        }

        \Illuminate\Support\Facades\Storage::disk($disk)->makeDirectory($directory);
        $this->components->info("Upload dizini hazır: {$disk}:{$directory}");

        return self::SUCCESS;
    }
}
