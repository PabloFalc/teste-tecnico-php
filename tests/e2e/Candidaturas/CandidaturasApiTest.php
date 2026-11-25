<?php

namespace Tests\Feature\Candidaturas;

use App\Modules\Usuarios\Domain\Models\User;
use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CandidaturasApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------- POST /api/vagas/{vaga}/candidaturas ----------

    public function test_candidatura(): void
    {
        $candidato = User::factory()->candidato()->create();
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs($candidato);

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")
            ->assertCreated()
            ->assertJsonPath('id', $vaga->id)
            ->assertJsonPath('pivot.status', 'pendente');

        $this->assertDatabaseHas('candidaturas', ['candidato_id' => $candidato->id, 'vaga_id' => $vaga->id]);
    }

    public function test_nao_se_candidata_duas_vezes_na_mesma_vaga(): void
    {
        $candidato = User::factory()->candidato()->create();
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs($candidato);

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertCreated();
        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertStatus(409);

        $this->assertDatabaseCount('candidaturas', 1);
    }

    public function test_multiplas_candidaturas(): void
    {
        $candidato = User::factory()->candidato()->create();
        $vagas = Vaga::factory()->count(3)->create();
        Sanctum::actingAs($candidato);

        foreach ($vagas as $vaga) {
            $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertCreated();
        }

        $this->assertDatabaseCount('candidaturas', 3);
    }


    public function test_nao_se_candidata_em_vaga_inativa(): void
    {
        $vaga = Vaga::factory()->create(['ativa' => false]);
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertUnprocessable();

        $this->assertDatabaseCount('candidaturas', 0);
    }

    public function test_candidatura_em_vaga_inexistente_da_404(): void
    {
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->postJson('/api/vagas/999/candidaturas')->assertNotFound();
    }

    public function test_recrutador_nao_se_candidata(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertForbidden();
    }

    public function test_candidatar_exige_token(): void
    {
        $vaga = Vaga::factory()->create();

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertUnauthorized();
    }

    // ---------- DELETE /api/vagas/{vaga}/candidaturas ----------

    public function test_candidato_remove_propria_candidatura(): void
    {
        $candidato = User::factory()->candidato()->create();
        $vaga = Vaga::factory()->create();
        $candidato->candidaturas()->attach($vaga->id);
        Sanctum::actingAs($candidato);

        $this->deleteJson("/api/vagas/{$vaga->id}/candidaturas")->assertNoContent();

        $this->assertDatabaseCount('candidaturas', 0);
    }

    public function test_remover_nao_afeta_candidatura_de_outro(): void
    {
        $vaga = Vaga::factory()->create();
        $outro = User::factory()->candidato()->create();
        $outro->candidaturas()->attach($vaga->id);
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->deleteJson("/api/vagas/{$vaga->id}/candidaturas")->assertNotFound();

        $this->assertDatabaseHas('candidaturas', ['candidato_id' => $outro->id, 'vaga_id' => $vaga->id]);
    }

    public function test_pode_se_candidatar_de_novo_depois_de_remover(): void
    {
        $candidato = User::factory()->candidato()->create();
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs($candidato);

        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertCreated();
        $this->deleteJson("/api/vagas/{$vaga->id}/candidaturas")->assertNoContent();
        $this->postJson("/api/vagas/{$vaga->id}/candidaturas")->assertCreated();
    }

    public function test_recrutador_nao_remove_candidatura(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->deleteJson("/api/vagas/{$vaga->id}/candidaturas")->assertForbidden();
    }

    // ---------- GET /api/candidaturas ----------

    public function test_lista_somente_as_proprias_candidaturas(): void
    {
        $candidato = User::factory()->candidato()->create();
        $outro = User::factory()->candidato()->create();
        $minhas = Vaga::factory()->count(2)->create();
        $deOutro = Vaga::factory()->create();

        $candidato->candidaturas()->attach($minhas->pluck('id'));
        $outro->candidaturas()->attach($deOutro->id);
        Sanctum::actingAs($candidato);

        $resposta = $this->getJson('/api/candidaturas')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.pivot.status', 'pendente');

        $this->assertEqualsCanonicalizing(
            $minhas->pluck('id')->all(),
            array_column($resposta->json('data'), 'id'),
        );
    }

    public function test_lista_vazia_sem_candidaturas(): void
    {
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->getJson('/api/candidaturas')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_recrutador_nao_lista_candidaturas(): void
    {
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->getJson('/api/candidaturas')->assertForbidden();
    }

    public function test_listar_exige_token(): void
    {
        $this->getJson('/api/candidaturas')->assertUnauthorized();
    }
}
