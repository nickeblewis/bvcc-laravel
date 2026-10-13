<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $address
 * @property string|null $town
 * @property string|null $postcode
 * @property float|null $latitude
 * @property float|null $longitude
 */
class Venue extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_listed' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([$this->address, $this->town, $this->postcode]));
    }

    public function mapUrl(): string
    {
        $query = $this->latitude && $this->longitude
            ? "{$this->latitude},{$this->longitude}"
            : "{$this->name}, {$this->fullAddress()}";

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }
}
