<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Measure;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'measure_id' => Measure::factory(),
            'filename' => $this->faker->word() . '.pdf',
            'mimetype' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 1048576),
            'hash' => $this->faker->sha256(),
        ];
    }
}
