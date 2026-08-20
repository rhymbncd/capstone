<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['registration', 'login', 'content', 'system', 'error']),
            'title' => $this->faker->sentence(3),
            'sub' => $this->faker->sentence(6),
            'badge' => 'Event',
            'user_id' => null,
            'user_name' => null,
            'user_role' => null,
            'created_at' => now(),
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['archived_at' => now()]);
    }
}
