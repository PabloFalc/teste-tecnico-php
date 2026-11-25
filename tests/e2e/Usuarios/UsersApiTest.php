<?php

namespace Tests\Feature\Usuarios;

use App\Modules\Usuarios\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    // ---------- GET /api/users ----------

    public function test_lista_usuarios_paginados_com_20_por_pagina(): void
    {
        Sanctum::actingAs(User::factory()->create());

        User::factory()->count(24)->create(); // +1 logado = 25

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('per_page', 20)
            ->assertJsonPath('total', 25)
            ->assertJsonCount(20, 'data');
    }

    public function test_lista_segunda_pagina(): void
    {
        Sanctum::actingAs(User::factory()->create());

        User::factory()->count(24)->create(); // +1 logado = 25

        $this->getJson('/api/users?page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');
    }

    public function test_respeita_page_size_informado(): void
    {
        Sanctum::actingAs(User::factory()->create());

        User::factory()->count(10)->create();

        $this->getJson('/api/users?page_size=3')
            ->assertOk()
            ->assertJsonPath('per_page', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_page_size_e_limitado_a_100(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/users?page_size=100000')
            ->assertOk()
            ->assertJsonPath('per_page', 100);
    }

    public function test_lista_nao_expoe_password(): void
    {
        Sanctum::actingAs(User::factory()->create());

        User::factory()->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonMissingPath('data.0.password');
    }

    // ---------- GET /api/users/{user} ----------

    public function test_mostra_um_usuario(): void
    {
        Sanctum::actingAs(User::factory()->create());

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
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/users/999')->assertNotFound();
    }

    // ---------- PUT /api/users/{user} ----------

    public function test_atualiza_usuario(): void
    {
        $user = User::factory()->candidato()->create();
        Sanctum::actingAs($user);

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
        Sanctum::actingAs($user);

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
        Sanctum::actingAs($user);

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
        Sanctum::actingAs($user);

        $this->putJson("/api/users/{$user->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'email', 'tipo']);
    }

    public function test_rejeita_email_invalido_e_tipo_invalido(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

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
        Sanctum::actingAs($user);
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
        Sanctum::actingAs(User::factory()->create());

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
        Sanctum::actingAs($user);

        $this->deleteJson("/api/users/{$user->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_retorna_404_quando_usuario_nao_existe(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/users/999')->assertNotFound();
    }

    // ---------- autenticação e dono ----------

    public function test_rotas_de_usuarios_exigem_token(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson("/api/users/{$user->id}")->assertUnauthorized();
        $this->putJson("/api/users/{$user->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/users/{$user->id}")->assertUnauthorized();
    }

    public function test_nao_atualiza_outro_usuario(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $outro = User::factory()->create();

        $this->putJson("/api/users/{$outro->id}", [
            'nome' => 'X', 'email' => 'x@teste.com', 'tipo' => 'candidato',
        ])->assertForbidden();
    }

    public function test_nao_remove_outro_usuario(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $outro = User::factory()->create();

        $this->deleteJson("/api/users/{$outro->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $outro->id]);
    }
}
