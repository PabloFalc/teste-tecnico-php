<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Domain\Models\User;
use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $candidatos = User::factory()->candidato()->count(20)->create();

        // Recrutadores fixos (credenciais no README)
        $recrutadores = collect([
            ['nome' => 'Recrutador Um', 'email' => 'recrutador1@teste.com'],
            ['nome' => 'Recrutador Dois', 'email' => 'recrutador2@teste.com'],
        ])->map(fn (array $dados) => User::factory()->recrutador()->create($dados + ['password' => 'password']));

        // 250 vagas distribuídas aleatoriamente entre os recrutadores
        $vagas = Vaga::factory()
            ->count(250)
            ->state(fn () => ['user_id' => $recrutadores->random()->id])
            ->create();

        // Cada candidato se candidata a 3–8 vagas aleatórias (sem repetir)
        foreach ($candidatos as $candidato) {
            $candidato->candidaturas()->attach(
                $vagas->random(random_int(3, 8))->pluck('id')->all()
            );
        }
    }
}
