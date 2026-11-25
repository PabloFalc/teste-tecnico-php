<?php

namespace App\Modules\Vagas\Domain\Models;

use App\Modules\Usuarios\Domain\Models\User;
use Database\Factories\VagaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vaga extends Model
{
    use HasFactory;

    // O Laravel não adivinha a factory para models dentro de módulos.
    protected static function newFactory(): VagaFactory
    {
        return VagaFactory::new();
    }

    // user_id fica de fora: o dono vem do token. ativa = false é a vaga congelada.
    protected $fillable = ['titulo', 'descricao', 'tipo', 'ativa'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    // Candidatos que se candidataram à vaga.
    public function candidatos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidaturas', 'vaga_id', 'candidato_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    // Recrutador dono da vaga.
    public function recrutador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
