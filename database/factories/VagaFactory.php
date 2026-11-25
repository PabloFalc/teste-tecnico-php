<?php

namespace Database\Factories;

use App\Modules\Usuarios\Domain\Models\User;
use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vaga>
 */
class VagaFactory extends Factory
{
    protected $model = Vaga::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->recrutador(),
            'titulo' => fake()->jobTitle(),
            'descricao' => fake()->paragraph(),
            'tipo' => fake()->randomElement(['CLT', 'PJ', 'Freelancer']),
            'ativa' => true,
        ];
    }
}
