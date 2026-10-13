<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Models\Concerns\HasPayments;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One year of membership on a tier. The lead member is the contact; a family
 * membership also covers other people through the pivot.
 *
 * @property int $id
 * @property int $membership_tier_id
 * @property int $member_id
 * @property CarbonInterface $starts_on
 * @property CarbonInterface $expires_on
 * @property MembershipStatus $status
 * @property int $price_pence
 * @property int $monthly_pence
 * @property CarbonInterface|null $last_chased_at
 * @property-read MembershipTier $tier
 * @property-read Member $lead
 */
class Membership extends Model
{
    use HasPayments;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'expires_on' => 'date',
            'status' => MembershipStatus::class,
            'last_chased_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MembershipTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Everyone covered, including the lead member.
     *
     * @return BelongsToMany<Member, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class);
    }

    public function totalPence(): int
    {
        return $this->price_pence;
    }

    /**
     * Bring the status in line with what has been paid.
     */
    public function refreshStatus(): void
    {
        if ($this->status === MembershipStatus::Pending && $this->balancePence() <= 0) {
            $this->update(['status' => MembershipStatus::Active]);
        }
    }

    public function expiresWithin(int $days): bool
    {
        return $this->status === MembershipStatus::Active
            && $this->expires_on->between(today(), today()->addDays($days));
    }

    /**
     * Active memberships running out in the next $days days.
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        static::whereExpiringWithin($query, $days);
    }

    /**
     * The same rule as the scope, for use on any query over memberships,
     * such as the one inside a whereHas().
     */
    public static function whereExpiringWithin(QueryBuilder $query, int $days): void
    {
        $query->where('status', MembershipStatus::Active)
            ->whereDate('expires_on', '>=', today())
            ->whereDate('expires_on', '<=', today()->addDays($days));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        static::whereCurrent($query);
    }

    /**
     * Active today. See whereExpiringWithin() for why this is separate.
     */
    public static function whereCurrent(QueryBuilder $query): void
    {
        $query->where('status', MembershipStatus::Active)
            ->whereDate('starts_on', '<=', today())
            ->whereDate('expires_on', '>=', today());
    }
}
