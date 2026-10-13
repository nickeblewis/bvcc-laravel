<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $colour
 */
class EventCategory extends Model
{
    /** The slug of the category that holds the regular weekly sessions. */
    public const SESSIONS = 'sessions';

    /**
     * Colour names an admin can pick; the public site and the admin calendar
     * each map these to their own palette.
     *
     * @var array<string, string>
     */
    public const COLOURS = [
        'teal' => 'Teal',
        'blue' => 'River blue',
        'gold' => 'Gold',
        'coral' => 'Coral',
        'green' => 'Reed green',
        'violet' => 'Violet',
        'slate' => 'Slate',
    ];

    protected $guarded = [];

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
