<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A committee post and its role inbox. The office holder's name is optional
 * and only shown when the club chooses to list it.
 *
 * @property int $id
 * @property string $title
 * @property string|null $email
 * @property string|null $holder_name
 */
class CommitteeRole extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_listed' => 'boolean',
            'takes_enquiries' => 'boolean',
        ];
    }
}
