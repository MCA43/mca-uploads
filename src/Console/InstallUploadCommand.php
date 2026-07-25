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

        $this->call('vendor:publish', [
            '--tag' => 'mca-upload-assets',
            '--force' => true,
        ]);

        if ((bool) config('upload.cloudbox.enabled', false)) {
            $this->components->info('Cloud Box driver etkin — yerel upload dizini atlandı.');
            $this->line('  MCA_UPLOAD_CLOUDBOX_URL / MCA_UPLOAD_CLOUDBOX_TOKEN ayarlarını doğrulayın.');

            return self::SUCCESS;
        }

        $presets = (array) config('upload.presets', []);
        $directory = (string) (($presets['branding.light_logo']['directory'] ?? null) ?: 'uploads/branding');
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
