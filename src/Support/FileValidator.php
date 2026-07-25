<?php

namespace Mca\Upload\Support;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class FileValidator
{
    public function validate(UploadedFile $file, UploadOptions $options): void
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException($this->uploadErrorMessage($file->getError()));
        }

        $sizeKb = (int) ceil($file->getSize() / 1024);
        if ($sizeKb > $options->maxKb) {
            throw new InvalidArgumentException(
                "Dosya boyutu {$options->maxKb} KB sınırını aşıyor ({$sizeKb} KB)."
            );
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));
        if ($extension !== '' && in_array($extension, $options->blockedExtensions, true)) {
            throw new InvalidArgumentException("Bu uzantıya izin verilmiyor: .{$extension}");
        }

        if (substr_count($file->getClientOriginalName(), '.') > 1) {
            $parts = explode('.', strtolower($file->getClientOriginalName()));
            foreach ($parts as $part) {
                if (in_array($part, $options->blockedExtensions, true)) {
                    throw new InvalidArgumentException('Çift uzantılı / tehlikeli dosya adı reddedildi.');
                }
            }
        }

        $mime = $this->detectMime($file);

        if ($mime === 'image/svg+xml' && ! $options->allowSvg) {
            throw new InvalidArgumentException('SVG yükleme varsayılan olarak kapalıdır.');
        }

        if ($options->allowedMimes !== [] && ! in_array($mime, $options->allowedMimes, true)) {
            throw new InvalidArgumentException("MIME tipine izin verilmiyor: {$mime}");
        }

        if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            $info = @getimagesize($file->getRealPath() ?: $file->getPathname());
            if ($info === false) {
                throw new InvalidArgumentException('Dosya geçerli bir görsel değil.');
            }
        }
    }

    public function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (is_string($path) && is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    return strtolower($detected);
                }
            }
        }

        return strtolower((string) ($file->getMimeType() ?: 'application/octet-stream'));
    }

    public function extensionForMime(string $mime, UploadedFile $file): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'application/pdf' => 'pdf',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        $client = strtolower((string) $file->getClientOriginalExtension());

        return $client !== '' ? $client : 'bin';
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya sunucu yükleme sınırını aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör eksik.',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi yüklemeyi durdurdu.',
            default => 'Dosya yükleme hatası.',
        };
    }
}
