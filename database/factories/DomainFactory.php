<?php

namespace Database\Factories;

use App\Enums\DomainStatus;
use App\Models\DomainCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    public function definition(): array
    {
        $purchaseDate = fake()->dateTimeBetween('-3 years', '-1 month');
        $expirationDate = (clone $purchaseDate)->modify('+1 year');

        return [
            'name' => fake()->unique()->domainName(),
            'domain_category_id' => DomainCategory::factory(),
            'registrar' => fake()->randomElement(['Namecheap', 'GoDaddy', 'Cloudflare', 'Google Domains']),
            'status' => DomainStatus::Watching->value,
            'purchase_date' => $purchaseDate,
            'renewal_date' => $expirationDate,
            'expiration_date' => $expirationDate,
            'purchase_cost' => fake()->randomFloat(2, 8, 60),
            'renewal_cost' => fake()->randomFloat(2, 10, 70),
            'currency' => 'USD',
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'project_id' => null,
            'is_for_sale' => false,
            'auto_renew' => fake()->boolean(70),
        ];
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'expiration_date' => now()->addDays(fake()->numberBetween(1, 29)),
        ]);
    }

    public function forSale(): static
    {
        return $this->state(fn () => [
            'status' => DomainStatus::ForSale->value,
            'is_for_sale' => true,
        ]);
    }
}
