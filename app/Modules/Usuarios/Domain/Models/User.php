<?php

namespace App\Modules\Usuarios\Domain\Models;

use App\Modules\Vagas\Domain\Models\Vaga;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    // O Laravel não adivinha a factory para models dentro de módulos.
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = ['nome', 'email', 'password', 'tipo'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // Vagas criadas (usuário do tipo recrutador).
    public function vagasCriadas()
    {
        return $this->hasMany(Vaga::class);
    }

    // Vagas em que se candidatou (usuário do tipo candidato).
    public function candidaturas()
    {
        return $this->belongsToMany(Vaga::class, 'candidaturas', 'candidato_id', 'vaga_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}
