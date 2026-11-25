<?php

namespace App\Modules\Vagas\Domain\Models;

use App\Modules\Usuarios\Domain\Models\User;
use Database\Factories\VagaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaga extends Model
{
    use HasFactory;

    // O Laravel não adivinha a factory para models dentro de módulos.
    protected static function newFactory(): VagaFactory
    {
        return VagaFactory::new();
    }

    // user_id e ativa ficam de fora: o dono vem do token e o congelamento terá rota própria.
    protected $fillable = ['titulo', 'descricao', 'tipo'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    // Recrutador dono da vaga.
    public function recrutador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
