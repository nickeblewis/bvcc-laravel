<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\FeeType;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(3, 60))->setTime(19, 0);

        return [
            'title' => fake()->unique()->words(3, true),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(90),
            'event_category_id' => fn () => EventCategory::firstOrCreate(
                ['slug' => EventCategory::SESSIONS],
                ['name' => 'Sessions'],
            )->id,
            'fee_type' => FeeType::Session,
            'status' => EventStatus::Published,
        ];
    }
}
