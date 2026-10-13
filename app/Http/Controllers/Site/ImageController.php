<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Present;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves uploaded photos resized to one of a few fixed widths as WebP, so
 * phones download a small file. The result is cached on disk the first
 * time it is asked for.
 */
class ImageController extends Controller
{
    public function __invoke(int $width, string $path): BinaryFileResponse
    {
        abort_unless(in_array($width, Present::IMAGE_WIDTHS, true), 404);
        abort_if(str_contains($path, '..') || str_starts_with($path, '_resized/'), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $cached = "_resized/{$width}/{$path}.webp";

        if (! $disk->exists($cached) || $disk->lastModified($cached) < $disk->lastModified($path)) {
            $webp = $this->resize((string) $disk->get($path), $width);

            abort_if($webp === null, 404);

            $disk->put($cached, $webp);
        }

        return response()->file($disk->path($cached), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function resize(string $bytes, int $width): ?string
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return null;
        }

        // Never enlarge: a small original is served at its own size.
        if (imagesx($image) > $width) {
            $scaled = imagescale($image, $width);

            if ($scaled !== false) {
                $image = $scaled;
            }
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 78);

        return ob_get_clean() ?: null;
    }
}
