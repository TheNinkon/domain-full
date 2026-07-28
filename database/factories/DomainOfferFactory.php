<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainOffer>
 */
class DomainOfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'amount' => fake()->randomFloat(2, 200, 8000),
            'currency' => 'USD',
            'message' => fake()->boolean(60) ? fake()->sentence() : null,
            'status' => OfferStatus::Pending->value,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
