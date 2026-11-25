<?php

namespace Database\Factories;

use App\Modules\Usuarios\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'tipo' => fake()->randomElement(['recrutador', 'candidato']),
        ];
    }

    public function recrutador(): static
    {
        return $this->state(['tipo' => 'recrutador']);
    }

    public function candidato(): static
    {
        return $this->state(['tipo' => 'candidato']);
    }
}
