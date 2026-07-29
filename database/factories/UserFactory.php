<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'login' => $this->faker->unique()->userName(),
            'name' => $this->faker->name(),
            'title' => $this->faker->jobTitle(),
            'role' => User::ROLE_ADMIN,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function apiUser(): static
    {
        return $this->state(['role' => User::ROLE_API]);
    }

    public function admin(): static
    {
        return $this->state(['role' => User::ROLE_ADMIN]);
    }

    public function auditor(): static
    {
        return $this->state(['role' => User::ROLE_AUDITOR]);
    }

    public function user(): static
    {
        return $this->state(['role' => User::ROLE_USER]);
    }

    public function auditee(): static
    {
        return $this->state(['role' => User::ROLE_AUDITEE]);
    }
}
