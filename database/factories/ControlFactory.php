<?php

namespace Database\Factories;

use App\Models\Control;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class ControlFactory extends Factory
{
    protected $model = Control::class;

    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'clause' => $this->faker->unique()->numerify('##.#.#'),
            'name' => $this->faker->sentence(4),
            'objective' => $this->faker->paragraph(),
            'attributes' => null,
            'input' => $this->faker->sentence(),
            'model' => $this->faker->paragraph(),
            'indicator' => $this->faker->sentence(),
            'action_plan' => $this->faker->sentence(),
        ];
    }
}
