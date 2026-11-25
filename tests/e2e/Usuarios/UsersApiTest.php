<?php

namespace Tests\Feature\Usuarios;

use App\Modules\Usuarios\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------- GET /api/users ----------

    public function test_lista_usuarios_paginados_com_20_por_pagina(): void
    {
        User::factory()->count(25)->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('per_page', 20)
            ->assertJsonPath('total', 25)
            ->assertJsonCount(20, 'data');
    }

    public function test_lista_segunda_pagina(): void
    {
        User::factory()->count(25)->create();

        $this->getJson('/api/users?page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');
    }

    public function test_respeita_page_size_informado(): void
    {
        User::factory()->count(10)->create();

        $this->getJson('/api/users?page_size=3')
            ->assertOk()
            ->assertJsonPath('per_page', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_page_size_e_limitado_a_100(): void
    {
        $this->getJson('/api/users?page_size=100000')
            ->assertOk()
            ->assertJsonPath('per_page', 100);
    }

    public function test_lista_nao_expoe_password(): void
    {
        User::factory()->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonMissingPath('data.0.password');
    }

    // ---------- GET /api/users/{user} ----------

    public function test_mostra_um_usuario(): void
    {
        $user = User::factory()->recrutador()->create();

        $this->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'tipo' => 'recrutador',
            ])
            ->assertJsonMissingPath('password');
    }

    public function test_mostra_404_quando_usuario_nao_existe(): void
    {
        $this->getJson('/api/users/999')->assertNotFound();
    }

    // ---------- PUT /api/users/{user} ----------

    public function test_atualiza_usuario(): void
    {
        $user = User::factory()->candidato()->create();

        $this->putJson("/api/users/{$user->id}", [
            'nome' => 'Novo Nome',
            'email' => 'novo@teste.com',
            'tipo' => 'recrutador',
        ])
            ->assertOk()
            ->assertJson(['nome' => 'Novo Nome', 'email' => 'novo@teste.com', 'tipo' => 'recrutador']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nome' => 'Novo Nome',
            'email' => 'novo@teste.com',
            'tipo' => 'recrutador',
        ]);
    }

    public function test_pode_manter_o_proprio_email_ao_atualizar(): void
    {
        $user = User::factory()->create(['email' => 'meu@teste.com']);

        $this->putJson("/api/users/{$user->id}", [
            'nome' => 'Outro Nome',
            'email' => 'meu@teste.com',
            'tipo' => $user->tipo,
        ])->assertOk();
    }

    public function test_rejeita_email_de_outro_usuario(): void
    {
        User::factory()->create(['email' => 'ocupado@teste.com']);
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'nome' => 'Fulano',
            'email' => 'ocupado@teste.com',
            'tipo' => 'candidato',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_rejeita_campos_obrigatorios_ausentes(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'email', 'tipo']);
    }

    public function test_rejeita_email_invalido_e_tipo_invalido(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'nome' => 'Fulano',
            'email' => 'nao-e-email',
            'tipo' => 'admin',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'tipo']);
    }

    public function test_update_ignora_campo_fora_das_regras(): void
    {
        $user = User::factory()->create(['password' => 'original']);
        $hashAntes = $user->fresh()->password;

        $this->putJson("/api/users/{$user->id}", [
            'nome' => 'Fulano',
            'email' => 'fulano@teste.com',
            'tipo' => 'candidato',
            'password' => 'trocada',
        ])->assertOk();

        $this->assertSame($hashAntes, $user->fresh()->password);
    }

    public function test_update_retorna_404_quando_usuario_nao_existe(): void
    {
        $this->putJson('/api/users/999', [
            'nome' => 'Fulano',
            'email' => 'fulano@teste.com',
            'tipo' => 'candidato',
        ])->assertNotFound();
    }

    // ---------- DELETE /api/users/{user} ----------

    public function test_remove_usuario(): void
    {
        $user = User::factory()->create();

        $this->deleteJson("/api/users/{$user->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_retorna_404_quando_usuario_nao_existe(): void
    {
        $this->deleteJson('/api/users/999')->assertNotFound();
    }
}
