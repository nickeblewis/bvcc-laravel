<?php

namespace App\Services;

use App\Enums\FeeBand;
use App\Enums\FeeType;
use App\Models\Event;
use App\Models\FeeRate;

class FeeCalculator
{
    /** @var array<string, int|null>|null keyed by "band:type" */
    private ?array $rates = null;

    /**
     * What one person in a fee band pays for an event, in pence. Null means
     * the club has no rate for that combination ("n/a" in the fee table).
     */
    public function priceFor(Event $event, FeeBand $band): ?int
    {
        return match ($event->fee_type) {
            FeeType::Free => 0,
            FeeType::Custom => $event->custom_prices[$band->value] ?? null,
            default => $this->rate($band, $event->fee_type),
        };
    }

    public function rate(FeeBand $band, FeeType $type): ?int
    {
        if ($this->rates === null) {
            $this->rates = [];

            foreach (FeeRate::all() as $rate) {
                $this->rates["{$rate->fee_band->value}:{$rate->fee_type->value}"] = $rate->amount_pence;
            }
        }

        return $this->rates["{$band->value}:{$type->value}"] ?? null;
    }

    /**
     * Every band's price for an event, for the public page and the admin.
     *
     * @return array<string, int|null> keyed by fee band value
     */
    public function pricesFor(Event $event): array
    {
        $prices = [];

        foreach (FeeBand::cases() as $band) {
            $prices[$band->value] = $this->priceFor($event, $band);
        }

        return $prices;
    }
}
