<?php

namespace Database\Factories;

use App\Models\Exception;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExceptionFactory extends Factory
{
    protected $model = Exception::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'justification' => $this->faker->sentence(),
            'compensating_controls' => $this->faker->sentence(),
            'control_id' => null,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'status' => Exception::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => Exception::STATUS_SUBMITTED,
            'submitted_by' => User::factory(),
            'submitted_at' => now(),
        ]);
    }
}
