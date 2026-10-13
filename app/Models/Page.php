<?php

namespace App\Models;

use App\Enums\PageSection;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An editable content page under one of the site's sections.
 *
 * @property int $id
 * @property PageSection $section
 * @property string $slug
 * @property string $title
 * @property string|null $summary
 * @property string|null $body
 * @property list<array{label: string, value: string}>|null $facts
 * @property string|null $faq_category
 * @property CarbonInterface|null $reviewed_on
 */
class Page extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => PageSection::class,
            'facts' => 'array',
            'reviewed_on' => 'date',
            'is_published' => 'boolean',
            'to_confirm' => 'boolean',
        ];
    }

    public function path(): string
    {
        return "/{$this->section->value}/{$this->slug}";
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort');
    }
}
