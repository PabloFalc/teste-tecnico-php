<?php

use App\Modules\Candidaturas\Http\Controllers\CandidaturasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tipo:candidato'])->group(function () {
    Route::get('/candidaturas', [CandidaturasController::class, 'list']);
    Route::post('/vagas/{vaga}/candidaturas', [CandidaturasController::class, 'store']);
    Route::delete('/vagas/{vaga}/candidaturas', [CandidaturasController::class, 'destroy']);
});
