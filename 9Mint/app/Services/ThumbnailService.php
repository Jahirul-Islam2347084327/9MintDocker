<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class ThumbnailService
{
    public const MAX_EDGE = 720;

    /**
     * Generate a 720px WebP thumbnail from a source image path on disk.
     * Returns the public-relative URL of the saved thumbnail, or null on failure.
     */
    public static function generate(string $sourceAbsolutePath, string $outputDir, string $prefix = 'thumb'): ?string
    {
        if (! file_exists($sourceAbsolutePath)) {
            return null;
        }

        $filename = $prefix . '-' . Str::uuid() . '.webp';
        $targetDir = public_path($outputDir);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        $isGif = strtolower((string) pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION)) === 'gif';

        // Prefer Imagick for GIF thumbnails so animated sources stay animated in WebP output.
        if ($isGif && class_exists(\Imagick::class)) {
            try {
                $imagickManager = new ImageManager(new ImagickDriver());
                $image = $imagickManager->read($sourceAbsolutePath);
                $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
                $image->toWebp(quality: 80)->save($targetPath);

                return '/' . trim($outputDir, '/') . '/' . $filename;
            } catch (\Throwable $e) {
                // Fall back to GD static rendering below.
            }
        }

        $gdManager = new ImageManager(new GdDriver());
        $image = $gdManager->read($sourceAbsolutePath);

        // Scale down so the longest edge is MAX_EDGE, keep aspect ratio
        $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
        $image->toWebp(quality: 80)->save($targetPath);

        return '/' . trim($outputDir, '/') . '/' . $filename;
    }

    /**
     * Generate a WebP cover image.
     * For GIF sources, this attempts to preserve animation via Imagick when available.
     * Falls back to GD (static frame) if Imagick is unavailable or encoding fails.
     */
    public static function generateCover(string $sourceAbsolutePath, string $outputDir, string $prefix = 'cover'): ?string
    {
        if (! file_exists($sourceAbsolutePath)) {
            return null;
        }

        $targetDir = public_path($outputDir);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $prefix . '-' . Str::uuid() . '.webp';
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        $isGif = strtolower((string) pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION)) === 'gif';

        // Prefer Imagick for GIF covers so animation can be preserved in WebP output.
        if ($isGif && class_exists(\Imagick::class)) {
            try {
                $imagickManager = new ImageManager(new ImagickDriver());
                $image = $imagickManager->read($sourceAbsolutePath);
                $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
                $image->toWebp(quality: 80)->save($targetPath);

                return '/' . trim($outputDir, '/') . '/' . $filename;
            } catch (\Throwable $e) {
                // Fall back to GD static rendering below.
            }
        }

        $gdManager = new ImageManager(new GdDriver());
        $image = $gdManager->read($sourceAbsolutePath);
        $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
        $image->toWebp(quality: 80)->save($targetPath);

        return '/' . trim($outputDir, '/') . '/' . $filename;
    }

    /**
     * Resolve the absolute disk path for an image_url stored in the DB.
     * Handles both /storage/... and /images/... patterns.
     */
    public static function resolveAbsolutePath(string $imageUrl): ?string
    {
        $relative = ltrim($imageUrl, '/');

        // /storage/nfts/... → storage/app/public/nfts/...
        if (str_starts_with($relative, 'storage/')) {
            $storagePath = str_replace('storage/', '', $relative);
            $abs = storage_path('app/public/' . $storagePath);
            if (file_exists($abs)) {
                return $abs;
            }
        }

        // /images/nfts/... → public/images/nfts/...
        $abs = public_path($relative);
        if (file_exists($abs)) {
            return $abs;
        }

        return null;
    }

    public static function supportsAnimatedGifThumbnails(): bool
    {
        return class_exists(\Imagick::class);
    }

    /**
     * Report commonly expected animation tools so the caller can produce a helpful warning.
     *
     * Note: the current thumbnail pipeline preserves GIF animation via the PHP Imagick extension.
     */
    public static function missingAnimationDependencies(): array
    {
        $missing = [];

        if (! class_exists(\Imagick::class)) {
            $missing[] = 'PHP Imagick extension';
        }

        if (! self::commandExists('magick')) {
            $missing[] = 'ImageMagick CLI (magick)';
        }

        if (! self::commandExists('ffmpeg')) {
            $missing[] = 'FFmpeg (ffmpeg)';
        }

        return $missing;
    }

    private static function commandExists(string $command): bool
    {
        $checkCommand = PHP_OS_FAMILY === 'Windows'
            ? sprintf('where %s 2>NUL', escapeshellarg($command))
            : sprintf('command -v %s 2>/dev/null', escapeshellarg($command));

        $output = @shell_exec($checkCommand);

        return is_string($output) && trim($output) !== '';
    }
}
