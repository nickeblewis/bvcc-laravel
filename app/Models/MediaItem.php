<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A photo in the media library. Imported items came from the old WordPress
 * site and are meant to be replaced over time.
 *
 * @property int $id
 * @property string $file
 * @property string|null $alt
 * @property bool $is_imported
 */
class MediaItem extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_imported' => 'boolean'];
    }
}
