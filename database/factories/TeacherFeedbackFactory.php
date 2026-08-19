<?php

namespace Database\Factories;

use App\Models\TeacherFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherFeedback>
 */
class TeacherFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'student_id' => User::factory(),
            'type' => $this->faker->randomElement(['encouragement', 'improvement', 'praise', 'reminder']),
            'message' => $this->faker->sentence(12),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
