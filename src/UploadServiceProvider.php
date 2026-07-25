<?php

namespace Mca\Upload;

use Illuminate\Support\ServiceProvider;
use Mca\Upload\Console\InstallUploadCommand;
use Mca\Upload\Services\UploadManager;
use Mca\Upload\Support\FileNamer;
use Mca\Upload\Support\FileValidator;

class UploadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/upload.php', 'upload');

        $this->app->singleton(FileValidator::class);
        $this->app->singleton(FileNamer::class);
        $this->app->singleton(UploadManager::class);
    }

    public function boot(): void
    {
        if (! config('upload.enabled', true)) {
            return;
        }

        $this->registerPublishing();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mca-upload');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mca-upload');
        $this->registerHub();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallUploadCommand::class,
            ]);
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/upload.php' => config_path('upload.php'),
        ], 'mca-upload-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/mca-upload'),
        ], 'mca-upload-views');
    }

    protected function registerHub(): void
    {
        if (! function_exists('mca_hub_register')) {
            return;
        }

        mca_hub_register('uploads', [
            'enabled' => fn () => (bool) config('upload.enabled', true),
            'name' => 'mca/uploads',
            'title' => 'Uploads',
            'description' => 'Secure uploads and branding image widgets',
            'order' => 25,
            'icon' => 'upload',
        ]);
    }
}
