<?php

namespace Tests\Feature\Usuarios;

use App\Modules\Usuarios\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function dadosRegistro(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Fulano',
            'email' => 'fulano@teste.com',
            'password' => 'senha1234',
            'tipo' => 'candidato',
        ], $extra);
    }

    public function test_registra_e_devolve_token(): void
    {
        $this->postJson('/api/register', $this->dadosRegistro())
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'nome', 'email', 'tipo'], 'token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'fulano@teste.com']);
    }

    public function test_registro_rejeita_campos_invalidos(): void
    {
        User::factory()->create(['email' => 'fulano@teste.com']);

        $this->postJson('/api/register', $this->dadosRegistro(['tipo' => 'admin', 'password' => '123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'tipo', 'password']);
    }

    public function test_login_devolve_token(): void
    {
        User::factory()->create(['email' => 'a@teste.com', 'password' => 'senha1234']);

        $this->postJson('/api/login', ['email' => 'a@teste.com', 'password' => 'senha1234'])
            ->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejeita_credenciais_erradas(): void
    {
        User::factory()->create(['email' => 'a@teste.com', 'password' => 'senha1234']);

        $this->postJson('/api/login', ['email' => 'a@teste.com', 'password' => 'errada'])->assertUnauthorized();
        $this->postJson('/api/login', ['email' => 'x@teste.com', 'password' => 'senha1234'])->assertUnauthorized();
    }

    public function test_me_exige_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_com_token_bearer(): void
    {
        $token = $this->postJson('/api/register', $this->dadosRegistro())->json('token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJson(['email' => 'fulano@teste.com']);
    }

    public function test_logout_revoga_o_token(): void
    {
        $token = $this->postJson('/api/register', $this->dadosRegistro())->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function rotaDeRecrutador(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'tipo:recrutador'])
            ->get('/api/_teste-recrutador', fn () => response()->json(['ok' => true]));
    }

    public function test_middleware_tipo_libera_recrutador(): void
    {
        $this->rotaDeRecrutador();
        $token = $this->postJson('/api/register', $this->dadosRegistro(['tipo' => 'recrutador']))->json('token');

        $this->withToken($token)->getJson('/api/_teste-recrutador')->assertOk();
    }

    public function test_middleware_tipo_bloqueia_candidato(): void
    {
        $this->rotaDeRecrutador();
        $token = $this->postJson('/api/register', $this->dadosRegistro())->json('token');

        $this->withToken($token)->getJson('/api/_teste-recrutador')->assertForbidden();
    }

    public function test_middleware_tipo_exige_autenticacao(): void
    {
        $this->rotaDeRecrutador();

        $this->getJson('/api/_teste-recrutador')->assertUnauthorized();
    }
}
