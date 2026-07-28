<?php

namespace Database\Factories;

use App\Enums\DomainLogType;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainLog>
 */
class DomainLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'user_id' => null,
            'type' => DomainLogType::Note->value,
            'description' => fake()->sentence(),
            'meta' => null,
        ];
    }
}
