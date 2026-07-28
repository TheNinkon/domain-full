<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\DomainCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        if (Domain::count() === 0) {
            $categories = collect(['Tech', 'Música', 'Finanzas'])
                ->map(fn (string $name) => DomainCategory::factory()->create(['name' => $name]));

            Domain::factory(5)->create([
                'domain_category_id' => fn () => $categories->random()->id,
            ]);

            Domain::factory(2)->expiringSoon()->create([
                'domain_category_id' => fn () => $categories->random()->id,
            ]);

            Domain::factory()->forSale()->create([
                'domain_category_id' => fn () => $categories->random()->id,
            ]);
        }
    }
}
