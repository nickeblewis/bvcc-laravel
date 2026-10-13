<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $category
 * @property string $question
 * @property string $answer
 */
class Faq extends Model
{
    /** @var array<string, string> */
    public const CATEGORIES = [
        'getting-started' => 'Getting started',
        'sessions' => 'Sessions',
        'membership' => 'Membership and fees',
        'booking' => 'Events and booking',
        'kit' => 'Kit and hire',
    ];

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'to_confirm' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort');
    }
}
