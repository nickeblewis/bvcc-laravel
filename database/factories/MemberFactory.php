<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-9 years'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('07### ######'),
            'town' => fake()->randomElement(['Aldershot', 'Farnborough', 'Farnham', 'Fleet', 'Camberley', 'Yateley']),
            'emergency_contacts' => [[
                'name' => fake()->name(),
                'relationship' => fake()->randomElement(['Partner', 'Parent', 'Friend', 'Sibling']),
                'phone' => fake()->numerify('07### ######'),
            ]],
            'medical_notes' => fake()->boolean(25) ? fake()->randomElement(['Asthma, carries inhaler', 'Allergic to penicillin', 'Type 1 diabetes']) : null,
            'photo_consent' => fake()->boolean(80),
        ];
    }
}
