<?php

namespace App\Models;

use App\Enums\FeeBand;
use App\Enums\MembershipStatus;
use Carbon\CarbonInterface;
use Database\Factories\MemberFactory;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A person known to the club: a member, a family member covered by someone
 * else's membership, or a non-member who has been booked onto something.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $name
 * @property CarbonInterface|null $date_of_birth
 * @property string|null $email
 * @property list<array{name?: string, relationship?: string, phone?: string}>|null $emergency_contacts
 * @property string|null $medical_notes
 * @property-read User|null $user
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Emergency and medical details never leave the server in JSON.
     *
     * @var list<string>
     */
    protected $hidden = ['emergency_contacts', 'medical_notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'paddle_uk_expires_on' => 'date',
            'emergency_contacts' => 'encrypted:array',
            'medical_notes' => 'encrypted',
            'photo_consent' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Every membership that covers this person, as lead or family member.
     *
     * @return BelongsToMany<Membership, $this>
     */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class);
    }

    /**
     * The member's own login, if they have one (F-50).
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return HasMany<BookingAttendee, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(BookingAttendee::class);
    }

    public function currentMembership(?CarbonInterface $on = null): ?Membership
    {
        $on ??= today();

        return $this->memberships()
            ->where('status', MembershipStatus::Active)
            ->whereDate('starts_on', '<=', $on)
            ->whereDate('expires_on', '>=', $on)
            ->with('tier')
            ->orderByDesc('expires_on')
            ->first();
    }

    public function feeBand(?CarbonInterface $on = null): FeeBand
    {
        return $this->currentMembership($on)->tier->fee_band ?? FeeBand::NonMember;
    }

    public function age(?CarbonInterface $on = null): ?int
    {
        return $this->date_of_birth === null
            ? null
            : (int) $this->date_of_birth->diffInYears($on ?? today());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        static::whereMatches($query, $term);
    }

    /**
     * Name or email contains the term. Usable on any query over members,
     * such as the one inside a whereHas().
     */
    public static function whereMatches(QueryBuilder $query, string $term): void
    {
        $query->where(fn (QueryBuilder $q) => $q
            ->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }
}
