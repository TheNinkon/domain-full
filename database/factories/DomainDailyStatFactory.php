<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainDailyStat>
 */
class DomainDailyStatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'visits' => fake()->numberBetween(0, 50),
            'unique_visitors' => fake()->numberBetween(0, 40),
        ];
    }
}
