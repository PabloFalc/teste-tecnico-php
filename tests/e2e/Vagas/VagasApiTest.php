<?php

namespace Tests\Feature\Vagas;

use App\Modules\Usuarios\Domain\Models\User;
use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VagasApiTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return array_merge([
            'titulo' => 'Dev PHP',
            'descricao' => 'Laravel e Postgres',
            'tipo' => 'CLT',
        ], $extra);
    }

    // ---------- POST /api/vagas ----------

    public function test_recrutador_cria_vaga_como_dono_e_ativa(): void
    {
        $recrutador = User::factory()->recrutador()->create();
        Sanctum::actingAs($recrutador);

        $this->postJson('/api/vagas', $this->dados())
            ->assertCreated()
            ->assertJson(['titulo' => 'Dev PHP', 'tipo' => 'CLT', 'ativa' => true, 'user_id' => $recrutador->id]);

        $this->assertDatabaseHas('vagas', ['titulo' => 'Dev PHP', 'user_id' => $recrutador->id]);
    }

    public function test_criacao_ignora_user_id_e_ativa_enviados(): void
    {
        $recrutador = User::factory()->recrutador()->create();
        $outro = User::factory()->recrutador()->create();
        Sanctum::actingAs($recrutador);

        $this->postJson('/api/vagas', $this->dados(['user_id' => $outro->id, 'ativa' => false]))
            ->assertCreated()
            ->assertJson(['user_id' => $recrutador->id, 'ativa' => true]);
    }

    public function test_criacao_valida_campos(): void
    {
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->postJson('/api/vagas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titulo', 'descricao', 'tipo']);

        $this->postJson('/api/vagas', $this->dados(['tipo' => 'Estagio']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo');
    }

    public function test_candidato_nao_cria_vaga(): void
    {
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->postJson('/api/vagas', $this->dados())->assertForbidden();
    }

    public function test_criar_vaga_exige_token(): void
    {
        $this->postJson('/api/vagas', $this->dados())->assertUnauthorized();
    }

    // ---------- PUT /api/vagas/{vaga} ----------

    public function test_dono_atualiza_vaga(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs($vaga->recrutador);

        $this->putJson("/api/vagas/{$vaga->id}", $this->dados(['titulo' => 'Novo titulo', 'tipo' => 'PJ']))
            ->assertOk()
            ->assertJson(['titulo' => 'Novo titulo', 'tipo' => 'PJ']);

        $this->assertDatabaseHas('vagas', ['id' => $vaga->id, 'titulo' => 'Novo titulo']);
    }

    public function test_update_nao_altera_dono_nem_ativa(): void
    {
        $vaga = Vaga::factory()->create();
        $outro = User::factory()->recrutador()->create();
        Sanctum::actingAs($vaga->recrutador);

        $this->putJson("/api/vagas/{$vaga->id}", $this->dados(['user_id' => $outro->id, 'ativa' => false]))
            ->assertOk();

        $this->assertDatabaseHas('vagas', ['id' => $vaga->id, 'user_id' => $vaga->user_id, 'ativa' => true]);
    }

    public function test_recrutador_nao_atualiza_vaga_de_outro(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->putJson("/api/vagas/{$vaga->id}", $this->dados())->assertForbidden();
    }

    public function test_candidato_nao_atualiza_vaga(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->putJson("/api/vagas/{$vaga->id}", $this->dados())->assertForbidden();
    }

    public function test_update_retorna_404_quando_vaga_nao_existe(): void
    {
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->putJson('/api/vagas/999', $this->dados())->assertNotFound();
    }

    // ---------- DELETE /api/vagas/{vaga} ----------

    public function test_dono_remove_vaga(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs($vaga->recrutador);

        $this->deleteJson("/api/vagas/{$vaga->id}")->assertNoContent();

        $this->assertDatabaseMissing('vagas', ['id' => $vaga->id]);
    }

    public function test_recrutador_nao_remove_vaga_de_outro(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->deleteJson("/api/vagas/{$vaga->id}")->assertForbidden();

        $this->assertDatabaseHas('vagas', ['id' => $vaga->id]);
    }

    public function test_candidato_nao_remove_vaga(): void
    {
        $vaga = Vaga::factory()->create();
        Sanctum::actingAs(User::factory()->candidato()->create());

        $this->deleteJson("/api/vagas/{$vaga->id}")->assertForbidden();
    }

    public function test_delete_retorna_404_quando_vaga_nao_existe(): void
    {
        Sanctum::actingAs(User::factory()->recrutador()->create());

        $this->deleteJson('/api/vagas/999')->assertNotFound();
    }
}
