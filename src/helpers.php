<?php

use Mca\Upload\Services\UploadManager;

if (! function_exists('mca_upload')) {
    function mca_upload(): UploadManager
    {
        return app(UploadManager::class);
    }
}

if (! function_exists('mca_upload_url')) {
    function mca_upload_url(?string $path, ?string $disk = null): ?string
    {
        return mca_upload()->url($path, $disk);
    }
}
