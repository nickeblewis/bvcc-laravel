<?php

namespace App\Models;

use App\Enums\HireRequestStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property list<string> $items
 * @property CarbonInterface $collect_on
 * @property CarbonInterface $return_on
 * @property HireRequestStatus $status
 */
class HireRequest extends Model
{
    /** @var list<string> */
    public const ITEMS = ['Boat or board', 'Paddle', 'Helmet', 'Buoyancy aid', 'Cag or jacket', 'Trousers', 'Spray deck'];

    /** @var list<string> */
    public const CRAFT_TYPES = [
        'General purpose kayak', 'Whitewater kayak', 'Touring kayak', 'Sea kayak',
        'Open canoe', 'Inflatable paddleboard', 'Hard paddleboard', 'Other',
    ];

    /** @var array<string, string> */
    public const USE_TYPES = [
        'personal' => 'Personal or independent paddling',
        'club' => 'Official club activity or event',
    ];

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'collect_on' => 'date',
            'return_on' => 'date',
            'status' => HireRequestStatus::class,
        ];
    }
}
