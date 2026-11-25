<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaga extends Model
{
    protected $fillable = ['user_id', 'titulo', 'descricao', 'tipo', 'ativa'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    // Recrutador dono da vaga.
    public function recrutador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function candidatos()
    {
        return $this->belongsToMany(User::class, 'candidaturas', 'vaga_id', 'candidato_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}
