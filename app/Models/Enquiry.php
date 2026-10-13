<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $message
 * @property CarbonInterface|null $handled_at
 * @property-read CommitteeRole|null $role
 */
class Enquiry extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<CommitteeRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(CommitteeRole::class, 'committee_role_id');
    }
}
