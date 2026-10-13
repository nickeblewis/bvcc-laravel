<?php

namespace App\Models;

use App\Enums\FeeBand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property FeeBand $fee_band
 * @property int $initial_pence
 * @property int $monthly_pence
 */
class MembershipTier extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_band' => FeeBand::class,
            'covers_household' => 'boolean',
            'on_sale' => 'boolean',
            'to_confirm' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
