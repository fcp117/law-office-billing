<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'name' => $this->faker->company,
        'email' => $this->faker->unique()->safeEmail,
        'start_date' => now(),
        'partner_in_charge' => 'GFO',
        'with_retainer' => true,
        'retainer_amount' => 50000.00,
        ];
    }
}
