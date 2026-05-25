<?php

namespace Database\Factories;

use App\Models\Measure;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasureFactory extends Factory
{
    protected $model = Measure::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'objective' => $this->faker->paragraph(),
            'observations' => $this->faker->paragraph(),
            'input' => $this->faker->sentence(),
            'score' => $this->faker->numberBetween(1, 5),
            'attributes' => null,
            'model' => $this->faker->paragraph(),
            'action_plan' => $this->faker->sentence(),
            'realisation_date' => null,
            'plan_date' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'periodicity' => $this->faker->randomElement([1, 3, 6, 12]),
        ];
    }

    public function done(): static
    {
        return $this->state(['realisation_date' => now()->subDays(5)->format('Y-m-d')]);
    }
}
