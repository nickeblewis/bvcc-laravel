<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A permanent redirect from an old URL path to a new one.
 *
 * @property int $id
 * @property string $from_path
 * @property string $to_path
 */
class Redirect extends Model
{
    protected $guarded = [];

    /**
     * Paths are stored lower-case with a leading slash and no trailing slash.
     */
    public static function normalise(string $path): string
    {
        return '/'.trim(strtolower($path), '/');
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = static::normalise($redirect->from_path);
        });
    }
}
