<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded file: a policy, a set of minutes, a risk assessment or a form.
 *
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string $file
 * @property CarbonInterface|null $document_date
 */
class Document extends Model
{
    /** @var array<string, string> */
    public const CATEGORIES = [
        'policy' => 'Policies',
        'governance' => 'Governance',
        'risk' => 'Operating procedures and risk assessments',
        'minutes' => 'Meeting minutes and agendas',
        'form' => 'Forms',
    ];

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file);
    }
}
