<?php

namespace Mca\Upload\Support;

use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Opsiyonel görsel işleme: WebP dönüşümü + uzun kenar sınırlama.
 * GD kullanır; başarısız olursa orijinal dosya korunur.
 */
final class ImageProcessor
{
    public function supportsWebp(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * @return array{file: UploadedFile, mime: string, width: ?int, height: ?int, cleanup: ?string}|null
     */
    public function process(UploadedFile $file, UploadOptions $options): ?array
    {
        if ($options->convert !== 'webp' || ! $this->supportsWebp()) {
            return null;
        }

        $sourcePath = $file->getRealPath() ?: $file->getPathname();
        if (! is_string($sourcePath) || $sourcePath === '' || ! is_readable($sourcePath)) {
            return null;
        }

        try {
            $image = $this->createImage($sourcePath, (string) $file->getMimeType());
            if ($image === false) {
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $maxEdge = $options->maxEdge;

            if ($maxEdge !== null && $maxEdge > 0) {
                $longest = max($width, $height);
                if ($longest > $maxEdge) {
                    $scale = $maxEdge / $longest;
                    $newWidth = max(1, (int) round($width * $scale));
                    $newHeight = max(1, (int) round($height * $scale));
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    if ($resized === false) {
                        imagedestroy($image);

                        return null;
                    }

                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                    $width = $newWidth;
                    $height = $newHeight;
                }
            }

            $tempBase = tempnam(sys_get_temp_dir(), 'mca-webp-');
            if ($tempBase === false) {
                imagedestroy($image);

                return null;
            }

            $webpPath = $tempBase.'.webp';
            @unlink($tempBase);

            $ok = imagewebp($image, $webpPath, max(1, min(100, $options->quality)));
            imagedestroy($image);

            if (! $ok || ! is_file($webpPath)) {
                @unlink($webpPath);

                return null;
            }

            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'image';
            $processed = new UploadedFile($webpPath, $base.'.webp', 'image/webp', null, true);

            return [
                'file' => $processed,
                'mime' => 'image/webp',
                'width' => $width,
                'height' => $height,
                'cleanup' => $webpPath,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return \GdImage|false
     */
    private function createImage(string $path, string $mime)
    {
        $mime = strtolower($mime);

        return match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            default => @imagecreatefromstring((string) file_get_contents($path)),
        };
    }
}
