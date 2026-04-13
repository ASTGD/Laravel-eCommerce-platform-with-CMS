<?php

namespace Platform\PlatformSupport\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SquareCanvasImageService
{
    public function fromUploadedFile(UploadedFile $file, string $directory, int $size = 220): string
    {
        $realPath = $file->getRealPath();

        if (! $realPath || ! file_exists($realPath)) {
            throw new RuntimeException('Unable to read the uploaded image file.');
        }

        $binary = file_get_contents($realPath);

        if ($binary === false) {
            throw new RuntimeException('Unable to read the uploaded image file contents.');
        }

        return $this->fromBinary(
            $binary,
            $directory,
            $file->getClientOriginalName().':'.$file->getSize().':'.$file->getClientOriginalExtension(),
            $size
        );
    }

    public function fromRelativePath(string $relativePath, string $directory, int $size = 220): string
    {
        $disk = Storage::disk('public');
        $absolutePath = $disk->path($relativePath);

        if (! file_exists($absolutePath)) {
            throw new RuntimeException("Unable to locate square-canvas source media: {$relativePath}");
        }

        $binary = file_get_contents($absolutePath);

        if ($binary === false) {
            throw new RuntimeException("Unable to read square-canvas source media: {$relativePath}");
        }

        return $this->fromBinary($binary, $directory, $relativePath, $size);
    }

    public function fromBinary(string $binary, string $directory, string $sourceKey, int $size = 220): string
    {
        $disk = Storage::disk('public');
        $format = $this->preferredFormat();
        $directory = trim($directory, '/');

        $relativePath = $directory.'/'.sha1($sourceKey.'|'.$size.'|'.$format.'|square-canvas-v1').'.'.$format;

        if ($disk->exists($relativePath)) {
            return $relativePath;
        }

        $sourceImage = imagecreatefromstring($binary);

        if (! $sourceImage) {
            throw new RuntimeException('Unable to decode square-canvas source media.');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefill($canvas, 0, 0, $white);

        $padding = max(6, (int) round($size * 0.04));
        $maxWidth = $size - ($padding * 2);
        $maxHeight = $size - ($padding * 2);
        $scale = min(
            $maxWidth / max(1, $sourceWidth),
            $maxHeight / max(1, $sourceHeight),
        );

        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetX = (int) floor(($size - $targetWidth) / 2);
        $targetY = (int) floor(($size - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $sourceImage,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $disk->makeDirectory(dirname($relativePath));
        $this->saveCanvas($canvas, $disk->path($relativePath), $format);

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        return $relativePath;
    }

    private function preferredFormat(): string
    {
        if (function_exists('imageavif')) {
            return 'avif';
        }

        if (function_exists('imagewebp')) {
            return 'webp';
        }

        return 'png';
    }

    private function saveCanvas($canvas, string $path, string $format): void
    {
        $result = match ($format) {
            'avif' => imageavif($canvas, $path, 82),
            'webp' => imagewebp($canvas, $path, 82),
            default => imagepng($canvas, $path, 6),
        };

        if ($result === false) {
            throw new RuntimeException('Unable to write square-canvas media to disk.');
        }
    }
}
