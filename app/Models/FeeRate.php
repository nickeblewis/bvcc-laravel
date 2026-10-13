<?php

namespace App\Models;

use App\Enums\FeeBand;
use App\Enums\FeeType;
use Illuminate\Database\Eloquent\Model;

/**
 * One cell of the club fee table: what a fee band pays for a type of event.
 *
 * @property FeeBand $fee_band
 * @property FeeType $fee_type
 * @property int|null $amount_pence
 */
class FeeRate extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_band' => FeeBand::class,
            'fee_type' => FeeType::class,
        ];
    }
}
